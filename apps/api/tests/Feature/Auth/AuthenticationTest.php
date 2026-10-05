<?php

namespace Tests\Feature\Auth;

use App\Enums\UserStatus;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\URL;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    public function test_registration_creates_only_a_hashed_platform_user(): void
    {
        $response = $this->postJson('/api/v1/auth/register', [
            'name' => 'Ada Lovelace',
            'email' => 'ada@example.test',
            'password' => 'A-secure-password-1!',
            'password_confirmation' => 'A-secure-password-1!',
        ]);

        $response->assertCreated()->assertJsonPath('data.email', 'ada@example.test')->assertJsonMissingPath('data.password');
        $user = User::where('email', 'ada@example.test')->firstOrFail();
        $this->assertTrue(Hash::check('A-secure-password-1!', $user->password));
        $this->assertDatabaseCount('organizations', 0);
        $this->assertDatabaseCount('organization_memberships', 0);
    }

    public function test_registration_validates_unique_email_and_password_confirmation(): void
    {
        User::factory()->create(['email' => 'ada@example.test']);

        $this->postJson('/api/v1/auth/register', [
            'name' => 'Ada Lovelace',
            'email' => 'ada@example.test',
            'password' => 'A-secure-password-1!',
            'password_confirmation' => 'different-password-1!',
        ])->assertUnprocessable()
            ->assertJsonPath('error.code', 'VALIDATION_ERROR')
            ->assertJsonPath('error.details.fields.email.0', 'The email has already been taken.');
    }

    public function test_session_login_authenticates_active_user_and_rejects_invalid_or_inactive_accounts(): void
    {
        $user = User::factory()->create(['email' => 'active@example.test', 'password' => Hash::make('password')]);

        $this->withHeader('Origin', 'http://localhost:3000')
            ->postJson('/api/v1/auth/login', ['email' => $user->email, 'password' => 'password'])
            ->assertOk()->assertJsonPath('data.id', $user->id);
        $this->assertAuthenticatedAs($user, 'web');

        $this->postJson('/api/v1/auth/login', ['email' => $user->email, 'password' => 'invalid'])
            ->assertUnprocessable();

        foreach ([UserStatus::Suspended, UserStatus::Disabled] as $status) {
            $inactive = User::factory()->create(['status' => $status, 'password' => Hash::make('password')]);
            $this->postJson('/api/v1/auth/login', ['email' => $inactive->email, 'password' => 'password'])->assertForbidden();
        }
    }

    public function test_tokens_authenticate_and_are_revoked_by_logout_all_or_account_suspension(): void
    {
        $user = User::factory()->create(['password' => Hash::make('password')]);
        $response = $this->postJson('/api/v1/auth/token', ['email' => $user->email, 'password' => 'password', 'client_name' => 'Flutter']);
        $token = $response->assertCreated()->json('token');

        $this->withToken($token)->getJson('/api/v1/auth/me')
            ->assertOk()->assertJsonPath('data.id', $user->id)->assertJsonMissingPath('data.password');

        $this->withToken($token)->postJson('/api/v1/auth/logout-all')->assertNoContent();
        app('auth')->forgetGuards();
        $this->withToken($token)->getJson('/api/v1/auth/me')->assertUnauthorized();

        $token = $user->createToken('Desktop')->plainTextToken;
        $user->forceFill(['status' => UserStatus::Suspended])->save();
        app('auth')->forgetGuards();
        $this->withToken($token)->getJson('/api/v1/auth/me')->assertForbidden();
    }

    public function test_invalid_or_inactive_users_cannot_receive_tokens(): void
    {
        $user = User::factory()->create(['status' => UserStatus::Disabled, 'password' => Hash::make('password')]);

        $this->postJson('/api/v1/auth/token', ['email' => $user->email, 'password' => 'password', 'client_name' => 'Desktop'])->assertForbidden();
        $this->postJson('/api/v1/auth/token', ['email' => 'missing@example.test', 'password' => 'password', 'client_name' => 'Desktop'])->assertUnprocessable();
    }

    public function test_password_reset_does_not_disclose_users_and_revokes_tokens_when_successful(): void
    {
        Notification::fake();
        $user = User::factory()->create(['password' => Hash::make('password')]);
        $user->createToken('Desktop');

        $this->postJson('/api/v1/auth/forgot-password', ['email' => 'missing@example.test'])->assertStatus(202);
        $this->postJson('/api/v1/auth/forgot-password', ['email' => $user->email])->assertStatus(202);
        Notification::assertSentTo($user, ResetPassword::class);

        $token = Password::broker()->createToken($user);
        $this->postJson('/api/v1/auth/reset-password', [
            'email' => $user->email,
            'token' => $token,
            'password' => 'A-new-secure-password-1!',
            'password_confirmation' => 'A-new-secure-password-1!',
        ])->assertOk();

        $this->assertTrue(Hash::check('A-new-secure-password-1!', $user->fresh()->password));
        $this->assertCount(0, $user->fresh()->tokens);
    }

    public function test_email_verification_uses_signed_url_and_is_safe_when_repeated(): void
    {
        $user = User::factory()->unverified()->create();
        $url = URL::temporarySignedRoute('verification.verify', now()->addMinutes(10), ['id' => $user->id, 'hash' => sha1($user->email)]);

        $this->getJson($url)->assertOk()->assertJsonPath('data.id', $user->id);
        $this->assertNotNull($user->fresh()->email_verified_at);
        $this->getJson($url)->assertOk();
        $this->getJson('/api/v1/auth/email/verify/'.$user->id.'/invalid')->assertForbidden();
    }

    public function test_active_user_can_request_a_verification_notification(): void
    {
        Notification::fake();
        $user = User::factory()->unverified()->create();
        Sanctum::actingAs($user);

        $this->postJson('/api/v1/auth/email/verification-notification')->assertStatus(202);
        Notification::assertSentTo($user, VerifyEmail::class);
    }

    public function test_authentication_rate_limit_is_enforced(): void
    {
        RateLimiter::clear('missing@example.test|127.0.0.1');

        for ($attempt = 0; $attempt < 5; $attempt++) {
            $this->postJson('/api/v1/auth/login', ['email' => 'missing@example.test', 'password' => 'password'])->assertUnprocessable();
        }

        $this->postJson('/api/v1/auth/login', ['email' => 'missing@example.test', 'password' => 'password'])->assertTooManyRequests();
    }

    public function test_authentication_does_not_establish_tenant_access(): void
    {
        $organization = Organization::factory()->create();
        $user = User::factory()->create();

        Sanctum::actingAs($user);
        $this->withHeaders(['X-Organization-ID' => $organization->id])->getJson('/api/v1/auth/me')->assertOk();
        $this->assertFalse($user->hasActiveMembershipIn($organization));
        $this->assertDatabaseCount('organization_memberships', 0);
    }
}
