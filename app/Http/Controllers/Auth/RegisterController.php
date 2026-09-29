<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class RegisterController extends Controller
{
    public function __construct()
    {
        $this->middleware('guest');
    }

    public function showRegistrationForm()
    {
        return spa('auth.register');
    }

    public function register(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:100',
            'email' => 'required|email|max:150|unique:users,email',
            'phone' => 'nullable|string|max:30|unique:users,phone',
            'password' => 'required|string|min:8|confirmed',
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

        auth()->login($user);

        // Your bag lives in the session, so it comes with you automatically.
        if ($request->filled('redirect')) {
            return redirect($request->redirect);
        }

        return redirect()->route('account');
    }
}
