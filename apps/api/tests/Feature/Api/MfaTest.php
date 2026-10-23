<?php

namespace Tests\Feature\Api;

use App\Models\MfaChallenge;
use App\Models\User;
use App\Support\Mfa\MfaManager;
use App\Support\Mfa\Totp;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class MfaTest extends TestCase
{
    protected function enrolledUser(): User
    {
        $user = User::factory()->create(['email_verified_at' => now()]);
        Sanctum::actingAs($user);

        $secret = $this->postJson('/api/v1/auth/mfa/enroll')->assertCreated()->json('data.secret');

        $this->postJson('/api/v1/auth/mfa/confirm', ['code' => $this->codeFor($secret)])
            ->assertOk()
            ->assertJsonCount(8, 'data.recovery_codes');

        Sanctum::actingAs($user->fresh());

        return $user->fresh();
    }

    protected function codeFor(string $secret): string
    {
        return Totp::codeAt($secret, Totp::counterAt());
    }

    public function test_a_user_can_enrol_and_confirm_multi_factor_authentication(): void
    {
        $user = User::factory()->create(['email_verified_at' => now()]);
        Sanctum::actingAs($user);

        $enrollment = $this->postJson('/api/v1/auth/mfa/enroll')->assertCreated()->json('data');

        $this->assertNotEmpty($enrollment['secret']);
        $this->assertStringStartsWith('otpauth://totp/flowOS%3A', $enrollment['otpauth_uri']);
        $this->assertStringContainsString('secret='.$enrollment['secret'], $enrollment['otpauth_uri']);

        $confirmation = $this->postJson('/api/v1/auth/mfa/confirm', [
            'code' => $this->codeFor($enrollment['secret']),
        ])->assertOk()->json('data.recovery_codes');

        $this->assertCount(8, $confirmation);
        $this->assertNotNull($user->fresh()->mfa_enrolled_at);
        $this->assertNotNull($user->fresh()->mfa_secret);
        $this->assertNull($user->fresh()->mfa_pending_secret);
    }

    public function test_enrolment_requires_an_authenticated_account(): void
    {
        $this->postJson('/api/v1/auth/mfa/enroll')->assertUnauthorized();
    }

    public function test_enrolment_requires_a_verified_email_address(): void
    {
        Sanctum::actingAs(User::factory()->unverified()->create());

        $this->postJson('/api/v1/auth/mfa/enroll')
            ->assertForbidden()
            ->assertJsonPath('error.code', 'EMAIL_NOT_VERIFIED');
    }

    public function test_a_wrong_confirmation_code_does_not_enrol_the_account(): void
    {
        $user = User::factory()->create(['email_verified_at' => now()]);
        Sanctum::actingAs($user);

        $secret = $this->postJson('/api/v1/auth/mfa/enroll')->assertCreated()->json('data.secret');

        $this->postJson('/api/v1/auth/mfa/confirm', ['code' => '000000'])
            ->assertUnauthorized()
            ->assertJsonPath('error.code', 'MFA_CODE_INVALID');

        $this->assertNull($user->fresh()->mfa_enrolled_at);
    }

    public function test_login_issues_no_session_before_the_second_factor_is_verified(): void
    {
        $user = $this->enrolledUser();
        $user->forceFill(['password' => bcrypt('password')])->save();

        $response = $this->withHeader('Origin', 'http://localhost:3000')
            ->postJson('/api/v1/auth/login', ['email' => $user->email, 'password' => 'password']);

        $response->assertStatus(202)
            ->assertJsonPath('mfa_required', true)
            ->assertJsonPath('methods.0', 'totp');

        $this->assertNotEmpty($response->json('challenge'));
        $this->assertGuest('web');
        $this->assertDatabaseCount('mfa_challenges', 1);
    }

    public function test_a_valid_code_redeems_the_challenge_and_establishes_the_session(): void
    {
        $user = $this->enrolledUser();
        $user->forceFill(['password' => bcrypt('password')])->save();

        $challenge = $this->withHeader('Origin', 'http://localhost:3000')
            ->postJson('/api/v1/auth/login', ['email' => $user->email, 'password' => 'password'])
            ->assertStatus(202)
            ->json('challenge');

        $this->withHeader('Origin', 'http://localhost:3000')
            ->postJson('/api/v1/auth/mfa/verify', [
                'challenge' => $challenge,
                'code' => $this->codeFor((string) $user->fresh()->mfa_secret),
            ])->assertOk()
            ->assertJsonPath('data.id', $user->id);

        $this->assertAuthenticatedAs($user, 'web');
        $this->assertDatabaseCount('mfa_challenges', 1);
        $this->assertNotNull(MfaChallenge::firstOrFail()->consumed_at);
    }

    public function test_the_token_route_issues_no_token_before_the_second_factor_is_verified(): void
    {
        $user = $this->enrolledUser();
        $user->forceFill(['password' => bcrypt('password')])->save();

        $this->postJson('/api/v1/auth/token', [
            'email' => $user->email,
            'password' => 'password',
            'client_name' => 'Flutter',
        ])->assertStatus(202)
            ->assertJsonPath('mfa_required', true);

        $this->assertDatabaseCount('personal_access_tokens', 0);
    }

    public function test_the_token_route_challenge_mints_a_token_and_not_a_session(): void
    {
        $user = $this->enrolledUser();
        $user->forceFill(['password' => bcrypt('password')])->save();

        $challenge = $this->postJson('/api/v1/auth/token', [
            'email' => $user->email,
            'password' => 'password',
            'client_name' => 'Flutter',
        ])->assertStatus(202)->json('challenge');

        $response = $this->postJson('/api/v1/auth/mfa/verify', [
            'challenge' => $challenge,
            'code' => $this->codeFor((string) $user->fresh()->mfa_secret),
        ])->assertCreated();

        $this->assertNotEmpty($response->json('token'));
        $this->assertSame('Bearer', $response->json('token_type'));
        $this->assertNull($response->json('data'));
        $this->assertGuest('web');
        $this->assertDatabaseCount('personal_access_tokens', 1);
    }

    public function test_an_invalid_code_issues_no_credential(): void
    {
        $user = $this->enrolledUser();
        $user->forceFill(['password' => bcrypt('password')])->save();

        $challenge = $this->postJson('/api/v1/auth/token', [
            'email' => $user->email,
            'password' => 'password',
            'client_name' => 'Flutter',
        ])->assertStatus(202)->json('challenge');

        $this->postJson('/api/v1/auth/mfa/verify', [
            'challenge' => $challenge,
            'code' => '000000',
        ])->assertUnauthorized()
            ->assertJsonPath('error.code', 'MFA_CODE_INVALID');

        $this->assertDatabaseCount('personal_access_tokens', 0);
    }

    public function test_a_challenge_is_locked_out_after_repeated_failures(): void
    {
        $user = $this->enrolledUser();
        $user->forceFill(['password' => bcrypt('password')])->save();

        $challenge = $this->postJson('/api/v1/auth/token', [
            'email' => $user->email,
            'password' => 'password',
            'client_name' => 'Flutter',
        ])->assertStatus(202)->json('challenge');

        for ($attempt = 1; $attempt < MfaManager::MAX_ATTEMPTS; $attempt++) {
            $this->postJson('/api/v1/auth/mfa/verify', [
                'challenge' => $challenge,
                'code' => '000000',
            ])->assertUnauthorized()
                ->assertJsonPath('error.code', 'MFA_CODE_INVALID');
        }

        $this->postJson('/api/v1/auth/mfa/verify', [
            'challenge' => $challenge,
            'code' => '000000',
        ])->assertStatus(429)
            ->assertJsonPath('error.code', 'MFA_LOCKED');

        $this->assertNotNull(MfaChallenge::firstOrFail()->consumed_at);
        $this->assertDatabaseCount('personal_access_tokens', 0);
    }

    public function test_a_challenge_cannot_be_redeemed_twice(): void
    {
        $user = $this->enrolledUser();
        $user->forceFill(['password' => bcrypt('password')])->save();

        $challenge = $this->postJson('/api/v1/auth/token', [
            'email' => $user->email,
            'password' => 'password',
            'client_name' => 'Flutter',
        ])->assertStatus(202)->json('challenge');

        $this->postJson('/api/v1/auth/mfa/verify', [
            'challenge' => $challenge,
            'code' => $this->codeFor((string) $user->fresh()->mfa_secret),
        ])->assertCreated();

        $this->postJson('/api/v1/auth/mfa/verify', [
            'challenge' => $challenge,
            'code' => $this->codeFor((string) $user->fresh()->mfa_secret),
        ])->assertUnauthorized()
            ->assertJsonPath('error.code', 'MFA_CHALLENGE_INVALID');

        $this->assertDatabaseCount('personal_access_tokens', 1);
    }

    public function test_a_code_cannot_be_replayed_inside_its_validity_window(): void
    {
        $user = $this->enrolledUser();
        $user->forceFill(['password' => bcrypt('password')])->save();
        $secret = (string) $user->fresh()->mfa_secret;

        $first = $this->postJson('/api/v1/auth/token', [
            'email' => $user->email,
            'password' => 'password',
            'client_name' => 'Flutter',
        ])->assertStatus(202)->json('challenge');

        $this->postJson('/api/v1/auth/mfa/verify', [
            'challenge' => $first,
            'code' => $this->codeFor($secret),
        ])->assertCreated();

        $second = $this->postJson('/api/v1/auth/token', [
            'email' => $user->email,
            'password' => 'password',
            'client_name' => 'Flutter',
        ])->assertStatus(202)->json('challenge');

        $this->postJson('/api/v1/auth/mfa/verify', [
            'challenge' => $second,
            'code' => $this->codeFor($secret),
        ])->assertUnauthorized()
            ->assertJsonPath('error.code', 'MFA_CODE_INVALID');

        $this->assertDatabaseCount('personal_access_tokens', 1);
    }

    public function test_a_recovery_code_is_accepted_once(): void
    {
        $user = User::factory()->create(['email_verified_at' => now()]);
        Sanctum::actingAs($user);

        $secret = $this->postJson('/api/v1/auth/mfa/enroll')->assertCreated()->json('data.secret');
        $recoveryCode = $this->postJson('/api/v1/auth/mfa/confirm', [
            'code' => $this->codeFor($secret),
        ])->assertOk()->json('data.recovery_codes.0');

        $user->forceFill(['password' => bcrypt('password')])->save();

        $first = $this->postJson('/api/v1/auth/token', [
            'email' => $user->email,
            'password' => 'password',
            'client_name' => 'Flutter',
        ])->assertStatus(202)->json('challenge');

        $this->postJson('/api/v1/auth/mfa/verify', [
            'challenge' => $first,
            'code' => $recoveryCode,
        ])->assertCreated();

        $second = $this->postJson('/api/v1/auth/token', [
            'email' => $user->email,
            'password' => 'password',
            'client_name' => 'Flutter',
        ])->assertStatus(202)->json('challenge');

        $this->postJson('/api/v1/auth/mfa/verify', [
            'challenge' => $second,
            'code' => $recoveryCode,
        ])->assertUnauthorized()
            ->assertJsonPath('error.code', 'MFA_CODE_INVALID');

        $this->assertDatabaseCount('personal_access_tokens', 1);
        $this->assertCount(7, $user->fresh()->mfa_recovery_codes);
    }

    public function test_an_expired_challenge_is_refused(): void
    {
        $user = $this->enrolledUser();
        $user->forceFill(['password' => bcrypt('password')])->save();

        $challenge = $this->postJson('/api/v1/auth/token', [
            'email' => $user->email,
            'password' => 'password',
            'client_name' => 'Flutter',
        ])->assertStatus(202)->json('challenge');

        MfaChallenge::firstOrFail()->forceFill(['expires_at' => now()->subMinute()])->save();

        $this->postJson('/api/v1/auth/mfa/verify', [
            'challenge' => $challenge,
            'code' => $this->codeFor((string) $user->fresh()->mfa_secret),
        ])->assertUnauthorized()
            ->assertJsonPath('error.code', 'MFA_CHALLENGE_INVALID');

        $this->assertDatabaseCount('personal_access_tokens', 0);
    }

    public function test_an_unknown_challenge_is_refused(): void
    {
        $this->postJson('/api/v1/auth/mfa/verify', [
            'challenge' => 'not-a-real-challenge',
            'code' => '123456',
        ])->assertUnauthorized()
            ->assertJsonPath('error.code', 'MFA_CHALLENGE_INVALID');
    }

    public function test_multi_factor_authentication_can_be_disabled_with_a_valid_code(): void
    {
        $user = $this->enrolledUser();
        Sanctum::actingAs($user);

        $this->postJson('/api/v1/auth/mfa/disable', [
            'code' => $this->codeFor((string) $user->fresh()->mfa_secret),
        ])->assertNoContent();

        $this->assertNull($user->fresh()->mfa_enrolled_at);
        $this->assertNull($user->fresh()->mfa_secret);
        $this->assertNull($user->fresh()->mfa_recovery_codes);
    }

    public function test_multi_factor_authentication_cannot_be_disabled_with_a_wrong_code(): void
    {
        $user = $this->enrolledUser();
        Sanctum::actingAs($user);

        $this->postJson('/api/v1/auth/mfa/disable', ['code' => '000000'])
            ->assertUnauthorized()
            ->assertJsonPath('error.code', 'MFA_CODE_INVALID');

        $this->assertNotNull($user->fresh()->mfa_enrolled_at);
    }

    public function test_the_secret_and_recovery_codes_are_never_serialized(): void
    {
        $user = $this->enrolledUser();
        Sanctum::actingAs($user);

        $response = $this->getJson('/api/v1/auth/me')->assertOk();

        $response->assertJsonMissingPath('data.mfa_secret');
        $response->assertJsonMissingPath('data.mfa_pending_secret');
        $response->assertJsonMissingPath('data.mfa_recovery_codes');
    }

    public function test_an_account_without_enrolment_still_authenticates_normally(): void
    {
        $user = User::factory()->create(['email_verified_at' => now()]);
        $user->forceFill(['password' => bcrypt('password')])->save();

        $this->postJson('/api/v1/auth/token', [
            'email' => $user->email,
            'password' => 'password',
            'client_name' => 'Flutter',
        ])->assertCreated();

        $this->assertDatabaseCount('personal_access_tokens', 1);
        $this->assertDatabaseCount('mfa_challenges', 0);
    }
}
