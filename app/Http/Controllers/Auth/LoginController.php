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
    }

    public function showLoginForm()
    {
        return spa('auth.login');
    }

    public function login(Request $request)
    {
        $input = $request->all();
        $field = filter_var($input['login'], FILTER_VALIDATE_EMAIL) ? 'email' : 'phone';

        $rules = [
            'login' => 'required|string',
            'password' => 'required',
        ];
        $messages = [
            'login.required' => 'Email or phone is required',
            'password.required' => 'Password is required',
        ];

        if ($field === 'phone') {
            $rules['login'] = 'required|string|digits:11';
            $messages['login.digits'] = 'Phone must be 11 digits';
        } else {
            $rules['login'] = 'required|email';
            $messages['login.email'] = 'Please enter a valid email';
        }

        $this->validate($request, $rules, $messages);

        $user = User::where($field, $input['login'])->first();

        if ($user) {
            if ($user->status == 1) {
                if (auth()->attempt(['email' => $user->email, 'password' => $input['password']])) {
                    if ($request->has('redirect')) {
                        return redirect($request->redirect);
                    }
                    if (auth()->user()->user_type == '1') {
                        return redirect()->route('admin.dashboard');
                    } elseif (auth()->user()->user_type == '0') {
                        return redirect()->route('home');
                    }
                } else {
                    return redirect()->back()
                        ->withInput($request->only('login'))
                        ->withErrors(['password' => 'Password is incorrect']);
                }
            } else {
                return redirect()->back()
                    ->withInput($request->only('login'))
                    ->withErrors(['login' => 'Your account is inactive']);
            }
        } else {
            return redirect()->back()
                ->withInput($request->only('login'))
                ->withErrors(['login' => 'Email or password is incorrect']);
        }
    }
}
