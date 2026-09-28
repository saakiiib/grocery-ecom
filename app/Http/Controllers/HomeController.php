<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Auth;

class HomeController extends Controller
{
    public function dashboard()
    {
        if (Auth::check()) {
            $user = auth()->user();

            if ($user->user_type == '1') {
                return redirect()->route('admin.dashboard');
            } elseif ($user->user_type == '0') {
                return redirect()->route('home');
            }
        } else {
            return redirect()->route('login');
        }
    }

    public function adminHome()
    {
        return view('admin.pages.dashboard');
    }

    public function managerHome()
    {
        return 'manager';
    }

    public function userHome()
    {
        return 'user';
    }
}
