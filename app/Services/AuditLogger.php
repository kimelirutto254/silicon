<?php

namespace App\Services;

use App\Models\AuditLog;
use App\Models\User;

class AuditLogger
{
    public function log(?User $actor, string $action, object|string $target, array $before = [], array $after = []): void
    {
        AuditLog::create([
            'actor_id' => $actor?->id,
            'action' => $action,
            'target_type' => is_object($target) ? $target::class : $target,
            'target_id' => is_object($target) && isset($target->id) ? $target->id : null,
            'before' => $before,
            'after' => $after,
        ]);
    }
}
