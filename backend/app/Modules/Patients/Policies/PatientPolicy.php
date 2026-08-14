<?php

namespace App\Modules\Patients\Policies;

use App\Models\User;
use App\Modules\Patients\Models\Patient;

class PatientPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('patient.view');
    }

    public function view(User $user, Patient $patient): bool
    {
        return $user->can('patient.view') && $user->belongsToHospital($patient->hospital_id);
    }

    public function create(User $user): bool
    {
        return $user->can('patient.create');
    }

    public function update(User $user, Patient $patient): bool
    {
        return $user->can('patient.edit') && $user->belongsToHospital($patient->hospital_id);
    }
}
