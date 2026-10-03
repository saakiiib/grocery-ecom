@extends('admin.pages.master')
@section('title', ($offer->exists ? 'Manage' : 'Add') . ' Offer')

@section('content')

    <div class="container-fluid">
        <div class="row justify-content-center">
            <div class="col-xl-10">
                <div class="card">
                    <div class="card-header"><h4 class="card-title mb-0">{{ $offer->exists ? 'Manage' : 'Add New' }} Offer</h4></div>
                    <div class="card-body">
                        <form method="POST" action="{{ $offer->exists ? route('offers.update') : route('offers.store') }}">
                            @csrf
                            @if ($offer->exists)<input type="hidden" name="id" value="{{ $offer->id }}">@endif
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label">Name</label>
                                    <input type="text" name="name" class="form-control" required maxlength="120" value="{{ old('name', $offer->name) }}" placeholder="e.g. Weekend Deals">
                                    @error('name')<p class="text-danger small mt-1">{{ $message }}</p>@enderror
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label">Starts at</label>
                                    <input type="datetime-local" name="starts_at" class="form-control" required value="{{ old('starts_at', $offer->starts_at?->format('Y-m-d\TH:i')) }}">
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label">Ends at</label>
                                    <input type="datetime-local" name="ends_at" class="form-control" required value="{{ old('ends_at', $offer->ends_at?->format('Y-m-d\TH:i')) }}">
                                    @error('ends_at')<p class="text-danger small mt-1">{{ $message }}</p>@enderror
                                </div>
                                <div class="col-md-12">
                                    <div class="form-check form-switch" dir="ltr">
                                        <input type="checkbox" name="is_active" value="1" class="form-check-input" {{ old('is_active', $offer->status ?? true) ? 'checked' : '' }}>
                                        <label class="form-check-label">Active (switching off pauses every item at once)</label>
                                    </div>
                                </div>
                                <div class="col-md-12">
                                    <button type="submit" class="btn btn-primary">Save offer</button>
                                    <a href="{{ route('offers.index') }}" class="btn btn-secondary">Back to offers</a>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>

                @if ($offer->exists)
                    <div class="card">
                        <div class="card-header d-flex align-items-center">
                            <h4 class="card-title mb-0 flex-grow-1">Items under this offer</h4>
                            <a href="{{ route('bogo.create', ['offer_id' => $offer->id]) }}" class="btn btn-sm btn-outline-success me-1">+ BOGO</a>
                            <a href="{{ route('flash.create', ['offer_id' => $offer->id]) }}" class="btn btn-sm btn-outline-danger me-1">+ Flash</a>
                            <a href="{{ route('bundles.create', ['offer_id' => $offer->id]) }}" class="btn btn-sm btn-outline-primary">+ Bundle</a>
                        </div>
                        <div class="card-body">
                            @php $groups = ['BOGO' => $offer->bogos, 'Flash' => $offer->flashes, 'Bundle' => $offer->bundles]; @endphp
                            @foreach ($groups as $type => $items)
                                @if ($items->isNotEmpty())
                                    <h6 class="mt-2">{{ $type }}</h6>
                                    <ul class="list-group mb-2">
                                        @foreach ($items as $item)
                                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                                <span>
                                                    @if ($type === 'BOGO'){{ $item->product?->name ?? '—' }} — Buy {{ $item->buy_qty }} Get {{ $item->free_qty }} FREE
                                                    @elseif ($type === 'Flash'){{ $item->product?->name ?? '—' }} — £{{ number_format($item->promo_price, 2) }}
                                                    @else{{ $item->name }} — Any {{ $item->required_qty }} for £{{ number_format($item->bundle_price, 2) }}
                                                    @endif
                                                </span>
                                                <span>
                                                    @if ($type === 'BOGO')<a href="{{ route('bogo.edit', $item->id) }}" class="btn btn-sm btn-soft-secondary">Edit</a>
                                                    @elseif ($type === 'Flash')<a href="{{ route('flash.edit', $item->id) }}" class="btn btn-sm btn-soft-secondary">Edit</a>
                                                    @else<a href="{{ route('bundles.edit', $item->id) }}" class="btn btn-sm btn-soft-secondary">Edit</a>
                                                    @endif
                                                </span>
                                            </li>
                                        @endforeach
                                    </ul>
                                @endif
                            @endforeach
                            @if ($offer->bogos->isEmpty() && $offer->flashes->isEmpty() && $offer->bundles->isEmpty())
                                <p class="text-muted mb-0">Nothing attached yet — use the buttons above. Items without dates follow this offer's window.</p>
                            @endif
                        </div>
                    </div>
                @endif
            </div>
        </div>
    </div>

@endsection
