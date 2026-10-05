<?php

namespace App\Http\Controllers\Api\V1\Auth;

use App\Enums\UserStatus;
use App\Http\Requests\Auth\CreateTokenRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class TokenController
{
    public function store(CreateTokenRequest $request): JsonResponse
    {
        $credentials = $request->validated();
        $user = User::where('email', $credentials['email'])->first();

        if (! $user || ! Hash::check($credentials['password'], $user->password)) {
            throw ValidationException::withMessages(['email' => ['The provided credentials are incorrect.']]);
        }

        if ($user->status !== UserStatus::Active) {
            return response()->json(['error' => ['code' => 'ACCOUNT_INACTIVE', 'message' => 'This account is not active.']], 403);
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
