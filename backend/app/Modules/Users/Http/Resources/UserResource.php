<?php

namespace App\Modules\Users\Http\Resources;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin User */
class UserResource extends JsonResource
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
            'last_login_at' => $this->last_login_at,
            'roles' => $this->whenLoaded('roles', fn () => $this->roles->pluck('name')),
            'hospital' => $this->whenLoaded('hospital', fn () => $this->hospital ? [
                'id' => $this->hospital->id,
                'name' => $this->hospital->name,
                'code' => $this->hospital->code,
            ] : null),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
