<?php

/**
 * Shpping File
 * This class is for adding actions of shipping to woocommerce
 */

if (!defined('ABSPATH')) {
    exit;
}

//init the tipax shipping class
add_action('woocommerce_shipping_init', 'tipax_shipping_method_init');
//add tipax shipping method to shipping methods
add_filter('woocommerce_shipping_methods', 'add_tipax_shipping_method');
//add receiver phone field to checkout page
add_action('woocommerce_before_checkout_billing_form', 'phone_checkout_field');
//add receiver plaque field to checkout page
add_action('woocommerce_after_checkout_billing_form', 'plaque_checkout_field');
//add receiver phone value and plaque to order
add_action('woocommerce_checkout_update_order_meta', 'update_order_meta');
//validation for receiver phone field in checkout page
add_action('woocommerce_checkout_process', 'validate_fields' );



function get_woocommerce_checkout_page_id() {
    $checkout_page_id = get_option('woocommerce_checkout_page_id');
    return $checkout_page_id;
}

add_filter('use_block_editor_for_post', 'disable_gutenberg_editor_for_specific_page', 10, 2);

function disable_gutenberg_editor_for_specific_page($can_edit, $post)
{
    // Specify the ID of the page where you want to disable Gutenberg
    $restricted_page_id = (int)get_woocommerce_checkout_page_id(); // Replace 123 with your desired page ID

    if ($post->ID === $restricted_page_id) {
        return false; // Disable the block editor for this specific page
    }

    return $can_edit; // Enable the block editor for other pages
}
function modify_content_for_specific_page($content) {
    $targeted_page_id = (int)get_woocommerce_checkout_page_id(); // Replace 123 with the ID of your specific page

    // Check if the current post being viewed is the targeted page
    if (is_page($targeted_page_id)) {
        $modified_content = '[woocommerce_checkout]';
        return $modified_content; // Replace content with WooCommerce checkout shortcode
    }

    return $content;
}
add_filter('the_content', 'modify_content_for_specific_page');
/**
 * init the tipax shipping class 
 */
function tipax_shipping_method_init()
{
    require_once TSM_DIR . 'includes/classes/WC_Tipax_Shipping_Method.php';
}

/**
 * add tipax shipping method to shipping methods
 * @param $methods
 * @return array
 */
function add_tipax_shipping_method($methods)
{
    $methods["tipax_shipping_method"] = 'WC_Tipax_Shipping_Method';
    return $methods;
}


/**
 * add receiver plaque field to checkout page
 * @param $checkout
 */
function plaque_checkout_field($checkout)
{

    echo '<div id="custom_checkout_field">';

    woocommerce_form_field('plaque_field', array(

        'type' => 'text',

        'class' => array(

            'my-field-class form-row-wide'

        ) ,

        'label' => __('Plaque','tipax-shipping') ,
        'required'=>true,

        'placeholder' => __('Enter the plaque of receiver','tipax-shipping') ,

    ) ,

        $checkout->get_value('plaque_field'));

    echo '</div>';

}


/**
 * add receiver phone field to checkout page
 * @param $checkout
 */
function phone_checkout_field($checkout)
{
    /*echo "<pre>";
    print_r($checkout);
    die();*/

    /*echo "<pre>";
    print_r($checkout->checkout_fields);*/

    /*echo "<pre>";
    foreach ($checkout->checkout_fields as $fields){
        foreach ($fields as $key=>$field) {
            $customField[] = $key;
        }
    }

    print_r($customField);
    die();*/

    echo '<div id="custom_checkout_field">';

    woocommerce_form_field('phone_field', array(

        'type' => 'text',

        'class' => array(

            'my-field-class form-row-wide'

        ) ,

        'label' => __('Phone','tipax-shipping') ,
        'required'=>true,

        'placeholder' => __('Enter the phone number of receiver','tipax-shipping') ,

    ) ,

        $checkout->get_value('phone_field'));

    echo '</div>';

}


/**
 * add receiver phone value to order
 * add receiver plaque value to order
 * @param $order_id
 */
function update_order_meta($order_id){
    if (!empty($_POST['phone_field'])) {
        update_post_meta($order_id, 'phone',sanitize_text_field($_POST['phone_field']));
    }
    if (!empty($_POST['plaque_field'])) {
        update_post_meta($order_id, 'plaque',sanitize_text_field($_POST['plaque_field']));
    }
}


/**
 * validation for receiver phone field in checkout page
 * validation for receiver plaque field in checkout page
 */
function validate_fields() {
    if ( ! $_POST['phone_field'] ) {
        wc_add_notice(__('Please enter your Phone Number','tipax-shipping'), 'error' );
    }
    if ( ! $_POST['plaque_field'] ) {
        wc_add_notice(__('Please enter your plaque','tipax-shipping'), 'error' );
    }

    if(isset($_POST['billing_postcode']) && $_POST['billing_postcode'] != null){
        if ( postalcodeValidate($_POST['billing_postcode']) == null ) {
            wc_add_notice(__('Please enter valid postalcode','tipax-shipping'), 'error' );
        }
    }
}