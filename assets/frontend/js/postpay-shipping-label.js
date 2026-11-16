(function () {
    'use strict';

    var config = window.tipaxPostpayShipping || {};
    var message = String(config.message || '').trim();
    var rateIdFragment = String(config.rateIdFragment || 'tipax_shipping_method');
    var scanScheduled = false;
    var freeLabels = ['رایگان', 'مجانی', 'free', 'free shipping'];

    if (!message) {
        return;
    }

    function normalizeText(value) {
        return String(value || '')
            .replace(/[\u200c\u200e\u200f\u202a-\u202e]/g, '')
            .replace(/ي/g, 'ی')
            .replace(/ك/g, 'ک')
            .replace(/^[\s:،,\-–—]+|[\s:،,\-–—]+$/g, '')
            .replace(/\s+/g, ' ')
            .trim()
            .toLowerCase();
    }

    function isFreeLabel(value) {
        return freeLabels.indexOf(normalizeText(value)) !== -1;
    }

    function containsTipaxPostpayText(element) {
        var text = normalizeText(element && element.textContent);
        var hasTipax = text.indexOf('تیپاکس') !== -1 || text.indexOf('tipax') !== -1;
        var hasPostpay = text.replace(/\s+/g, '').indexOf('پسکرایه') !== -1
            || text.indexOf('postpay') !== -1;

        return hasTipax && hasPostpay;
    }

    function containsTipaxRateInput(element) {
        if (!element || typeof element.querySelector !== 'function') {
            return false;
        }

        if (element.control && String(element.control.value || '').indexOf(rateIdFragment) !== -1) {
            return true;
        }

        var inputs = element.querySelectorAll('input[type="radio"]');
        for (var index = 0; index < inputs.length; index++) {
            if (String(inputs[index].value || '').indexOf(rateIdFragment) !== -1) {
                return true;
            }
        }

        return false;
    }

    function belongsToTipaxPostpayRate(textNode) {
        if (!textNode.parentElement || typeof textNode.parentElement.closest !== 'function') {
            return false;
        }

        var scope = textNode.parentElement.closest([
            '.wc-block-components-radio-control__option',
            '.wc-block-components-totals-item',
            'label',
            'li',
            'tr'
        ].join(','));

        return Boolean(scope && (containsTipaxRateInput(scope) || containsTipaxPostpayText(scope)));
    }

    function replacePostpayFreeLabels() {
        var walker = document.createTreeWalker(document.body, NodeFilter.SHOW_TEXT);
        var candidates = [];
        var node;

        while ((node = walker.nextNode())) {
            if (isFreeLabel(node.nodeValue)) {
                candidates.push(node);
            }
        }

        candidates.forEach(function (textNode) {
            if (!belongsToTipaxPostpayRate(textNode)) {
                return;
            }

            var original = String(textNode.nodeValue || '');
            var trimmed = original.trim();
            textNode.nodeValue = original.replace(trimmed, message);
            if (textNode.parentElement) {
                textNode.parentElement.classList.add('tipax-postpay-shipping-message');
            }
        });
    }

    function scheduleScan() {
        if (scanScheduled) {
            return;
        }

        scanScheduled = true;
        window.requestAnimationFrame(function () {
            scanScheduled = false;
            replacePostpayFreeLabels();
        });
    }

    function initialize() {
        scheduleScan();

        if (!document.body || typeof MutationObserver === 'undefined') {
            return;
        }

        new MutationObserver(scheduleScan).observe(document.body, {
            childList: true,
            characterData: true,
            subtree: true
        });
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initialize);
    } else {
        initialize();
    }
}());
