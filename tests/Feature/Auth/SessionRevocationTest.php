<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use App\Services\SessionSecurityService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class SessionRevocationTest extends TestCase
{
    use RefreshDatabase;

    public function test_password_change_preserves_current_device_and_rotates_remember_token(): void
    {
        $user = User::factory()->create();
        $token = $user->remember_token;
        $this->actingAs($user)->get('/profile')->assertOk();
        $this->from('/profile')->put('/password', [
            'current_password' => 'password', 'password' => 'new-password',
            'password_confirmation' => 'new-password', 'logout_other_devices' => '0',
        ])->assertSessionHasNoErrors()->assertRedirect('/profile');
        $this->assertNotSame($token, $user->fresh()->remember_token);
        $this->get('/profile')->assertOk();
        $this->assertAuthenticatedAs($user);
    }

    public function test_stale_session_cannot_read_pdf_or_perform_an_update(): void
    {
        foreach (['pdf', 'update', 'feed'] as $target) {
            $user = User::factory()->create();
            $oldHash = $user->getAuthPassword();
            $user->update(['password' => Hash::make('new-password')]);
            $this->actingAs($user->fresh())->withSession(['password_hash_web' => $oldHash]);
            if ($target === 'pdf') {
                $this->get('/quality-certificates/999999/pdf')->assertRedirect('/login');
            } elseif ($target === 'update') {
                $this->patch('/profile', ['name' => 'Must not save', 'email' => $user->email])->assertRedirect('/login');
                $this->assertNotSame('Must not save', $user->fresh()->name);
            } else {
                $this->getJson('/notifications/feed')->assertUnauthorized();
            }
            $this->assertGuest();
        }
    }

    public function test_old_remember_cookie_cannot_restore_a_revoked_login(): void
    {
        $user = User::factory()->create();
        $cookieName = Auth::guard('web')->getRecallerName();
        $cookie = $user->id . '|' . $user->remember_token . '|' . Auth::guard('web')->hashPasswordForCookie($user->password);
        app(SessionSecurityService::class)->revokeUserSessions($user);
        Auth::forgetGuards();
        $this->withCookie($cookieName, $cookie)->get('/profile')->assertRedirect('/login');
        $this->assertGuest();
    }

    public function test_old_password_hash_in_remember_cookie_is_rejected_even_if_token_matches(): void
    {
        $user = User::factory()->create();
        $cookieName = Auth::guard('web')->getRecallerName();
        $cookie = $user->id . '|' . $user->remember_token . '|' . Auth::guard('web')->hashPasswordForCookie($user->password);
        $user->update(['password' => Hash::make('new-password')]);
        Auth::forgetGuards();
        $this->withCookie($cookieName, $cookie)->get('/profile')->assertRedirect('/login');
        $this->assertGuest();
    }

    public function test_database_revocation_only_deletes_other_sessions_of_target_user(): void
    {
        config(['session.driver' => 'database']);
        $user = User::factory()->create();
        $other = User::factory()->create();
        foreach (['current' => $user->id, 'old' => $user->id, 'unrelated' => $other->id] as $id => $userId) {
            DB::table('sessions')->insert(['id' => $id, 'user_id' => $userId, 'payload' => '', 'last_activity' => time()]);
        }
        $this->assertSame(1, app(SessionSecurityService::class)->revokeUserSessions($user, 'current'));
        $this->assertDatabaseHas('sessions', ['id' => 'current']);
        $this->assertDatabaseHas('sessions', ['id' => 'unrelated']);
        $this->assertDatabaseMissing('sessions', ['id' => 'old']);
    }
}
