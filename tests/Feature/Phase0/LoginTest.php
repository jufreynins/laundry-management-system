<?php

namespace Tests\Feature\Phase0;

use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class LoginTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_redirected_to_login(): void
    {
        $response = $this->get('/');
        $response->assertRedirect(route('login'));
    }

    public function test_user_can_login_with_correct_credentials(): void
    {
        $user = User::factory()->create([
            'password' => Hash::make('correct-password-123'),
        ]);

        $response = $this->post('/login', [
            'email' => $user->email,
            'password' => 'correct-password-123',
        ]);

        $response->assertRedirect(route('dashboard'));
        $this->assertAuthenticatedAs($user);
    }

    public function test_login_fails_with_wrong_password(): void
    {
        $user = User::factory()->create([
            'password' => Hash::make('correct-password-123'),
        ]);

        $response = $this->from('/login')->post('/login', [
            'email' => $user->email,
            'password' => 'wrong-password',
        ]);

        $response->assertRedirect('/login');
        $this->assertGuest();
    }

    public function test_login_failure_message_is_generic(): void
    {
        $response = $this->post('/login', [
            'email' => 'nonexistent@example.com',
            'password' => 'whatever-password',
        ]);

        $response->assertSessionHasErrors('email');
        $message = session('errors')->first('email');
        $this->assertStringNotContainsString('exist', strtolower($message));
    }

    public function test_inactive_user_cannot_login(): void
    {
        $user = User::factory()->create([
            'password' => Hash::make('correct-password-123'),
            'active' => false,
        ]);

        $response = $this->post('/login', [
            'email' => $user->email,
            'password' => 'correct-password-123',
        ]);

        $response->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    public function test_failed_login_creates_audit_log(): void
    {
        $user = User::factory()->create([
            'password' => Hash::make('correct-password-123'),
        ]);

        $this->post('/login', [
            'email' => $user->email,
            'password' => 'wrong-password',
        ]);

        $this->assertDatabaseHas('audit_logs', [
            'action' => 'login_failed',
            'user_id' => $user->id,
        ]);
    }

    public function test_successful_login_creates_audit_log(): void
    {
        $user = User::factory()->create([
            'password' => Hash::make('correct-password-123'),
        ]);

        $this->post('/login', [
            'email' => $user->email,
            'password' => 'correct-password-123',
        ]);

        $this->assertDatabaseHas('audit_logs', [
            'action' => 'login',
            'user_id' => $user->id,
        ]);
    }

    public function test_login_rate_limited_after_multiple_failures(): void
    {
        $user = User::factory()->create([
            'password' => Hash::make('correct-password-123'),
        ]);

        for ($i = 0; $i < 5; $i++) {
            $this->post('/login', [
                'email' => $user->email,
                'password' => 'wrong-password',
            ]);
        }

        $response = $this->post('/login', [
            'email' => $user->email,
            'password' => 'wrong-password',
        ]);

        $response->assertSessionHasErrors('email');
        $message = session('errors')->first('email');
        $this->assertStringContainsString('Too many', $message);
    }

    public function test_authenticated_user_can_logout(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $response = $this->post('/logout');

        $response->assertRedirect(route('login'));
        $this->assertGuest();
    }

    public function test_authenticated_user_can_view_dashboard(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/dashboard');

        $response->assertOk();
    }
}
