<?php

namespace App\Modules\Appointments\Http\Resources;

use App\Modules\Appointments\Models\Appointment;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Appointment */
class AppointmentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'hospital_id' => $this->hospital_id,
            'patient_id' => $this->patient_id,
            'doctor_user_id' => $this->doctor_user_id,
            'department_id' => $this->department_id,
            'scheduled_at' => $this->scheduled_at,
            'status' => $this->status,
            'type' => $this->type,
            'reason' => $this->reason,
            'notes' => $this->notes,
            'checked_in_at' => $this->checked_in_at,
            'cancelled_at' => $this->cancelled_at,
            'cancellation_reason' => $this->cancellation_reason,
            'patient' => $this->whenLoaded('patient', fn () => $this->patient ? [
                'id' => $this->patient->id,
                'mrn' => $this->patient->mrn,
                'name' => $this->patient->name,
                'phone' => $this->patient->phone,
            ] : null),
            'doctor' => $this->whenLoaded('doctor', fn () => $this->doctor ? [
                'id' => $this->doctor->id,
                'name' => $this->doctor->name,
                'email' => $this->doctor->email,
            ] : null),
            'department' => $this->whenLoaded('department'),
            'created_at' => $this->created_at,
        ];
    }
}
