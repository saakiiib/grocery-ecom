@php
    $waNumber = preg_replace('/\D/', '', (string) ($company->whatsapp ?? ''));
    $callNumber = preg_replace('/\s+/', '', (string) ($company->phone1 ?? ''));
    $safeSocialUrl = function ($url, $hosts) {
        if (! $url) {
            return null;
        }
        $parts = parse_url((string) $url) ?: [];
        $host = strtolower($parts['host'] ?? '');
        if (($parts['scheme'] ?? '') !== 'https' || ! in_array($host, $hosts, true)) {
            return null;
        }
        return $url;
    };
    $messengerUrl = $safeSocialUrl(\App\Models\Setting::get('messenger_url', ''), ['m.me', 'messenger.com', 'www.messenger.com']);
    $chatLinks = array_values(array_filter([
        ['icon' => 'whatsapp', 'url' => $waNumber ? 'https://wa.me/' . $waNumber : null, 'label' => 'WhatsApp', 'cls' => 'is-wa'],
        ['icon' => 'messenger', 'url' => $messengerUrl, 'label' => 'Messenger', 'cls' => 'is-msgr'],
        ['icon' => 'phone', 'url' => $callNumber ? 'tel:' . $callNumber : null, 'label' => 'Call us', 'cls' => 'is-call'],
        ['icon' => 'mail', 'url' => $company->email1 ? 'mailto:' . $company->email1 : null, 'label' => 'Email us', 'cls' => 'is-mail'],
        ['icon' => 'facebook', 'url' => $safeSocialUrl($company->facebook, ['facebook.com', 'www.facebook.com']), 'label' => 'Facebook', 'cls' => 'is-fb'],
        ['icon' => 'instagram', 'url' => $safeSocialUrl($company->instagram, ['instagram.com', 'www.instagram.com']), 'label' => 'Instagram', 'cls' => 'is-ig'],
        ['icon' => 'twitter', 'url' => $safeSocialUrl($company->twitter, ['x.com', 'www.x.com', 'twitter.com', 'www.twitter.com']), 'label' => 'X', 'cls' => 'is-x'],
        ['icon' => 'youtube', 'url' => $safeSocialUrl($company->youtube, ['youtube.com', 'www.youtube.com', 'youtu.be']), 'label' => 'YouTube', 'cls' => 'is-yt'],
    ], fn ($l) => ! empty($l['url'])));
@endphp
@if (! empty($chatLinks))
    <div class="float-contact" data-float-contact>
        <div class="float-panel" data-float-panel hidden>
            @foreach ($chatLinks as $l)
                <a href="{{ $l['url'] }}" target="_blank" rel="noopener" class="float-link {{ $l['cls'] }}" aria-label="{{ $l['label'] }}" title="{{ $l['label'] }}">
                    <x-icon name="{{ $l['icon'] }}" />
                    <span>{{ $l['label'] }}</span>
                </a>
            @endforeach
        </div>
        <button type="button" class="float-main" onclick="toggleFloatContact()" aria-label="Chat with us" aria-expanded="false">
            <span class="float-main-open"><x-icon name="message-circle" /></span>
            <span class="float-main-close" hidden><x-icon name="x" /></span>
        </button>
    </div>
    <script>
        var floatContactOpen = false;
        function toggleFloatContact() {
            var wrap = document.querySelector('[data-float-contact]');
            if (!wrap) return;
            var panel = wrap.querySelector('[data-float-panel]');
            var btn = wrap.querySelector('.float-main');
            floatContactOpen = !floatContactOpen;
            panel.hidden = !floatContactOpen;
            wrap.classList.toggle('open', floatContactOpen);
            btn.setAttribute('aria-expanded', floatContactOpen ? 'true' : 'false');
            btn.querySelector('.float-main-open').hidden = floatContactOpen;
            btn.querySelector('.float-main-close').hidden = !floatContactOpen;
        }
    </script>
@endif
