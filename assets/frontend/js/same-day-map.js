(function ($) {
    'use strict';

    var config = window.tipaxSameDayMap || {};
    var messages = config.messages || {};
    var currentLocation = normalizeLocation(config.location || {});
    var activeRoot = null;
    var activeFrame = null;
    var frameReady = false;
    var frameWarmTimer = null;
    var scanTimer = null;
    var saveToken = 0;
    var validationId = 'tipax-same-day-location-required';
    var phoneValidationId = 'tipax-same-day-phone-required';
    var locationPersisted = Boolean(currentLocation);

    function normalizeLocation(location) {
        var latitude = Number(location && location.latitude);
        var longitude = Number(location && location.longitude);
        if (!isFinite(latitude) || !isFinite(longitude)) {
            return null;
        }
        if (latitude < 35.3 || latitude > 36.1 || longitude < 50.8 || longitude > 52.0) {
            return null;
        }

        return {
            latitude: latitude,
            longitude: longitude,
            address: String(location.address || '')
        };
    }

    function isSameDayRateId(rateId) {
        rateId = String(rateId || '').toLowerCase();
        if (!rateId || rateId.indexOf('tipax_shipping_method') === -1) {
            return false;
        }

        var expected = String(config.rateId || 'tipax_shipping_method12').toLowerCase();
        var position = rateId.indexOf(expected);
        if (position === -1) {
            return false;
        }
        var nextCharacter = rateId.charAt(position + expected.length);
        return nextCharacter === '' || !/[0-9]/.test(nextCharacter);
    }

    function textIndicatesSameDay(value) {
        return /(?:همان[\s\u200c-]*روز|same[\s-]*day)/i.test(String(value || ''));
    }

    function getSelectedRateControls() {
        return Array.prototype.slice.call(document.querySelectorAll([
            'input[name^="shipping_method"]:checked',
            'input[name*="shipping-rate"]:checked',
            '.wc-block-components-shipping-rates-control input[type="radio"]:checked',
            'input[name^="shipping_method"][type="hidden"]',
            'select[name^="shipping_method"]',
            'select[name*="shipping-rate"]',
            '[data-rate-id][aria-checked="true"]',
            '[data-shipping-rate][aria-checked="true"]'
        ].join(',')));
    }

    function getControlText(control) {
        var parts = [];
        if (!control) {
            return '';
        }
        if (control.value) {
            parts.push(control.value);
        }
        ['rateId', 'shippingRate', 'methodId'].forEach(function (key) {
            if (control.dataset && control.dataset[key]) {
                parts.push(control.dataset[key]);
            }
        });
        if (control.tagName === 'SELECT' && control.selectedIndex >= 0) {
            parts.push(control.options[control.selectedIndex].textContent || '');
        }
        if (control.id) {
            Array.prototype.slice.call(document.getElementsByTagName('label')).some(function (label) {
                if (label.htmlFor === control.id) {
                    parts.push(label.textContent || '');
                    return true;
                }
                return false;
            });
        }
        var row = control.closest && control.closest([
            'li',
            'tr',
            '.wc-block-components-radio-control__option',
            '.wc-block-components-shipping-rates-control__package',
            '[data-shipping-method]'
        ].join(','));
        if (row) {
            parts.push(row.textContent || '');
        }
        return parts.join(' ');
    }

    function controlUsesSameDay(control) {
        var descriptor = getControlText(control);
        return isSameDayRateId(descriptor) || textIndicatesSameDay(descriptor);
    }

    function blockStoreUsesSameDay() {
        if (!window.wp || !wp.data || typeof wp.data.select !== 'function') {
            return false;
        }

        try {
            var cartStore = wp.data.select('wc/store/cart');
            if (!cartStore || typeof cartStore.getCartData !== 'function') {
                return false;
            }
            var cartData = cartStore.getCartData() || {};
            var packages = cartData.shippingRates || cartData.shipping_rates || [];
            return packages.some(function (shippingPackage) {
                var rates = shippingPackage.shipping_rates || shippingPackage.shippingRates || shippingPackage.rates || [];
                return rates.some(function (rate) {
                    var selected = rate.selected === true || rate.is_selected === true;
                    var rateId = rate.rate_id || rate.rateId || rate.id || '';
                    return selected && isSameDayRateId(rateId);
                });
            });
        } catch (error) {
            return false;
        }
    }

    function sameDayIsSelected() {
        var controls = getSelectedRateControls();
        return controls.some(controlUsesSameDay)
            || blockStoreUsesSameDay()
            || (controls.length === 0 && Boolean(config.serverSelected));
    }

    function createFallbackRoot() {
        var root = document.createElement('section');
        root.className = 'tipax-same-day-location';
        root.setAttribute('data-tipax-same-day-location', '');
        root.setAttribute('dir', 'rtl');
        root.setAttribute('aria-live', 'polite');
        root.hidden = true;
        root.innerHTML = [
            '<div class="tipax-same-day-location__summary">',
                '<span class="tipax-same-day-location__icon" aria-hidden="true">&#8982;</span>',
                '<div class="tipax-same-day-location__content">',
                    '<span class="tipax-same-day-location__eyebrow">ارسال همان روز</span>',
                    '<h3>' + (messages.title || 'موقعیت دقیق گیرنده') + '</h3>',
                    '<p data-tipax-location-status>' + (messages.description || 'برای ارسال همان روز، محل تحویل را روی نقشه مشخص کنید.') + '</p>',
                '</div>',
                '<button type="button" class="tipax-same-day-location__open" data-tipax-open-map>',
                    '<span data-tipax-open-map-label>' + (messages.openMap || 'انتخاب موقعیت روی نقشه') + '</span>',
                '</button>',
            '</div>',
            '<input type="hidden" name="tipax_same_day_latitude" data-tipax-latitude>',
            '<input type="hidden" name="tipax_same_day_longitude" data-tipax-longitude>',
            '<input type="hidden" name="tipax_same_day_map_address" data-tipax-map-address>',
            '<dialog class="tipax-same-day-modal" data-tipax-map-modal aria-labelledby="tipax-same-day-modal-title-fallback">',
                '<div class="tipax-same-day-modal__dialog">',
                    '<div class="tipax-same-day-modal__header">',
                        '<div>',
                            '<span class="tipax-same-day-location__eyebrow">ارسال همان روز</span>',
                            '<h3 id="tipax-same-day-modal-title-fallback">انتخاب موقعیت گیرنده</h3>',
                            '<p>نقشه را جابه‌جا کنید تا نشانگر روی محل دقیق تحویل قرار بگیرد.</p>',
                        '</div>',
                        '<button type="button" class="tipax-same-day-modal__close" data-tipax-close-map aria-label="' + (messages.close || 'بستن') + '">&times;</button>',
                    '</div>',
                    '<div class="tipax-same-day-location__map-shell" data-tipax-map-shell>',
                        '<div class="tipax-same-day-location__loading">در حال بارگذاری نقشه نشان...</div>',
                    '</div>',
                    '<div class="tipax-same-day-modal__footer">',
                        '<button type="button" class="tipax-same-day-location__locate" data-tipax-geolocate>',
                            '<span aria-hidden="true">◎</span>' + (messages.geolocation || 'موقعیت فعلی من'),
                        '</button>',
                        '<div class="tipax-same-day-location__result" data-tipax-location-result>',
                            '<span class="tipax-same-day-location__result-dot" aria-hidden="true"></span>',
                            '<span data-tipax-location-status>' + (messages.required || 'نقطه دقیق تحویل را روی نقشه انتخاب کنید.') + '</span>',
                        '</div>',
                        '<button type="button" class="tipax-same-day-modal__confirm" data-tipax-confirm-map disabled>' + (messages.confirm || 'تأیید موقعیت') + '</button>',
                    '</div>',
                '</div>',
            '</dialog>'
        ].join('');
        return root;
    }

    function insertFallbackRoot(root) {
        var blockActions = document.querySelector('.wc-block-checkout__actions, .wp-block-woocommerce-checkout-actions-block');
        if (blockActions && blockActions.parentNode) {
            if (root.parentNode !== blockActions.parentNode || root.nextElementSibling !== blockActions) {
                blockActions.parentNode.insertBefore(root, blockActions);
            }
            return true;
        }

        var classicForm = document.querySelector('form.checkout, form.woocommerce-checkout');
        var placeOrder = classicForm && classicForm.querySelector(
            '#place_order, button[name="woocommerce_checkout_place_order"]'
        );
        var placeOrderRow = placeOrder && placeOrder.closest('.place-order');
        var classicTarget = placeOrderRow || placeOrder;
        if (classicTarget && classicTarget.parentNode) {
            if (root.parentNode !== classicTarget.parentNode || root.nextElementSibling !== classicTarget) {
                classicTarget.parentNode.insertBefore(root, classicTarget);
            }
            return true;
        }

        if (classicForm) {
            classicForm.appendChild(root);
            return true;
        }

        var checkout = document.querySelector('.woocommerce-checkout, .wc-block-checkout, .wp-block-woocommerce-checkout');
        if (checkout) {
            checkout.appendChild(root);
            return true;
        }
        return false;
    }

    function findOrCreateRoot() {
        var roots = Array.prototype.slice.call(document.querySelectorAll('[data-tipax-same-day-location]'));
        var root = roots.shift() || null;
        roots.forEach(function (duplicate) {
            duplicate.remove();
        });

        if (!root) {
            root = createFallbackRoot();
        }
        if (!insertFallbackRoot(root) && !root.isConnected) {
            return null;
        }

        return root;
    }

    function setRootState(state, text) {
        if (!activeRoot) {
            return;
        }

        activeRoot.classList.remove('is-ready', 'is-saving', 'has-error');
        if (state) {
            activeRoot.classList.add(state);
        }
        activeRoot.querySelectorAll('[data-tipax-location-status]').forEach(function (status) {
            status.textContent = text || '';
        });
        var openLabel = activeRoot.querySelector('[data-tipax-open-map-label]');
        if (openLabel) {
            openLabel.textContent = currentLocation && locationPersisted
                ? (messages.editMap || 'ویرایش موقعیت')
                : (messages.openMap || 'انتخاب موقعیت روی نقشه');
        }
        var confirmButton = activeRoot.querySelector('[data-tipax-confirm-map]');
        if (confirmButton) {
            confirmButton.disabled = !currentLocation || !locationPersisted;
        }
    }

    function syncHiddenFields() {
        if (!activeRoot) {
            return;
        }
        var latitude = activeRoot.querySelector('[data-tipax-latitude]');
        var longitude = activeRoot.querySelector('[data-tipax-longitude]');
        var address = activeRoot.querySelector('[data-tipax-map-address]');
        if (latitude) {
            latitude.value = currentLocation ? currentLocation.latitude : '';
        }
        if (longitude) {
            longitude.value = currentLocation ? currentLocation.longitude : '';
        }
        if (address) {
            address.value = currentLocation ? currentLocation.address : '';
        }
    }

    function setCheckoutExtensionData() {
        if (!window.wp || !wp.data || typeof wp.data.dispatch !== 'function') {
            return;
        }

        try {
            var checkoutDispatch = wp.data.dispatch('wc/store/checkout');
            var setter = checkoutDispatch && (
                checkoutDispatch.setExtensionData || checkoutDispatch.__internalSetExtensionData
            );
            if (typeof setter === 'function') {
                setter('tipax-same-day-location', currentLocation ? {
                    latitude: currentLocation.latitude,
                    longitude: currentLocation.longitude,
                    address: currentLocation.address || ''
                } : {});
            }
        } catch (error) {
            // Session storage remains the compatibility path for older Blocks versions.
        }
    }

    function updateBlockValidation() {
        updatePhoneRequirement();
        updatePlaceOrderAvailability();

        if (!window.wp || !wp.data || typeof wp.data.dispatch !== 'function') {
            return;
        }

        try {
            var validation = wp.data.dispatch('wc/store/validation');
            if (!validation) {
                return;
            }

            var selected = sameDayIsSelected();
            var locationMissing = selected && (!currentLocation || !locationPersisted);
            var phoneMissing = selected && !hasRecipientMobile();
            var errors = {};
            if (locationMissing) {
                errors[validationId] = {
                    message: messages.required || 'انتخاب موقعیت روی نقشه برای سرویس همان روز الزامی است.',
                    hidden: false
                };
            }
            if (phoneMissing) {
                errors[phoneValidationId] = {
                    message: messages.phoneRequired || 'شماره موبایل برای سرویس همان روز الزامی است.',
                    hidden: false
                };
            }
            if (Object.keys(errors).length > 0 && typeof validation.setValidationErrors === 'function') {
                validation.setValidationErrors(errors);
            }
            if (typeof validation.clearValidationError === 'function') {
                if (!locationMissing) {
                    validation.clearValidationError(validationId);
                }
                if (!phoneMissing) {
                    validation.clearValidationError(phoneValidationId);
                }
            }
        } catch (error) {
            // Server-side Store API validation remains authoritative.
        }
    }

    function getCheckoutPhoneFields() {
        var checkout = document.querySelector([
            'form.checkout',
            'form.woocommerce-checkout',
            '.wc-block-checkout',
            '.wp-block-woocommerce-checkout'
        ].join(','));
        if (!checkout) {
            return [];
        }

        return Array.prototype.slice.call(checkout.querySelectorAll([
            '#billing_phone',
            '#shipping_phone',
            '#billing-phone',
            '#shipping-phone',
            'input[name="billing_phone"]',
            'input[name="shipping_phone"]',
            'input[name="phone_field"]',
            'input[autocomplete="tel"]',
            'input[type="tel"]'
        ].join(','))).filter(function (field, index, fields) {
            return fields.indexOf(field) === index;
        });
    }

    function hasRecipientMobile() {
        var fields = getCheckoutPhoneFields();
        if (fields.length === 0) {
            return true;
        }
        return fields.some(function (field) {
            return String(field.value || '').trim() !== '';
        });
    }

    function updatePhoneRequirement() {
        var required = sameDayIsSelected();
        getCheckoutPhoneFields().forEach(function (field) {
            if (required) {
                if (!field.hasAttribute('data-tipax-original-required')) {
                    field.setAttribute('data-tipax-original-required', field.required ? '1' : '0');
                    field.setAttribute(
                        'data-tipax-original-aria-required',
                        field.hasAttribute('aria-required') ? field.getAttribute('aria-required') : '__missing__'
                    );
                }
                field.required = true;
                field.setAttribute('aria-required', 'true');
            } else if (field.hasAttribute('data-tipax-original-required')) {
                field.required = field.getAttribute('data-tipax-original-required') === '1';
                var originalAria = field.getAttribute('data-tipax-original-aria-required');
                if (originalAria === '__missing__') {
                    field.removeAttribute('aria-required');
                } else {
                    field.setAttribute('aria-required', originalAria);
                }
                field.removeAttribute('data-tipax-original-required');
                field.removeAttribute('data-tipax-original-aria-required');
            }

            var label = null;
            if (field.id) {
                Array.prototype.slice.call(document.getElementsByTagName('label')).some(function (candidate) {
                    if (candidate.htmlFor === field.id) {
                        label = candidate;
                        return true;
                    }
                    return false;
                });
            }
            if (!label) {
                return;
            }
            if (required && !label.hasAttribute('data-tipax-original-label')) {
                label.setAttribute('data-tipax-original-label', label.innerHTML);
                label.textContent = label.textContent
                    .replace(/\s*\((?:اختیاری|optional)\)\s*/gi, '')
                    .trim() + ' *';
            } else if (!required && label.hasAttribute('data-tipax-original-label')) {
                label.innerHTML = label.getAttribute('data-tipax-original-label');
                label.removeAttribute('data-tipax-original-label');
            }
        });
    }

    function getPlaceOrderButtons() {
        return document.querySelectorAll([
            '#place_order',
            'button[name="woocommerce_checkout_place_order"]',
            '.wc-block-components-checkout-place-order-button'
        ].join(','));
    }

    function syncCheckoutAccent() {
        if (!activeRoot || activeRoot.style.getPropertyValue('--tipax-checkout-accent')) {
            return;
        }
        var button = getPlaceOrderButtons()[0];
        if (!button) {
            return;
        }
        var color = window.getComputedStyle(button).backgroundColor;
        if (color && color !== 'transparent' && color !== 'rgba(0, 0, 0, 0)') {
            activeRoot.style.setProperty('--tipax-checkout-accent', color);
        }
    }

    function updatePlaceOrderAvailability() {
        var blocked = sameDayIsSelected()
            && (!currentLocation || !locationPersisted || !hasRecipientMobile());
        var buttons = getPlaceOrderButtons();

        buttons.forEach(function (button) {
            if (blocked) {
                if (button.getAttribute('data-tipax-location-disabled') !== '1') {
                    button.setAttribute('data-tipax-was-disabled', button.disabled ? '1' : '0');
                    button.setAttribute(
                        'data-tipax-original-aria-disabled',
                        button.hasAttribute('aria-disabled') ? button.getAttribute('aria-disabled') : '__missing__'
                    );
                }
                button.disabled = true;
                button.setAttribute('aria-disabled', 'true');
                button.setAttribute('data-tipax-location-disabled', '1');
                return;
            }

            if (button.getAttribute('data-tipax-location-disabled') === '1') {
                button.disabled = button.getAttribute('data-tipax-was-disabled') === '1';
                var originalAria = button.getAttribute('data-tipax-original-aria-disabled');
                if (originalAria === '__missing__') {
                    button.removeAttribute('aria-disabled');
                } else {
                    button.setAttribute('aria-disabled', originalAria);
                }
                button.removeAttribute('data-tipax-location-disabled');
                button.removeAttribute('data-tipax-was-disabled');
                button.removeAttribute('data-tipax-original-aria-disabled');
            }
        });
    }

    function locationSummary(location) {
        if (location.address) {
            return location.address;
        }
        return (messages.coordinates || 'مختصات انتخاب‌شده') + ': '
            + location.latitude.toFixed(6) + '، ' + location.longitude.toFixed(6);
    }

    function activeModal() {
        return activeRoot ? activeRoot.querySelector('[data-tipax-map-modal]') : null;
    }

    function modalIsOpen() {
        var modal = activeModal();
        return Boolean(modal && modal.hasAttribute('open'));
    }

    function openMapModal() {
        var modal = activeModal();
        if (!modal) {
            return;
        }
        if (typeof modal.showModal === 'function') {
            if (!modal.open) {
                modal.showModal();
            }
        } else {
            modal.setAttribute('open', '');
        }
        document.documentElement.classList.add('tipax-same-day-modal-open');
        initializeFrame();
        window.setTimeout(function () {
            if (activeFrame && activeFrame.contentWindow && frameReady) {
                activeFrame.contentWindow.postMessage({
                    source: 'tipax-same-day-checkout',
                    type: 'invalidate-size'
                }, new URL(config.frameUrl, window.location.href).origin);
                sendLocationToFrame(currentLocation, false);
            }
        }, 80);
    }

    function closeMapModal() {
        var modal = activeModal();
        if (!modal) {
            return;
        }
        if (typeof modal.close === 'function' && modal.open) {
            modal.close();
        } else {
            modal.removeAttribute('open');
        }
        document.documentElement.classList.remove('tipax-same-day-modal-open');
    }

    function saveLocation(location) {
        var token = ++saveToken;
        currentLocation = location;
        locationPersisted = false;
        syncHiddenFields();
        setCheckoutExtensionData();
        updateBlockValidation();
        setRootState('is-saving', messages.saving || 'در حال ثبت موقعیت...');

        $.ajax({
            url: config.ajaxUrl,
            method: 'POST',
            dataType: 'json',
            data: {
                action: 'tipax_save_same_day_location',
                nonce: config.nonce,
                latitude: location.latitude,
                longitude: location.longitude
            }
        }).done(function (response) {
            if (token !== saveToken) {
                return;
            }
            if (!response || !response.success || !response.data) {
                setRootState('has-error', messages.saveError || 'ثبت موقعیت ناموفق بود.');
                currentLocation = null;
                locationPersisted = false;
                syncHiddenFields();
                setCheckoutExtensionData();
                updateBlockValidation();
                return;
            }

            currentLocation = normalizeLocation(response.data);
            if (!currentLocation) {
                locationPersisted = false;
                setRootState('has-error', messages.saveError || 'ثبت موقعیت ناموفق بود.');
                updateBlockValidation();
                return;
            }
            locationPersisted = true;
            syncHiddenFields();
            setCheckoutExtensionData();
            updateBlockValidation();
            setRootState('is-ready', locationSummary(currentLocation));
        }).fail(function () {
            if (token !== saveToken) {
                return;
            }
            currentLocation = null;
            locationPersisted = false;
            syncHiddenFields();
            setCheckoutExtensionData();
            updateBlockValidation();
            setRootState('has-error', messages.saveError || 'ثبت موقعیت ناموفق بود.');
        });
    }

    function sendLocationToFrame(location, commit) {
        if (!activeFrame || !activeFrame.contentWindow || !frameReady || !location) {
            return;
        }
        activeFrame.contentWindow.postMessage({
            source: 'tipax-same-day-checkout',
            type: 'set-location',
            latitude: location.latitude,
            longitude: location.longitude,
            commit: Boolean(commit)
        }, new URL(config.frameUrl, window.location.href).origin);
    }

    function initializeFrame() {
        if (frameWarmTimer !== null) {
            window.clearTimeout(frameWarmTimer);
            frameWarmTimer = null;
        }
        if (!activeRoot) {
            return;
        }
        var shell = activeRoot.querySelector('[data-tipax-map-shell]');
        if (!shell) {
            return;
        }

        activeFrame = shell.querySelector('iframe[data-tipax-map-frame]');
        if (!activeFrame) {
            activeFrame = document.createElement('iframe');
            activeFrame.setAttribute('data-tipax-map-frame', '');
            activeFrame.setAttribute('title', 'انتخاب موقعیت گیرنده روی نقشه نشان');
            activeFrame.setAttribute('loading', 'eager');
            activeFrame.setAttribute('allow', 'geolocation');
            activeFrame.setAttribute('referrerpolicy', 'strict-origin-when-cross-origin');
            var frameUrl = new URL(config.frameUrl, window.location.href);
            if (currentLocation) {
                frameUrl.searchParams.set('latitude', currentLocation.latitude.toFixed(7));
                frameUrl.searchParams.set('longitude', currentLocation.longitude.toFixed(7));
            }
            activeFrame.src = frameUrl.toString();
            shell.appendChild(activeFrame);
            frameReady = false;
        }
    }

    function scheduleFrameWarmup() {
        if (activeFrame || frameWarmTimer !== null) {
            return;
        }

        frameWarmTimer = window.setTimeout(function () {
            frameWarmTimer = null;
            if (sameDayIsSelected()) {
                initializeFrame();
            }
        }, 250);
    }

    function bindRoot(root) {
        if (activeRoot === root && root.getAttribute('data-tipax-bound') === '1') {
            return;
        }

        activeRoot = root;
        activeRoot.setAttribute('data-tipax-bound', '1');
        if (!currentLocation) {
            var latitude = activeRoot.querySelector('[data-tipax-latitude]');
            var longitude = activeRoot.querySelector('[data-tipax-longitude]');
            var address = activeRoot.querySelector('[data-tipax-map-address]');
            var embeddedLocation = normalizeLocation({
                latitude: latitude ? latitude.value : null,
                longitude: longitude ? longitude.value : null,
                address: address ? address.value : ''
            });
            if (embeddedLocation) {
                currentLocation = embeddedLocation;
                locationPersisted = true;
            }
        }
        syncHiddenFields();

        activeRoot.querySelectorAll('[data-tipax-open-map]').forEach(function (button) {
            button.addEventListener('click', openMapModal);
        });
        activeRoot.querySelectorAll('[data-tipax-close-map]').forEach(function (button) {
            button.addEventListener('click', closeMapModal);
        });
        activeRoot.querySelectorAll('[data-tipax-confirm-map]').forEach(function (button) {
            button.addEventListener('click', function () {
                if (currentLocation && locationPersisted) {
                    closeMapModal();
                    return;
                }
                setRootState('has-error', messages.required || 'انتخاب موقعیت روی نقشه برای سرویس همان روز الزامی است.');
            });
        });

        var modal = activeModal();
        if (modal) {
            modal.addEventListener('click', function (event) {
                if (event.target === modal) {
                    closeMapModal();
                }
            });
            modal.addEventListener('close', function () {
                document.documentElement.classList.remove('tipax-same-day-modal-open');
            });
        }

        activeRoot.querySelectorAll('[data-tipax-geolocate]').forEach(function (locateButton) {
            locateButton.addEventListener('click', function () {
                if (!navigator.geolocation) {
                    setRootState('has-error', messages.geolocationError || 'دسترسی به موقعیت فعلی ممکن نبود.');
                    return;
                }
                locateButton.disabled = true;
                navigator.geolocation.getCurrentPosition(function (position) {
                    locateButton.disabled = false;
                    var location = normalizeLocation({
                        latitude: position.coords.latitude,
                        longitude: position.coords.longitude
                    });
                    if (location) {
                        saveLocation(location);
                        sendLocationToFrame(location, false);
                    } else {
                        setRootState('has-error', messages.outsideTehran || 'موقعیت گیرنده برای سرویس همان روز باید در محدوده تهران باشد.');
                    }
                }, function () {
                    locateButton.disabled = false;
                    setRootState('has-error', messages.geolocationError || 'دسترسی به موقعیت فعلی ممکن نبود.');
                }, {
                    enableHighAccuracy: true,
                    timeout: 10000,
                    maximumAge: 60000
                });
            });
        });

        if (currentLocation) {
            setRootState('is-ready', locationSummary(currentLocation));
        } else {
            setRootState('', messages.required || 'انتخاب موقعیت روی نقشه برای سرویس همان روز الزامی است.');
        }
    }

    function syncMapVisibility() {
        var root = findOrCreateRoot();
        if (!root) {
            return;
        }
        bindRoot(root);
        syncCheckoutAccent();

        var visible = sameDayIsSelected();
        if (visible) {
            config.serverSelected = true;
        }
        root.hidden = !visible;
        root.setAttribute('aria-hidden', visible ? 'false' : 'true');
        if (visible) {
            scheduleFrameWarmup();
        } else if (frameWarmTimer !== null) {
            window.clearTimeout(frameWarmTimer);
            frameWarmTimer = null;
        }
        if (!visible && modalIsOpen()) {
            closeMapModal();
        }
        updateBlockValidation();
    }

    function scheduleSync() {
        if (scanTimer !== null) {
            return;
        }

        scanTimer = window.setTimeout(function () {
            scanTimer = null;
            syncMapVisibility();
        }, 80);
    }

    window.addEventListener('message', function (event) {
        if (!activeFrame || event.source !== activeFrame.contentWindow || !event.data || event.data.source !== 'tipax-same-day-map') {
            return;
        }
        var expectedOrigin = new URL(config.frameUrl, window.location.href).origin;
        if (event.origin !== expectedOrigin) {
            return;
        }

        if (event.data.type === 'ready') {
            frameReady = true;
            var frameLocation = normalizeLocation(event.data);
            if (
                currentLocation
                && (
                    !frameLocation
                    || Math.abs(frameLocation.latitude - currentLocation.latitude) > 0.0000001
                    || Math.abs(frameLocation.longitude - currentLocation.longitude) > 0.0000001
                )
            ) {
                sendLocationToFrame(currentLocation, false);
            }
            return;
        }
        if (event.data.type === 'error') {
            setRootState('has-error', messages.mapError || 'بارگذاری نقشه نشان ناموفق بود.');
            return;
        }
        if (event.data.type === 'location') {
            var location = normalizeLocation(event.data);
            if (location) {
                saveLocation(location);
            }
        }
    });

    $(document.body).on('updated_checkout updated_shipping_method', scheduleSync);
    $(document.body).on('change', [
        'input[name^="shipping_method"]',
        'input[name*="shipping-rate"]',
        '.wc-block-components-shipping-rates-control input[type="radio"]'
    ].join(','), scheduleSync);
    $(document.body).on('input change', [
        '#billing_phone',
        '#shipping_phone',
        '#billing-phone',
        '#shipping-phone',
        'input[name="billing_phone"]',
        'input[name="shipping_phone"]',
        'input[name="phone_field"]',
        'input[autocomplete="tel"]',
        'input[type="tel"]'
    ].join(','), scheduleSync);

    $(document.body).on('checkout_place_order', 'form.checkout', function () {
        if (!sameDayIsSelected() || (currentLocation && locationPersisted)) {
            return true;
        }
        syncMapVisibility();
        setRootState('has-error', messages.required || 'انتخاب موقعیت روی نقشه برای سرویس همان روز الزامی است.');
        openMapModal();
        if (activeRoot && typeof activeRoot.scrollIntoView === 'function') {
            activeRoot.scrollIntoView({ behavior: 'smooth', block: 'center' });
        }
        return false;
    });

    document.addEventListener('click', function (event) {
        var placeOrder = event.target && event.target.closest
            ? event.target.closest('.wc-block-components-checkout-place-order-button')
            : null;
        if (!placeOrder || !sameDayIsSelected() || (currentLocation && locationPersisted)) {
            return;
        }
        event.preventDefault();
        event.stopImmediatePropagation();
        syncMapVisibility();
        setRootState('has-error', messages.required || 'انتخاب موقعیت روی نقشه برای سرویس همان روز الزامی است.');
        openMapModal();
    }, true);

    document.addEventListener('submit', function (event) {
        var checkoutForm = event.target && event.target.matches
            && event.target.matches('form.checkout, form.woocommerce-checkout');
        if (!checkoutForm || !sameDayIsSelected() || (currentLocation && locationPersisted)) {
            return;
        }
        event.preventDefault();
        event.stopImmediatePropagation();
        syncMapVisibility();
        setRootState('has-error', messages.required || 'انتخاب موقعیت روی نقشه برای سرویس همان روز الزامی است.');
        openMapModal();
    }, true);

    document.addEventListener('keydown', function (event) {
        if (event.key === 'Escape' && modalIsOpen()) {
            closeMapModal();
        }
    });

    if (window.wp && wp.data && typeof wp.data.subscribe === 'function') {
        wp.data.subscribe(scheduleSync, 'wc/store/cart');
    }

    if (window.MutationObserver && document.querySelector([
        'form.checkout',
        '.woocommerce-checkout',
        '.wc-block-checkout',
        '.wp-block-woocommerce-checkout'
    ].join(','))) {
        new MutationObserver(scheduleSync).observe(document.body, {
            childList: true,
            subtree: true,
            attributes: true,
            attributeFilter: ['checked', 'class']
        });
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', scheduleSync);
    } else {
        scheduleSync();
    }
}(jQuery));
