<?php

namespace App\Modules\Clinical\Http\Resources;

use App\Modules\Clinical\Models\Encounter;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Encounter */
class EncounterResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'hospital_id' => $this->hospital_id,
            'patient_id' => $this->patient_id,
            'appointment_id' => $this->appointment_id,
            'doctor_user_id' => $this->doctor_user_id,
            'department_id' => $this->department_id,
            'type' => $this->type,
            'status' => $this->status,
            'started_at' => $this->started_at,
            'closed_at' => $this->closed_at,
            'patient' => $this->whenLoaded('patient', fn () => $this->patient ? [
                'id' => $this->patient->id,
                'mrn' => $this->patient->mrn,
                'name' => $this->patient->name,
            ] : null),
            'doctor' => $this->whenLoaded('doctor', fn () => $this->doctor ? [
                'id' => $this->doctor->id,
                'name' => $this->doctor->name,
            ] : null),
            'department' => $this->whenLoaded('department'),
            'appointment' => $this->whenLoaded('appointment'),
            'triage' => $this->whenLoaded('triage'),
            'clinical_notes' => $this->whenLoaded('clinicalNotes'),
            'diagnoses' => $this->whenLoaded('diagnoses'),
        ];
    }
}
