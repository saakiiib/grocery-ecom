<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\DeliverySlot;
use App\Models\Order;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class DeliverySlotController extends Controller
{
    public function index()
    {
        $slots = DeliverySlot::orderBy('sort_order')->orderBy('id')->get();

        return view('admin.delivery-slots.index', compact('slots'));
    }

    public function create()
    {
        return view('admin.delivery-slots.manage', ['slot' => new DeliverySlot(['cutoff_hour' => 20, 'is_active' => true])]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request);
        $data['sort_order'] = $data['sort_order'] ?? ((int) DeliverySlot::max('sort_order') + 1);
        DeliverySlot::create($data);

        return redirect()->route('delivery-slots.index')->with('status', 'Delivery slot added.');
    }

    public function edit(int $id)
    {
        $slot = DeliverySlot::findOrFail($id);

        return view('admin.delivery-slots.manage', compact('slot'));
    }

    public function update(Request $request): RedirectResponse
    {
        $slot = DeliverySlot::findOrFail($request->input('id'));
        $slot->update($this->validated($request));

        return redirect()->route('delivery-slots.index')->with('status', 'Delivery slot saved.');
    }

    public function destroy(int $id): RedirectResponse
    {
        $slot = DeliverySlot::findOrFail($id);

        if (Order::where('delivery_slot_id', $slot->id)->exists()) {
            return redirect()->route('delivery-slots.index')->with('error', 'That slot has orders — deactivate it instead of deleting.');
        }
        $slot->delete();

        return redirect()->route('delivery-slots.index')->with('status', 'Delivery slot deleted.');
    }

    public function toggleStatus(Request $request)
    {
        $slot = DeliverySlot::findOrFail($request->input('id'));
        $slot->is_active = ! $slot->is_active;
        $slot->save();

        return response()->json(['success' => true]);
    }

    /** @return array<string, mixed> */
    private function validated(Request $request): array
    {
        return $request->validate([
            'name' => 'required|string|max:100',
            'starts_at' => 'required|date_format:H:i',
            'ends_at' => 'required|date_format:H:i|after:starts_at',
            'fee' => 'required|numeric|min:0|max:999',
            'cutoff_hour' => 'required|integer|min:0|max:23',
            'sort_order' => 'nullable|integer|min:0',
            'is_active' => 'nullable|boolean',
        ]) + ['is_active' => $request->boolean('is_active')];
    }
}
