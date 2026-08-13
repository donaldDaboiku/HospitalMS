<?php

namespace App\Modules\Roles\Http\Controllers;

use App\Core\Http\ApiResponse;
use App\Core\Support\PermissionCatalog;
use App\Http\Controllers\Controller;
use App\Modules\Roles\Models\Role;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class RoleController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        abort_unless($request->user()->can('role.view'), 403);

        $roles = Role::query()
            ->with('permissions:id,name')
            ->orderBy('name')
            ->get()
            ->map(fn (Role $role) => [
                'id' => $role->id,
                'name' => $role->name,
                'permissions' => $role->permissions->pluck('name')->values(),
            ]);

        return ApiResponse::success($roles);
    }

    public function permissions(Request $request): JsonResponse
    {
        abort_unless($request->user()->can('role.view'), 403);

        return ApiResponse::success(PermissionCatalog::GROUPS);
    }
}
