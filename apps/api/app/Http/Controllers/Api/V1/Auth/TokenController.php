<?php

namespace App\Http\Controllers\Api\V1\Auth;

use App\Enums\UserStatus;
use App\Http\Requests\Auth\CreateTokenRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use App\Support\Mfa\MfaManager;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class TokenController
{
    public function store(CreateTokenRequest $request, MfaManager $mfa): JsonResponse
    {
        $credentials = $request->validated();
        $user = User::where('email', $credentials['email'])->first();

        if (! $user || ! Hash::check($credentials['password'], $user->password)) {
            throw ValidationException::withMessages(['email' => ['The provided credentials are incorrect.']]);
        }

        if ($user->status !== UserStatus::Active) {
            return response()->json(['error' => ['code' => 'ACCOUNT_INACTIVE', 'message' => 'This account is not active.']], 403);
        }

        // An enrolled account does not receive a token from the password
        // alone. The token is issued only once the second factor has been
        // presented against the challenge.
        if ($mfa->isEnrolled($user)) {
            $challenge = $mfa->issueChallenge($user, MfaManager::PURPOSE_TOKEN, $credentials['client_name']);

            return response()->json([
                'mfa_required' => true,
                'challenge' => $challenge['challenge'],
                'expires_at' => $challenge['expires_at']->toIso8601String(),
                'methods' => ['totp', 'recovery_code'],
            ], 202);
        }

        $token = $user->createToken($credentials['client_name'], ['*']);
        $user->forceFill(['last_login_at' => now()])->save();

        return response()->json([
            'token' => $token->plainTextToken,
            'token_type' => 'Bearer',
            'user' => new UserResource($user),
        ], 201);
    }
}
