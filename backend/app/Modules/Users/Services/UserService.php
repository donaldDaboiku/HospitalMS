<?php

namespace App\Modules\Users\Services;

use App\Core\Support\Roles;
use App\Models\User;
use App\Modules\Audit\Services\AuditLogger;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Arr;
use Illuminate\Validation\ValidationException;

class UserService
{
    public function __construct(private readonly AuditLogger $auditLogger) {}

    public function paginate(User $actor, array $filters): LengthAwarePaginator
    {
        $query = User::query()->with(['hospital', 'roles']);

        if (! $actor->isSuperAdmin()) {
            $query->where('hospital_id', $actor->hospital_id);
        } elseif (! empty($filters['hospital_id'])) {
            $query->where('hospital_id', $filters['hospital_id']);
        }

        if (! empty($filters['search'])) {
            $search = '%'.str_replace(['%', '_'], ['\\%', '\\_'], $filters['search']).'%';
            $operator = $query->getConnection()->getDriverName() === 'pgsql' ? 'ilike' : 'like';
            $query->where(function ($builder) use ($search, $operator) {
                $builder->where('first_name', $operator, $search)
                    ->orWhere('last_name', $operator, $search)
                    ->orWhere('email', $operator, $search)
                    ->orWhere('phone', $operator, $search);
            });
        }

        if (isset($filters['is_active'])) {
            $query->where('is_active', filter_var($filters['is_active'], FILTER_VALIDATE_BOOLEAN));
        }

        return $query->orderBy('last_name')->paginate(
            min((int) ($filters['per_page'] ?? config('hms.pagination.per_page')), config('hms.pagination.max_per_page'))
        );
    }

    public function create(User $actor, array $data): User
    {
        $roles = $data['roles'];
        $this->assertAssignableRoles($actor, $roles);

        $payload = Arr::except($data, ['roles', 'password_confirmation']);

        if (! $actor->isSuperAdmin()) {
            $payload['hospital_id'] = $actor->hospital_id;
        }

        $user = User::query()->create($payload);
        $user->syncRoles($roles);

        $this->auditLogger->record(
            action: 'user.created',
            module: 'users',
            auditable: $user,
            newValues: Arr::except($user->toArray(), ['password']),
            user: $actor,
        );

        return $user->load(['hospital', 'roles']);
    }

    public function update(User $actor, User $user, array $data): User
    {
        $old = Arr::except($user->toArray(), ['password']);

        if (isset($data['roles'])) {
            $this->assertAssignableRoles($actor, $data['roles']);
            $user->syncRoles($data['roles']);
        }

        $payload = Arr::except($data, ['roles', 'password_confirmation']);

        if (empty($payload['password'])) {
            unset($payload['password']);
        }

        if (! $actor->isSuperAdmin()) {
            unset($payload['hospital_id']);
        }

        $user->fill($payload)->save();

        $this->auditLogger->record(
            action: 'user.updated',
            module: 'users',
            auditable: $user,
            oldValues: $old,
            newValues: Arr::except($user->fresh()->toArray(), ['password']),
            user: $actor,
        );

        return $user->fresh(['hospital', 'roles']);
    }

    public function delete(User $actor, User $user): void
    {
        if ($actor->is($user)) {
            throw ValidationException::withMessages([
                'user' => ['You cannot delete your own account.'],
            ]);
        }

        $old = Arr::except($user->toArray(), ['password']);
        $user->tokens()->delete();
        $user->delete();

        $this->auditLogger->record(
            action: 'user.deleted',
            module: 'users',
            auditable: $user,
            oldValues: $old,
            user: $actor,
        );
    }

    /**
     * @param  list<string>  $roles
     */
    private function assertAssignableRoles(User $actor, array $roles): void
    {
        if (in_array(Roles::SUPER_ADMIN, $roles, true) && ! $actor->isSuperAdmin()) {
            throw ValidationException::withMessages([
                'roles' => ['Only a super administrator can assign the SUPER_ADMIN role.'],
            ]);
        }
    }
}
