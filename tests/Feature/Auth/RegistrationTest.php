<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RegistrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_registration_screen_can_be_rendered(): void
    {
        $response = $this->get('/register');

        $response->assertStatus(200);
    }

    public function test_new_users_can_register(): void
    {
        $response = $this->post('/register', [
            'name'                  => 'Test User',
            'username'              => 'testuser',
            'email'                 => 'test@example.com',
            'password'              => 'password',
            'password_confirmation' => 'password',
        ]);

        // After registration the user is NOT yet authenticated —
        // they must verify their email first via the custom token flow.
        $this->assertGuest();
        $response->assertRedirect(route('verification.pending', absolute: false));
    }

    public function test_user_record_is_created_on_registration(): void
    {
        $this->post('/register', [
            'name'                  => 'Test User',
            'username'              => 'testuser',
            'email'                 => 'test@example.com',
            'password'              => 'password',
            'password_confirmation' => 'password',
        ]);

        $this->assertDatabaseHas('users', [
            'email'    => 'test@example.com',
            'username' => 'testuser',
        ]);

        // Email must not be verified yet — user clicked nothing in email.
        $this->assertNull(User::where('email', 'test@example.com')->value('email_verified_at'));
    }
}
