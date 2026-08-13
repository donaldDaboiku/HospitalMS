<?php

namespace App\Modules\Users\Policies;

use App\Models\User;

class UserPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('user.view');
    }

    public function view(User $actor, User $user): bool
    {
        return $actor->can('user.view') && $actor->belongsToHospital($user->hospital_id);
    }

    public function create(User $user): bool
    {
        return $user->can('user.create');
    }

    public function update(User $actor, User $user): bool
    {
        return $actor->can('user.edit') && $actor->belongsToHospital($user->hospital_id);
    }

    public function delete(User $actor, User $user): bool
    {
        return $actor->can('user.delete') && $actor->belongsToHospital($user->hospital_id);
    }
}
