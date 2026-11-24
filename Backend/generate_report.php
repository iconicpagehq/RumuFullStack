<?php

$data = json_decode(file_get_contents('audit_data.json'), true);

$report = "# Detailed Code-Backed Implementation Audit\n\n";

// 1. Phases (We will manually attribute files to phases since the code doesn't know phases)
// But we can list ALL Billing related files.
$billingControllers = [];
foreach ($data['controllers'] as $class => $info) {
    if (str_contains($class, 'Billing') || str_contains($class, 'Payment') || str_contains($class, 'Invoice') || str_contains($class, 'Subscription') || str_contains($class, 'Coupon') || str_contains($class, 'Promotion') || str_contains($class, 'Analytics') || str_contains($class, 'Referral')) {
        $billingControllers[$class] = $info;
    }
}

$billingServices = [];
foreach ($data['services'] as $class => $info) {
    if (str_contains($class, 'Billing') || str_contains($class, 'Payment') || str_contains($class, 'Invoice') || str_contains($class, 'Subscription') || str_contains($class, 'Coupon') || str_contains($class, 'Promotion') || str_contains($class, 'Analytics') || str_contains($class, 'Referral') || str_contains($class, 'Tax')) {
        $billingServices[$class] = $info;
    }
}

$billingModels = [];
foreach ($data['models'] as $class => $info) {
    if (in_array(class_basename($class), ['Invoice', 'InvoiceItem', 'Subscription', 'Payment', 'Coupon', 'CouponUsage', 'Referral', 'BillingNotification', 'BillingNotificationTemplate', 'BillingSetting', 'BillingSettingAudit'])) {
        $billingModels[$class] = $info;
    }
}

$report .= "## Controllers Audit\n\n";
foreach ($billingControllers as $class => $info) {
    $report .= "### `$class`\n";
    $report .= "- **Injected Services:** " . (empty($info['injected_services']) ? "None" : implode(", ", $info['injected_services'])) . "\n";
    $report .= "- **Methods:**\n";
    foreach ($info['methods'] as $method) {
        $report .= "  - `$method()`\n";
    }
    $report .= "\n";
}

$report .= "## Services Audit\n\n";
foreach ($billingServices as $class => $info) {
    $report .= "### `$class`\n";
    $report .= "- **Dependencies:** " . (empty($info['dependencies']) ? "None" : implode(", ", $info['dependencies'])) . "\n";
    $report .= "- **Methods:**\n";
    foreach ($info['methods'] as $method) {
        $report .= "  - `$method()`\n";
    }
    $report .= "\n";
}

$report .= "## Models Audit\n\n";
foreach ($billingModels as $class => $info) {
    $report .= "### `$class`\n";
    $report .= "- **Table:** `{$info['table']}`\n";
    $report .= "- **Fillable:** " . implode(", ", $info['fillable'] ?? []) . "\n";
    $report .= "- **Relationships:** " . implode(", ", $info['relationships'] ?? []) . "\n\n";
}

$report .= "## Routes Audit\n\n";
$report .= "| Route | Method | Action | Middleware |\n";
$report .= "|---|---|---|---|\n";
foreach ($data['routes'] as $route) {
    if (str_contains($route['uri'], 'billing') || str_contains($route['uri'], 'invoice') || str_contains($route['uri'], 'subscription') || str_contains($route['uri'], 'payment') || str_contains($route['uri'], 'coupon') || str_contains($route['uri'], 'analytics') || str_contains($route['uri'], 'settings')) {
        $methods = implode('|', $route['methods']);
        $middleware = implode(', ', $route['middleware']);
        $action = is_string($route['action']) ? $route['action'] : 'Closure';
        $report .= "| `{$route['uri']}` | `$methods` | `$action` | `$middleware` |\n";
    }
}

file_put_contents('audit_report_raw.md', $report);
echo "Report generated.";
