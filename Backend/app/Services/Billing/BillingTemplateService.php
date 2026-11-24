<?php

namespace App\Services\Billing;

use App\Models\BillingNotificationTemplate;
use Illuminate\Support\Facades\Log;

class BillingTemplateService
{
    /**
     * Parses a template substituting placeholders with actual data.
     *
     * @param string $templateType
     * @param array $data
     * @return array ['subject' => string, 'body' => string]
     */
    public function parseTemplate(string $templateType, array $data): array
    {
        $template = BillingNotificationTemplate::where('type', $templateType)->where('is_active', true)->first();

        if (!$template) {
            Log::warning("Billing notification template not found for type: {$templateType}");
            return ['subject' => '', 'body' => ''];
        }

        $subject = $template->subject;
        $body = $template->body_content;

        foreach ($data as $key => $value) {
            $placeholder = '{{' . $key . '}}';
            // Simple string replacement for placeholders
            $subject = str_replace($placeholder, $value ?? '', $subject);
            $body = str_replace($placeholder, $value ?? '', $body);
        }

        return [
            'subject' => $subject,
            'body' => $body,
        ];
    }
}
