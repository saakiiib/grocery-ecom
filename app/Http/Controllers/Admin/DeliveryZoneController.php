<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\DeliveryZone;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class DeliveryZoneController extends Controller
{
    public function index()
    {
        $zones = DeliveryZone::withCount('postcodes')->ordered()->get();

        return view('admin.delivery-zones.index', compact('zones'));
    }

    public function create()
    {
        return view('admin.delivery-zones.manage', ['zone' => new DeliveryZone(['is_active' => true])]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request);
        $zone = DeliveryZone::create([
            'name' => $data['name'],
            'sort_order' => $data['sort_order'] ?? ((int) DeliveryZone::max('sort_order') + 1),
            'is_active' => $data['is_active'],
        ]);
        $this->syncPrefixes($zone, $data['prefixes']);

        return redirect()->route('delivery-zones.index')->with('status', 'Delivery zone added.');
    }

    public function edit(int $id)
    {
        $zone = DeliveryZone::with('postcodes')->findOrFail($id);

        return view('admin.delivery-zones.manage', compact('zone'));
    }

    public function update(Request $request): RedirectResponse
    {
        $zone = DeliveryZone::findOrFail($request->input('id'));
        $data = $this->validated($request);
        $zone->update([
            'name' => $data['name'],
            'sort_order' => $data['sort_order'] ?? $zone->sort_order,
            'is_active' => $data['is_active'],
        ]);
        $this->syncPrefixes($zone, $data['prefixes']);

        return redirect()->route('delivery-zones.index')->with('status', 'Delivery zone saved.');
    }

    public function destroy(int $id): RedirectResponse
    {
        DeliveryZone::findOrFail($id)->delete();

        return redirect()->route('delivery-zones.index')->with('status', 'Delivery zone deleted.');
    }

    public function toggleStatus(Request $request)
    {
        $zone = DeliveryZone::findOrFail($request->input('id'));
        $zone->is_active = ! $zone->is_active;
        $zone->save();

        return response()->json(['success' => true]);
    }

    /** @return array<string, mixed> */
    private function validated(Request $request): array
    {
        return $request->validate([
            'name' => 'required|string|max:100',
            'prefixes' => 'nullable|string|max:2000',
            'sort_order' => 'nullable|integer|min:0',
            'is_active' => 'nullable|boolean',
        ]) + ['is_active' => $request->boolean('is_active')];
    }

    /** Replace the zone's prefix list (one outward code per line, e.g. LS1). */
    private function syncPrefixes(DeliveryZone $zone, ?string $raw): void
    {
        $prefixes = collect(preg_split('/\r\n|\r|\n/', $raw ?? ''))
            ->map(fn ($p) => DeliveryZone::normalize($p))
            ->filter()
            ->unique()
            ->values();

        $zone->postcodes()->delete();
        foreach ($prefixes as $prefix) {
            $zone->postcodes()->create(['prefix' => substr($prefix, 0, 10)]);
        }
    }
}
