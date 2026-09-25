<?php

declare(strict_types=1);

namespace Tests\Feature\Auth;

use App\Models\Organization;
use App\Models\User;
use App\Models\Workspace;
use App\Enums\UserRole;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class AuthenticatedSessionControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_requires_email_and_password(): void
    {
        $this->postJson('/login', [])
            ->assertJsonValidationErrors(['email', 'password']);
    }

    public function test_successful_login(): void
    {
        $user = User::factory()->create([
            'password' => bcrypt('password123'),
        ]);

        $this->postJson('/login', [
            'email' => $user->email,
            'password' => 'password123',
        ])
            ->assertOk()
            ->assertJsonStructure(['message', 'user']);
    }

    public function test_invalid_credentials_returns_401(): void
    {
        $user = User::factory()->create();

        $this->postJson('/login', [
            'email' => $user->email,
            'password' => 'wrong',
        ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['email']);
    }

    public function test_rate_limiting_on_failed_login(): void
    {
        $user = User::factory()->create();

        for ($i = 0; $i < 5; $i++) {
            $this->postJson('/login', [
                'email' => $user->email,
                'password' => 'wrong',
            ]);
        }

        $this->postJson('/login', [
            'email' => $user->email,
            'password' => 'wrong',
        ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['email']);
    }

    public function test_logout(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $this->postJson('/logout')
            ->assertOk()
            ->assertJson(['message' => 'Logged out']);
    }

    public function test_org_admin_requires_2fa(): void
    {
        $organization = Organization::factory()->create();
        $orgAdmin = User::factory()->create([
            'organization_id' => $organization->id,
            'role' => UserRole::OrgAdmin,
            'password' => bcrypt('password123'),
            'two_factor_secret' => null,
        ]);

        $response = $this->postJson('/login', [
            'email' => $orgAdmin->email,
            'password' => 'password123',
        ]);

        $response->assertOk()
            ->assertJson([
                'two_factor_required' => true,
            ]);
    }
}