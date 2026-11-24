<?php

namespace App\Http\Controllers;

use App\Jobs\ProcessWebhookLeadJob;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class MetaWebhookController extends Controller
{
    /**
     * Verify webhook registration challenge from Meta.
     */
    public function verify(Request $request)
    {
        $mode = $request->query('hub_mode') ?: $request->query('hub.mode');
        $token = $request->query('hub_verify_token') ?: $request->query('hub.verify_token');
        $challenge = $request->query('hub_challenge') ?: $request->query('hub.challenge');

        $configuredToken = config('services.meta.webhook_verify_token');

        if ($mode === 'subscribe' && $token === $configuredToken) {
            Log::info('Meta Webhook Verification: Success');
            return response($challenge, 200)->header('Content-Type', 'text/plain');
        }

        Log::warning('Meta Webhook Verification: Mismatch or invalid parameters.', [
            'hub_mode' => $mode,
            'verify_token_received' => $token ? 'Yes (hidden)' : 'No',
        ]);

        return response('Forbidden', 403);
    }

    /**
     * Handle incoming Meta leadgen webhook events.
     */
    public function handle(Request $request)
    {
        Log::info('Meta Webhook: Payload received.');

        if (!$this->validateSignature($request)) {
            Log::warning('Meta Webhook: Signature verification failed.');
            return response('Unauthorized', 401);
        }

        $payload = $request->json()->all();

        if (($payload['object'] ?? '') !== 'page') {
            Log::warning('Meta Webhook: Payload object is not page.', [
                'object' => $payload['object'] ?? null,
            ]);
            return response('Payload object is not page', 400);
        }

        $entries = $payload['entry'] ?? [];
        $dispatchedCount = 0;

        foreach ($entries as $entry) {
            $pageId = $entry['id'] ?? null;
            $changes = $entry['changes'] ?? [];

            if (!$pageId) {
                continue;
            }

            foreach ($changes as $change) {
                if (($change['field'] ?? '') !== 'leadgen') {
                    continue;
                }

                $value = $change['value'] ?? [];
                $leadId = $value['leadgen_id'] ?? null;
                $formId = $value['form_id'] ?? null;

                if (!$leadId || !$formId) {
                    continue;
                }

                // Dispatch thin ProcessWebhookLeadJob to process asynchronously
                ProcessWebhookLeadJob::dispatch($pageId, $formId, $leadId);
                $dispatchedCount++;
            }
        }

        Log::info('Meta Webhook: Payload successfully verified and queued.', [
            'jobs_dispatched' => $dispatchedCount,
        ]);

        return response('Webhook Handled', 200);
    }

    /**
     * Validate Meta webhook HMAC-SHA256 signature using App Secret.
     */
    private function validateSignature(Request $request): bool
    {
        $signatureHeader = $request->header('X-Hub-Signature-256');
        if (!$signatureHeader) {
            return false;
        }

        $parts = explode('=', $signatureHeader, 2);
        if (count($parts) !== 2 || strtolower($parts[0]) !== 'sha256') {
            return false;
        }

        $signature = $parts[1];
        $appSecret = config('services.meta.app_secret');

        if (!$appSecret) {
            Log::error('Meta Webhook: App secret is not configured.');
            return false;
        }

        $payload = $request->getContent();
        $expectedSignature = hash_hmac('sha256', $payload, $appSecret);

        return hash_equals($expectedSignature, $signature);
    }
}
