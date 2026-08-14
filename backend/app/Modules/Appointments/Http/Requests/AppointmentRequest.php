<?php

namespace App\Modules\Appointments\Http\Requests;

use App\Modules\Appointments\Models\Appointment;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AppointmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        $appointment = $this->route('appointment');

        if ($appointment instanceof Appointment) {
            return $this->user()?->can('update', $appointment) ?? false;
        }

        return $this->user()?->can('create', Appointment::class) ?? false;
    }

    public function rules(): array
    {
        $required = $this->isMethod('post') ? 'required' : 'sometimes';

        return [
            'hospital_id' => ['nullable', 'uuid', 'exists:hospitals,id'],
            'patient_id' => [$required, 'uuid', 'exists:patients,id'],
            'doctor_user_id' => [$required, 'uuid', 'exists:users,id'],
            'department_id' => ['nullable', 'uuid', 'exists:departments,id'],
            'scheduled_at' => [$required, 'date'],
            'status' => ['sometimes', Rule::in(Appointment::STATUSES)],
            'type' => ['sometimes', Rule::in(['scheduled', 'walk_in', 'follow_up'])],
            'reason' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string'],
            'cancellation_reason' => ['nullable', 'string', 'max:255'],
        ];
    }
}
