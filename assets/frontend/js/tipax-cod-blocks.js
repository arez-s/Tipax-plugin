(function () {
    'use strict';

    const registry = window.wc && window.wc.wcBlocksRegistry;
    const settingsApi = window.wc && window.wc.wcSettings;
    const element = window.wp && window.wp.element;
    const htmlEntities = window.wp && window.wp.htmlEntities;

    if (!registry || !settingsApi || !element || !htmlEntities) {
        return;
    }

    const settings = settingsApi.getSetting('tipax_cod_data', {});
    const title = htmlEntities.decodeEntities(settings.title || 'پرداخت نقدی تیپاکس');
    const description = htmlEntities.decodeEntities(
        settings.description || 'مبلغ سفارش هنگام تحویل مرسوله توسط تیپاکس دریافت می‌شود.'
    );
    const content = element.createElement('div', null, description);

    function isTipaxRate(rate) {
        if (typeof rate === 'string') {
            return rate.indexOf('tipax_shipping_method') !== -1;
        }

        if (!rate || typeof rate !== 'object') {
            return false;
        }

        const rateId = rate.rate_id || rate.rateId || rate.method_id || rate.methodId || rate.id || '';
        return String(rateId).indexOf('tipax_shipping_method') !== -1;
    }

    registry.registerPaymentMethod({
        name: 'tipax_cod',
        label: element.createElement('span', null, title),
        content: content,
        edit: content,
        ariaLabel: title,
        canMakePayment: function (context) {
            const selected = context && context.selectedShippingMethods;
            return !Array.isArray(selected) || selected.length === 0 || selected.some(isTipaxRate);
        },
        supports: {
            features: Array.isArray(settings.supports) ? settings.supports : ['products']
        }
    });
}());
