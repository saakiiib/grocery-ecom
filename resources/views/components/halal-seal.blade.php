@props([])
{{-- Halal certification mark. Artwork: "Halal logo" by Ayzkao, released into the public domain
     (Wikimedia Commons, File:Halal_logo.svg). Served locally from our own images folder. --}}
<img src="{{ asset('resources/frontend/images/halal-logo.svg') }}" alt="Halal" loading="lazy" decoding="async" {{ $attributes->merge(['class' => 'halal-svg']) }}>
