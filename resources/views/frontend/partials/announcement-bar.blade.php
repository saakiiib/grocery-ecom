@php($announcement = \App\Support\SitePromo::announcement())
@if ($announcement)
    <div class="announce-bar" data-announce-bar data-announce-text="{{ $announcement['text'] }}">
        <div class="container announce-inner">
            <p class="announce-text">
                {{ $announcement['text'] }}
                @if ($announcement['link_text'])
                    <?php $au = $announcement['link_url'] ?: route('shop.offers'); ?>
                    @if (is_string($au) && str_starts_with($au, 'http') && ! str_starts_with($au, url('/')))<a href="{{ $au }}" class="announce-link">{{ $announcement['link_text'] }}</a>@else<a @spa href="{{ $au }}" class="announce-link">{{ $announcement['link_text'] }}</a>@endif
                @endif
            </p>
            <button type="button" class="announce-close" data-announce-close aria-label="Dismiss announcement">
                <x-icon name="x" />
            </button>
        </div>
    </div>
@endif
