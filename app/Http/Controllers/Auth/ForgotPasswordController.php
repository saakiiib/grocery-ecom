<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password;
use Illuminate\View\View;

class ForgotPasswordController extends Controller
{
    public function __construct()
    {
        $this->middleware('guest');
    }

    public function showLinkRequestForm(): View
    {
        return spa('auth.passwords.email');
    }

    public function sendResetLinkEmail(Request $request): RedirectResponse
    {
        $request->validate([
            'email' => 'required|email|max:150',
        ], [
            'email.required' => 'Email address is required',
            'email.email' => 'Enter a valid email address',
        ]);

        try {
            $status = Password::sendResetLink($request->only('email'));
        } catch (\Throwable $e) {
            report($e);

            return back()->withErrors(['email' => 'We could not send the reset email right now — please try again in a few minutes.']);
        }

        // Unknown addresses read as success — no account probing.
        if ($status === Password::RESET_THROTTLED) {
            return back()->withErrors(['email' => 'Too many reset requests — please wait a few minutes and try again.']);
        }

        return back()->with('status', 'We have emailed your password reset link! Check your inbox (and spam folder).');
    }
}
