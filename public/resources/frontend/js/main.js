/* Frontend JS - Laravel Backend Functions */

// ===== SPA SEARCH =====
function spaSearch(form) {
    var input = form.querySelector('input[name="search"]');
    var q = input.value.trim();
    var dropdown = document.getElementById('searchDropdown');
    if (dropdown) dropdown.classList.remove('show');
    if (!q) return false;
    var url = form.action + '?search=' + encodeURIComponent(q);
    input.value = '';
    if (typeof window.spaNavigate === 'function') {
        window.spaNavigate(url, {push: true, scroll: true});
    } else {
        window.location.href = url;
    }
    return false;
}
function initAnnouncementHide() {
    var announcementBar = document.querySelector('.announcement');
    if (!announcementBar) return;
    function checkAnnouncement() {
        if (window.scrollY > 80) {
            announcementBar.classList.add('hide-bar');
        } else {
            announcementBar.classList.remove('hide-bar');
        }
    }
    checkAnnouncement();
    window.removeEventListener('scroll', checkAnnouncement);
    window.addEventListener('scroll', checkAnnouncement, {passive: true});
}

// ===== CART FUNCTIONS =====
function addToCart(id, qty = 1) {
    $.post('/cart/add', { _token: $('meta[name="csrf-token"]').attr('content'), id: id, qty: qty }, function(res) {
        if (res.success) {
            $('[data-cart-count-badge]').text(res.count).removeClass('pulse').addClass('pulse');
            showSmartNotify({
                type: 'cart',
                title: 'Added to Cart',
                message: res.message,
                image: res.product_image || null,
                productName: res.product_name || '',
                actions: [
                    { label: 'View Cart', url: '/cart', style: 'primary' },
                    { label: 'Continue Shopping', url: '/shop', style: 'ghost' }
                ]
            });
        }
    }).fail(function(xhr) {
        var msg = xhr.responseJSON?.message || 'Failed to add to cart';
        showSmartNotify({ type: 'error', title: 'Oops!', message: msg });
    });
}

function updateCartQty(id, qty) {
    var row = $('#cart-row-' + id);
    if (!row.length) return;
    $.post('/cart/update', { _token: $('meta[name="csrf-token"]').attr('content'), id: id, qty: qty }, function(res) {
        if (res.success) {
            if (qty <= 0) {
                row.fadeOut(300, function() { $(this).remove(); updateCartTotal(); });
            } else {
                var price = parseFloat(row.data('price'));
                var newTotal = price * qty;
                row.attr('data-subtotal', newTotal);
                row.find('.cart-qty-input').val(qty);
                row.find('.cart-item-total').text('৳ ' + newTotal.toFixed(2));
                updateCartTotal();
            }
            $('[data-cart-count-badge]').text(res.count);
        }
    }).fail(function(xhr) {
        var msg = xhr.responseJSON?.message || 'Error updating cart';
        showSmartNotify({ type: 'error', title: 'Oops!', message: msg });
    });
}

function removeCartItem(id) {
    $.post('/cart/remove', { _token: $('meta[name="csrf-token"]').attr('content'), id: id }, function(res) {
        if (res.success) {
            $('#cart-row-' + id).fadeOut(300, function() { $(this).remove(); updateCartTotal(); });
            $('[data-cart-count-badge]').text(res.count);
            showSmartNotify({ type: 'cart', title: 'Removed', message: res.message });
        }
    });
}

function updateCartTotal() {
    var total = 0;
    var visibleRows = 0;
    $('#cartTable tbody tr').each(function() {
        if ($(this).is(':visible')) {
            total += parseFloat($(this).attr('data-subtotal') || 0);
            visibleRows++;
        }
    });
    var formatted = '৳ ' + total.toFixed(0);
    $('#cartTotal').text(formatted);
    $('#cartTotalBottom').text(formatted);
    if (visibleRows === 0) {
        $('#cartContent').html('<div class="text-center p-5"><div style="width:80px;height:80px;border-radius:50%;background:rgba(21,147,165,.08);display:inline-flex;align-items:center;justify-content:center;margin-bottom:16px"><i class="bi bi-bag" style="font-size:2rem;color:#1593A5"></i></div><h4 class="mt-2" style="font-weight:700">Your cart is empty</h4><p class="text-muted" style="font-size:.9rem">Add some products to get started</p><a href="/shop" class="btn btn-primary" style="margin-top:8px;border-radius:14px;padding:.65rem 2rem">Shop Now</a></div>');
    }
}

// ===== WISHLIST FUNCTIONS =====
function toggleWish(id) {
    $.post('/wishlist/toggle', { _token: $('meta[name="csrf-token"]').attr('content'), id: id }, function(res) {
        if (res.success) {
            var btn = $('[data-wish="' + id + '"]');
            btn.toggleClass('active');
            btn.find('i').toggleClass('bi-heart bi-heart-fill');
            $('[data-wish-count]').text(res.count).show();
            showSmartNotify({
                type: res.added ? 'wishlist' : 'info',
                title: res.added ? 'Added to Wishlist' : 'Removed from Wishlist',
                message: res.message,
                image: res.product_image || null,
                productName: res.product_name || '',
                actions: res.added ? [
                    { label: 'View Wishlist', url: '/wishlist', style: 'primary' },
                    { label: 'Continue Shopping', url: '/shop', style: 'ghost' }
                ] : []
            });

            if ($('#wish-row-' + id).length) {
                $('#wish-row-' + id).fadeOut(300, function() { $(this).remove(); });
            }
        }
    });
}

