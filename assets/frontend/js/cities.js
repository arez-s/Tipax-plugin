jQuery(function ($) {
    const settings = window.tsm_settings || {};
    const managesTipaxCities = Boolean(settings.manage_cities);
    const messages = settings.messages || {};
    const cityCache = {};
    const blockRequestTokens = {};
    let checkoutRefreshTimer = null;
    let initializeTimer = null;
    let blockCustomerTimer = null;

    function normalizeText(value) {
        return String(value || '')
            .replace(/[\u200c\u200f\u202a-\u202e]/g, '')
            .replace(/ي/g, 'ی')
            .replace(/ك/g, 'ک')
            .replace(/\s+/g, '')
            .trim()
            .toLowerCase();
    }

    function getFieldType(element) {
        const id = String(element.attr('id') || element.attr('name') || '');
        if (id.indexOf('shipping') !== -1) {
            return 'shipping';
        }

        return element.closest('.wc-block-checkout__shipping-fields, .wp-block-woocommerce-checkout-shipping-address-block').length
            ? 'shipping'
            : 'billing';
    }

    function getStateTitle(stateValue, selectedText) {
        const value = String(stateValue || '').trim();
        const text = String(selectedText || '').trim();
        const labels = settings.state_labels || {};
        if (!value) {
            return '';
        }
        const knownLabel = labels[value.toUpperCase()] || '';

        if (knownLabel) {
            return knownLabel;
        }

        if (text && normalizeText(text) !== normalizeText(value)) {
            return text;
        }

        return value;
    }

    function fetchCities(stateValue, stateTitle) {
        const cacheKey = normalizeText(stateTitle || stateValue);
        if (!cacheKey) {
            return $.Deferred().resolve([]).promise();
        }

        if (Object.prototype.hasOwnProperty.call(cityCache, cacheKey)) {
            return $.Deferred().resolve(cityCache[cacheKey]).promise();
        }

        return $.ajax({
            url: settings.ajax_url,
            method: 'POST',
            dataType: 'json',
            data: {
                action: 'get_tipax_cities',
                security: settings.nonce,
                state: stateValue,
                state_title: stateTitle
            }
        }).then(function (response) {
            if (!response || !response.success || !response.data || !Array.isArray(response.data.cities)) {
                return $.Deferred().reject().promise();
            }

            cityCache[cacheKey] = response.data.cities;
            return response.data.cities;
        });
    }

    function findMatchingCity(cities, currentValue) {
        const value = String(currentValue || '').trim();
        const normalizedValue = normalizeText(value);
        if (!value) {
            return '';
        }

        for (let index = 0; index < cities.length; index += 1) {
            const city = cities[index] || {};
            if (String(city.id || '') === value || normalizeText(city.title) === normalizedValue) {
                return String(city.id || '');
            }
        }

        return '';
    }

    function fillCitySelect(element, cities, currentValue, emptyMessage) {
        const selectedValue = findMatchingCity(cities, currentValue);
        element.empty().append($('<option>', {
            value: '',
            text: cities.length ? (messages.select_city || 'یک شهر را انتخاب کنید') : emptyMessage
        }));

        cities.forEach(function (city) {
            element.append($('<option>', {
                value: String(city.id),
                text: city.title
            }).attr('data-tipax-city-title', city.title));
        });

        element.val(selectedValue).prop('disabled', cities.length === 0);
    }

    function initializeSelectWoo(element) {
        if (!element.length || typeof $.fn.selectWoo !== 'function') {
            return;
        }

        try {
            if (element.hasClass('select2-hidden-accessible')) {
                element.selectWoo('destroy');
            }
            element.selectWoo({
                placeholder: messages.select_city || 'یک شهر را انتخاب کنید',
                width: '100%'
            });
        } catch (error) {
            // Native select remains fully functional when a theme owns SelectWoo.
        }
    }

    function getClassicField(type, field) {
        const checkoutForm = $('form.checkout').first();
        if (!checkoutForm.length) {
            return $();
        }

        return checkoutForm.find('#' + type + '_' + field + ', [name="' + type + '_' + field + '"]').first();
    }

    function ensureClassicCitySelect(type) {
        let cityElement = getClassicField(type, 'city');
        if (!cityElement.length || cityElement.is('select')) {
            return cityElement;
        }

        const select = $('<select></select>');
        Array.from(cityElement.get(0).attributes || []).forEach(function (attribute) {
            if (attribute.name !== 'type' && attribute.name !== 'value') {
                select.attr(attribute.name, attribute.value);
            }
        });
        select.addClass('tipax-city-select');
        select.data('tipax-previous-city', cityElement.val() || '');
        cityElement.replaceWith(select);
        return select;
    }

    function syncClassicCityContext(type) {
        const checkoutForm = $('form.checkout').first();
        const cityElement = getClassicField(type, 'city');
        const stateElement = getClassicField(type, 'state');
        if (!checkoutForm.length || !cityElement.length) {
            return;
        }

        const cityValue = String(cityElement.val() || '').trim();
        const cityTitle = cityElement.is('select')
            ? String(cityElement.find('option:selected').attr('data-tipax-city-title') || cityElement.find('option:selected').text() || '').trim()
            : cityValue;
        const selectedStateText = stateElement.is('select') ? stateElement.find('option:selected').text() : '';
        const stateTitle = getStateTitle(stateElement.val(), selectedStateText);

        let cityTitleInput = checkoutForm.find('input[name="tipax_' + type + '_city_title"]');
        let stateTitleInput = checkoutForm.find('input[name="tipax_' + type + '_state_title"]');
        if (!cityTitleInput.length) {
            cityTitleInput = $('<input>', {
                type: 'hidden',
                name: 'tipax_' + type + '_city_title'
            }).appendTo(checkoutForm);
        }
        if (!stateTitleInput.length) {
            stateTitleInput = $('<input>', {
                type: 'hidden',
                name: 'tipax_' + type + '_state_title'
            }).appendTo(checkoutForm);
        }

        cityTitleInput.val(cityValue && cityValue !== '0' ? cityTitle : '');
        stateTitleInput.val(stateTitle);
    }

    function requestClassicCheckoutRefresh() {
        window.clearTimeout(checkoutRefreshTimer);
        checkoutRefreshTimer = window.setTimeout(function () {
            $(document.body).trigger('update_checkout');
        }, 250);
    }

    function loadClassicCities(type) {
        if (!managesTipaxCities) {
            syncClassicCityContext(type);
            return;
        }

        const stateElement = getClassicField(type, 'state');
        const cityElement = ensureClassicCitySelect(type);
        if (!stateElement.length || !cityElement.length) {
            return;
        }

        const stateValue = String(stateElement.val() || '').trim();
        const selectedStateText = stateElement.is('select') ? stateElement.find('option:selected').text() : '';
        const stateTitle = getStateTitle(stateValue, selectedStateText);
        const stateKey = normalizeText(stateTitle || stateValue);
        const previousStateKey = String(cityElement.data('tipax-state-key') || '');
        const currentValue = previousStateKey === stateKey
            ? cityElement.val()
            : (cityElement.data('tipax-previous-city') || cityElement.val());

        if (stateKey && String(cityElement.data('tipax-loaded-state') || '') === stateKey) {
            syncClassicCityContext(type);
            return;
        }
        if (stateKey && String(cityElement.data('tipax-loading-state') || '') === stateKey) {
            return;
        }

        if (!stateKey) {
            if (String(cityElement.data('tipax-loaded-state') || '') === '__empty__') {
                return;
            }
            fillCitySelect(cityElement, [], '', messages.select_city || 'یک شهر را انتخاب کنید');
            cityElement.prop('disabled', true);
            cityElement.data('tipax-loaded-state', '__empty__');
            syncClassicCityContext(type);
            return;
        }

        cityElement
            .data('tipax-state-key', stateKey)
            .data('tipax-loading-state', stateKey)
            .prop('disabled', true)
            .empty()
            .append($('<option>', { value: '', text: messages.loading || 'در حال بارگذاری لیست شهرها...' }));

        fetchCities(stateValue, stateTitle).done(function (cities) {
            fillCitySelect(cityElement, cities, currentValue, messages.empty || 'شهری یافت نشد');
            cityElement.data('tipax-loaded-state', stateKey);
            cityElement.removeData('tipax-loading-state');
            initializeSelectWoo(cityElement);
            syncClassicCityContext(type);
        }).fail(function () {
            fillCitySelect(cityElement, [], '', messages.error || 'دریافت فهرست شهرها ناموفق بود');
            cityElement.data('tipax-loaded-state', stateKey);
            cityElement.removeData('tipax-loading-state');
            syncClassicCityContext(type);
        });
    }

    function getBlockSection(type) {
        const checkout = document.querySelector('.wp-block-woocommerce-checkout, .wc-block-checkout');
        if (!checkout) {
            return null;
        }

        const sectionSelector = type === 'shipping'
            ? '.wc-block-checkout__shipping-fields, .wp-block-woocommerce-checkout-shipping-address-block'
            : '.wc-block-checkout__billing-fields, .wp-block-woocommerce-checkout-billing-address-block';
        return checkout.querySelector(sectionSelector);
    }

    function getBlockFieldCandidates(type, field) {
        const checkout = document.querySelector('.wp-block-woocommerce-checkout, .wc-block-checkout');
        if (!checkout) {
            return [];
        }

        const directSelectors = [
            '#' + type + '-' + field,
            '#' + type + '_' + field,
            '[name="' + type + '_' + field + '"]'
        ];
        const candidates = [];
        directSelectors.forEach(function (selector) {
            checkout.querySelectorAll(selector).forEach(function (fieldElement) {
                if (!fieldElement.classList.contains('tipax-block-city-select') && candidates.indexOf(fieldElement) === -1) {
                    candidates.push(fieldElement);
                }
            });
        });

        const section = getBlockSection(type);
        if (section) {
            section.querySelectorAll(field === 'city'
                ? 'input[autocomplete="address-level2"]'
                : 'select[autocomplete="address-level1"], input[autocomplete="address-level1"]'
            ).forEach(function (fieldElement) {
                if (candidates.indexOf(fieldElement) === -1) {
                    candidates.push(fieldElement);
                }
            });
        }

        return candidates;
    }

    function getBlockField(type, field) {
        const candidates = getBlockFieldCandidates(type, field);
        if (!candidates.length) {
            return null;
        }

        if (field === 'city') {
            const visibleCandidate = candidates.find(function (fieldElement) {
                const wrapper = fieldElement.closest('.wc-block-components-text-input, .wc-block-components-combobox, .wc-block-components-address-form__city') || fieldElement;
                return !wrapper.classList.contains('tipax-city-source-field-hidden');
            });
            if (visibleCandidate) {
                return visibleCandidate;
            }
        }

        return candidates[candidates.length - 1];
    }

    function hideBlockCitySources(type) {
        getBlockFieldCandidates(type, 'city').forEach(function (sourceInput) {
            const wrapper = sourceInput.closest('.wc-block-components-text-input, .wc-block-components-combobox, .wc-block-components-address-form__city') || sourceInput.parentElement;
            if (wrapper) {
                wrapper.classList.add('tipax-city-source-field-hidden');
            }
            sourceInput.setAttribute('data-tipax-city-source', type);
            sourceInput.setAttribute('aria-hidden', 'true');
            sourceInput.tabIndex = -1;
        });
    }

    function setNativeInputValue(input, value) {
        if (!input) {
            return;
        }

        const prototype = input.tagName === 'SELECT' ? HTMLSelectElement.prototype : HTMLInputElement.prototype;
        const descriptor = Object.getOwnPropertyDescriptor(prototype, 'value');
        if (descriptor && descriptor.set) {
            descriptor.set.call(input, value);
        } else {
            input.value = value;
        }

        input.dispatchEvent(new Event('input', { bubbles: true }));
        input.dispatchEvent(new Event('change', { bubbles: true }));
    }

    function updateBlockCustomer(type, cityValue) {
        if (!window.wp || !wp.data || typeof wp.data.select !== 'function' || typeof wp.data.dispatch !== 'function') {
            return;
        }

        window.clearTimeout(blockCustomerTimer);
        blockCustomerTimer = window.setTimeout(function () {
            const storeName = 'wc/store/cart';
            const cartSelect = wp.data.select(storeName);
            const cartDispatch = wp.data.dispatch(storeName);
            if (!cartSelect || !cartDispatch || typeof cartSelect.getCartData !== 'function') {
                return;
            }

            const cartData = cartSelect.getCartData() || {};
            const addressKey = type === 'shipping' ? 'shippingAddress' : 'billingAddress';
            const actionName = type === 'shipping' ? 'setShippingAddress' : 'setBillingAddress';
            const address = Object.assign({}, cartData[addressKey] || {}, { city: cityValue });
            if (typeof cartDispatch[actionName] === 'function') {
                cartDispatch[actionName](address);
            }

            if (typeof cartDispatch.updateCustomerData === 'function') {
                const customerData = {};
                customerData[addressKey] = address;
                const updateRequest = cartDispatch.updateCustomerData(customerData, true);
                if (updateRequest && typeof updateRequest.then === 'function') {
                    updateRequest.then(scheduleInitialization, scheduleInitialization);
                }
            }
            scheduleInitialization();
        }, 120);
    }

    function commitBlockCity(type, cityValue) {
        const sourceInput = getBlockField(type, 'city');
        setNativeInputValue(sourceInput, cityValue);
        updateBlockCustomer(type, cityValue);
        scheduleInitialization();
    }

    function createBlockCitySelect(type, sourceInput) {
        const sourceWrapper = sourceInput.closest('.wc-block-components-text-input, .wc-block-components-combobox, .wc-block-components-address-form__city') || sourceInput.parentElement;
        if (!sourceWrapper || !sourceWrapper.parentNode) {
            return null;
        }

        const checkout = document.querySelector('.wp-block-woocommerce-checkout, .wc-block-checkout');
        let container = sourceWrapper.parentNode.querySelector(':scope > .tipax-block-city-field[data-tipax-address-type="' + type + '"]');
        if (!container) {
            container = document.createElement('div');
            container.className = 'tipax-block-city-field';
            container.setAttribute('data-tipax-address-type', type);

            const label = document.createElement('label');
            label.setAttribute('for', 'tipax-' + type + '-city');
            label.textContent = 'شهر';

            const select = document.createElement('select');
            select.id = 'tipax-' + type + '-city';
            select.className = 'tipax-block-city-select';
            select.required = true;

            container.appendChild(label);
            container.appendChild(select);
            sourceWrapper.parentNode.insertBefore(container, sourceWrapper.nextSibling);
        }

        if (checkout) {
            checkout.querySelectorAll('.tipax-block-city-field[data-tipax-address-type="' + type + '"]').forEach(function (candidate) {
                if (candidate !== container) {
                    candidate.remove();
                }
            });
        }
        hideBlockCitySources(type);
        return container.querySelector('select');
    }

    function loadBlockCities(type) {
        if (!managesTipaxCities) {
            return;
        }

        const sourceInput = getBlockField(type, 'city');
        const stateElement = getBlockField(type, 'state');
        if (!sourceInput || !stateElement) {
            return;
        }

        const selectNode = createBlockCitySelect(type, sourceInput);
        if (!selectNode) {
            return;
        }

        const citySelect = $(selectNode);
        const stateValue = String($(stateElement).val() || '').trim();
        const selectedStateText = stateElement.tagName === 'SELECT'
            ? stateElement.options[stateElement.selectedIndex] && stateElement.options[stateElement.selectedIndex].text
            : '';
        const stateTitle = getStateTitle(stateValue, selectedStateText);
        const stateKey = normalizeText(stateTitle || stateValue);
        const previousStateKey = String(citySelect.data('tipax-state-key') || '');
        const currentValue = previousStateKey === stateKey ? citySelect.val() : sourceInput.value;
        if (stateKey && String(citySelect.data('tipax-loaded-state') || '') === stateKey) {
            const cachedCities = cityCache[stateKey] || [];
            const matchingValue = findMatchingCity(cachedCities, sourceInput.value);
            if (matchingValue && citySelect.val() !== matchingValue) {
                citySelect.val(matchingValue);
            }
            return;
        }
        if (stateKey && String(citySelect.data('tipax-loading-state') || '') === stateKey) {
            return;
        }

        if (!stateKey) {
            if (String(citySelect.data('tipax-loaded-state') || '') === '__empty__') {
                return;
            }
            fillCitySelect(citySelect, [], '', messages.select_city || 'یک شهر را انتخاب کنید');
            citySelect.prop('disabled', true);
            citySelect.data('tipax-loaded-state', '__empty__');
            return;
        }

        if (previousStateKey && previousStateKey !== stateKey) {
            commitBlockCity(type, '');
        }

        citySelect
            .data('tipax-state-key', stateKey)
            .data('tipax-loading-state', stateKey)
            .prop('disabled', true)
            .empty()
            .append($('<option>', { value: '', text: messages.loading || 'در حال بارگذاری لیست شهرها...' }));

        // MutationObserver runs again when the loading option is inserted. Only
        // rotate the token when a real request starts, not on that observer pass.
        const requestToken = String(Date.now()) + Math.random();
        blockRequestTokens[type] = requestToken;

        fetchCities(stateValue, stateTitle).done(function (cities) {
            if (blockRequestTokens[type] !== requestToken || !document.body.contains(selectNode)) {
                return;
            }

            fillCitySelect(citySelect, cities, currentValue, messages.empty || 'شهری یافت نشد');
            citySelect.data('tipax-loaded-state', stateKey);
            citySelect.removeData('tipax-loading-state');
        }).fail(function () {
            if (blockRequestTokens[type] !== requestToken || !document.body.contains(selectNode)) {
                return;
            }

            fillCitySelect(citySelect, [], '', messages.error || 'دریافت فهرست شهرها ناموفق بود');
            citySelect.data('tipax-loaded-state', stateKey);
            citySelect.removeData('tipax-loading-state');
        });
    }

    function initializeCheckoutCities() {
        const types = Array.isArray(settings.types) && settings.types.length ? settings.types : ['billing', 'shipping'];
        if (managesTipaxCities && document.querySelector('.wp-block-woocommerce-checkout, .wc-block-checkout')) {
            document.body.classList.add('tipax-manages-block-cities');
        }
        types.forEach(function (type) {
            loadClassicCities(type);
            loadBlockCities(type);
            if (!managesTipaxCities) {
                syncClassicCityContext(type);
            }
        });
    }

    function scheduleInitialization() {
        window.clearTimeout(initializeTimer);
        initializeTimer = window.setTimeout(initializeCheckoutCities, 60);
    }

    $(document.body).on('change select2:select', 'form.checkout select[id$="_state"], form.checkout input[id$="_state"], form.checkout [name$="_state"]', function () {
        loadClassicCities(getFieldType($(this)));
    });

    $(document.body).on('change select2:select', 'form.checkout select[id$="_city"], form.checkout input[id$="_city"], form.checkout [name$="_city"]', function () {
        const type = getFieldType($(this));
        syncClassicCityContext(type);
        requestClassicCheckoutRefresh();
    });

    $(document.body).on('input', 'form.checkout input[id$="_city"], form.checkout [name$="_city"]', function () {
        syncClassicCityContext(getFieldType($(this)));
    });

    $(document.body).on('updated_checkout', scheduleInitialization);
    $(document.body).on('change input', '#shipping-state, #billing-state, .wc-block-checkout [autocomplete="address-level1"]', scheduleInitialization);
    $(document.body).on('change input', '.wc-block-checkout input[autocomplete="address-level2"], #shipping-city, #billing-city', scheduleInitialization);
    $(document.body).on('change', '.tipax-block-city-select', function () {
        const cityTitle = String($(this).find('option:selected').attr('data-tipax-city-title') || '').trim();
        commitBlockCity(
            String($(this).closest('.tipax-block-city-field').data('tipax-address-type') || 'shipping'),
            cityTitle
        );
    });

    $('form.checkout').on('checkout_place_order', function () {
        (settings.types || ['billing', 'shipping']).forEach(syncClassicCityContext);
        return true;
    });

    if (window.MutationObserver) {
        const observer = new MutationObserver(function (mutations) {
            const needsInitialization = mutations.some(function (mutation) {
                if (mutation.type === 'childList') {
                    return true;
                }
                const target = mutation.target;
                return target instanceof Element && Boolean(
                    target.closest('.wc-block-components-text-input, .wc-block-components-address-form__city')
                );
            });
            if (needsInitialization) {
                scheduleInitialization();
            }
        });
        observer.observe(document.body, {
            childList: true,
            subtree: true,
            attributes: true,
            attributeFilter: ['class']
        });
    }

    scheduleInitialization();
});
