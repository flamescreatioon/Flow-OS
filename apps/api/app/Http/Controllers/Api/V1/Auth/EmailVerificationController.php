<?php

namespace App\Http\Controllers\Api\V1\Auth;

use App\Http\Resources\UserResource;
use App\Models\User;
use Illuminate\Auth\Events\Verified;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class EmailVerificationController
{
    public function sendNotification(Request $request): JsonResponse
    {
        $user = $request->user();

        if ($user->hasVerifiedEmail()) {
            return response()->json(['message' => 'Email address is already verified.']);
        }

        $user->sendEmailVerificationNotification();

        return response()->json(['message' => 'Verification link sent.'], 202);
    }

    public function verify(Request $request, string $id, string $hash): JsonResponse
    {
        abort_unless($request->hasValidSignature(), 403);

        $user = User::findOrFail($id);
        abort_unless(hash_equals((string) $hash, sha1($user->getEmailForVerification())), 403);

        if (! $user->hasVerifiedEmail()) {
            $user->markEmailAsVerified();
            event(new Verified($user));
        }

        return response()->json(['data' => new UserResource($user)]);
    }
}
