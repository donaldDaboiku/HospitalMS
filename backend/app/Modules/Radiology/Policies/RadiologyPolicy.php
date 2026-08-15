<?php

namespace App\Modules\Radiology\Policies;

use App\Models\User;
use App\Modules\Radiology\Models\RadiologyOrder;

class RadiologyPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('radiology.order') || $user->can('radiology.report') || $user->can('radiology.approve');
    }

    public function view(User $user, RadiologyOrder $order): bool
    {
        return $this->viewAny($user) && $user->belongsToHospital($order->hospital_id);
    }

    public function create(User $user): bool
    {
        return $user->can('radiology.order');
    }

    public function report(User $user, RadiologyOrder $order): bool
    {
        return $user->can('radiology.report') && $user->belongsToHospital($order->hospital_id);
    }
}
