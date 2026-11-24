<?php

namespace App\Http\Controllers\Api\Enterprise;

use App\Http\Controllers\Api\Calls\HandlesCalls;
use App\Models\CallLog;
use App\Models\EnterpriseMembership;
use App\Models\Lead;
use App\Services\Calls\CallAccessService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

class CallController extends EnterpriseApiController
{
    use HandlesCalls;

    protected function callScope(Request $request): Builder
    {
        return app(CallAccessService::class)->enterpriseQuery($this->membership($request));
    }

    protected function assertCallAccess(Request $request, CallLog $call): void
    {
        abort_unless(
            app(CallAccessService::class)->visibleToEnterprise($call, $this->membership($request)),
            404
        );
    }

    protected function assertLeadAccess(Request $request, Lead $lead): void
    {
        abort_unless(
            $this->hierarchyService()->leadVisible($lead, $this->membership($request)),
            404
        );
    }

    protected function callMembership(Request $request): ?EnterpriseMembership
    {
        return $this->membership($request);
    }

    protected function canDeleteRecording(Request $request, CallLog $call): bool
    {
        return app(CallAccessService::class)
            ->canDeleteRecordingEnterprise($call, $this->membership($request));
    }
}
