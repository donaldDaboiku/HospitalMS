<?php

namespace App\Modules\Patients\Http\Resources;

use App\Modules\Patients\Models\Patient;
use App\Modules\Patients\Models\PatientContact;
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
            'photo_url' => $this->photo_path ? '/api/v1/patients/'.$this->id.'/photo' : null,
            'status' => $this->status,
            'registered_at' => $this->registered_at,
            'contacts' => $this->whenLoaded('contacts', fn () => $this->contacts->map(fn (PatientContact $contact) => [
                'id' => $contact->id,
                'type' => $contact->type,
                'related_patient_id' => $contact->related_patient_id,
                'full_name' => $contact->full_name,
                'relationship' => $contact->relationship,
                'phone' => $contact->phone,
                'email' => $contact->email,
                'address' => $contact->address,
                'is_primary' => $contact->is_primary,
                'related_patient' => $contact->relationLoaded('relatedPatient') && $contact->relatedPatient
                    ? [
                        'id' => $contact->relatedPatient->id,
                        'mrn' => $contact->relatedPatient->mrn,
                        'name' => $contact->relatedPatient->name,
                        'phone' => $contact->relatedPatient->phone,
                        'photo_url' => $contact->relatedPatient->photo_path
                            ? '/api/v1/patients/'.$contact->relatedPatient->id.'/photo'
                            : null,
                    ]
                    : null,
            ])->values()->all()),
            'allergies' => $this->whenLoaded('allergies'),
            'medical_histories' => $this->whenLoaded('medicalHistories'),
            'identifications' => $this->whenLoaded('identifications'),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
