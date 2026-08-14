<?php

namespace App\Modules\Patients\Http\Requests;

use App\Modules\Patients\Models\Patient;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class FamilyPatientRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', Patient::class) ?? false;
    }

    public function rules(): array
    {
        $person = $this->personRules();

        return [
            'primary' => ['required', 'array'],
            ...$this->prefixRules('primary', $person),
            'primary.phone' => ['required', 'string', 'max:32'],
            'primary.hospital_id' => ['nullable', 'uuid', 'exists:hospitals,id'],
            'primary.branch_id' => ['nullable', 'uuid', 'exists:branches,id'],
            'members' => ['required', 'array', 'min:1', 'max:10'],
            'members.*.relationship_to_primary' => ['required', 'string', 'max:64'],
            ...$this->prefixRules('members.*', $person),
        ];
    }

    /**
     * @return array<string, list<string|\Illuminate\Validation\Rules\In>>
     */
    private function personRules(): array
    {
        return [
            'first_name' => ['required', 'string', 'max:100'],
            'middle_name' => ['nullable', 'string', 'max:100'],
            'last_name' => ['required', 'string', 'max:100'],
            'date_of_birth' => ['required', 'date', 'before:today'],
            'gender' => ['required', Rule::in(['male', 'female', 'other', 'unknown'])],
            'phone' => ['nullable', 'string', 'max:32'],
            'email' => ['nullable', 'email', 'max:255'],
            'address' => ['nullable', 'string'],
            'state' => ['nullable', 'string', 'max:100'],
            'country' => ['nullable', 'string', 'size:2'],
            'occupation' => ['nullable', 'string', 'max:100'],
            'marital_status' => ['nullable', Rule::in(['single', 'married', 'divorced', 'widowed', 'separated', 'unknown'])],
            'blood_group' => ['nullable', Rule::in(['A+', 'A-', 'B+', 'B-', 'AB+', 'AB-', 'O+', 'O-', 'unknown'])],
            'genotype' => ['nullable', Rule::in(['AA', 'AS', 'AC', 'SS', 'SC', 'CC', 'unknown'])],
        ];
    }

    /**
     * @param  array<string, list<mixed>>  $rules
     * @return array<string, list<mixed>>
     */
    private function prefixRules(string $prefix, array $rules): array
    {
        $prefixed = [];
        foreach ($rules as $field => $rule) {
            $prefixed[$prefix.'.'.$field] = $rule;
        }

        return $prefixed;
    }
}
