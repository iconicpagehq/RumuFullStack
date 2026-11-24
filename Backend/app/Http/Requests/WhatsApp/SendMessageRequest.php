<?php

namespace App\Http\Requests\WhatsApp;

use Illuminate\Foundation\Http\FormRequest;

class SendMessageRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'to' => ['required', 'string', 'max:50'],
            'type' => ['nullable', 'string', 'in:text,image,document,video,audio,template'],
            'message' => ['required_without_all:template_name,media_url', 'nullable', 'string'],
            'media_url' => ['nullable', 'string', 'url'],
            'caption' => ['nullable', 'string', 'max:1000'],
            'template_name' => ['required_if:type,template', 'nullable', 'string', 'max:100'],
            'language' => ['nullable', 'string', 'max:20'],
            'components' => ['nullable', 'array'],
            'account_id' => ['nullable', 'integer', 'exists:whatsapp_accounts,id'],
            'conversation_id' => ['nullable', 'integer', 'exists:whatsapp_conversations,id'],
        ];
    }

    public function messages(): array
    {
        return [
            'to.required' => 'The recipient phone number (to) is required.',
            'message.required_without_all' => 'Message text is required when sending a standard text message.',
            'template_name.required_if' => 'Template name is required when sending a template message.',
        ];
    }
}
