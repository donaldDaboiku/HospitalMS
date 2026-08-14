<?php

namespace App\Modules\Patients\Http\Controllers;

use App\Core\Http\ApiResponse;
use App\Http\Controllers\Controller;
use App\Modules\Audit\Services\AuditLogger;
use App\Modules\Patients\Http\Requests\FamilyPatientRequest;
use App\Modules\Patients\Http\Requests\PatientRequest;
use App\Modules\Patients\Http\Resources\PatientResource;
use App\Modules\Patients\Models\Patient;
use App\Modules\Patients\Services\PatientService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class PatientController extends Controller
{
    public function __construct(
        private readonly PatientService $patients,
        private readonly AuditLogger $auditLogger,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Patient::class);

        return ApiResponse::paginated(
            $this->patients->paginate($request->user(), $request->query()),
            map: fn (Patient $patient) => (new PatientResource($patient))->resolve(),
        );
    }

    public function duplicates(PatientRequest $request): JsonResponse
    {
        $this->authorize('create', Patient::class);

        return ApiResponse::success(
            PatientResource::collection($this->patients->findPotentialDuplicates($request->user(), $request->validated()))->resolve(),
            'Potential duplicate matches.'
        );
    }

    public function store(PatientRequest $request): JsonResponse
    {
        $this->authorize('create', Patient::class);
        $patient = $this->patients->create($request->user(), $request->validated());

        return ApiResponse::success(new PatientResource($patient), 'Patient registered.', 201);
    }

    public function storeFamily(FamilyPatientRequest $request): JsonResponse
    {
        $validated = $request->validated();
        $family = $this->patients->createFamily(
            $request->user(),
            $validated['primary'],
            $validated['members'],
        );

        return ApiResponse::success([
            'primary' => (new PatientResource($family['primary']))->resolve(),
            'members' => array_map(
                fn (Patient $member) => (new PatientResource($member))->resolve(),
                $family['members'],
            ),
        ], 'Family registered.', 201);
    }

    public function show(Request $request, Patient $patient): JsonResponse
    {
        $this->authorize('view', $patient);
        $patient->load(['contacts.relatedPatient', 'allergies', 'medicalHistories', 'identifications']);

        $this->auditLogger->record(
            action: 'patient.viewed',
            module: 'patients',
            auditable: $patient,
            user: $request->user(),
        );

        return ApiResponse::success(new PatientResource($patient));
    }

    public function update(PatientRequest $request, Patient $patient): JsonResponse
    {
        $this->authorize('update', $patient);
        $patient = $this->patients->update($request->user(), $patient, $request->validated());

        return ApiResponse::success(new PatientResource($patient), 'Patient updated.');
    }

    public function uploadPhoto(Request $request, Patient $patient): JsonResponse
    {
        $this->authorize('update', $patient);
        $validated = $request->validate([
            'photo' => ['required', 'image', 'mimes:jpeg,jpg,png,webp', 'max:2048'],
        ]);

        $patient = $this->patients->storePhoto($request->user(), $patient, $validated['photo']);

        return ApiResponse::success(new PatientResource($patient), 'Patient photo saved.');
    }

    public function photo(Patient $patient): StreamedResponse
    {
        $this->authorize('view', $patient);
        abort_if($patient->photo_path === null || ! Storage::disk('local')->exists($patient->photo_path), 404);

        return Storage::disk('local')->response($patient->photo_path);
    }
}
