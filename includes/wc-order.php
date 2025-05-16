<?php
if(!class_exists('tipax_shipping_orders_common'))
{
    class tipax_shipping_orders_common
    {
        private static $hpos_enabled = null;
         function is_wc_hpos_enabled()
            {
                if(is_null(self::$hpos_enabled))
                {
                    if(class_exists('Automattic\WooCommerce\Utilities\OrderUtil'))
                    {
                        self::$hpos_enabled = Automattic\WooCommerce\Utilities\OrderUtil::custom_orders_table_usage_is_enabled();
                    }else
                    {
                        self::$hpos_enabled = false;
                    }
                }
                return self::$hpos_enabled;
            }
    }
}

 
function tipax_barcode_order_meta_box() {
    $instance = new tipax_shipping_orders_common();
    $order_details_screen = $instance->is_wc_hpos_enabled() ? wc_get_page_screen_id( 'shop-order' ) : 'shop_order';
	add_meta_box('tipax-barcode-box', __('Tipax Barcodes','tipax-shipping'), 'tipax_shipping_barcode_meta_box',$order_details_screen, 'side', 'default');
}

add_action( 'add_meta_boxes', 'tipax_barcode_order_meta_box' );


function tipax_shipping_barcode_meta_box($order) {
    $desired_shipping_method = 'tipax_shipping_method';
    if (!is_a($order, 'WC_Order')) {
        $order = wc_get_order($order->ID);
        if (!is_a($order, 'WC_Order')) {
            $error = true;
        } else {
            $error = false;
        }
    } else {
        $error = false;
    }
    if(!$error) {
        foreach ($order->get_shipping_methods() as $shipping_item) {
            if ($shipping_item->get_method_id() === $desired_shipping_method) {
                require 'vendor/autoload.php';
                    echo '<div id="printable-content" style="text-align:center">';
                $generator = new Picqer\Barcode\BarcodeGeneratorPNG();
                $is_barcode = 0;
                    foreach ($order->get_items() as $item_id => $item) {
                        $barcodes = explode(' ', $item->get_meta("tipax_tracking_code"));
                        if($barcodes){
                            foreach($barcodes as $barcode){
                                if($barcode != '' || $barcode == ' '){
                                    $is_barcode++;
                                    echo '<img style="max-width: 100%" src="data:image/png;base64,' . base64_encode($generator->getBarcode($barcode, $generator::TYPE_CODE_128,2,80)) . '"><br />'.$barcode.'<br /><br />';
                                }
                            }
                        }
                    }
                    if($is_barcode){
                        echo '</div><button onclick="printContent()" class="button save_order button-primary">'.__('Print Barcodes', 'tipax-shipping').'</button>';
                        echo '<script>
                            function printContent() {
                                var content = document.getElementById("printable-content").innerHTML;
                                var printWindow = window.open("", "_blank");
                                printWindow.document.open();
                                printWindow.document.write("<html><head><title>Print</title><style>@media print{body{background: #fff;}.wrap{margin: 0;padding: 0;width: 148mm;height: 210mm;}}</style></head><body>" + content + "</body></html>");
                                printWindow.document.close();
                                printWindow.print();
                                printWindow.close();
                            }
                        </script>';
                    } else {
                        echo '</div>'.__('There is no barcodes.','tipax-shipping');
                    }
                break;
            } else {
                echo __('Tipax is not shipping method for this order.','tipax-shipping');
            }
        }
    } else {
        echo __('Order type is not WooCommerce.','tipax-shipping');
    }
}