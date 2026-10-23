<?php

namespace App\Http\Controllers\Api\V1\Auth;

use App\Enums\UserStatus;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use App\Support\Mfa\MfaManager;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class SessionController
{
    public function store(LoginRequest $request, MfaManager $mfa): JsonResponse
    {
        $credentials = $request->validated();
        $user = User::where('email', $credentials['email'])->first();

        if (! $user || ! Auth::guard('web')->validate($credentials)) {
            throw ValidationException::withMessages(['email' => ['The provided credentials are incorrect.']]);
        }

        if ($user->status !== UserStatus::Active) {
            return response()->json(['error' => ['code' => 'ACCOUNT_INACTIVE', 'message' => 'This account is not active.']], 403);
        }

        // An enrolled account does not receive a session from the password
        // alone. The session is issued only once the second factor has been
        // presented against the challenge.
        if ($mfa->isEnrolled($user)) {
            return $this->mfaChallengeResponse($mfa, $user, MfaManager::PURPOSE_SESSION);
        }

        Auth::guard('web')->login($user);
        $request->session()->regenerate();
        $user->forceFill(['last_login_at' => now()])->save();

        return (new UserResource($user))->response();
    }

    private function mfaChallengeResponse(MfaManager $mfa, User $user, string $purpose): JsonResponse
    {
        $challenge = $mfa->issueChallenge($user, $purpose);

        return response()->json([
            'mfa_required' => true,
            'challenge' => $challenge['challenge'],
            'expires_at' => $challenge['expires_at']->toIso8601String(),
            'methods' => ['totp', 'recovery_code'],
        ], 202);
    }

    public function destroy(Request $request): JsonResponse
    {
        $user = $request->user();

        if ($token = $user->currentAccessToken()) {
            $token->delete();
        } else {
            Auth::guard('web')->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();
        }

        return response()->json([], 204);
    }

    public function destroyAll(Request $request): JsonResponse
    {
        $request->user()->tokens()->delete();

        if ($request->hasSession()) {
            Auth::guard('web')->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();
        }

        return response()->json([], 204);
    }
}
