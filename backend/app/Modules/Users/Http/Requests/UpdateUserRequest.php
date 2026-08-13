<?php

namespace App\Modules\Users\Http\Requests;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class UpdateUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->route('user');

        return $user instanceof User && ($this->user()?->can('update', $user) ?? false);
    }

    public function rules(): array
    {
        /** @var User $user */
        $user = $this->route('user');

        return [
            'hospital_id' => ['nullable', 'uuid', 'exists:hospitals,id'],
            'branch_id' => ['nullable', 'uuid', 'exists:branches,id'],
            'first_name' => ['sometimes', 'string', 'max:100'],
            'middle_name' => ['nullable', 'string', 'max:100'],
            'last_name' => ['sometimes', 'string', 'max:100'],
            'email' => ['sometimes', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user->id)],
            'phone' => ['nullable', 'string', 'max:32'],
            'password' => ['nullable', 'confirmed', Password::min(10)->mixedCase()->numbers()],
            'is_active' => ['sometimes', 'boolean'],
            'roles' => ['sometimes', 'array', 'min:1'],
            'roles.*' => ['string', Rule::exists('roles', 'name')],
        ];
    }
}