// ===== SMART NOTIFICATION =====
var _notifyQueue = [];
var _notifyActive = false;

function showSmartNotify(opts) {
    _notifyQueue.push(opts);
    if (!_notifyActive) _showNextNotify();
}

function _showNextNotify() {
    if (!_notifyQueue.length) { _notifyActive = false; return; }
    _notifyActive = true;
    var opts = _notifyQueue.shift();

    // Remove any existing
    document.querySelectorAll('.smart-notify').forEach(function(el) { el.remove(); });

    var t = document.createElement('div');
    t.className = 'smart-notify sn-' + (opts.type || 'info');

    var html = '<div class="sn-body">';
    if (opts.image) {
        html += '<div class="sn-content"><img class="sn-img" src="' + opts.image + '" alt=""><div><div class="sn-title">' + opts.title + '</div><div class="sn-msg">' + opts.message + '</div></div></div>';
    } else {
        html += '<div class="sn-content"><div class="sn-title">' + opts.title + '</div><div class="sn-msg">' + opts.message + '</div></div>';
    }
    if (opts.actions && opts.actions.length) {
        html += '<div class="sn-actions">';
        opts.actions.forEach(function(a) {
            html += '<a href="' + a.url + '" class="sn-btn sn-' + a.style + '">' + a.label + '</a>';
        });
        html += '</div>';
    }
    html += '</div>';
    html += '<button class="sn-close" onclick="this.parentElement.remove()"><i class="bi bi-x"></i></button>';
    // Progress bar
    html += '<div class="sn-progress"><div class="sn-progress-bar"></div></div>';

    t.innerHTML = html;
    document.body.appendChild(t);

    // Trigger animation
    requestAnimationFrame(function() { requestAnimationFrame(function() { t.classList.add('show'); }); });

    // Animate progress bar then dismiss
    var bar = t.querySelector('.sn-progress-bar');
    if (bar) {
        bar.style.transition = 'width 3.5s linear';
        requestAnimationFrame(function() { requestAnimationFrame(function() { bar.style.width = '0%'; }); });
    }
    setTimeout(function() {
        t.classList.remove('show');
        setTimeout(function() { t.remove(); _showNextNotify(); }, 400);
    }, 3500);
}

// Legacy compat
function showToast(msg, type) {
    showSmartNotify({
        type: type === 'error' ? 'error' : 'info',
        title: type === 'error' ? 'Error' : 'Success',
        message: msg
    });
}

// ===== INIT =====
function primeInit() {
    initAnnouncementHide();
    (function($) {
        // Show counts on load
        $.get('/cart/count', function(res) { $('[data-cart-count-badge]').text(res.count); });
        $.get('/wishlist/count', function(res) { $('[data-wish-count]').text(res.count); });

        // Cart +/- buttons
        $(document).off('click.cartBtn').on('click.cartBtn', '.cart-minus, .cart-plus', function() {
            var row = $(this).closest('tr');
            var id = row.data('id');
            var input = row.find('.cart-qty-input');
            var current = parseInt(input.val()) || 1;
            var next = $(this).hasClass('cart-minus') ? current - 1 : current + 1;
            if (next < 1) next = 1;
            updateCartQty(id, next);
        });
        $(document).off('click.cartRemove').on('click.cartRemove', '.cart-remove-btn', function() {
            var row = $(this).closest('tr');
            removeCartItem(row.data('id'));
        });

        // Countdown
        if (typeof offerEndDate !== 'undefined' && offerEndDate) {
            var t = Math.max(0, Math.floor((new Date(offerEndDate) - new Date()) / 1000));
            function updateCountdown() {
                if (t <= 0) return;
                t--;
                var d = Math.floor(t / 86400);
                var h = Math.floor(t % 86400 / 3600);
                var m = Math.floor(t % 3600 / 60);
                var s = t % 60;
                var cd_d = document.getElementById('cd-d');
                var cd_h = document.getElementById('cd-h');
                var cd_m = document.getElementById('cd-m');
                var cd_s = document.getElementById('cd-s');
                if (cd_d) cd_d.textContent = String(d).padStart(2, '0');
                if (cd_h) cd_h.textContent = String(h).padStart(2, '0');
                if (cd_m) cd_m.textContent = String(m).padStart(2, '0');
                if (cd_s) cd_s.textContent = String(s).padStart(2, '0');
            }
            updateCountdown();
            setInterval(updateCountdown, 1000);
        }
    })(jQuery);
}

document.addEventListener('DOMContentLoaded', primeInit);
document.addEventListener('spa:loaded', primeInit);
