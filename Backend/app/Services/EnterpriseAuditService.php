<?php

namespace App\Services;

use App\Models\Enterprise;
use App\Models\EnterpriseAuditLog;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class EnterpriseAuditService
{
    public function log(
        Enterprise $enterprise,
        ?User $actor,
        string $action,
        Model|string|null $subject = null,
        ?string $description = null,
        array $meta = [],
    ): EnterpriseAuditLog {
        $subjectType = $subject instanceof Model ? $subject::class : $subject;

        return EnterpriseAuditLog::create([
            'enterprise_id' => $enterprise->id,
            'user_id' => $actor?->id,
            'action' => $action,
            'subject_type' => $subjectType,
            'subject_id' => $subject instanceof Model ? $subject->getKey() : null,
            'description' => $description,
            'meta' => $meta ?: null,
            'ip_address' => request()?->ip(),
        ]);
    }
}
