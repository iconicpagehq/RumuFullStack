<?php

namespace App\Services\Calls;

use App\Models\CallLog;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class CallMetricsService
{
    public function payload(Builder $query, int $recentLimit = 5): array
    {
        $today = (clone $query)->whereDate('created_at', now()->toDateString());

        $todayTotal = (clone $today)->count();
        $todayConnected = (clone $today)->whereNotNull('answered_at')->count();
        $todayRecorded = (clone $today)
            ->where('recording_status', CallLog::RECORDING_AVAILABLE)
            ->whereNull('recording_deleted_at')
            ->count();
        $avgDuration = (int) round((float) (clone $today)
            ->where('duration_seconds', '>', 0)
            ->avg('duration_seconds'));

        $pendingDispositions = (clone $query)
            ->whereIn('status', CallLog::TERMINAL_STATUSES)
            ->whereNull('disposition')
            ->count();

        $byStatus = (clone $query)
            ->select('status', DB::raw('COUNT(*) as total'))
            ->groupBy('status')
            ->pluck('total', 'status');

        $byUser = (clone $query)
            ->select('initiated_by', DB::raw('COUNT(*) as total'), DB::raw('SUM(duration_seconds) as seconds'))
            ->whereNotNull('initiated_by')
            ->groupBy('initiated_by')
            ->orderByDesc('total')
            ->limit(5)
            ->get();

        $names = User::whereIn('id', $byUser->pluck('initiated_by')->filter()->all())
            ->pluck('name', 'id');

        return [
            'today' => [
                'total' => $todayTotal,
                'connected' => $todayConnected,
                'recorded' => $todayRecorded,
                'avg_duration_seconds' => $avgDuration,
            ],
            'pending_dispositions' => $pendingDispositions,
            // Force a JSON object even when no calls exist, so mobile clients that
            // decode this field as Map<String, Int> do not receive [].
            'by_status' => (object) $byStatus->all(),
            'by_user' => $byUser->map(fn ($row) => [
                'user_id' => (int) $row->initiated_by,
                'name' => $names[$row->initiated_by] ?? 'Team member',
                'total' => (int) $row->total,
                'seconds' => (int) $row->seconds,
            ])->values(),
            'trend' => $this->trend($query),
            'recent' => (clone $query)
                ->withLeadRelation()
                ->latest('started_at')
                ->latest('id')
                ->limit($recentLimit)
                ->get(),
        ];
    }

    public function trend(Builder $query, int $days = 7): array
    {
        $rows = (clone $query)
            ->where('created_at', '>=', now()->subDays($days - 1)->startOfDay())
            ->get(['created_at', 'answered_at', 'recording_status', 'recording_deleted_at']);

        $trend = [];

        for ($i = $days - 1; $i >= 0; $i--) {
            $day = Carbon::today()->subDays($i);
            $dayRows = $rows->filter(
                fn (CallLog $call) => $call->created_at?->toDateString() === $day->toDateString()
            );

            $trend[] = [
                'date' => $day->toDateString(),
                'label' => $day->format('D'),
                'total' => $dayRows->count(),
                'connected' => $dayRows->whereNotNull('answered_at')->count(),
                'recorded' => $dayRows->filter(
                    fn (CallLog $call) => $call->recording_status === CallLog::RECORDING_AVAILABLE
                        && $call->recording_deleted_at === null
                )->count(),
            ];
        }

        return $trend;
    }
}
