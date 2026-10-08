@extends('frontend.layout')
@section('title', 'Our story')

@section('content')
<main>
    <section class="hero" style="min-height:420px;">
        <div class="hero-overlay"></div>
        <div class="container">
            <div class="hero-content">
                <span class="hero-badge">Our story</span>
                <h1>Good food starts here.</h1>
                <p>We believe everyday meals deserve ingredients worth looking forward to.</p>
            </div>
        </div>
    </section>

    <section class="trust-bar">
        <div class="container">
            <div class="trust-grid">
                <div class="trust-item">
                    <div class="trust-icon"><x-icon name="truck" /></div>
                    <div><strong>Delivered to your door</strong><span>Free delivery over £50</span></div>
                </div>
                <div class="trust-item">
                    <div class="trust-icon"><x-icon name="leaf" /></div>
                    <div><strong>Picked for freshness</strong><span>Quality you can feel good about</span></div>
                </div>
                <div class="trust-item">
                    <div class="trust-icon"><x-icon name="shield-check" /></div>
                    <div><strong>Shop with confidence</strong><span>Carefully selected, every time</span></div>
                </div>
            </div>
        </div>
    </section>

    <section class="section">
        <div class="container" style="max-width:720px;text-align:center;">
            <p class="section-label">Fresh living, every day</p>
            <h2 class="section-title">A simpler way to bring the market home.</h2>
            @if ($company->about_us)
                <div style="color:var(--muted-foreground);line-height:1.7;margin-top:1rem;">{!! $company->about_us !!}</div>
            @else
                <p style="color:var(--muted-foreground);line-height:1.7;margin-top:1rem;">
                    {{ $company->company_name ?? 'Alam Mini Market' }} began with a simple idea: great ingredients shouldn't be hard to find.
                    We work with growers and makers who care about quality, seasonality, and the people who enjoy their produce.
                </p>
                <p style="color:var(--muted-foreground);line-height:1.7;margin-top:1rem;">
                    We're a UK grocery service focused on freshness, fair pricing, and reliable delivery.
                    Whether you're stocking the week or picking up something for tonight, we're here to make good food the easy choice.
                </p>
            @endif
            <a @spa href="{{ route('shop') }}" class="btn btn-dark" style="margin-top:2rem;">Shop the market</a>
        </div>
    </section>
</main>
@endsection
