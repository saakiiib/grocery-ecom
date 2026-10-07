<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ApiToken;
use App\Models\User;
use App\Models\UserPoint;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class AuthApiController extends Controller
{
    public function register(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name' => 'required|string|max:100',
            'email' => 'required|email|max:150|unique:users,email',
            'phone' => 'nullable|string|max:30|unique:users,phone',
            'password' => 'required|digits:6|confirmed',
        ], [
            'name.required' => 'Please tell us your name',
            'email.unique' => 'An account with this email already exists — try signing in',
            'phone.unique' => 'An account with this phone number already exists — try signing in',
            'password.confirmed' => 'The passwords do not match',
        ]);

        $user = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'phone' => $data['phone'] ?? null,
            'password' => Hash::make($data['password']),
            'user_type' => 0,
            'status' => 1,
        ]);

        return response()->json([
            'token' => ApiToken::issue($user),
            'user' => $this->userPayload($user),
        ], 201);
    }

    public function login(Request $request): JsonResponse
    {
        $request->validate([
            'login' => 'required|string|min:7|max:100',
            'password' => 'required',
        ], [
            'login.required' => 'Email or phone is required',
            'password.required' => 'Password is required',
        ]);

        $login = (string) $request->input('login');
        $field = filter_var($login, FILTER_VALIDATE_EMAIL) ? 'email' : 'phone';
        $user = User::where($field, $login)->first();

        // One message for every failure; admins (user_type 1) never sign in here.
        if (! $user || $user->status != 1 || $user->user_type != 0
            || ! Auth::validate(['email' => $user->email, 'password' => $request->input('password')])) {
            return response()->json([
                'message' => 'These credentials do not match our records.',
                'errors' => ['login' => ['These credentials do not match our records.']],
            ], 422);
        }

        return response()->json([
            'token' => ApiToken::issue($user),
            'user' => $this->userPayload($user),
        ]);
    }

    public function logout(Request $request): JsonResponse
    {
        if ($plain = $request->bearerToken()) {
            ApiToken::where('token', hash('sha256', $plain))->delete();
        }

        return response()->json(['ok' => true]);
    }

    public function me(): JsonResponse
    {
        return response()->json(['user' => $this->userPayload(auth()->user())]);
    }

    private function userPayload(User $user): array
    {
        return array_merge($user->only(['id', 'name', 'email', 'phone', 'address', 'city', 'postcode']), [
            'points' => UserPoint::balance($user->id),
        ]);
    }
}
