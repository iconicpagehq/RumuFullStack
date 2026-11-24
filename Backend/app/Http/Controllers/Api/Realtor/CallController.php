<?php

namespace App\Http\Controllers\Api\Realtor;

use App\Http\Controllers\Api\ApiController;
use App\Http\Controllers\Api\Calls\HandlesCalls;
use App\Models\CallLog;
use App\Models\EnterpriseMembership;
use App\Models\Lead;
use App\Services\Calls\CallAccessService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

class CallController extends ApiController
{
    use HandlesCalls;

    protected function callScope(Request $request): Builder
    {
        return app(CallAccessService::class)->realtorQuery($request->user()->id);
    }

    protected function assertCallAccess(Request $request, CallLog $call): void
    {
        abort_unless(
            app(CallAccessService::class)->visibleToRealtor($call, $request->user()->id),
            404
        );
    }

    protected function assertLeadAccess(Request $request, Lead $lead): void
    {
        $ownsLead = $lead->enterprise_id === null
            && Lead::whereNull('enterprise_id')
                ->forRealtor($request->user()->id)
                ->whereKey($lead->id)
                ->exists();

        abort_unless($ownsLead, 404);
    }

    protected function callMembership(Request $request): ?EnterpriseMembership
    {
        return null;
    }

    protected function canDeleteRecording(Request $request, CallLog $call): bool
    {
        return app(CallAccessService::class)->canDeleteRecordingRealtor($call, $request->user()->id);
    }
}
