<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Resources\UserResource;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;

class AuthenticatedSessionController extends Controller
{

    protected $tokenName="Auth";
    /**
     * Handle an incoming authentication request.
     */
    public function store(LoginRequest $request): UserResource
    {
        $request->authenticate();

        // $request->session()->regenerate();

        // return response()->noContent();

        $user = $request->user();

        $user->token = $request->user()->createToken($this->tokenName);

        return new UserResource($user);
    }

    /**
     * Destroy an authenticated session.
     */
    public function destroy(Request $request): Response
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();

        $request->session()->regenerateToken();

        return response()->noContent();
    }
}
