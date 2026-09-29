@extends('frontend.layout')
@section('title', 'Contact us')

@section('content')
<main>
    <div class="page-hero">
        <div class="container">
            <p class="section-label">Get in touch</p>
            <h1>Contact us</h1>
            <p>Questions about an order, delivery, or our produce? We're here to help.</p>
        </div>
    </div>
    <div class="container" style="max-width:560px;padding-bottom:4rem;">
        <form class="auth-card" onsubmit="return handleContactSubmit(event)">
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
        <div style="margin-top:2rem;text-align:center;color:var(--muted-foreground);font-size:14px;">
            @if ($company->email1)<p><strong>Email:</strong> {{ $company->email1 }}</p>@endif
            @if ($company->phone1)<p style="margin-top:0.35rem;"><strong>Phone:</strong> {{ $company->phone1 }}</p>@endif
            @if ($company->opening_time)<p style="margin-top:0.35rem;"><strong>Hours:</strong> {{ $company->opening_time }}</p>@endif
        </div>
        @if ($company->google_map)
            <div class="contact-map" style="margin-top:2rem;">{!! $company->google_map !!}</div>
            <style>.contact-map iframe{width:100%!important;height:380px!important;border:0!important;border-radius:12px;}</style>
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
