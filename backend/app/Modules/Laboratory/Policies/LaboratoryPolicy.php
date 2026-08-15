<?php

namespace App\Modules\Laboratory\Policies;

use App\Models\User;
use App\Modules\Laboratory\Models\LabOrder;
use App\Modules\Laboratory\Models\LabResult;
use App\Modules\Laboratory\Models\LabTest;

class LaboratoryPolicy
{
    public function viewCatalog(User $user): bool
    {
        return $user->can('lab.order') || $user->can('lab.collect') || $user->can('lab.result') || $user->can('lab.verify');
    }

    public function manageCatalog(User $user): bool
    {
        return $user->can('settings.manage') || $user->can('department.manage');
    }

    public function viewAnyOrders(User $user): bool
    {
        return $this->viewCatalog($user);
    }

    public function viewOrder(User $user, LabOrder $order): bool
    {
        return $this->viewAnyOrders($user) && $user->belongsToHospital($order->hospital_id);
    }

    public function createOrder(User $user): bool
    {
        return $user->can('lab.order');
    }

    public function collect(User $user, LabOrder $order): bool
    {
        return $user->can('lab.collect') && $user->belongsToHospital($order->hospital_id);
    }

    public function enterResult(User $user, LabOrder $order): bool
    {
        return $user->can('lab.result') && $user->belongsToHospital($order->hospital_id);
    }

    public function verifyResult(User $user, LabResult $result): bool
    {
        return $user->can('lab.verify') && $user->belongsToHospital($result->hospital_id);
    }

    public function manageTest(User $user, ?LabTest $test = null): bool
    {
        if (! $this->manageCatalog($user)) {
            return false;
        }

        return $test === null || $user->belongsToHospital($test->hospital_id);
    }
}
