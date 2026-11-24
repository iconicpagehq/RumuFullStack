<?php

namespace App\Services;

use App\Models\Enterprise;
use App\Models\EnterpriseInvitation;
use App\Models\InventoryUnit;
use App\Models\Lead;
use App\Models\Plan;
use App\Models\Task;
use App\Models\Team;
use App\Models\Campaign;
use Illuminate\Http\Exceptions\HttpResponseException;

class EnterpriseLimitService
{
    public const RESOURCES = [
        'managers' => 'Managers',
        'employees' => 'Employees',
        'teams' => 'Teams',
        'monthly_leads' => 'Monthly leads',
        'inventory_units' => 'Inventory units',
        'campaigns' => 'Campaigns',
    ];

    public function plan(Enterprise $enterprise): ?Plan
    {
        return $enterprise->plan;
    }

    public function limit(Enterprise $enterprise, string $resource): ?int
    {
        $column = 'max_' . $resource;
        $limit = $enterprise->plan?->{$column};

        if ($limit === null) {
            return null;
        }

        return (int) $limit + $this->extraSeats($enterprise, $resource);
    }

    public function extraSeats(Enterprise $enterprise, string $resource): int
    {
        return match ($resource) {
            'managers' => (int) ($enterprise->extra_managers ?? 0),
            'employees' => (int) ($enterprise->extra_employees ?? 0),
            default => 0,
        };
    }

    public function usage(Enterprise $enterprise): array
    {
        $pendingInvitations = EnterpriseInvitation::query()
            ->where('enterprise_id', $enterprise->id)
            ->pending()
            ->where('expires_at', '>', now());

        $unitQuery = InventoryUnit::query()->forEnterprise($enterprise->id);

        return [
            'managers' => $enterprise->memberships()->where('role', 'manager')->where('status', 'active')->count()
                + (clone $pendingInvitations)->where('role', 'manager')->count(),
            'employees' => $enterprise->memberships()->where('role', 'employee')->where('status', 'active')->count()
                + (clone $pendingInvitations)->where('role', 'employee')->count(),
            'teams' => $enterprise->teams()->count(),
            'monthly_leads' => $enterprise->leads()->where('created_at', '>=', now()->startOfMonth())->count(),
            'inventory_units' => $unitQuery->count(),
            'campaigns' => $enterprise->campaigns()->count(),
        ];
    }

    public function usageSummary(Enterprise $enterprise): array
    {
        $usage = $this->usage($enterprise);

        return collect(self::RESOURCES)->mapWithKeys(function ($label, $key) use ($enterprise, $usage) {
            $limit = $this->limit($enterprise, $key);
            $baseLimit = $enterprise->plan?->{'max_' . $key};
            $extra = $this->extraSeats($enterprise, $key);

            return [$key => [
                'label' => $label,
                'used' => $usage[$key],
                'limit' => $limit,
                'base_limit' => $baseLimit === null ? null : (int) $baseLimit,
                'extra' => $extra,
                'percentage' => $limit === null || $limit === 0
                    ? null
                    : min(100, (int) round(($usage[$key] / $limit) * 100)),
                'remaining' => $limit === null ? null : max(0, $limit - $usage[$key]),
            ]];
        })->all();
    }

    public function can(Enterprise $enterprise, string $resource, int $increment = 1): bool
    {
        $limit = $this->limit($enterprise, $resource);
        if ($limit === null) {
            return true;
        }

        return ($this->usage($enterprise)[$resource] ?? 0) + $increment <= $limit;
    }

    /**
     * @throws \Illuminate\Validation\ValidationException
     */
    public function ensure(Enterprise $enterprise, string $resource, int $increment = 1): void
    {
        if ($this->can($enterprise, $resource, $increment)) {
            return;
        }

        $label = self::RESOURCES[$resource] ?? $resource;
        $limit = $this->limit($enterprise, $resource);

        throw \Illuminate\Validation\ValidationException::withMessages([
            'limit' => ["Your plan allows {$limit} {$label}. Upgrade your enterprise plan to add more."],
        ]);
    }

    public function feature(Enterprise $enterprise, string $feature): bool
    {
        return (bool) $enterprise->plan?->{$feature};
    }

    public function requireFeature(Enterprise $enterprise, string $feature): void
    {
        if ($this->feature($enterprise, $feature)) {
            return;
        }

        throw new HttpResponseException(response()->json([
            'success' => false,
            'message' => 'This capability is not included in your current plan. Upgrade to Growth to unlock it.',
            'code' => 'feature_locked',
            'feature' => $feature,
            'plan' => $enterprise->plan?->name,
        ], 403));
    }
}
