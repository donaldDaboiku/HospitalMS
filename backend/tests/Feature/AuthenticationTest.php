<?php

namespace Tests\Feature;

use App\Core\Support\Roles;
use Tests\FeatureTestCase;

class AuthenticationTest extends FeatureTestCase
{
    public function test_user_can_login_with_valid_credentials(): void
    {
        $user = $this->makeUser(Roles::HOSPITAL_ADMIN);

        $response = $this->postJson('/api/v1/auth/login', [
            'email' => $user->email,
            'password' => 'Password!123',
        ]);

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonStructure(['data' => ['token', 'user' => ['id', 'email', 'roles', 'permissions']]]);

        $this->assertNotEmpty($response->json('data.token'));
        $this->assertDatabaseHas('audit_logs', [
            'user_id' => $user->id,
            'action' => 'auth.login',
            'module' => 'authentication',
        ]);
    }

    public function test_login_fails_with_invalid_credentials(): void
    {
        $user = $this->makeUser(Roles::DOCTOR);

        $this->postJson('/api/v1/auth/login', [
            'email' => $user->email,
            'password' => 'wrong-password',
        ])->assertStatus(422)
            ->assertJsonPath('success', false);
    }

    public function test_inactive_user_cannot_login(): void
    {
        $user = $this->makeUser(Roles::NURSE);
        $user->forceFill(['is_active' => false])->save();

        $this->postJson('/api/v1/auth/login', [
            'email' => $user->email,
            'password' => 'Password!123',
        ])->assertForbidden();
    }

    public function test_authenticated_user_can_view_profile_and_logout(): void
    {
        $user = $this->makeUser(Roles::RECEPTIONIST);
        $token = $user->createToken('web')->plainTextToken;

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson('/api/v1/auth/me')
            ->assertOk()
            ->assertJsonPath('data.email', $user->email);

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson('/api/v1/auth/logout')
            ->assertOk();

        $this->assertDatabaseHas('audit_logs', [
            'user_id' => $user->id,
            'action' => 'auth.logout',
        ]);
        $this->assertSame(0, $user->tokens()->count());
    }

    public function test_login_requires_email_and_password(): void
    {
        $this->postJson('/api/v1/auth/login', [])
            ->assertStatus(422)
            ->assertJsonStructure(['errors' => ['email', 'password']]);
    }
}
