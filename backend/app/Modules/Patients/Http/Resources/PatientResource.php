<?php

namespace App\Modules\Patients\Http\Resources;

use App\Modules\Patients\Models\Patient;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Patient */
class PatientResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'hospital_id' => $this->hospital_id,
            'branch_id' => $this->branch_id,
            'mrn' => $this->mrn,
            'first_name' => $this->first_name,
            'middle_name' => $this->middle_name,
            'last_name' => $this->last_name,
            'name' => $this->name,
            'date_of_birth' => $this->date_of_birth?->toDateString(),
            'gender' => $this->gender,
            'phone' => $this->phone,
            'email' => $this->email,
            'address' => $this->address,
            'state' => $this->state,
            'country' => $this->country,
            'occupation' => $this->occupation,
            'marital_status' => $this->marital_status,
            'blood_group' => $this->blood_group,
            'genotype' => $this->genotype,
            'status' => $this->status,
            'registered_at' => $this->registered_at,
            'contacts' => $this->whenLoaded('contacts'),
            'allergies' => $this->whenLoaded('allergies'),
            'medical_histories' => $this->whenLoaded('medicalHistories'),
            'identifications' => $this->whenLoaded('identifications'),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
