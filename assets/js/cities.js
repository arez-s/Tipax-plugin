jQuery(function ($) {

    function tsm_selectWoo(element) {
        let select2_args = {
            placeholder: element.attr('data-placeholder') || element.attr('placeholder') || '',
            width: '100%'
        };

        element.selectWoo(select2_args);
    }

    function tsm_state_changed(type, state) {

        let city_element = $('select#' + type + '_city');

        city_element.html('<option value="0">در حال بارگزاری لیست شهرها...</option>');

        let data = {
            'action': 'get_tipax_cities',
            'security': tsm_settings.nonce,
            'state': state,
            'type': type
        };

        $.post(tsm_settings.ajax_url, data, function (response) {
            city_element.html(response);
        });

        tsm_selectWoo(city_element);
    }

    $("select[id$='_state']").on('select2:select', function (e) {
        let type = $(this).attr('id').indexOf('billing') !== -1 ? 'billing' : 'shipping';
        let data = e.params.data;
        tsm_state_changed(type, data.text);
    });
    $("select[id='billing_state']").on('select2:select', function (e) {
        let type = $(this).attr('id').indexOf('billing') !== -1 ? 'billing' : 'shipping';
        let data = e.params.data;
        $("select[id='shipping_state']").val($("select[id='billing_state']").val()).trigger('change');
        tsm_state_changed('shipping', data.text);
    });
    
    $(document).ready(function () {
        var shipping = document.getElementById("ship-to-different-address-checkbox");
        var orderType;
    
        if (shipping !== null) {
            if (shipping.checked) {
                orderType = 'shipping';
            } else {
                orderType = 'billing';
            }
        } else {
            orderType = 'billing';
        }
    
        var stateOption = $('#billing_state').find(':selected');
        var stateText = stateOption.text();
    
        tsm_state_changed(orderType, stateText);
    });

    tsm_settings.types.forEach(type => {
        tsm_selectWoo($('select#' + type + '_state'));
        tsm_selectWoo($('select#' + type + '_city'));
    });
    
    $("select[id$='_city']").on('select2:select', function (e) {
        // Call WooCommerce update order review
        $(document.body).trigger('update_checkout');
    });

});