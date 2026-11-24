<?php

namespace App\Http\Controllers\Admin\Analytics;

use App\Http\Controllers\Controller;
use App\Services\Analytics\ReportService;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class ReportController extends Controller
{
    public function index()
    {
        return view('admin.analytics.reports');
    }

    public function export(Request $request, ReportService $reportSvc)
    {
        $validated = $request->validate([
            'type' => 'required|in:revenue,customers',
            'format' => 'required|in:csv', // Phase 13 scope: PDF can be added later
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
        ]);

        $start = Carbon::parse($validated['start_date']);
        $end = Carbon::parse($validated['end_date']);
        $type = $validated['type'];

        $filename = "{$type}_report_{$start->format('Ymd')}_{$end->format('Ymd')}.csv";

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"$filename\"",
        ];

        if ($type === 'revenue') {
            return response()->stream($reportSvc->generateRevenueCSV($start, $end), 200, $headers);
        } elseif ($type === 'customers') {
            return response()->stream($reportSvc->generateCustomerCSV($start, $end), 200, $headers);
        }
        
        return back()->with('error', 'Report type not supported.');
    }
}
