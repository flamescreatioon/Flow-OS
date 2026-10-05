<?php

namespace App\Http\Controllers\Api\V1\Auth;

use App\Http\Requests\Auth\ForgotPasswordRequest;
use App\Http\Requests\Auth\ResetPasswordRequest;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;

class PasswordResetController
{
    public function sendLink(ForgotPasswordRequest $request): JsonResponse
    {
        Password::sendResetLink($request->validated());

        return response()->json(['message' => 'If an account exists for that email, a reset link has been sent.'], 202);
    }

    public function reset(ResetPasswordRequest $request): JsonResponse
    {
        $status = Password::reset($request->validated(), function ($user, string $password): void {
            $user->forceFill(['password' => Hash::make($password), 'remember_token' => Str::random(60)])->save();
            $user->tokens()->delete();
            event(new PasswordReset($user));
        });

        if ($status !== Password::PASSWORD_RESET) {
            return response()->json(['error' => ['code' => 'PASSWORD_RESET_INVALID', 'message' => 'The password reset token is invalid or has expired.']], 422);
        }

        return response()->json(['message' => 'Your password has been reset.']);
    }
}
