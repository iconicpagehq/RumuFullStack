<?php

namespace App\Services;

use App\Models\Campaign;
use App\Models\User;
use App\Services\MetaLeadApiService;
use App\Services\MetaLeadCsvMapper;
use RuntimeException;

class MetaLeadImportService
{
    /**
     * @param array{name:string,email?:?string,phone?:?string,company?:?string,meta_lead_id?:?string,custom_fields?:?array} $data
     * @return 'imported'|'skipped'|'limit'
     */
    public function importRecord(Campaign $campaign, array $data, User $user): string
    {
        if (!empty($data['meta_lead_id']) && $campaign->leads()->where('meta_lead_id', $data['meta_lead_id'])->exists()) {
            return 'skipped';
        }

        if (!empty($data['phone']) && !$this->isValidPhone($data['phone'])) {
            return 'skipped';
        }

        $isEnterprise = $campaign->enterprise_id !== null;

        if ($isEnterprise) {
            $enterprise = $campaign->enterprise;
            if (!$enterprise || !app(\App\Services\EnterpriseLimitService::class)->can($enterprise, 'monthly_leads')) {
                return 'limit';
            }
        } elseif (!$user->canAddLeadToCampaign($campaign)) {
            return 'limit';
        }

        $attributes = [
            'name' => $data['name'],
            'email' => $data['email'] ?? null,
            'phone' => $data['phone'] ?? null,
            'company' => $data['company'] ?? null,
            'status' => 'new',
            'source' => 'meta_ads',
            'meta_lead_id' => $data['meta_lead_id'] ?? null,
            'custom_fields' => !empty($data['custom_fields']) ? $data['custom_fields'] : null,
        ];

        if ($isEnterprise) {
            $attributes['enterprise_id'] = $campaign->enterprise_id;
            $attributes['team_id'] = $campaign->team_id;
            $attributes['enterprise_stage'] = 'new';
            $attributes['assigned_manager_id'] = $campaign->team?->manager_id;
        }

        $lead = $campaign->leads()->create($attributes);

        if ($isEnterprise && $campaign->team) {
            $this->applyEnterpriseAutoAssignment($lead, $campaign);
        }

        return 'imported';
    }

    private function applyEnterpriseAutoAssignment(\App\Models\Lead $lead, Campaign $campaign): void
    {
        $team = $campaign->team;
        if (!$team) {
            return;
        }

        $autoAssign = $team->lead_distribution_mode === 'round_robin'
            || (bool) $campaign->enterprise?->setting('auto_assign_round_robin', false);

        if (!$autoAssign) {
            return;
        }

        $candidate = \App\Models\TeamMembership::query()
            ->where('team_id', $team->id)
            ->where('enterprise_id', $team->enterprise_id)
            ->whereHas('user.enterpriseMembership', function ($query) use ($team) {
                $query->where('enterprise_id', $team->enterprise_id)
                    ->where('status', 'active')
                    ->whereIn('role', ['employee', 'manager']);
            })
            ->orderByRaw('CASE WHEN last_assigned_at IS NULL THEN 0 ELSE 1 END')
            ->orderBy('last_assigned_at')
            ->orderByRaw('(SELECT COUNT(*) FROM leads WHERE leads.assigned_to = team_memberships.user_id AND leads.team_id = team_memberships.team_id AND leads.deleted_at IS NULL)')
            ->orderBy('id')
            ->first();

        if (!$candidate) {
            return;
        }

        $lead->update([
            'assigned_to' => $candidate->user_id,
            'assigned_at' => now(),
        ]);

        $candidate->update(['last_assigned_at' => now()]);
    }

