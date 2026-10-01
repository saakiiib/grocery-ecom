@extends('frontend.layout')
@section('title', 'Contact us')

@php
    $address = collect([$company->address1, $company->address2, $company->address3])->filter()->implode(', ');
    $phones = collect([$company->phone1, $company->phone2])->filter()->values();
    $emails = collect([$company->email1, $company->email2])->filter()->values();
@endphp

@section('content')
<main>
    <div class="page-hero">
        <div class="container">
            <p class="section-label">Get in touch</p>
            <h1>Contact us</h1>
            <p>Questions about an order, delivery, or our produce? We're here to help.</p>
        </div>
    </div>
    <div class="container" style="padding-bottom:4rem;">
        <div class="contact-grid">
            <div class="auth-card">
                <h2 style="font-size:1.25rem;margin-bottom:1.25rem;">Visit or call</h2>
                @if ($address)
                    <div class="info-row">
                        <x-icon name="map-pin" />
                        <span>{{ $address }}</span>
                    </div>
                @endif
                @foreach ($phones as $phone)
                    <div class="info-row">
                        <x-icon name="phone" />
                        <a href="tel:{{ preg_replace('/\s/', '', $phone) }}">{{ $phone }}</a>
                    </div>
                @endforeach
                @foreach ($emails as $email)
                    <div class="info-row">
                        <x-icon name="mail" />
                        <a href="mailto:{{ $email }}">{{ $email }}</a>
                    </div>
                @endforeach
                @if ($company->opening_time)
                    <div class="info-row">
                        <x-icon name="clock" />
                        <span>{{ $company->opening_time }}</span>
                    </div>
                @endif
                @if ($company->whatsapp)
                    <a class="btn btn-dark btn-block" style="margin-top:1rem;" href="https://wa.me/{{ preg_replace('/\D/', '', $company->whatsapp) }}" target="_blank" rel="noopener"><x-icon name="message-circle" />Chat on WhatsApp</a>
                @endif
            </div>
            <form class="auth-card" style="margin:0;" onsubmit="return handleContactSubmit(event)">
                <h2 style="font-size:1.25rem;margin-bottom:1.25rem;">Send a message</h2>
                <div id="contact-form-fields">
                    <div class="form-group">
                        <label for="contact-name">Name</label>
                        <input type="text" id="contact-name" required placeholder="Your name">
                    </div>
                    <div class="form-group">
                        <label for="contact-email">Email</label>
                        <input type="email" id="contact-email" required placeholder="you@example.com">
                    </div>
                    <div class="form-group">
                        <label for="contact-topic">Topic</label>
                        <input type="text" id="contact-topic" placeholder="How can we help?">
                    </div>
                    <div class="form-group">
                        <label for="contact-postcode">Postcode</label>
                        <input type="text" id="contact-postcode" placeholder="e.g. SW1A 1AA">
                    </div>
                    <div class="form-group">
                        <label for="contact-message">Message</label>
                        <textarea id="contact-message" required placeholder="Tell us a little more…"></textarea>
                    </div>
                    <button type="submit" class="btn btn-dark btn-block">Send message</button>
                </div>
                <div id="contact-form-success" style="display:none;text-align:center;padding:2rem 0;">
                    <h3>Message received.</h3>
                    <p class="text-muted">Thank you — we'll reply within one working day.</p>
                </div>
            </form>
        </div>
        @if ($company->google_map)
            <div class="map-embed" style="margin-top:1.5rem;">{!! $company->google_map !!}</div>
        @endif
    </div>
</main>
@endsection

@section('script')
<script>
    function handleContactSubmit(e) {
      e.preventDefault();
      var csrf = (document.querySelector('meta[name="csrf-token"]') || {}).content || '';
      var form = e.target;
      var btn = form.querySelector('button[type="submit"]');
      if (btn) btn.disabled = true;
      fetch("{{ route('contact.store') }}", {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json' },
        body: JSON.stringify({
          name: document.getElementById('contact-name').value,
          email: document.getElementById('contact-email').value,
          topic: document.getElementById('contact-topic').value,
          postcode: document.getElementById('contact-postcode').value,
          message: document.getElementById('contact-message').value,
        }),
      }).then(function (r) {
        if (btn) btn.disabled = false;
        if (!r.ok) {
          return r.json().catch(function () { return {}; }).then(function (d) {
            var msg = d.message || (d.errors ? Object.values(d.errors)[0][0] : 'Please check the form.');
            if (window.EGF) EGF.showToast(msg);
          });
        }
        document.getElementById('contact-form-fields').style.display = 'none';
        document.getElementById('contact-form-success').style.display = 'block';
        window.scrollTo({ top: 0, behavior: 'smooth' });
      }).catch(function () {
        if (btn) btn.disabled = false;
        if (window.EGF) EGF.showToast('Something went wrong. Please try again.');
      });
      return false;
    }
</script>
@endsection
