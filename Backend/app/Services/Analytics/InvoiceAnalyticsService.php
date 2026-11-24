<?php

namespace App\Services\Analytics;

use App\Models\Invoice;
use Illuminate\Support\Facades\Cache;

class InvoiceAnalyticsService
{
    public function getTotalInvoicesGenerated(): int
    {
        return Cache::remember('analytics_total_invoices', 60, function () {
            return Invoice::count();
        });
    }

    public function getInvoiceStatusDistributionChartData(): array
    {
        $invoices = Invoice::selectRaw('status, count(*) as count')
            ->groupBy('status')
            ->get();

        $labels = [];
        $data = [];

        foreach ($invoices as $i) {
            $labels[] = ucfirst($i->status);
            $data[] = $i->count;
        }

        return ['labels' => $labels, 'data' => $data];
    }
}
