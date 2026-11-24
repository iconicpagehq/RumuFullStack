<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Api\ApiController;
use App\Models\Campaign;
use App\Models\Enterprise;
use App\Models\EnterpriseMembership;
use App\Models\Lead;
use App\Models\Plan;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class DashboardController extends ApiController
{
    public function index()
    {
        $leadsByStatus = Lead::whereNull('enterprise_id')->select('status', DB::raw('COUNT(*) as count'))
            ->groupBy('status')
            ->pluck('count', 'status')
            ->toArray();

        $campaignsByStatus = Campaign::whereNull('enterprise_id')->select('status', DB::raw('COUNT(*) as count'))
            ->groupBy('status')
            ->pluck('count', 'status')
            ->toArray();

        $realtorsByPlan = User::where('role', 'realtor')
            ->select('plan_id', DB::raw('COUNT(*) as count'))
            ->groupBy('plan_id')
            ->pluck('count', 'plan_id')
            ->toArray();

        $planNames = Plan::pluck('name', 'id')->toArray();

        $months = [];
        $leadsMonthly = [];
        $campaignsMonthly = [];
        $enterpriseLeadsMonthly = [];

        for ($i = 5; $i >= 0; $i--) {
            $date = now()->subMonths($i);
            $months[] = $date->format('M Y');

            $leadsMonthly[] = Lead::whereNull('enterprise_id')
                ->whereYear('created_at', $date->year)
                ->whereMonth('created_at', $date->month)
                ->count();

            $campaignsMonthly[] = Campaign::whereNull('enterprise_id')
                ->whereYear('created_at', $date->year)
                ->whereMonth('created_at', $date->month)
                ->count();

            $enterpriseLeadsMonthly[] = Lead::whereNotNull('enterprise_id')
                ->whereYear('created_at', $date->year)
                ->whereMonth('created_at', $date->month)
                ->count();
        }

        $enterprisePlans = Enterprise::whereNotNull('plan_id')
            ->select('plan_id', DB::raw('COUNT(*) as count'))
            ->groupBy('plan_id')
            ->pluck('count', 'plan_id')
            ->toArray();

        return $this->success([
            'leads_by_status' => $leadsByStatus,
            'campaigns_by_status' => $campaignsByStatus,
            'realtors_by_plan' => $realtorsByPlan,
            'plan_names' => $planNames,
            'months' => $months,
            'leads_monthly' => $leadsMonthly,
            'campaigns_monthly' => $campaignsMonthly,
            'enterprise_leads_monthly' => $enterpriseLeadsMonthly,
            'enterprises_total' => Enterprise::count(),
            'enterprises_active' => Enterprise::where('status', 'active')->count(),
            'enterprise_members' => EnterpriseMembership::where('status', EnterpriseMembership::STATUS_ACTIVE)->count(),
            'enterprise_leads_total' => Lead::whereNotNull('enterprise_id')->count(),
            'enterprise_plans' => $enterprisePlans,
        ]);
    }
}

