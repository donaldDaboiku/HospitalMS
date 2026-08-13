<?php

namespace App\Modules\Audit\Services;

use App\Models\User;
use App\Modules\Audit\Models\AuditLog;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class AuditLogger
{
    public function record(
        string $action,
        string $module,
        ?Model $auditable = null,
        ?array $oldValues = null,
        ?array $newValues = null,
        ?User $user = null,
        ?Request $request = null,
    ): AuditLog {
        $request ??= request();
        $user ??= $request?->user();

        return AuditLog::query()->create([
            'hospital_id' => $user?->hospital_id,
            'user_id' => $user?->id,
            'action' => $action,
            'module' => $module,
            'auditable_type' => $auditable?->getMorphClass(),
            'auditable_id' => $auditable?->getKey(),
            'old_values' => $this->redact($oldValues),
            'new_values' => $this->redact($newValues),
            'ip_address' => $request?->ip(),
            'user_agent' => $request?->userAgent(),
            'url' => $request ? Str::limit($request->fullUrl(), 2048, '') : null,
            'method' => $request?->method(),
            'request_id' => $request?->headers->get('X-Request-Id') ?: (string) Str::uuid(),
            'created_at' => now(),
        ]);
    }

    private function redact(?array $values): ?array
    {
        if ($values === null) {
            return null;
        }

        foreach (['password', 'password_confirmation', 'remember_token', 'token'] as $secret) {
            if (array_key_exists($secret, $values)) {
                $values[$secret] = '[redacted]';
            }
        }

        return $values;
    }
}
