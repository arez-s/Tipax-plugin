<?php
add_action('admin_menu', 'add_tipax_shipping_plugin_page');

function add_tipax_shipping_plugin_page() {
    add_submenu_page(
        'woocommerce',
        __('Tipax Shipping', 'tipax-shipping'),
        __('Tipax Shipping', 'tipax-shipping'),
        'manage_options',
        'tipax-shipping-method',
        'render_tipax_shipping_plugin_page'
    );
}


function render_tipax_shipping_plugin_page() {
    $remaining_assets = get_wallet_remaining_asset();

    echo '<div class="wrap">';
    echo '<h1>تیپاکس</h1>';
    echo '<p>موجودی کیف پول: ' . $remaining_assets . ' ریال</p>';

    echo '<form method="post" action="">';
    echo '<label for="recharge_amount">مقدار شارژ به ریال:</label>';
    echo '<input type="number" id="recharge_amount" name="recharge_amount" required>';
    echo '<input type="submit" name="submit_recharge" value="شارژ کیف پول">';
    echo '</form>';

    if (isset($_POST['submit_recharge'])) {
        recharge_wallet($_POST['recharge_amount']);
    }

    echo '</div>';
}

function get_wallet_remaining_asset(){
    $request = new Request();
    $accessToken = get_option('woocommerce_tipax_shipping_method_accesstoken', null);
    $data = [];
    $response = $request->apiRequest('Customers/Wallet', $data, "GET", $accessToken);
    if(is_numeric($response)){
        return number_format($response);
    } else {
        return 'ERROR';
    }
}
function recharge_wallet($amount){
    if(is_numeric($amount) && $amount > 1000){
        $request = new Request();
        $accessToken = get_option('woocommerce_tipax_shipping_method_accesstoken', null);
        $data = new stdClass;
        $data->amount = $amount;
        $data->frontCallBackUrl = admin_url().'admin.php?page=tipax-shipping-method';
        
        $response = $request->apiRequest('Customers/RechargeWallet', $data, "POST", $accessToken);
        header("Location: ".$response->paymentURL);
    } else {
        echo 'مقدار شارژ معتبر نیست.';
    }
}