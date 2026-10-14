<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\View\View;

class ResetPasswordController extends Controller
{
    public function __construct()
    {
        $this->middleware('guest');
    }

    public function showResetForm(Request $request, string $token): View
    {
        $email = (string) ($request->query('email', '') ?? '');

        return spa('auth.passwords.reset', compact('token', 'email'));
    }

    public function reset(Request $request): RedirectResponse
    {
        $request->validate([
            'token' => 'required|string',
            'email' => 'required|email|max:150',
            'password' => 'required|string|min:8|max:72|confirmed',
        ], [
            'email.required' => 'Email address is required',
            'email.email' => 'Enter a valid email address',
            'password.min' => 'Use at least 8 characters',
            'password.confirmed' => 'The passwords do not match',
        ]);

        $status = Password::reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function ($user, string $password): void {
                $user->forceFill(['password' => Hash::make($password)])->save();
            }
        );

        if ($status !== Password::PASSWORD_RESET) {
            return back()
                ->withInput($request->only('email'))
                ->withErrors(['email' => 'This reset link is invalid or has expired — please request a new one.']);
        }

        return redirect()->route('login')->with('status', 'Your password has been reset! Sign in with your new password.');
    }
}
