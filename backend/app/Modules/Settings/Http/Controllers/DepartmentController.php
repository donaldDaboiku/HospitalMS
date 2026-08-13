<?php

namespace App\Modules\Settings\Http\Controllers;

use App\Core\Http\ApiResponse;
use App\Http\Controllers\Controller;
use App\Modules\Settings\Models\Department;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DepartmentController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        abort_unless($request->user()->can('department.view'), 403);

        $query = Department::query()->orderBy('name');

        if (! $request->user()->isSuperAdmin()) {
            $query->where('hospital_id', $request->user()->hospital_id);
        }

        return ApiResponse::success($query->get());
    }
}
