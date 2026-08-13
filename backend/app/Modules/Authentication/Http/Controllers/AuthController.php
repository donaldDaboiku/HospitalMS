<?php

namespace App\Modules\Authentication\Http\Controllers;

use App\Core\Http\ApiResponse;
use App\Http\Controllers\Controller;
use App\Models\User;
use App\Modules\Audit\Services\AuditLogger;
use App\Modules\Authentication\Http\Requests\LoginRequest;
use App\Modules\Authentication\Http\Resources\AuthenticatedUserResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function __construct(private readonly AuditLogger $auditLogger) {}

    public function login(LoginRequest $request): JsonResponse
    {
        $user = User::query()
            ->where('email', $request->validated('email'))
            ->first();

        if (! $user || ! Hash::check($request->validated('password'), $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['The provided credentials are incorrect.'],
            ]);
        }

        if (! $user->is_active) {
            return ApiResponse::error('This account is inactive.', 403);
        }

        $user->forceFill(['last_login_at' => now()])->save();
        $user->tokens()->where('name', 'web')->delete();

        $expiresAt = now()->addMinutes((int) config('sanctum.expiration', 720));
        $token = $user->createToken('web', ['*'], $expiresAt)->plainTextToken;

        $this->auditLogger->record(
            action: 'auth.login',
            module: 'authentication',
            auditable: $user,
            user: $user,
            request: $request,
        );

        $user->load('hospital');

        return ApiResponse::success([
            'token' => $token,
            'token_type' => 'Bearer',
            'expires_at' => $expiresAt->toIso8601String(),
            'user' => (new AuthenticatedUserResource($user))->resolve(),
        ], 'Authenticated.');
    }

    public function me(Request $request): JsonResponse
    {
        $user = $request->user();
        $user->load('hospital');

        return ApiResponse::success(
            (new AuthenticatedUserResource($user))->resolve(),
            'Authenticated user.'
        );
    }

    public function logout(Request $request): JsonResponse
    {
        $user = $request->user();

        $this->auditLogger->record(
            action: 'auth.logout',
            module: 'authentication',
            auditable: $user,
            user: $user,
            request: $request,
        );

        $request->user()->currentAccessToken()?->delete();

        return ApiResponse::success(null, 'Logged out.');
    }
}
