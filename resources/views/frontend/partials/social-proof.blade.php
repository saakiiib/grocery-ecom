@php($proof = \App\Support\SitePromo::socialProof())
@if ($proof)
    <div class="proof-toast" data-proof hidden>
        <script type="application/json" data-proof-data>@json($proof)</script>
        <a @spa href="#" class="proof-card" data-proof-link>
            <span class="proof-avatar"><x-icon name="user" /></span>
            <span class="proof-text" data-proof-text></span>
        </a>
        <button type="button" class="proof-close" data-proof-close aria-label="Dismiss">
            <x-icon name="x" />
        </button>
    </div>
@endif
