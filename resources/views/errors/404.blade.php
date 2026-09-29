@extends('frontend.layout')
@section('title', 'Page not found')

@section('content')
<main>
    <div class="container" style="padding-bottom:4rem;">
        <div class="empty-state">
            <p class="text-muted" style="letter-spacing:0.25em;font-size:12px;">— 404 —</p>
            <h2>That page is not on our shelves.</h2>
            <p>The page you are looking for does not exist or was moved. Let's get you back to the fresh stuff.</p>
            <div style="display:flex;gap:0.75rem;justify-content:center;flex-wrap:wrap;">
                <a @spa href="{{ route('home') }}" class="btn btn-dark">Go home</a>
                <a @spa href="{{ route('collections') }}" class="btn btn-ghost">Shop groceries</a>
            </div>
        </div>
    </div>
</main>
@endsection
