<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class MetaLeadApiService
{
    private const GRAPH_VERSION = 'v19.0';
    private const MAX_LEADS_PER_SYNC = 500;

    /**
     * @return array<int, array{id: string, name: string, status: ?string}>
     */
    public function listLeadForms(\App\Models\MetaPage $page): array
    {
        $pageId = $page->page_id;
        $token = $page->access_token;

        if (!$pageId || !$token) {
            throw new \App\Exceptions\MetaApiException('Facebook Page is not connected. Please link Facebook from your profile.');
        }

        $cacheKey = "meta_lead_forms_{$pageId}";
        
        return \Illuminate\Support\Facades\Cache::remember($cacheKey, now()->addMinutes(10), function () use ($pageId, $token) {
            $response = Http::get($this->graphUrl("/{$pageId}/leadgen_forms"), [
                'access_token' => $token,
                'fields' => 'id,name,status',
                'limit' => 100,
            ]);

            if (!$response->successful()) {
                $this->handleApiError($response, 'Failed to fetch lead forms.');
            }

            $forms = $response->json('data', []);

            return array_map(function ($form) {
                return [
                    'id' => (string) ($form['id'] ?? ''),
                    'name' => (string) ($form['name'] ?? 'Unnamed Form'),
                    'status' => $form['status'] ?? null,
                ];
            }, $forms);
        });
    }

    /**
     * Fetch and normalize leads from a Meta leadgen form.
     *
     * @return array<int, array{name: string, email: ?string, phone: ?string, company: ?string, meta_lead_id: string, custom_fields: array}>
     */
    public function fetchAllLeads(\App\Models\MetaPage $page, string $formId): array
    {
        $token = $page->access_token;
        if (!$token) {
            throw new \App\Exceptions\MetaApiException('Facebook Page is not connected. Please link Facebook from your profile.');
        }

        if ($formId === 'all') {
            $forms = $this->listLeadForms($page);
            $allLeads = [];
            foreach ($forms as $form) {
                // Fetch leads for each form and combine them
                $formLeads = $this->fetchLeadsForForm($token, (string) $form['id']);
                $allLeads = array_merge($allLeads, $formLeads);
                if (count($allLeads) >= self::MAX_LEADS_PER_SYNC) {
                    return array_slice($allLeads, 0, self::MAX_LEADS_PER_SYNC);
                }
            }
            return $allLeads;
        }

        return $this->fetchLeadsForForm($token, $formId);
    }

    private function fetchLeadsForForm(string $token, string $formId): array
    {
        $leads = [];
        $after = null;

        do {
            $params = [
                'access_token' => $token,
                'fields' => 'id,created_time,field_data',
                'limit' => 100,
            ];
            if ($after) {
                $params['after'] = $after;
            }

            $response = \Illuminate\Support\Facades\Http::get($this->graphUrl("/{$formId}/leads"), $params);

            if (!$response->successful()) {
                $this->handleApiError($response, 'Failed to fetch leads from Meta.');
            }

            $data = $response->json('data', []);
            foreach ($data as $item) {
                $leads[] = $this->normalizeApiLead($item);
                if (count($leads) >= self::MAX_LEADS_PER_SYNC) {
                    break 2;
                }
            }

            $after = $response->json('paging.cursors.after');
        } while ($after && count($leads) < self::MAX_LEADS_PER_SYNC);

        return $leads;
    }

    /**
     * Fetch details of a single lead from Meta by ID.
     *
     * @return array{name: string, email: ?string, phone: ?string, company: ?string, meta_lead_id: string, custom_fields: array}
     */
    public function fetchLead(\App\Models\MetaPage $page, string $leadId): array
    {
        $token = $page->access_token;
        if (!$token) {
            throw new \App\Exceptions\MetaApiException('Facebook Page is not connected.');
        }

        $response = Http::get($this->graphUrl("/{$leadId}"), [
            'access_token' => $token,
            'fields' => 'id,created_time,field_data',
        ]);

        if (!$response->successful()) {
            $this->handleApiError($response, 'Failed to fetch lead details from Meta.');
        }

        return $this->normalizeApiLead($response->json() ?: []);
    }

    public function syncUserPagesFromToken(User $user, string $userAccessToken, ?int $enterpriseId = null): void
    {
        $response = Http::get($this->graphUrl('/me/accounts'), [
            'access_token' => $userAccessToken,
            'fields' => 'id,name,access_token',
            'limit' => 100, // fetch all pages (up to 100)
        ]);

        if (!$response->successful()) {
            \Illuminate\Support\Facades\Log::error('FB Pages Fetch Error', ['body' => $response->json()]);
            $this->handleApiError($response, 'Failed to fetch Facebook pages.');
        }

        \Illuminate\Support\Facades\Log::info('FB Pages Response', ['body' => $response->json()]);

        // Debug token
        $debugResponse = Http::get($this->graphUrl('/debug_token'), [
            'input_token' => $userAccessToken,
            'access_token' => config('services.meta.app_id') . '|' . config('services.meta.app_secret')
        ]);
        \Illuminate\Support\Facades\Log::info('FB Token Debug', ['body' => $debugResponse->json()]);

        $debugData = $debugResponse->json('data', []);
        
        $pages = $response->json('data', []);
        
        // Find all page IDs explicitly granted via granular scopes
        $grantedPageIds = [];
        foreach ($debugData['granular_scopes'] ?? [] as $scopeData) {
            if (in_array($scopeData['scope'], ['pages_show_list', 'pages_manage_ads', 'leads_retrieval'])) {
                foreach ($scopeData['target_ids'] ?? [] as $targetId) {
                    $grantedPageIds[$targetId] = true;
                }
            }
        }
        
        // Find which ones were missed by /me/accounts
        $foundPageIds = array_column($pages, 'id');
        $missingPageIds = array_diff(array_keys($grantedPageIds), $foundPageIds);
        
        // Explicitly fetch missing pages
        \Illuminate\Support\Facades\Log::info('Missing Page IDs', ['ids' => $missingPageIds]);
        foreach ($missingPageIds as $missingPageId) {
            $pageResponse = Http::get($this->graphUrl("/{$missingPageId}"), [
                'access_token' => $userAccessToken,
                'fields' => 'id,name,access_token',
            ]);
            
            \Illuminate\Support\Facades\Log::info("Fetch Page {$missingPageId}", ['status' => $pageResponse->status(), 'body' => $pageResponse->json()]);
            
            if ($pageResponse->successful() && $pageResponse->json('id')) {
                $pages[] = $pageResponse->json();
            } else {
                // Fallback: Meta blocked reading the page name due to Ads-only role. 
                // Insert the page anyway using the ID as the name and the User Token.
                $pages[] = [
                    'id' => $missingPageId,
                    'name' => "Missing Page Name ({$missingPageId})",
                    'access_token' => $userAccessToken,
                ];
            }
        }
        
        if (empty($pages)) {
            return;
        }

        // Backward compatibility: store the first page in the `users` table (realtor only)
        if ($enterpriseId === null) {
            $firstPage = $pages[0];
            $user->facebook_page_id = (string) ($firstPage['id'] ?? '');
            $user->facebook_page_name = (string) ($firstPage['name'] ?? '');
            $user->facebook_page_access_token = (string) ($firstPage['access_token'] ?? '');
            $user->facebook_token_expired = false;
            $user->save();
        }

        // New behavior: store all pages in `meta_pages`
        foreach ($pages as $page) {
            $pageId = (string) ($page['id'] ?? '');
            
            // If Facebook hides the page access token (common for Business Manager ads-only roles),
            // fallback to using the User Access Token which has the required granular scopes anyway!
            $pageToken = (string) ($page['access_token'] ?? '');
            if (empty($pageToken)) {
                $pageToken = $userAccessToken;
            }

            if ($enterpriseId !== null && $pageId !== '') {
                $conflict = \App\Models\MetaPage::where('page_id', $pageId)
                    ->where(function ($query) use ($enterpriseId) {
                        $query->whereNull('enterprise_id')->orWhere('enterprise_id', '!=', $enterpriseId);
                    })
                    ->exists();

                if ($conflict) {
                    throw new \App\Exceptions\MetaApiException(
                        "Facebook Page {$pageId} is already connected to another Rumu account. Disconnect it there before connecting it to this enterprise."
                    );
                }
            }

            \App\Models\MetaPage::updateOrCreate(
                $enterpriseId !== null
                    ? ['enterprise_id' => $enterpriseId, 'page_id' => $pageId]
                    : ['user_id' => $user->id, 'page_id' => $pageId],
                [
                    'user_id' => $user->id,
                    'page_name' => (string) ($page['name'] ?? ''),
                    'access_token' => $pageToken,
                    'status' => 'connected',
                    'token_last_synced_at' => now(),
                ]
            );

            // Subscribe the App to the Page's leadgen webhook
            if ($pageToken) {
                Http::post($this->graphUrl("/{$pageId}/subscribed_apps"), [
                    'access_token' => $pageToken,
                    'subscribed_fields' => 'leadgen'
                ]);
            }
        }
    }

    /**
     * @param array<string, mixed> $item
     * @return array{name: string, email: ?string, phone: ?string, company: ?string, meta_lead_id: string, custom_fields: array}
     */
    private function normalizeApiLead(array $item): array
    {
        $fields = [];
        foreach ($item['field_data'] ?? [] as $field) {
            $name = strtolower((string) ($field['name'] ?? ''));
            $value = $field['values'][0] ?? '';
            if ($name !== '') {
                $fields[$name] = is_string($value) ? trim($value) : '';
            }
        }

        $name = $fields['full_name'] ?? $fields['name'] ?? '';
        if (!$name && (isset($fields['first_name']) || isset($fields['last_name']))) {
            $name = trim(($fields['first_name'] ?? '') . ' ' . ($fields['last_name'] ?? ''));
        }

        $email = $fields['email'] ?? $fields['email_address'] ?? null;
        $phone = $fields['phone_number'] ?? $fields['phone'] ?? $fields['mobile'] ?? null;
        $company = $fields['company_name'] ?? $fields['company'] ?? null;

        if (!$name) {
            $name = $email ?: ($phone ?: 'Meta Lead');
        }

        return [
            'name' => $name,
            'email' => $email ?: null,
            'phone' => $phone ?: null,
            'company' => $company ?: null,
            'meta_lead_id' => (string) ($item['id'] ?? ''),
            'custom_fields' => array_filter([
                'lead_source' => 'facebook',
                'created_time' => $item['created_time'] ?? null,
            ]),
        ];
    }

    private function graphUrl(string $path): string
    {
        return 'https://graph.facebook.com/' . self::GRAPH_VERSION . $path;
    }

    /**
     * @param array<string, mixed>|null $json
     */
    private function extractError(?array $json, string $default): string
    {
        $message = $json['error']['message'] ?? null;

        return $message ? (string) $message : $default;
    }

    /**
     * Helper to handle Meta API errors and throw MetaApiException
     * 
     * @throws \App\Exceptions\MetaApiException
     */
    private function handleApiError(\Illuminate\Http\Client\Response $response, string $defaultMessage): void
    {
        $json = $response->json();
        $message = $this->extractError($json, $defaultMessage);

        $errorType = $json['error']['type'] ?? null;
        $errorCode = isset($json['error']['code']) ? (int) $json['error']['code'] : null;
        $errorSubcode = isset($json['error']['error_subcode']) ? (int) $json['error']['error_subcode'] : null;

        throw new \App\Exceptions\MetaApiException(
            $message,
            $response->status(),
            $errorCode,
            $errorSubcode,
            $errorType
        );
    }
}
