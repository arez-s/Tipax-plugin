(function () {
    'use strict';
    var banners = Array.prototype.slice.call(document.querySelectorAll('[data-tipax-hub-banner]'));
    var validatingBanners = false;
    banners.forEach(function (banner) {
        var image = banner.querySelector('img');
        if (image) image.addEventListener('error', function () { banner.remove(); }, { once: true });
    });
    var validateBanners = function () {
        var active = banners.filter(function (banner) { return banner.isConnected; });
        if (!active.length || validatingBanners) return;
        validatingBanners = true;
        active.forEach(function (banner) { banner.hidden = true; });
        var controller = typeof AbortController === 'function' ? new AbortController() : null;
        var timeout = controller ? setTimeout(function () { controller.abort(); }, 5000) : null;
        fetch('https://tipaxplugin.ir/api/v1/assets/manifest', {
            mode: 'cors', credentials: 'omit', cache: 'no-store',
            referrerPolicy: 'no-referrer', signal: controller ? controller.signal : undefined
        }).then(function (response) {
            if (!response.ok) throw new Error('Manifest unavailable');
            return response.json();
        }).then(function (payload) {
            if (!payload.ok || !payload.data || !Array.isArray(payload.data.assets)) throw new Error('Invalid manifest');
            var published = payload.data.assets.map(function (asset) { return asset && asset.url; });
            var removedSources = [];
            active.forEach(function (banner) {
                var image = banner.querySelector('img');
                if (!image || published.indexOf(image.getAttribute('src')) === -1) {
                    if (image) removedSources.push(image.getAttribute('src'));
                    banner.remove();
                } else {
                    banner.hidden = false;
                }
            });
            if (removedSources.length && window.tipaxHubMedia) {
                var body = new URLSearchParams();
                body.set('action', 'tipax_hub_forget_media');
                body.set('nonce', window.tipaxHubMedia.nonce);
                removedSources.forEach(function (source) { body.append('sources[]', source); });
                fetch(window.tipaxHubMedia.ajaxUrl, {
                    method: 'POST', credentials: 'same-origin', body: body
                }).catch(function () {});
            }
        }).catch(function () {
            active.forEach(function (banner) { banner.remove(); });
        }).finally(function () {
            if (timeout) clearTimeout(timeout);
            validatingBanners = false;
        });
    };
    validateBanners();
    document.addEventListener('visibilitychange', function () {
        if (!document.hidden) validateBanners();
    });
    var overlay = document.querySelector('[data-tipax-hub-modal]');
    if (!overlay) return;
    var focusable = overlay.querySelectorAll('button, a[href], input:not([type="hidden"])');
    var handleKey = function (event) {
        if (event.key === 'Escape') {
            event.preventDefault();
            event.stopImmediatePropagation();
            return;
        }
        if (event.key !== 'Tab' || !focusable.length) return;
        var first = focusable[0], last = focusable[focusable.length - 1];
        if (event.shiftKey && document.activeElement === first) {
            event.preventDefault(); last.focus();
        } else if (!event.shiftKey && document.activeElement === last) {
            event.preventDefault(); first.focus();
        }
    };
    document.addEventListener('keydown', handleKey, true);
    overlay.addEventListener('click', function (event) {
        if (event.target === overlay) {
            event.preventDefault();
            event.stopPropagation();
        }
    });
    var accept = overlay.querySelector('[name="tipax_hub_accept"]');
    if (accept) accept.focus();
})();
