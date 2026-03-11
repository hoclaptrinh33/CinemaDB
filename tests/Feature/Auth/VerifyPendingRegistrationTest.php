<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class VerifyPendingRegistrationTest extends TestCase
{
    use RefreshDatabase;

    // ── Helpers ──────────────────────────────────────────────────────────────

    private function createUnverifiedUser(): User
    {
        return User::factory()->unverified()->create();
    }

    private function storeToken(string $token, int $userId, int $ttlHours = 24): void
    {
        Cache::put("reg_verify:{$token}", $userId, now()->addHours($ttlHours));
    }

    // ── Happy path ────────────────────────────────────────────────────────────

    public function test_valid_token_verifies_email_and_logs_user_in(): void
    {
        $user  = $this->createUnverifiedUser();
        $token = 'valid-token-abc123';
        $this->storeToken($token, $user->id);

        $response = $this->get(route('register.verify', $token));

        // User should be authenticated after verification.
        $this->assertAuthenticatedAs($user);

        // User's email_verified_at should now be set.
        $this->assertNotNull($user->fresh()->email_verified_at);

        // Should redirect to home.
        $response->assertRedirect(route('home', absolute: false));
    }

    public function test_valid_token_is_consumed_after_use(): void
    {
        $user  = $this->createUnverifiedUser();
        $token = 'one-time-token';
        $this->storeToken($token, $user->id);

        $this->get(route('register.verify', $token));

        // Token must be deleted from cache so it cannot be reused.
        $this->assertNull(Cache::get("reg_verify:{$token}"));
    }

    // ── Already verified ──────────────────────────────────────────────────────

    public function test_already_verified_user_is_redirected_to_home_without_error(): void
    {
        $user  = User::factory()->create(); // verified by default
        $token = 'stale-token';
        $this->storeToken($token, $user->id);

        $response = $this->get(route('register.verify', $token));

        $response->assertRedirect(route('home', absolute: false));

        // Token should be cleaned up even for already-verified users.
        $this->assertNull(Cache::get("reg_verify:{$token}"));
    }

    // ── Invalid / expired token ───────────────────────────────────────────────

    public function test_nonexistent_token_redirects_to_register_with_error(): void
    {
        $response = $this->get(route('register.verify', 'this-token-does-not-exist'));

        $this->assertGuest();
        $response->assertRedirect(route('register', absolute: false));
        $response->assertSessionHasErrors('email');
    }

    public function test_expired_token_redirects_to_register_with_error(): void
    {
        $user  = $this->createUnverifiedUser();
        $token = 'expired-token';

        // Store with a TTL that is already past.
        Cache::put("reg_verify:{$token}", $user->id, now()->subSecond());

        $response = $this->get(route('register.verify', $token));

        $this->assertGuest();
        $response->assertRedirect(route('register', absolute: false));
        $response->assertSessionHasErrors('email');
    }

    public function test_token_for_deleted_user_redirects_to_register_with_error(): void
    {
        $user  = $this->createUnverifiedUser();
        $token = 'orphaned-token';
        $this->storeToken($token, $user->id);

        // Delete the user to simulate an orphaned token.
        $user->delete();

        $response = $this->get(route('register.verify', $token));

        // Token should be cleaned up.
        $this->assertNull(Cache::get("reg_verify:{$token}"));

        $this->assertGuest();
        $response->assertRedirect(route('register', absolute: false));
        $response->assertSessionHasErrors('email');
    }

    // ── Throttle smoke test ───────────────────────────────────────────────────

    public function test_verification_endpoint_is_throttled(): void
    {
        // The route is protected by throttle:10,1. After 10 attempts the
        // next response must be 429 Too Many Requests.
        for ($i = 0; $i < 10; $i++) {
            $this->get(route('register.verify', "bad-token-{$i}"));
        }

        $response = $this->get(route('register.verify', 'final-bad-token'));

        $response->assertStatus(429);
    }
}
