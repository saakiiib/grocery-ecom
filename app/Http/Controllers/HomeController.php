<?php

namespace App\Http\Controllers;

use App\Models\Contact;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\OrderStatus;
use App\Models\ProductVariant;
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
        $openSlugs = OrderStatus::where('is_active', true)->where('is_final', false)->pluck('slug')->all();
        $stats = [
            'today_orders' => (clone $today)->count(),
            'today_revenue' => (float) (clone $today)->whereNotIn('status_slug', ['cancelled'])->sum('total'),
            'open_orders' => Order::whereIn('status_slug', $openSlugs ?: ['new'])->count(),
            'customers' => User::where('user_type', 0)->count(),
        ];
        $needs = [
            'unpaid_online' => Order::where('payment_method', '!=', 'cod')
                ->whereNotIn('payment_status', ['paid', 'partially_refunded'])
                ->whereNotIn('status_slug', ['cancelled', 'delivered'])
                ->count(),
            'out_of_stock' => ProductVariant::where('in_stock', false)->count(),
            'new_messages' => Contact::where('created_at', '>=', now()->subDays(7))->count(),
        ];
        $statusCounts = Order::selectRaw('status_slug, COUNT(*) as c')->groupBy('status_slug')->pluck('c', 'status_slug')->all();
        $orderStatuses = OrderStatus::ordered();
        $weekRevenue = (float) Order::where('created_at', '>=', now()->subDays(7))->whereNotIn('status_slug', ['cancelled'])->sum('total');
        $topProducts = OrderItem::selectRaw('product_name, SUM(qty) as units, SUM(line_total) as revenue')
            ->where('status', 'ok')
            ->whereHas('order', fn ($q) => $q->where('created_at', '>=', now()->subDays(7))->whereNotIn('status_slug', ['cancelled']))
            ->groupBy('product_name')
            ->orderByDesc('units')
            ->take(5)
            ->get();

        return view('admin.pages.dashboard', compact('stats', 'needs', 'statusCounts', 'orderStatuses', 'weekRevenue', 'topProducts'));
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
