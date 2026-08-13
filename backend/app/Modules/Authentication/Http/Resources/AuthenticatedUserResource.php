<?php

namespace App\Modules\Authentication\Http\Resources;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin User */
class AuthenticatedUserResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'hospital_id' => $this->hospital_id,
            'branch_id' => $this->branch_id,
            'first_name' => $this->first_name,
            'middle_name' => $this->middle_name,
            'last_name' => $this->last_name,
            'name' => $this->name,
            'email' => $this->email,
            'phone' => $this->phone,
            'is_active' => $this->is_active,
            'roles' => $this->getRoleNames()->values(),
            'permissions' => $this->getAllPermissions()->pluck('name')->values(),
            'hospital' => $this->whenLoaded('hospital', fn () => [
                'id' => $this->hospital?->id,
                'name' => $this->hospital?->name,
                'code' => $this->hospital?->code,
            ]),
        ];
    }
}
