<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthControllerTest extends TestCase
{
    use RefreshDatabase;

    // region Register

    public function test_register_creates_user_and_returns_resource(): void
    {
        $response = $this->postJson('/api/auth/register', [
            'email' => 'user@example.com',
            'password' => 'secret123',
            'password_confirmation' => 'secret123',
        ]);

        $response->assertCreated()
            ->assertJsonStructure(['data' => ['id', 'email', 'telegram_chat_id']])
            ->assertJsonPath('data.email', 'user@example.com');

        $this->assertDatabaseHas('users', ['email' => 'user@example.com']);
    }

    public function test_register_fails_without_email(): void
    {
        $this->postJson('/api/auth/register', [
            'password' => 'secret123',
            'password_confirmation' => 'secret123',
        ])->assertUnprocessable()
            ->assertJsonValidationErrors(['email']);
    }

    public function test_register_fails_with_invalid_email(): void
    {
        $this->postJson('/api/auth/register', [
            'email' => 'not-an-email',
            'password' => 'secret123',
            'password_confirmation' => 'secret123',
        ])->assertUnprocessable()
            ->assertJsonValidationErrors(['email']);
    }

    public function test_register_fails_with_short_password(): void
    {
        $this->postJson('/api/auth/register', [
            'email' => 'user@example.com',
            'password' => '1234567',
            'password_confirmation' => '1234567',
        ])->assertUnprocessable()
            ->assertJsonValidationErrors(['password']);
    }

    public function test_register_fails_when_passwords_do_not_match(): void
    {
        $this->postJson('/api/auth/register', [
            'email' => 'user@example.com',
            'password' => 'secret123',
            'password_confirmation' => 'different',
        ])->assertUnprocessable()
            ->assertJsonValidationErrors(['password']);
    }

    public function test_register_fails_with_duplicate_email(): void
    {
        User::factory()->create(['email' => 'user@example.com']);

        $this->postJson('/api/auth/register', [
            'email' => 'user@example.com',
            'password' => 'secret123',
            'password_confirmation' => 'secret123',
        ])->assertUnprocessable()
            ->assertJsonValidationErrors(['email']);
    }

    // endregion

    // region Login

    public function test_login_returns_token_for_valid_credentials(): void
    {
        User::factory()->create([
            'email' => 'user@example.com',
            'password' => bcrypt('secret123'),
        ]);

        $response = $this->postJson('/api/auth/login', [
            'email' => 'user@example.com',
            'password' => 'secret123',
        ]);

        $response->assertOk()
            ->assertJsonStructure(['data' => ['token']]);

        $this->assertNotEmpty($response->json('data.token'));
    }

    public function test_login_fails_with_wrong_password(): void
    {
        User::factory()->create([
            'email' => 'user@example.com',
            'password' => bcrypt('secret123'),
        ]);

        $this->postJson('/api/auth/login', [
            'email' => 'user@example.com',
            'password' => 'wrongpassword',
        ])->assertUnauthorized();
    }

    public function test_login_fails_with_nonexistent_email(): void
    {
        $this->postJson('/api/auth/login', [
            'email' => 'ghost@example.com',
            'password' => 'secret123',
        ])->assertUnauthorized();
    }

    public function test_login_fails_without_credentials(): void
    {
        $this->postJson('/api/auth/login', [])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['email', 'password']);
    }

    // endregion

    // region Me

    public function test_me_returns_current_user(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->getJson('/api/auth/me')
            ->assertOk()
            ->assertJsonPath('data.id', $user->id)
            ->assertJsonPath('data.email', $user->email);
    }

    public function test_me_requires_authentication(): void
    {
        $this->getJson('/api/auth/me')
            ->assertUnauthorized();
    }

    // endregion

    // region Logout

    public function test_logout_revokes_token(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('api')->plainTextToken;

        $this->withToken($token)
            ->postJson('/api/auth/logout')
            ->assertOk();

        // Reset cached auth guard so next request re-authenticates from scratch
        $this->app['auth']->forgetGuards();

        // Token revoked — subsequent request must fail
        $this->withToken($token)
            ->getJson('/api/auth/me')
            ->assertUnauthorized();
    }

    public function test_logout_requires_authentication(): void
    {
        $this->postJson('/api/auth/logout')
            ->assertUnauthorized();
    }

    // endregion
}
