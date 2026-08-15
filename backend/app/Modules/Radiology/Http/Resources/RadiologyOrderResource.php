<?php

namespace App\Modules\Radiology\Http\Resources;

use App\Modules\Radiology\Models\RadiologyOrder;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin RadiologyOrder */
class RadiologyOrderResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'hospital_id' => $this->hospital_id,
            'patient_id' => $this->patient_id,
            'encounter_id' => $this->encounter_id,
            'modality' => $this->modality,
            'study_name' => $this->study_name,
            'status' => $this->status,
            'priority' => $this->priority,
            'clinical_indication' => $this->clinical_indication,
            'ordered_at' => $this->ordered_at,
            'patient' => $this->whenLoaded('patient', fn () => $this->patient ? [
                'id' => $this->patient->id,
                'mrn' => $this->patient->mrn,
                'name' => $this->patient->name,
            ] : null),
            'ordered_by' => $this->whenLoaded('orderedBy', fn () => $this->orderedBy ? [
                'id' => $this->orderedBy->id,
                'name' => $this->orderedBy->name,
            ] : null),
            'report' => $this->whenLoaded('report'),
            'created_at' => $this->created_at,
        ];
    }
}
