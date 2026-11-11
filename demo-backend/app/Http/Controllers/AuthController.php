<?php

namespace App\Http\Controllers;

use App\Http\Requests\LoginRequest;
use App\Http\Requests\RegisterRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class AuthController extends Controller
{
    /**
     * Register a new user and issue an API token.
     */
    public function register(RegisterRequest $request): JsonResponse
    {
        $credentials = $request->validated();

        try {
            $user = User::create([
                'name' => $credentials['name'],
                'email' => Str::lower($credentials['email']),
                'password' => $credentials['password'],
            ]);
        } catch (UniqueConstraintViolationException) {
            return response()->json([
                'message' => 'The email has already been registered.',
            ], 409);
        }

        return response()->json([
            'user' => new UserResource($user),
            'token' => $user->createToken('api')->plainTextToken,
        ]);
    }

    /**
     * Authenticate a user and issue an API token.
     */
    public function login(LoginRequest $request): JsonResponse
    {
        $credentials = $request->validated();

        $user = User::where('email', Str::lower($credentials['email']))->first();

        if (! $user || ! Hash::check($credentials['password'], $user->password)) {
            return response()->json([
                'message' => 'Wrong email or password',
            ], 401);
        }

        return response()->json([
            'user' => new UserResource($user),
            'token' => $user->createToken('api')->plainTextToken,
        ]);
    }

    /**
     * Revoke the token used for the current request.
     */
    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json(['ok' => true]);
    }
}
