<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Subscriber;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class SubscriberController extends Controller
{
    public function index()
    {
        $subscribers = Subscriber::orderByDesc('created_at')->paginate(25);

        return view('admin.subscribers.index', compact('subscribers'));
    }

    public function toggleStatus(Request $request): RedirectResponse
    {
        $subscriber = Subscriber::findOrFail($request->input('id'));
        $subscriber->update(['is_active' => ! $subscriber->is_active]);

        return redirect()->route('subscribers.index')->with('status', 'Subscriber updated.');
    }

    public function destroy($id): RedirectResponse
    {
        Subscriber::findOrFail($id)->delete();

        return redirect()->route('subscribers.index')->with('status', 'Subscriber deleted.');
    }
}
