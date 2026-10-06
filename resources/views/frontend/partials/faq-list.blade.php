@php
    $faqGrouped = $faqsJson->groupBy(fn ($f) => $f['badge'] ?? 'General');
@endphp
@if ($faqGrouped->count() > 1)
    <div class="faq-tabs" data-faq-tabs role="tablist" aria-label="Question categories">
        @foreach ($faqGrouped as $label => $items)
            <button type="button" class="faq-tab {{ $loop->first ? 'active' : '' }}" data-faq-tab="{{ $loop->index }}" role="tab">{{ $label }}</button>
        @endforeach
    </div>
@endif
@foreach ($faqGrouped as $label => $items)
    <div class="faq-panel {{ ! $loop->first ? 'is-hidden' : '' }}" data-faq-panel="{{ $loop->index }}">
        @if ($faqGrouped->count() === 1)
            <h2 class="faq-group">{{ $label }}</h2>
        @endif
        @foreach ($items as $f)
            <details class="faq-item" @if ($loop->parent->first && $loop->first) open @endif>
                <summary class="faq-q"><span>{{ $f['q'] }}</span><x-icon name="chevron-down" /></summary>
                <div class="faq-a"><p>{{ $f['a'] }}</p></div>
            </details>
        @endforeach
    </div>
@endforeach
