<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Contact;
use App\Models\Gallery;
use App\Models\Product;
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
        $productCount = Product::count();
        $categoryCount = Category::count();
        $contactCount = Contact::count();
        $galleryCount = Gallery::count();
        $contactsThisWeek = Contact::where('created_at', '>=', now()->subDays(7))->count();
        $recentContacts = Contact::latest()->limit(5)->get();
        $productsByCategory = Category::withCount('products')->orderByDesc('products_count')->limit(6)->get();

        return view('admin.pages.dashboard', compact('productCount', 'categoryCount', 'contactCount', 'galleryCount', 'contactsThisWeek', 'recentContacts', 'productsByCategory'));
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
