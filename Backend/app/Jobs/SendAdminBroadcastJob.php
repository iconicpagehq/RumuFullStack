<?php

namespace App\Jobs;

use App\Models\AdminBroadcast;
use App\Models\EnterpriseMembership;
use App\Models\Notification;
use App\Models\User;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;

class SendAdminBroadcastJob implements ShouldQueue
{
    use InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public function __construct(public AdminBroadcast $broadcast)
    {
        $this->onQueue('default');
    }

    public function handle(): void
    {
        $recipientIds = $this->recipientQuery()->distinct()->pluck('users.id');
        $now = now();
        $inserted = 0;

        foreach ($recipientIds->chunk(200) as $chunk) {
            $rows = $chunk->map(fn (int $userId) => [
                'user_id' => $userId,
                'type' => 'admin_broadcast',
                'title' => $this->broadcast->title,
                'message' => $this->broadcast->message,
                'related_id' => $this->broadcast->id,
                'related_type' => AdminBroadcast::class,
                'is_read' => false,
                'created_at' => $now,
                'updated_at' => $now,
            ])->values()->all();

            DB::table('notifications')->insert($rows);
            $inserted += count($rows);
        }

        $this->broadcast->update([
            'recipients_count' => $inserted,
            'status' => AdminBroadcast::STATUS_SENT,
            'sent_at' => $now,
        ]);
    }

    private function recipientQuery()
    {
        $query = User::query()->select('users.id');

        return match ($this->broadcast->audience) {
            AdminBroadcast::AUDIENCE_ALL_REALTORS => $query
                ->where('users.role', 'realtor')
                ->whereNull('users.subscription_suspended_at'),
            AdminBroadcast::AUDIENCE_ENTERPRISE_ADMINS => $query
                ->where('users.role', 'enterprise')
                ->whereHas('enterpriseMembership', fn ($membership) => $membership
                    ->where('role', EnterpriseMembership::ROLE_ADMIN)
                    ->where('status', EnterpriseMembership::STATUS_ACTIVE)
                    ->whereHas('enterprise', fn ($enterprise) => $enterprise->where('status', 'active'))),
            AdminBroadcast::AUDIENCE_ENTERPRISE_MEMBERS => $query
                ->where('users.role', 'enterprise')
                ->whereHas('enterpriseMembership', fn ($membership) => $membership
                    ->where('status', EnterpriseMembership::STATUS_ACTIVE)
                    ->whereHas('enterprise', fn ($enterprise) => $enterprise->where('status', 'active'))),
            AdminBroadcast::AUDIENCE_ENTERPRISE => $query
                ->where('users.role', 'enterprise')
                ->whereHas('enterpriseMembership', fn ($membership) => $membership
                    ->where('enterprise_id', $this->broadcast->enterprise_id)
                    ->where('status', EnterpriseMembership::STATUS_ACTIVE)
                    ->whereHas('enterprise', fn ($enterprise) => $enterprise->where('status', 'active'))),
            default => $query->whereRaw('1 = 0'),
        };
    }
}
