<?php

namespace App\Http\Controllers\Admin\Billing;

use App\Http\Controllers\Controller;
use App\Models\BillingNotificationTemplate;
use Illuminate\Http\Request;

class BillingNotificationTemplateController extends Controller
{
    public function index()
    {
        $templates = BillingNotificationTemplate::orderBy('type')->get();
        return view('admin.billing.notifications.templates.index', compact('templates'));
    }

    public function create()
    {
        return view('admin.billing.notifications.templates.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'type' => 'required|string|unique:billing_notification_templates,type|max:255',
            'subject' => 'required|string|max:255',
            'body_content' => 'required|string',
            'is_active' => 'boolean',
        ]);

        BillingNotificationTemplate::create($validated);

        return redirect()->route('admin.billing.notifications.templates.index')->with('success', 'Template created successfully.');
    }

    public function edit(BillingNotificationTemplate $template)
    {
        return view('admin.billing.notifications.templates.edit', compact('template'));
    }

    public function update(Request $request, BillingNotificationTemplate $template)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'type' => 'required|string|max:255|unique:billing_notification_templates,type,' . $template->id,
            'subject' => 'required|string|max:255',
            'body_content' => 'required|string',
            'is_active' => 'boolean',
        ]);

        $template->update($validated);

        return redirect()->route('admin.billing.notifications.templates.index')->with('success', 'Template updated successfully.');
    }
}
