<?php

namespace App\Modules\Laboratory\Http\Resources;

use App\Modules\Laboratory\Models\LabOrder;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin LabOrder */
class LabOrderResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'hospital_id' => $this->hospital_id,
            'patient_id' => $this->patient_id,
            'encounter_id' => $this->encounter_id,
            'status' => $this->status,
            'priority' => $this->priority,
            'clinical_notes' => $this->clinical_notes,
            'ordered_at' => $this->ordered_at,
            'patient' => $this->whenLoaded('patient', fn () => $this->patient ? [
                'id' => $this->patient->id,
                'mrn' => $this->patient->mrn,
                'name' => $this->patient->name,
                'phone' => $this->patient->phone ?? null,
            ] : null),
            'ordered_by' => $this->whenLoaded('orderedBy', fn () => $this->orderedBy ? [
                'id' => $this->orderedBy->id,
                'name' => $this->orderedBy->name,
            ] : null),
            'items' => $this->whenLoaded('items', fn () => $this->items->map(fn ($item) => [
                'id' => $item->id,
                'status' => $item->status,
                'lab_test_id' => $item->lab_test_id,
                'test' => $item->relationLoaded('test') ? $item->test : null,
                'result' => $item->relationLoaded('result') ? $item->result : null,
            ])->values()),
            'specimen' => $this->whenLoaded('specimen'),
            'created_at' => $this->created_at,
        ];
    }
}
