<?php

namespace App\Http\Controllers\Api\V1\Auth;

use App\Http\Resources\UserResource;
use Illuminate\Http\Request;

class CurrentUserController
{
    public function __invoke(Request $request): UserResource
    {
        return new UserResource($request->user());
    }
}
