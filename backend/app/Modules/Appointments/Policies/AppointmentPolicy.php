<?php

namespace App\Modules\Appointments\Policies;

use App\Models\User;
use App\Modules\Appointments\Models\Appointment;

class AppointmentPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('appointment.view');
    }

    public function view(User $user, Appointment $appointment): bool
    {
        return $user->can('appointment.view') && $user->belongsToHospital($appointment->hospital_id);
    }

    public function create(User $user): bool
    {
        return $user->can('appointment.create');
    }

    public function update(User $user, Appointment $appointment): bool
    {
        return $user->can('appointment.edit') && $user->belongsToHospital($appointment->hospital_id);
    }

    public function cancel(User $user, Appointment $appointment): bool
    {
        return $user->can('appointment.cancel') && $user->belongsToHospital($appointment->hospital_id);
    }
}
