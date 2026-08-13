<?php

namespace App\Modules\Audit\Http\Controllers;

use App\Core\Http\ApiResponse;
use App\Http\Controllers\Controller;
use App\Modules\Audit\Models\AuditLog;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AuditLogController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        abort_unless($request->user()->can('audit.view'), 403);

        $query = AuditLog::query()->with('user:id,first_name,last_name,email');

        if (! $request->user()->isSuperAdmin()) {
            $query->where('hospital_id', $request->user()->hospital_id);
        }

        if ($request->filled('module')) {
            $query->where('module', $request->string('module'));
        }

        if ($request->filled('action')) {
            $query->where('action', $request->string('action'));
        }

        $paginator = $query->latest('created_at')->paginate(
            min((int) $request->integer('per_page', config('hms.pagination.per_page')), config('hms.pagination.max_per_page'))
        );

        return ApiResponse::paginated($paginator);
    }

    public function show(Request $request, AuditLog $auditLog): JsonResponse
    {
        abort_unless($request->user()->can('audit.view'), 403);

        if (! $request->user()->isSuperAdmin() && $auditLog->hospital_id !== $request->user()->hospital_id) {
            abort(403);
        }

        $auditLog->load('user:id,first_name,last_name,email');

        return ApiResponse::success($auditLog);
    }
}
