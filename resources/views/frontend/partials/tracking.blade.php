{{--
    Analytics & marketing tags.

    One loader handles all three services so consent is checked once, the
    dataLayer/gtag shim is created once, and SPA navigations send a single
    page view. GTM wins over standalone GA when both IDs are set — otherwise
    GA would count every page twice.
--}}
@if ($hasTracking)
    <script>
        (function () {
            var gtmId = @json($googleTagManagerId);
            var gaId = @json($googleAnalyticsId);
            var pixelId = @json($facebookPixelId);

            function trackingConsent() {
                try {
                    return window.localStorage.getItem('egf-consent') === 'accepted';
                } catch (e) {
                    return false;
                }
            }

            function loadScript(src) {
                var script = document.createElement('script');
                script.async = true;
                script.src = src;
                document.head.appendChild(script);
            }

            window.egfLoadTracking = function () {
                if (window._egfTrackingLoaded) {
                    return;
                }
                window._egfTrackingLoaded = true;

                window.dataLayer = window.dataLayer || [];
                window.gtag = window.gtag || function () {
                    window.dataLayer.push(arguments);
                };
                window.gtag('js', new Date());

                /* ---- Google Tag Manager (preferred; can carry GA4) ---- */
                if (gtmId) {
                    window.dataLayer.push({ 'gtm.start': new Date().getTime(), event: 'gtm.js' });
                    loadScript('https://www.googletagmanager.com/gtm.js?id=' + encodeURIComponent(gtmId));
                }

                /* ---- Google Analytics 4 (standalone fallback) ---- */
                else if (gaId) {
                    loadScript('https://www.googletagmanager.com/gtag/js?id=' + encodeURIComponent(gaId));
                    window.gtag('config', gaId);
                }

                /* ---- Meta (Facebook) Pixel ---- */
                if (pixelId) {
                    !function (f, b, e, v, n, t, s) {
                        if (f.fbq) {
                            return;
                        }
                        n = f.fbq = function () {
                            n.callMethod ? n.callMethod.apply(n, arguments) : n.queue.push(arguments);
                        };
                        if (!f._fbq) {
                            f._fbq = n;
                        }
                        n.push = n;
                        n.loaded = true;
                        n.version = '2.0';
                        n.queue = [];
                        t = b.createElement(e);
                        t.async = true;
                        t.src = v;
                        s = b.getElementsByTagName(e)[0];
                        s.parentNode.insertBefore(t, s);
                    }(window, document, 'script', 'https://connect.facebook.net/en_US/fbevents.js');
                    window.fbq('init', pixelId);
                    window.fbq('track', 'PageView');
                }

                /* ---- SPA page views ---- */
                document.addEventListener('spa:loaded', function () {
                    var path = window.location.pathname + window.location.search;

                    window.dataLayer.push({
                        event: 'egf_page_view',
                        page_path: path,
                        page_location: window.location.href
                    });

                    if (!gtmId && gaId) {
                        window.gtag('config', gaId, {
                            page_path: path,
                            page_location: window.location.href
                        });
                    }

                    if (window.fbq) {
                        window.fbq('track', 'PageView');
                    }
                });
            };

            if (trackingConsent()) {
                window.egfLoadTracking();
            }
        })();
    </script>
@endif