    /**
     * @return array{imported:int,skipped:int,failed:int,errors:string[],message:string}
     */
    public function importFromCsv(Campaign $campaign, User $user, \Illuminate\Http\UploadedFile $file, MetaLeadCsvMapper $mapper): array
    {
        $fileContent = file_get_contents($file->getPathname());
        $encoding = mb_detect_encoding($fileContent ?: '', ['UTF-8', 'ISO-8859-1', 'Windows-1252'], true);
        if ($encoding && $encoding !== 'UTF-8') {
            $fileContent = mb_convert_encoding($fileContent, 'UTF-8', $encoding);
            file_put_contents($file->getPathname(), $fileContent);
        }

        $handle = fopen($file->getPathname(), 'r');
        if ($handle === false) {
            return [
                'imported' => 0,
                'skipped' => 0,
                'failed' => 1,
                'errors' => ['Could not read uploaded file.'],
                'message' => 'Import failed.',
            ];
        }

        $header = fgetcsv($handle);
        if (empty($header)) {
            fclose($handle);
            return [
                'imported' => 0,
                'skipped' => 0,
                'failed' => 1,
                'errors' => ['CSV file is empty or invalid.'],
                'message' => 'Import failed.',
            ];
        }

        $imported = 0;
        $skipped = 0;
        $errors = [];
        $rowNumber = 1;

        while (($row = fgetcsv($handle)) !== false) {
            $rowNumber++;
            if (empty(array_filter($row))) {
                continue;
            }

            $data = $mapper->mapRow($row, $header);
            if (!$mapper->hasValidLeadData($data)) {
                $errors[] = "Row {$rowNumber}: Name or email/phone is required";
                continue;
            }

            $data['name'] = $mapper->resolveDisplayName($data);

            $result = $this->importRecord($campaign, $data, $user);
            if ($result === 'imported') {
                $imported++;
            } elseif ($result === 'skipped') {
                $skipped++;
            } elseif ($result === 'limit') {
                $errors[] = "Plan limit reached at row {$rowNumber}. Upgrade plan to add more leads.";
                break;
            }
        }

        fclose($handle);

        $failed = count($errors);
        $message = "Imported {$imported} Meta lead(s).";
        if ($skipped > 0) {
            $message .= " Skipped {$skipped} duplicate(s).";
        }
        if ($failed > 0) {
            $message .= " {$failed} row(s) failed.";
        }

        return [
            'imported' => $imported,
            'skipped' => $skipped,
            'failed' => $failed,
            'errors' => $errors,
            'message' => $message,
        ];
    }

    /**
     * @return array{imported:int,skipped:int,failed:int,errors:string[],message:string}
     */
    public function syncFromApi(Campaign $campaign, User $user, string $formId, \App\Models\MetaPage $metaPage, MetaLeadApiService $metaApi): array
    {
        if (!$user->facebook_id && $metaPage->enterprise_id === null) {
            return [
                'imported' => 0,
                'skipped' => 0,
                'failed' => 1,
                'errors' => ['Please link your Facebook account first.'],
                'message' => 'Sync failed.',
            ];
        }

        if ($metaPage->status !== 'connected') {
            return [
                'imported' => 0,
                'skipped' => 0,
                'failed' => 1,
                'errors' => ['Facebook Page is not connected or token has expired.'],
                'message' => 'Sync failed.',
            ];
        }

        try {
            $leads = $metaApi->fetchAllLeads($metaPage, $formId);
            
            $metaPage->last_successful_api_request_at = now();
            $metaPage->consecutive_failures = 0;
            $metaPage->save();
        } catch (\App\Exceptions\MetaApiException $e) {
            $errMessage = $e->getMessage();
            
            $metaPage->last_failed_api_request_at = now();
            $metaPage->consecutive_failures = $metaPage->consecutive_failures + 1;

            if ($e->isOAuthError()) {
                $metaPage->status = 'expired';
                $metaPage->save();

                // For backward compatibility:
                $user->facebook_token_expired = true;
                $user->save();
            } else {
                $metaPage->save();
            }

            return [
                'imported' => 0,
                'skipped' => 0,
                'failed' => 1,
                'errors' => [$e->getMessage()],
                'message' => 'Sync failed.',
            ];
        }

        $campaign->update(['meta_lead_form_id' => $formId]);

        \Illuminate\Support\Facades\DB::table('meta_campaign_forms')->updateOrInsert(
            ['campaign_id' => $campaign->id],
            [
                'page_id' => $metaPage->page_id,
                'form_id' => $formId,
                'form_name' => 'Mapped Form',
                'updated_at' => now(),
            ]
        );

        $imported = 0;
        $skipped = 0;
        $errors = [];

        foreach ($leads as $data) {
            $result = $this->importRecord($campaign, $data, $user);
            if ($result === 'imported') {
                $imported++;
            } elseif ($result === 'skipped') {
                $skipped++;
            } elseif ($result === 'limit') {
                $errors[] = 'Plan limit reached. Some leads were not imported.';
                break;
            }
        }

        $failed = count($errors);
        $message = "Synced {$imported} lead(s) from Meta.";
        if ($skipped > 0) {
            $message .= " Skipped {$skipped} duplicate(s).";
        }
        if ($failed > 0) {
            $message .= ' ' . implode(' ', $errors);
        }

        \Illuminate\Support\Facades\DB::table('meta_campaign_forms')
            ->where('campaign_id', $campaign->id)
            ->where('form_id', $formId)
            ->update(['last_manual_sync_at' => now()]);

        \App\Models\MetaSyncLog::create([
            'user_id' => $user->id,
            'campaign_id' => $campaign->id,
            'type' => $failed > 0 ? ($imported > 0 ? 'warning' : 'error') : 'success',
            'event_type' => 'manual_sync',
            'message' => $message,
            'context' => ['imported' => $imported, 'skipped' => $skipped, 'failed' => $failed],
        ]);

        return [
            'imported' => $imported,
            'skipped' => $skipped,
            'failed' => $failed,
            'errors' => $errors,
            'message' => $message,
        ];
    }

