<?php

namespace App\Http\Controllers\Api\WhatsApp;

use App\Models\WhatsApp\WhatsAppConversation;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class WhatsAppConversationController extends BaseWhatsAppController
{
    /**
     * Fetch WhatsApp conversations for the current company.
     * GET /api/whatsapp/conversations
     */
    public function index(Request $request): JsonResponse
    {
        $query = $this->scopeTenant(WhatsAppConversation::query(), $request)
            ->with([
                'contact',
                'account:id,display_phone_number,verified_name,phone_number_id',
                'assignedEmployee:id,name,email',
            ])
            ->orderBy('last_message_at', 'desc');

        if ($request->has('status')) {
            $query->where('status', $request->query('status'));
        }

        if ($request->has('assigned_to')) {
            $query->where('assigned_to', $request->query('assigned_to'));
        }

        if ($request->filled('search')) {
            $search = $request->query('search');
            $query->whereHas('contact', function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('phone_number', 'like', "%{$search}%")
                  ->orWhere('wa_id', 'like', "%{$search}%");
            });
        }

        $conversations = $query->paginate($request->integer('per_page', 20));

        return $this->paginated($conversations);
    }

    /**
     * Fetch messages for a specific conversation.
     * GET /api/whatsapp/conversations/{id}/messages
     */
    public function messages(Request $request, int $id): JsonResponse
    {
        $conversation = $this->scopeTenant(WhatsAppConversation::query(), $request)
            ->with(['contact', 'account'])
            ->findOrFail($id);

        // Mark unread count as zero upon inspection
        if ($conversation->unread_count > 0) {
            $conversation->update(['unread_count' => 0]);
        }

        $messages = $conversation->messages()
            ->orderBy('created_at', 'asc')
            ->paginate($request->integer('per_page', 50));

        return $this->paginated($messages);
    }
}
