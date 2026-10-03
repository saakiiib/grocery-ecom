<?php

namespace App\Http\Controllers;

use App\Models\Address;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class AddressController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    /** Save a new book entry (first entry becomes both defaults). */
    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request);

        $address = auth()->user()->addresses()->create($data);
        $this->applyDefaults($address, $request->boolean('is_default_delivery'), $request->boolean('is_default_billing'), true);

        return redirect()->to(route('account').'#addresses')->with('status', 'Address saved to your book.');
    }

    /** Update one of the shopper's own entries. */
    public function update(Request $request, int $id): RedirectResponse
    {
        $address = $this->ownAddress($id);
        $address->update($this->validated($request));
        $this->applyDefaults($address, $request->boolean('is_default_delivery'), $request->boolean('is_default_billing'), false);

        return redirect()->to(route('account').'#addresses')->with('status', 'Address updated.');
    }

    /** Remove one of the shopper's own entries (defaults roll to the oldest left). */
    public function destroy(int $id): RedirectResponse
    {
        $address = $this->ownAddress($id);
        $wasDelivery = $address->is_default_delivery;
        $wasBilling = $address->is_default_billing;
        $address->delete();

        $next = auth()->user()->addresses()->oldest()->first();
        if ($next) {
            if ($wasDelivery) {
                $next->update(['is_default_delivery' => true]);
            }
            if ($wasBilling) {
                $next->update(['is_default_billing' => true]);
            }
        }

        return redirect()->to(route('account').'#addresses')->with('status', 'Address removed.');
    }

    /** Mark one entry as the default delivery and/or billing address. */
    public function setDefault(Request $request, int $id): RedirectResponse
    {
        $data = $request->validate(['type' => 'required|in:delivery,billing']);
        $address = $this->ownAddress($id);

        auth()->user()->addresses()->update(['is_default_'.$data['type'] => false]);
        $address->update(['is_default_'.$data['type'] => true]);

        return redirect()->to(route('account').'#addresses')->with('status', 'Default address updated.');
    }

    private function ownAddress(int $id): Address
    {
        return Address::where('user_id', auth()->id())->findOrFail($id);
    }

    /**
     * @return array{label: string, name: string, phone: string, address: string, city: string, postcode: string}
     */
    private function validated(Request $request): array
    {
        $validator = Validator::make($request->all(), [
            'label' => 'required|string|max:50',
            'name' => 'required|string|max:100',
            'phone' => 'required|string|max:30',
            'address' => 'required|string|max:500',
            'city' => 'required|string|max:100',
            'postcode' => 'required|string|max:20',
        ]);

        if ($validator->fails()) {
            redirect()->to(route('account').'#addresses')->withErrors($validator)->withInput()->throwResponse();
        }

        return $validator->validated();
    }

    private function applyDefaults(Address $address, bool $delivery, bool $billing, bool $isNew): void
    {
        $user = auth()->user();
        if ($isNew && $user->addresses()->count() === 1) {
            $delivery = true;
            $billing = true;
        }
        if ($delivery) {
            $user->addresses()->where('id', '!=', $address->id)->update(['is_default_delivery' => false]);
        }
        if ($billing) {
            $user->addresses()->where('id', '!=', $address->id)->update(['is_default_billing' => false]);
        }
        $address->update(['is_default_delivery' => $delivery, 'is_default_billing' => $billing]);
    }
}