    /**
     * Import a single lead from webhook request.
     */
    public function importSingleLeadFromWebhook(\App\Models\MetaPage $metaPage, string $formId, string $leadId, MetaLeadApiService $metaApi): string
    {
        $user = $metaPage->user;

        // 1. Find mapped campaign for this specific page and form (or if all forms mapped)
        $mapping = \Illuminate\Support\Facades\DB::table('meta_campaign_forms')
            ->where('page_id', $metaPage->page_id)
            ->where(function ($query) use ($formId) {
                $query->where('form_id', $formId)
                      ->orWhere('form_id', 'all');
            })
            ->first();
            
        if (!$mapping) {
            \Illuminate\Support\Facades\Log::warning('Webhook Lead Sync: Mapped campaign not found for form ID on this page', [
                'page_id' => $metaPage->page_id,
                'form_id' => $formId,
                'lead_id' => $leadId,
            ]);
            return 'no_campaign';
        }
        
        $campaign = \App\Models\Campaign::find($mapping->campaign_id);
        $isEnterpriseCampaign = $campaign
            && $campaign->enterprise_id !== null
            && (int) $metaPage->enterprise_id === (int) $campaign->enterprise_id;

        if (!$campaign || (!$isEnterpriseCampaign && $campaign->user_id !== $user->id)) {
            return 'no_campaign';
        }

        // 2. Idempotency Check: check if lead already exists in campaign using meta_lead_id
        if ($campaign->leads()->where('meta_lead_id', $leadId)->exists()) {
            \Illuminate\Support\Facades\Log::info('Webhook Lead Sync: Lead already exists (idempotent skip)', [
                'campaign_id' => $campaign->id,
                'lead_id' => $leadId,
            ]);
            return 'skipped';
        }

        // 3. Fetch lead details from Meta Graph API
        try {
            $leadData = $metaApi->fetchLead($metaPage, $leadId);
            
            // Record successful API request
            $metaPage->last_successful_api_request_at = now();
            $metaPage->consecutive_failures = 0;
            $metaPage->save();
        } catch (\App\Exceptions\MetaApiException $e) {
            $errMessage = $e->getMessage();
            
            // Record failed API request
            $metaPage->last_failed_api_request_at = now();
            $metaPage->consecutive_failures = $metaPage->consecutive_failures + 1;

            if ($e->isOAuthError()) {
                $metaPage->status = 'expired';
                $metaPage->save();

                // For backward compatibility until phase cleanup:
                $user->facebook_token_expired = true;
                $user->save();

                \App\Models\MetaSyncLog::create([
                    'user_id' => $user->id,
                    'campaign_id' => $campaign->id,
                    'type' => 'error',
                    'event_type' => 'webhook',
                    'message' => 'Meta token expired/invalid. Connection marked as expired.',
                    'context' => ['error' => $errMessage, 'page_id' => $metaPage->page_id],
                ]);

                \Illuminate\Support\Facades\Log::warning('Webhook Lead Sync: Meta token expired/invalid. Connection reset.', [
                    'user_id' => $user->id,
                    'error' => $errMessage,
                ]);
            } else {
                $metaPage->save();
                
                \App\Models\MetaSyncLog::create([
                    'user_id' => $user->id,
                    'campaign_id' => $campaign->id,
                    'type' => 'error',
                    'event_type' => 'webhook',
                    'message' => 'Meta Graph API failed',
                    'context' => ['lead_id' => $leadId, 'error' => $errMessage, 'transient' => $e->isTransient()],
                ]);

                \Illuminate\Support\Facades\Log::error('Webhook Lead Sync: Meta Graph API failed', [
                    'user_id' => $user->id,
                    'lead_id' => $leadId,
                    'error' => $errMessage,
                ]);
            }

            // Bubble up to let the Job handle retries based on transiency
            throw $e;
        }

        // 4. Import the record
        $result = $this->importRecord($campaign, $leadData, $user);

        if ($result === 'imported') {
            \Illuminate\Support\Facades\DB::table('meta_campaign_forms')
                ->where('campaign_id', $campaign->id)
                ->update(['last_webhook_received_at' => now()]);

            \App\Models\MetaSyncLog::create([
                'user_id' => $user->id,
                'campaign_id' => $campaign->id,
                'type' => 'success',
                'event_type' => 'webhook',
                'message' => 'Lead successfully imported via webhook',
                'context' => ['lead_id' => $leadId, 'meta_lead_id' => $leadData['meta_lead_id'] ?? null],
            ]);
        }

        return $result;
    }

    private function isValidPhone(string $phone): bool
    {
        $pattern = '/^[\+]?[(]?[0-9]{1,4}[)]?[-\s\.]?[0-9]{1,4}[-\s\.]?[0-9]{1,9}$/';
        return preg_match($pattern, $phone) === 1;
    }
}

