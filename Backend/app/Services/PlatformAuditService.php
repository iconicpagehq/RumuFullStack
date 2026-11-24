<?php

namespace App\Services;

use App\Models\PlatformAuditLog;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class PlatformAuditService
{
    public function log(
        ?User $admin,
        string $action,
        Model|string|null $subject = null,
        ?string $description = null,
        array $meta = [],
    ): PlatformAuditLog {
        return PlatformAuditLog::create([
            'admin_id' => $admin?->id,
            'action' => $action,
            'subject_type' => $subject instanceof Model ? $subject::class : $subject,
            'subject_id' => $subject instanceof Model ? $subject->getKey() : null,
            'description' => $description,
            'meta' => $meta ?: null,
            'ip_address' => request()?->ip(),
            'created_at' => now(),
        ]);
    }
}
