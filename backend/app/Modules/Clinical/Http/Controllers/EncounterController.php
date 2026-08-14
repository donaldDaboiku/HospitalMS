<?php

namespace App\Modules\Clinical\Http\Controllers;

use App\Core\Http\ApiResponse;
use App\Http\Controllers\Controller;
use App\Modules\Clinical\Http\Resources\EncounterResource;
use App\Modules\Clinical\Models\Encounter;
use App\Modules\Clinical\Models\TriageAssessment;
use App\Modules\Clinical\Services\EncounterService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class EncounterController extends Controller
{
    public function __construct(private readonly EncounterService $encounters) {}

    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Encounter::class);

        return ApiResponse::paginated(
            $this->encounters->paginate($request->user(), $request->query()),
            map: fn (Encounter $encounter) => (new EncounterResource($encounter))->resolve(),
        );
    }

    public function store(Request $request): JsonResponse
    {
        $this->authorize('create', Encounter::class);
        $validated = $request->validate([
            'hospital_id' => ['nullable', 'uuid', 'exists:hospitals,id'],
            'patient_id' => ['required', 'uuid', 'exists:patients,id'],
            'doctor_user_id' => ['nullable', 'uuid', 'exists:users,id'],
            'department_id' => ['nullable', 'uuid', 'exists:departments,id'],
            'type' => ['sometimes', Rule::in(Encounter::TYPES)],
        ]);

        $encounter = $this->encounters->createWalkIn($request->user(), $validated);

        return ApiResponse::success(new EncounterResource($encounter), 'Encounter opened.', 201);
    }

    public function show(Encounter $encounter): JsonResponse
    {
        $this->authorize('view', $encounter);

        return ApiResponse::success(new EncounterResource($this->encounters->show($encounter)));
    }

    public function close(Request $request, Encounter $encounter): JsonResponse
    {
        $this->authorize('update', $encounter);

        return ApiResponse::success(new EncounterResource($this->encounters->close($request->user(), $encounter)), 'Encounter closed.');
    }

    public function saveTriage(Request $request, Encounter $encounter): JsonResponse
    {
        $this->authorize('triage', $encounter);
        $validated = $request->validate([
            'temperature_c' => ['nullable', 'numeric', 'between:30,45'],
            'systolic_bp' => ['nullable', 'integer', 'between:40,300'],
            'diastolic_bp' => ['nullable', 'integer', 'between:20,200'],
            'pulse' => ['nullable', 'integer', 'between:20,250'],
            'respiratory_rate' => ['nullable', 'integer', 'between:5,80'],
            'oxygen_saturation' => ['nullable', 'integer', 'between:50,100'],
            'weight_kg' => ['nullable', 'numeric', 'between:0.5,500'],
            'height_cm' => ['nullable', 'numeric', 'between:20,300'],
            'pain_score' => ['nullable', 'integer', 'between:0,10'],
            'consciousness_level' => ['nullable', 'string', 'max:64'],
            'allergies_noted' => ['nullable', 'string'],
            'chief_complaint' => ['nullable', 'string'],
            'priority' => ['sometimes', Rule::in(TriageAssessment::PRIORITIES)],
        ]);

        $triage = $this->encounters->saveTriage($request->user(), $encounter, $validated);

        return ApiResponse::success($triage, 'Triage saved.');
    }

    public function addNote(Request $request, Encounter $encounter): JsonResponse
    {
        $this->authorize('note', $encounter);
        $validated = $request->validate([
            'chief_complaint' => ['nullable', 'string'],
            'history_of_presenting_illness' => ['nullable', 'string'],
            'past_medical_history' => ['nullable', 'string'],
            'family_history' => ['nullable', 'string'],
            'social_history' => ['nullable', 'string'],
            'examination' => ['nullable', 'string'],
            'assessment' => ['nullable', 'string'],
            'treatment_plan' => ['nullable', 'string'],
            'notes' => ['nullable', 'string'],
        ]);

        return ApiResponse::success($this->encounters->addClinicalNote($request->user(), $encounter, $validated), 'Clinical note saved.', 201);
    }

    public function addDiagnosis(Request $request, Encounter $encounter): JsonResponse
    {
        $this->authorize('diagnose', $encounter);
        $validated = $request->validate([
            'icd10_code' => ['nullable', 'string', 'max:16'],
            'description' => ['required', 'string', 'max:255'],
            'type' => ['sometimes', Rule::in(['primary', 'secondary', 'differential'])],
            'notes' => ['nullable', 'string'],
        ]);

        return ApiResponse::success($this->encounters->addDiagnosis($request->user(), $encounter, $validated), 'Diagnosis recorded.', 201);
    }
}
