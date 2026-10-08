@extends('frontend.layout')
@section('title', 'My lists')

@section('content')
<main>
    <div class="page-hero">
        <div class="container">
            <h1>My lists</h1>
            <p>Weekly shop, BBQ night — save it once, fill the bag in one tap.</p>
        </div>
    </div>
    <div class="container" style="padding-bottom:4rem;">
        @if (session('status'))
            <div class="auth-card" style="border-color:#1A2E22;margin-bottom:1.5rem;">{{ session('status') }}</div>
        @endif

        <form method="POST" action="{{ route('lists.store') }}" class="auth-card" style="margin-bottom:1.5rem;display:flex;gap:.6rem;align-items:flex-end;flex-wrap:wrap;">
            @csrf
            <div class="form-group" style="flex:1;min-width:200px;margin:0;">
                <label for="list-name">New list</label>
                <input id="list-name" type="text" name="name" required maxlength="100" placeholder="Weekly shop">
            </div>
            <button type="submit" class="btn btn-dark">Create list</button>
        </form>

        @forelse ($lists as $list)
            <section class="auth-card" style="margin-bottom:1.5rem;">
                <div style="display:flex;justify-content:space-between;align-items:center;gap:.75rem;flex-wrap:wrap;">
                    <h2 style="font-size:1.15rem;margin:0;">{{ $list->name }} <span class="text-muted" style="font-size:.85rem;font-weight:400;">({{ $list->items->count() }} items)</span></h2>
                    <span style="display:flex;gap:.5rem;">
                        <form method="POST" action="{{ route('lists.addAll', $list->id) }}">@csrf<button type="submit" class="btn btn-dark btn-sm">Add all to bag</button></form>
                        <form method="POST" action="{{ route('lists.delete', $list->id) }}" onsubmit="return confirm('Delete this list?');">@csrf @method('DELETE')<button type="submit" class="btn btn-ghost btn-sm">Delete</button></form>
                    </span>
                </div>
                @if ($list->items->isNotEmpty())
                    @foreach ($list->items as $item)
                        <div class="summary-row" style="align-items:center;border-bottom:1px solid var(--border);padding:0.7rem 0;">
                            <span>{{ $item->qty }} × {{ $item->variant?->product?->name ?? 'Unavailable item' }}@if ($item->variant)<br><span class="text-muted">{{ $item->variant->combinationLabel() }}</span>@endif</span>
                            <form method="POST" action="{{ route('lists.items.delete', [$list->id, $item->id]) }}">@csrf @method('DELETE')<button type="submit" class="btn btn-ghost btn-sm">Remove</button></form>
                        </div>
                    @endforeach
                @else
                    <p class="text-muted" style="font-size:14px;margin:.75rem 0 0;">Empty — open any product and save its pack here. <a @spa href="{{ route('shop') }}">Browse the shop</a></p>
                @endif
            </section>
        @empty
            <div class="empty-state">
                <h2>No lists yet</h2>
                <p>Create your first list above — your weekly shop, done in seconds.</p>
            </div>
        @endforelse
    </div>
</main>
@endsection
