<?php

namespace App\Http\Controllers\Api\WhatsApp;

use App\Http\Requests\WhatsApp\SendMessageRequest;
use App\Jobs\WhatsApp\SendWhatsAppMessageJob;
use App\Models\Lead;
use App\Models\WhatsApp\WhatsAppAccount;
use App\Models\WhatsApp\WhatsAppContact;
use App\Models\WhatsApp\WhatsAppConversation;
use App\Models\WhatsApp\WhatsAppMessage;
use Illuminate\Http\JsonResponse;

class WhatsAppMessageController extends BaseWhatsAppController
{
    /**
     * Send outgoing WhatsApp message.
     * POST /api/whatsapp/messages/send
     */
    public function send(SendMessageRequest $request): JsonResponse
    {
        $tenant = $this->getTenantContext($request);
        $companyId = $tenant['company_id'];
        $userId = $tenant['user_id'];

        if (!$companyId) {
            return $this->error('Company context not found for user. Access denied.', 403);
        }

        $validated = $request->validated();

        // 1. Resolve WhatsApp Account
        $accountQuery = WhatsAppAccount::where('company_id', $companyId)->where('status', 'connected');
        if (!empty($validated['account_id'])) {
            $account = $accountQuery->where('id', $validated['account_id'])->first();
        } else {
            $account = $accountQuery->orderByDesc('is_default')->first();
        }

        if (!$account) {
            return $this->error('No active WhatsApp account found for your company.', 404);
        }

        // 2. Resolve or Create WhatsApp Contact
        $rawTo = preg_replace('/[^0-9]/', '', $validated['to']);
        $contact = WhatsAppContact::firstOrCreate(
            [
                'company_id' => $companyId,
                'wa_id' => $rawTo,
            ],
            [
                'phone_number' => '+' . $rawTo,
                'name' => null,
                'last_active_at' => now(),
            ]
        );

        // Auto-match CRM Lead if available
        if (!$contact->lead_id) {
            $lead = Lead::where('enterprise_id', $companyId)
                ->where(function ($q) use ($rawTo) {
                    $q->where('phone', 'like', "%{$rawTo}%")
                      ->orWhere('phone', 'like', '%' . substr($rawTo, -10) . '%');
                })->first();

            if ($lead) {
                $contact->update(['lead_id' => $lead->id]);
            }
        }

        // 3. Resolve or Create Conversation
        $conversation = WhatsAppConversation::firstOrCreate(
            [
                'whatsapp_account_id' => $account->id,
                'whatsapp_contact_id' => $contact->id,
            ],
            [
                'company_id' => $companyId,
                'assigned_to' => $userId,
                'status' => 'open',
                'unread_count' => 0,
            ]
        );

        // 4. Create Message record
        $msgType = $validated['type'] ?? 'text';
        $content = $validated['message'] ?? ($validated['caption'] ?? null);

        $rawPayload = [];
        if ($msgType === 'template') {
            $rawPayload = [
                'template_name' => $validated['template_name'],
                'language' => $validated['language'] ?? 'en_US',
                'components' => $validated['components'] ?? [],
            ];
            $content = $content ?: "[Template: {$validated['template_name']}]";
        }

        $message = WhatsAppMessage::create([
            'company_id' => $companyId,
            'whatsapp_conversation_id' => $conversation->id,
            'direction' => 'outgoing',
            'sender_id' => $account->phone_number_id,
            'recipient_id' => $rawTo,
            'type' => $msgType,
            'content' => $content,
            'media_url' => $validated['media_url'] ?? null,
            'raw_payload' => $rawPayload,
            'status' => 'pending',
            'sent_at' => now(),
        ]);

        // 5. Dispatch background job to send via Meta Cloud API
        SendWhatsAppMessageJob::dispatch($message->id);

        return $this->success([
            'message_id' => $message->id,
            'conversation_id' => $conversation->id,
            'status' => $message->status,
            'type' => $message->type,
            'content' => $message->content,
            'recipient' => $message->recipient_id,
            'created_at' => $message->created_at,
        ], 'WhatsApp message queued for sending.', 201);
    }
}
