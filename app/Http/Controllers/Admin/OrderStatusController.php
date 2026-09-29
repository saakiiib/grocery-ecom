<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\OrderStatus;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class OrderStatusController extends Controller
{
    public function index()
    {
        $statuses = OrderStatus::orderBy('sort_order')->orderBy('id')->get();

        return view('admin.order-statuses.index', compact('statuses'));
    }

    public function edit(int $id)
    {
        $status = OrderStatus::findOrFail($id);

        return view('admin.order-statuses.manage', compact('status'));
    }

    public function update(Request $request): RedirectResponse
    {
        $status = OrderStatus::findOrFail($request->input('id'));

        $data = $request->validate([
            'name' => 'required|string|max:100',
            'color' => 'required|regex:/^#[0-9a-fA-F]{6}$/',
            'sort_order' => 'nullable|integer|min:0',
            'is_active' => 'nullable|boolean',
        ]) + ['is_active' => $request->boolean('is_active')];

        $status->update($data);

        return redirect()->route('order-statuses.index')->with('status', 'Status updated — orders using it update everywhere automatically.');
    }

    public function toggleStatus(Request $request)
    {
        $status = OrderStatus::findOrFail($request->input('id'));
        $status->is_active = ! $status->is_active;
        $status->save();

        return response()->json(['success' => true]);
    }
}
