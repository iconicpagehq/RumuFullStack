<?php

namespace App\Http\Controllers\Api\WhatsApp;

use App\Models\WhatsApp\WhatsAppAccount;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class WhatsAppAccountController extends BaseWhatsAppController
{
    /**
     * Fetch connected WhatsApp accounts for current company.
     * GET /api/whatsapp/accounts
     */
    public function index(Request $request): JsonResponse
    {
        $accounts = $this->scopeTenant(WhatsAppAccount::query(), $request)
            ->select([
                'id',
                'company_id',
                'waba_id',
                'phone_number_id',
                'display_phone_number',
                'verified_name',
                'code_verification_status',
                'quality_rating',
                'status',
                'api_version',
                'is_default',
                'last_synced_at',
                'created_at',
            ])
            ->orderByDesc('is_default')
            ->orderBy('created_at', 'desc')
            ->get();

        return $this->success($accounts, 'WhatsApp accounts retrieved successfully.');
    }
}
