<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Offer;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class OfferController extends Controller
{
    public function index()
    {
        $offers = Offer::withCount(['bogos', 'flashes', 'bundles'])
            ->orderBy('sort_order')->orderBy('id')
            ->get();

        return view('admin.offers.index', compact('offers'));
    }

    public function create()
    {
        return view('admin.offers.manage', ['offer' => new Offer(['status' => true])]);
    }

    public function store(Request $request): RedirectResponse
    {
        Offer::create($this->validated($request) + ['sort_order' => (int) Offer::max('sort_order') + 1]);

        return redirect()->route('offers.index')->with('status', 'Offer added — now attach BOGO, flash or bundle items to it.');
    }

    public function edit(int $id)
    {
        $offer = Offer::with(['bogos.product:id,name', 'flashes.product:id,name', 'bundles:id,offer_id,name,required_qty,bundle_price'])->findOrFail($id);

        return view('admin.offers.manage', compact('offer'));
    }

    public function update(Request $request): RedirectResponse
    {
        $offer = Offer::findOrFail($request->input('id'));
        $offer->update($this->validated($request));

        return redirect()->route('offers.index')->with('status', 'Offer saved.');
    }

    public function destroy(int $id): RedirectResponse
    {
        $offer = Offer::findOrFail($id);
        $offer->delete();

        return redirect()->route('offers.index')->with('status', 'Offer deleted — its items are now standalone.');
    }

    public function toggleStatus(Request $request)
    {
        $offer = Offer::findOrFail($request->input('id'));
        $offer->status = ! $offer->status;
        $offer->save();

        return response()->json(['success' => true]);
    }

    /** @return array<string, mixed> */
    private function validated(Request $request): array
    {
        $data = $request->validate([
            'name' => 'required|string|max:120',
            'starts_at' => 'required|date',
            'ends_at' => 'required|date|after:starts_at',
            'is_active' => 'nullable|boolean',
        ]);

        return [
            'name' => $data['name'],
            'starts_at' => $data['starts_at'],
            'ends_at' => $data['ends_at'],
            'status' => $request->boolean('is_active'),
        ];
    }
}
