@php($announcement = \App\Support\SitePromo::announcement())
@if ($announcement)
    <div class="announce-bar" data-announce-bar data-announce-text="{{ $announcement['text'] }}">
        <div class="container announce-inner">
            <p class="announce-text">
                {{ $announcement['text'] }}
                @if ($announcement['link_text'])
                    <a @spa href="{{ $announcement['link_url'] ?: route('shop.offers') }}" class="announce-link">{{ $announcement['link_text'] }}</a>
                @endif
            </p>
            <button type="button" class="announce-close" data-announce-close aria-label="Dismiss announcement">
                <x-icon name="x" />
            </button>
        </div>
    </div>
@endif
