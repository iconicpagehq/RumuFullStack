<?php

namespace App\Http\Controllers\Api\Admin\Billing;

use App\Http\Controllers\Api\ApiController;
use App\Http\Resources\BillingNotificationResource;
use App\Models\BillingNotificationTemplate;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class NotificationTemplateController extends ApiController
{
    public function index()
    {
        $templates = BillingNotificationTemplate::orderBy('type')->get();

        return $this->success($templates);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'type' => 'required|string|unique:billing_notification_templates,type|max:255',
            'subject' => 'required|string|max:255',
            'body_content' => 'required|string',
            'is_active' => 'sometimes|boolean',
        ]);

        $template = BillingNotificationTemplate::create($validated);

        return $this->success($template, 'Template created.', 201);
    }

    public function show(BillingNotificationTemplate $template)
    {
        return $this->success($template);
    }

    public function update(Request $request, BillingNotificationTemplate $template)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'type' => ['required', 'string', 'max:255', Rule::unique('billing_notification_templates', 'type')->ignore($template->id)],
            'subject' => 'required|string|max:255',
            'body_content' => 'required|string',
            'is_active' => 'sometimes|boolean',
        ]);

        $template->update($validated);

        return $this->success($template->fresh(), 'Template updated.');
    }

    public function destroy(BillingNotificationTemplate $template)
    {
        $template->delete();

        return $this->success(null, 'Template deleted.');
    }
}
