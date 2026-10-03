<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Foundation\Auth\AuthenticatesUsers;
use Illuminate\Http\Request;

class LoginController extends Controller
{
    use AuthenticatesUsers;

    protected $redirectTo = '/home';

    public function __construct()
    {
        $this->middleware('guest')->except('logout');
        $this->middleware('throttle:5,1')->only('login');
    }

    public function showLoginForm()
    {
        return spa('auth.login');
    }

    public function login(Request $request)
    {
        $rules = [
            'login' => 'required|string|min:7|max:100',
            'password' => 'required',
        ];
        $messages = [
            'login.required' => 'Email or phone is required',
            'password.required' => 'Password is required',
        ];
        $this->validate($request, $rules, $messages);

        $input = $request->only(['login', 'password', 'redirect']);
        $field = filter_var($input['login'], FILTER_VALIDATE_EMAIL) ? 'email' : 'phone';

        $user = User::where($field, $input['login'])->first();

        // One message for every failure: no account / inactive / wrong password oracle.
        if (! $user || $user->status != 1 || ! auth()->attempt(['email' => $user->email, 'password' => $input['password']])) {
            return redirect()->back()
                ->withInput($request->only('login'))
                ->withErrors(['login' => 'These credentials do not match our records.']);
        }

        $redirect = $request->input('redirect');
        if ($redirect && str_starts_with($redirect, '/') && ! str_starts_with($redirect, '//')) {
            return redirect($redirect);
        }
        if (auth()->user()->user_type == '1') {
            return redirect()->route('admin.dashboard');
        }

        return redirect()->route('home');
    }
}
