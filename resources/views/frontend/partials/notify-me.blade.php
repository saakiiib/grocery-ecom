@if (! ($defaultVariant['in_stock'] ?? true))
    <div class="notify-box" data-notify data-type="back_in_stock" data-product="{{ $product->id }}" data-variant="{{ $defaultVariant['id'] ?? '' }}">
        <strong>This pack is out of stock</strong>
        <p>Leave your email and we'll tell you the moment it's back.</p>
        <form data-notify-form novalidate>
            <input type="email" name="email" required maxlength="255" placeholder="you@example.com" aria-label="Email address">
            <button type="submit" class="btn btn-dark btn-sm">Notify me</button>
        </form>
        <p class="notify-msg" data-notify-msg style="display:none;"></p>
    </div>
@endif
<div class="notify-box is-subtle" data-notify data-type="price_drop" data-product="{{ $product->id }}" data-variant="{{ $defaultVariant['id'] ?? '' }}">
    <button type="button" class="notify-toggle" data-notify-toggle><x-icon name="mail" /> Alert me on price drop</button>
    <form data-notify-form style="display:none;" novalidate>
        <input type="email" name="email" required maxlength="255" placeholder="you@example.com" aria-label="Email address">
        <button type="submit" class="btn btn-ghost btn-sm">Watch price</button>
    </form>
    <p class="notify-msg" data-notify-msg style="display:none;"></p>
</div>
