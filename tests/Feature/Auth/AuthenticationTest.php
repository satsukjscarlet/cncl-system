<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_screen_can_be_rendered(): void
    {
        $response = $this->get('/login');

        $response->assertStatus(200);
        $this->assertStringContainsString('no-store', $response->headers->get('Cache-Control'));
        $response->assertSee('action="/login"', false);
    }

    public function test_expired_login_token_returns_to_login_without_replaying_password(): void
    {
        $this->enableCsrfChecks();
        $this->withSession(['_token' => 'current-token'])
            ->post('/login', ['_token' => 'stale-token', 'username' => 'tester', 'password' => 'never-flash-this'])
            ->assertStatus(303)->assertRedirect('/login')->assertSessionHasErrors('username')
            ->assertSessionHas('_old_input', ['username' => 'tester']);
        $this->assertGuest();
        $this->get('/login')->assertOk()->assertSee('Phiên đăng nhập đã hết hạn hoặc thay đổi.')
            ->assertDontSee('never-flash-this');
    }

    public function test_json_login_csrf_failure_retains_419_status(): void
    {
        $this->enableCsrfChecks();
        $this->postJson('/login', ['_token' => 'stale-token'])->assertStatus(419);
        $this->assertGuest();
    }

    public function test_valid_csrf_token_still_reaches_login_validation(): void
    {
        $this->enableCsrfChecks();
        $this->withSession(['_token' => 'current-token'])
            ->post('/login', ['_token' => 'current-token'])->assertSessionHasErrors(['username', 'password']);
    }

    public function test_matching_token_can_authenticate_and_other_routes_still_reject_bad_tokens(): void
    {
        $this->enableCsrfChecks();
        $user = User::factory()->create();
        $this->withSession(['_token' => 'current-token'])->post('/login', [
            '_token' => 'current-token', 'username' => $user->username, 'password' => 'password',
        ])->assertRedirect('/dashboard');
        $this->assertAuthenticatedAs($user);

        $this->post('/logout', ['_token' => 'stale-token'])->assertStatus(419);
        $this->assertAuthenticatedAs($user);
    }

    private function enableCsrfChecks(): void
    {
        $this->app->bind(\Illuminate\Foundation\Http\Middleware\ValidateCsrfToken::class, function ($app) {
            return new class($app, $app['encrypter']) extends \Illuminate\Foundation\Http\Middleware\ValidateCsrfToken {
                protected function runningUnitTests(): bool
                {
                    return false;
                }
            };
        });
    }

    public function test_users_can_authenticate_using_the_login_screen(): void
    {
        $user = User::factory()->create();

        $response = $this->post('/login', [
            'username' => $user->username,
            'password' => 'password',
        ]);

        $this->assertAuthenticated();
        $response->assertRedirect(route('dashboard', absolute: false));
    }

    public function test_users_can_not_authenticate_with_invalid_password(): void
    {
        $user = User::factory()->create();

        $this->post('/login', [
            'username' => $user->username,
            'password' => 'wrong-password',
        ]);

        $this->assertGuest();
    }

    public function test_users_can_logout(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post('/logout');

        $this->assertGuest();
        $response->assertRedirect('/');
    }
}
