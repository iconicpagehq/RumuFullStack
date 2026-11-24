<?php

namespace App\Services\WhatsApp;

use App\Models\WhatsApp\WhatsAppAccount;
use App\Models\WhatsApp\WhatsAppTemplate;
use Exception;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

class WhatsAppTemplateService
{
    protected WhatsAppService $whatsAppService;

    public function __construct(WhatsAppService $whatsAppService)
    {
        $this->whatsAppService = $whatsAppService;
    }

    /**
     * Sync approved and existing templates from Meta for a given WhatsApp account.
     */
    public function syncTemplates(WhatsAppAccount $account): array
    {
        if (empty($account->waba_id)) {
            throw new Exception("WhatsApp Business Account ID (WABA ID) is not set for account #{$account->id}.");
        }

        $metaResponse = $this->whatsAppService->getMessageTemplates($account->waba_id, $account);
        $templatesData = $metaResponse['data'] ?? [];

        $syncedCount = 0;

        foreach ($templatesData as $tData) {
            $name = $tData['name'] ?? null;
            $language = $tData['language'] ?? 'en_US';
            if (!$name) {
                continue;
            }

            WhatsAppTemplate::updateOrCreate(
                [
                    'whatsapp_account_id' => $account->id,
                    'name' => $name,
                    'language' => $language,
                ],
                [
                    'company_id' => $account->company_id,
                    'meta_template_id' => $tData['id'] ?? null,
                    'category' => $tData['category'] ?? 'UTILITY',
                    'status' => $tData['status'] ?? 'APPROVED',
                    'components' => $tData['components'] ?? [],
                    'rejected_reason' => $tData['rejected_reason'] ?? null,
                    'last_synced_at' => now(),
                ]
            );

            $syncedCount++;
        }

        return [
            'synced' => $syncedCount,
            'total' => count($templatesData),
        ];
    }

    /**
     * List templates filtered by company and optional status/account.
     */
    public function listTemplates(int $companyId, array $filters = []): Collection
    {
        $query = WhatsAppTemplate::where('company_id', $companyId);

        if (!empty($filters['whatsapp_account_id'])) {
            $query->where('whatsapp_account_id', $filters['whatsapp_account_id']);
        }

        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (!empty($filters['category'])) {
            $query->where('category', $filters['category']);
        }

        return $query->orderBy('name', 'asc')->get();
    }
}
