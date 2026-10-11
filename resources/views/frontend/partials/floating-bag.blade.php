@php
    $bagCount = \App\Http\Controllers\BagController::count();
@endphp
<a @spa href="{{ route('bag') }}" class="float-bag" aria-label="Shopping bag">
    <x-icon name="shopping-bag" />
    <span class="float-bag-count" data-bag-count>{{ $bagCount }}</span>
</a>
