<?php

namespace App\Modules\Clinical\Policies;

use App\Models\User;
use App\Modules\Clinical\Models\Encounter;

class EncounterPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('clinical.view') || $user->can('triage.view') || $user->can('appointment.view');
    }

    public function view(User $user, Encounter $encounter): bool
    {
        return ($user->can('clinical.view') || $user->can('triage.view') || $user->can('appointment.view'))
            && $user->belongsToHospital($encounter->hospital_id);
    }

    public function create(User $user): bool
    {
        return $user->can('clinical.create') || $user->can('appointment.create');
    }

    public function update(User $user, Encounter $encounter): bool
    {
        return $user->can('clinical.edit') && $user->belongsToHospital($encounter->hospital_id);
    }

    public function triage(User $user, Encounter $encounter): bool
    {
        return $user->can('triage.create') && $user->belongsToHospital($encounter->hospital_id);
    }

    public function note(User $user, Encounter $encounter): bool
    {
        return $user->can('clinical.create') && $user->belongsToHospital($encounter->hospital_id);
    }

    public function diagnose(User $user, Encounter $encounter): bool
    {
        return $user->can('clinical.create') && $user->belongsToHospital($encounter->hospital_id);
    }
}
