<?php

namespace App\Http\Controllers\Api\WhatsApp;

use App\Models\WhatsApp\WhatsAppTemplate;
use App\Services\WhatsApp\WhatsAppTemplateService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class WhatsAppTemplateController extends BaseWhatsAppController
{
    protected WhatsAppTemplateService $templateService;

    public function __construct(WhatsAppTemplateService $templateService)
    {
        $this->templateService = $templateService;
    }

    /**
     * Fetch available templates for current company.
     * GET /api/whatsapp/templates
     */
    public function index(Request $request): JsonResponse
    {
        $tenant = $this->getTenantContext($request);
        $companyId = $tenant['company_id'];

        if (!$companyId) {
            return $this->error('Company context not found for user. Access denied.', 403);
        }

        $filters = $request->only(['whatsapp_account_id', 'status', 'category']);
        $templates = $this->templateService->listTemplates($companyId, $filters);

        return $this->success($templates, 'WhatsApp templates retrieved successfully.');
    }
}
