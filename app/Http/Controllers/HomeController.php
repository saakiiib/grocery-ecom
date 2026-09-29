<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\User;
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
        $today = Order::whereDate('created_at', today());
        $stats = [
            'today_orders' => (clone $today)->count(),
            'today_revenue' => (float) (clone $today)->sum('total'),
            'open_orders' => Order::whereIn('status_slug', ['new', 'confirmed', 'packed', 'out_for_delivery'])->count(),
            'customers' => User::where('user_type', 0)->count(),
        ];
        $recent = Order::with('status')->orderByDesc('id')->take(5)->get();

        return view('admin.pages.dashboard', compact('stats', 'recent'));
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
