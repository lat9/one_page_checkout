<?php
// -----
// Part of the One-Page Checkout plugin, provided under GPL 2.0 license by lat9 (cindy@vinosdefrutastropicales.com).
// Copyright (C) 2013-2016, Vinos de Frutas Tropicales.  All rights reserved.
//
class zcAjaxOnePageCheckout extends base
{
    // -----
    // Update the order's shipping method when the selection has changed on the checkout_one page.
    //
    public function updateShipping ()
    {
        if (!(defined ('CHECKOUT_ONE_ENABLED') && CHECKOUT_ONE_ENABLED == 'true')) {
            trigger_error ('Invalid request; One-Page Checkout processing is not installed or not enabled.', E_USER_ERROR);
        }
        // -----
        // Since we're running as a function, need to declare the objects we're instantiating here, for use by the various classes
        // involved in creating the order's total-block.
        //
        global $order, $currencies, $checkout_one, $total_weight, $total_count, $discount_coupon;
        global $shipping_weight, $uninsurable_value, $shipping_quoted, $shipping_num_boxes, $current_page_base, $current_page, $template;

        // -----
        // Include the class required by some of the shipping methods, e.g. USPS.
        //
        require_once (DIR_WS_CLASSES . 'http_client.php');  
        
        $_GET['main_page'] = $current_page_base = $current_page = FILENAME_CHECKOUT_ONE;
        $total_count = $_SESSION['cart']->count_contents ();
        $total_weight = $_SESSION['cart']->show_weight();
        
        if ($_SESSION['cc_id']) {
            $discount_coupon_query = "SELECT coupon_code FROM " . TABLE_COUPONS . " WHERE coupon_id = :couponID LIMIT 1";
            $discount_coupon_query = $db->bindVars ($discount_coupon_query, ':couponID', $_SESSION['cc_id'], 'integer');
            $discount_coupon = $db->Execute ($discount_coupon_query);
        }
        
        require (DIR_WS_CLASSES . 'order.php');
        $order = new order ();
        list ($module, $method) = explode ('_', $_POST['shipping']);
        
        $checkout_one->debug_message ("Shipping method change to $module ($method). Current values: " . print_r ($_SESSION['shipping'], true), true, 'zcAjaxOnePageCheckout');

        global ${$module};
        
        require (DIR_WS_CLASSES . 'shipping.php');
        $shipping_modules = new shipping;

        $quote = $shipping_modules->quote ($method, $module);
        if (isset ($quote[0]['methods'][0]['title']) && isset ($quote[0]['methods'][0]['cost'])) {
            $_SESSION['shipping'] = array (
                'id' => $_POST['shipping'],
                'title' => $quote[0]['module'] . ' (' . $quote[0]['methods'][0]['title'] . ')',
                'cost' => $quote[0]['methods'][0]['cost']
            );
            $order = new order ();
        }
        $shipping_modules = new shipping ($_SESSION['shipping']);

        $checkout_one->debug_message ("Shipping method changed: " . print_r ($quote, true) . print_r ($_SESSION['shipping'], true), false, 'zcAjaxOnePageCheckout');

        require (DIR_WS_CLASSES . 'order_total.php');
        $order_total_modules = new order_total ();

        ob_start ();
        $order_total_modules->process ();
        $order_total_modules->output ();
        $order_total_html = ob_get_clean ();
        ob_flush ();

        return (
            array (
                'orderTotalHtml' => $order_total_html,
                'orderTotal' => $currencies->value ($order->info['total'], $order->info['currency'])
            )
        );
    }
}
