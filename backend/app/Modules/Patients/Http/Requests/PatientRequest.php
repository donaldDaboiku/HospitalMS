<?php

namespace App\Modules\Patients\Http\Requests;

use App\Modules\Patients\Models\Patient;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class PatientRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();
        $patient = $this->route('patient');

        if ($patient instanceof Patient) {
            return $user?->can('update', $patient) ?? false;
        }

        return $user?->can('create', Patient::class) ?? false;
    }

    public function rules(): array
    {
        $required = $this->isMethod('post') ? 'required' : 'sometimes';

        return [
            'hospital_id' => ['nullable', 'uuid', 'exists:hospitals,id'],
            'branch_id' => ['nullable', 'uuid', 'exists:branches,id'],
            'first_name' => [$required, 'string', 'max:100'],
            'middle_name' => ['nullable', 'string', 'max:100'],
            'last_name' => [$required, 'string', 'max:100'],
            'date_of_birth' => [$required, 'date', 'before:today'],
            'gender' => [$required, Rule::in(['male', 'female', 'other', 'unknown'])],
            'phone' => ['nullable', 'string', 'max:32'],
            'email' => ['nullable', 'email', 'max:255'],
            'address' => ['nullable', 'string'],
            'state' => ['nullable', 'string', 'max:100'],
            'country' => ['nullable', 'string', 'size:2'],
            'occupation' => ['nullable', 'string', 'max:100'],
            'marital_status' => ['nullable', Rule::in(['single', 'married', 'divorced', 'widowed', 'separated', 'unknown'])],
            'blood_group' => ['nullable', Rule::in(['A+', 'A-', 'B+', 'B-', 'AB+', 'AB-', 'O+', 'O-', 'unknown'])],
            'genotype' => ['nullable', Rule::in(['AA', 'AS', 'AC', 'SS', 'SC', 'CC', 'unknown'])],
            'status' => ['sometimes', Rule::in(['active', 'inactive', 'deceased'])],
            'contacts' => ['sometimes', 'array', 'max:10'],
            'contacts.*.type' => ['required_with:contacts', Rule::in(['emergency', 'next_of_kin', 'other'])],
            'contacts.*.full_name' => ['required_with:contacts', 'string', 'max:255'],
            'contacts.*.relationship' => ['nullable', 'string', 'max:64'],
            'contacts.*.phone' => ['required_with:contacts', 'string', 'max:32'],
            'contacts.*.email' => ['nullable', 'email', 'max:255'],
            'contacts.*.address' => ['nullable', 'string'],
            'contacts.*.is_primary' => ['sometimes', 'boolean'],
            'allergies' => ['sometimes', 'array', 'max:50'],
            'allergies.*.allergen' => ['required_with:allergies', 'string', 'max:255'],
            'allergies.*.reaction' => ['nullable', 'string', 'max:255'],
            'allergies.*.severity' => ['nullable', Rule::in(['mild', 'moderate', 'severe', 'unknown'])],
            'medical_histories' => ['sometimes', 'array', 'max:100'],
            'medical_histories.*.condition_name' => ['required_with:medical_histories', 'string', 'max:255'],
            'medical_histories.*.status' => ['sometimes', Rule::in(['active', 'resolved', 'unknown'])],
            'medical_histories.*.notes' => ['nullable', 'string'],
            'identifications' => ['sometimes', 'array', 'max:10'],
            'identifications.*.type' => ['required_with:identifications', 'string', 'max:64'],
            'identifications.*.number' => ['required_with:identifications', 'string', 'max:128'],
            'identifications.*.issuer' => ['nullable', 'string', 'max:128'],
            'identifications.*.expires_at' => ['nullable', 'date'],
        ];
    }
}
