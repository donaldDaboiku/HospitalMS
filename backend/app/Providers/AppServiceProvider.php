<?php

namespace App\Providers;

use App\Models\User;
use App\Modules\Appointments\Models\Appointment;
use App\Modules\Appointments\Policies\AppointmentPolicy;
use App\Modules\Clinical\Models\Encounter;
use App\Modules\Clinical\Policies\EncounterPolicy;
use App\Modules\Laboratory\Models\LabOrder;
use App\Modules\Laboratory\Models\LabOrderItem;
use App\Modules\Laboratory\Models\LabResult;
use App\Modules\Laboratory\Models\LabTest;
use App\Modules\Laboratory\Policies\LaboratoryPolicy;
use App\Modules\Patients\Models\Patient;
use App\Modules\Patients\Policies\PatientPolicy;
use App\Modules\Pharmacy\Models\PrescriptionItem;
use App\Modules\Pharmacy\Models\Product;
use App\Modules\Radiology\Models\RadiologyOrder;
use App\Modules\Radiology\Policies\RadiologyPolicy;
use App\Modules\Roles\Models\Permission;
use App\Modules\Roles\Models\Role;
use App\Modules\Users\Policies\UserPolicy;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        Gate::policy(User::class, UserPolicy::class);
        Gate::policy(Patient::class, PatientPolicy::class);
        Gate::policy(Appointment::class, AppointmentPolicy::class);
        Gate::policy(Encounter::class, EncounterPolicy::class);
        Gate::policy(LabTest::class, LaboratoryPolicy::class);
        Gate::policy(LabOrder::class, LaboratoryPolicy::class);
        Gate::policy(LabResult::class, LaboratoryPolicy::class);
        Gate::policy(RadiologyOrder::class, RadiologyPolicy::class);

        $this->bindHospitalScoped('patient', Patient::class);
        $this->bindHospitalScoped('appointment', Appointment::class);
        $this->bindHospitalScoped('encounter', Encounter::class);
        $this->bindHospitalScoped('labOrder', LabOrder::class);
        $this->bindHospitalScoped('labResult', LabResult::class);
        $this->bindHospitalScoped('radiologyOrder', RadiologyOrder::class);
        $this->bindHospitalScoped('product', Product::class);

        Route::bind('prescriptionItem', function (string $value) {
            $query = PrescriptionItem::query()->whereKey($value)->whereHas('prescription', function ($prescriptions) {
                $user = request()->user();
                if ($user instanceof User && ! $user->isSuperAdmin()) {
                    $prescriptions->where('hospital_id', $user->hospital_id);
                }
            });

            return $query->firstOrFail();
        });

        Route::bind('labOrderItem', function (string $value) {
            $query = LabOrderItem::query()->whereKey($value)->whereHas('order', function ($orders) {
                $user = request()->user();
                if ($user instanceof User && ! $user->isSuperAdmin()) {
                    $orders->where('hospital_id', $user->hospital_id);
                }
            });

            return $query->firstOrFail();
        });

        Gate::before(function (User $user, string $ability) {
            return $user->isSuperAdmin() ? true : null;
        });

        RateLimiter::for('login', function (Request $request) {
            return Limit::perMinute(5)->by($request->ip());
        });

        RateLimiter::for('api', function (Request $request) {
            return Limit::perMinute(60)->by($request->user()?->id ?: $request->ip());
        });

        config([
            'permission.models.role' => Role::class,
            'permission.models.permission' => Permission::class,
        ]);
    }

    private function bindHospitalScoped(string $parameter, string $modelClass): void
    {
        Route::bind($parameter, function (string $value) use ($modelClass) {
            $query = $modelClass::query()->whereKey($value);
            $user = request()->user();

            if ($user instanceof User && ! $user->isSuperAdmin()) {
                $query->where('hospital_id', $user->hospital_id);
            }

            return $query->firstOrFail();
        });
    }
}
