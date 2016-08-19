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
        global $db, $order, $currencies, $checkout_one, $total_weight, $total_count, $discount_coupon, $messageStack;
        global $shipping_weight, $uninsurable_value, $shipping_quoted, $shipping_num_boxes, $current_page_base, $current_page, $template, $template_dir;
        global $language_page_directory;

        // -----
        // Load the One-Page Checkout page's language files.
        //
        $_GET['main_page'] = $current_page_base = $current_page = FILENAME_CHECKOUT_ONE;
        require (DIR_WS_MODULES . zen_get_module_directory ('require_languages.php'));        
        
        $error_message = $order_total_html = $shipping_html = '';
        $status = 'ok';
        
        // -----
        // Check for a session timeout (i.e. no more customer_id in the session), returning a specific
        // status and message for that case.
        //
        if (!isset ($_SESSION['customer_id'])) {
            $status = 'timeout';
            $checkout_one->debug_message ("Session time-out detected.", 'zcAjaxOnePageCheckout');
        } else {
            // -----
            // Include the class required by some of the shipping methods, e.g. USPS.
            //
            require_once (DIR_WS_CLASSES . 'http_client.php'); 

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
            
            $checkout_one->debug_message ("Shipping method change to $module ($method). Current values: " . print_r ($_SESSION['shipping'], true) . PHP_EOL . print_r ($_POST, true), true, 'zcAjaxOnePageCheckout');

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
            } else {
                $checkout_one->debug_message ('Shipping method returned empty result; no longer valid.');
                $error_message = ERROR_PLEASE_RESELECT_SHIPPING_METHOD;
                $status = 'invalid';
                unset ($GLOBALS[_SESSION]['shipping']);
            }
            $order = new order ();
            $shipping_modules = new shipping (isset ($_SESSION['shipping']) ? $_SESSION['shipping'] : '');
            
            $shipping_html = '';
            if ($status == 'invalid') {
                $quotes = $shipping_modules->quote ();
                ob_start ();
                require ($template->get_template_dir ('tpl_modules_checkout_one_shipping.php', DIR_WS_TEMPLATE, $current_page_base, 'templates'). '/tpl_modules_checkout_one_shipping.php');
                $shipping_html = ob_get_clean ();
                ob_flush ();
            }
            
            if ($status == 'ok' && isset ($quote[0]['error'])) {
                $status = 'error';
                if (count ($messageStack->messages) > 0) {
                    foreach ($messageStack->messages as $current_message) {
                        if ($current_message['class'] == 'checkout_shipping') {
                            $error_message = strip_tags ($current_message['text']);
                            break;
                        }
                    }
                    $messageStack->reset ();
                }
            }
            unset ($GLOBALS[_SESSION]['messageToStack']);

            $checkout_one->debug_message ("Shipping method changed: " . print_r ($quote, true) . print_r ($_SESSION['shipping'], true), false, 'zcAjaxOnePageCheckout');

            require (DIR_WS_CLASSES . 'order_total.php');
            $order_total_modules = new order_total ();

            ob_start ();
            $order_total_modules->process ();
            $order_total_modules->output ();
            $order_total_html = ob_get_clean ();
            ob_flush ();
        }
        
        $return_array = array (
            'status' => $status,
            'errorMessage' => $error_message,
            'orderTotalHtml' => $order_total_html,
            'shippingHtml' => $shipping_html,
        );
        $checkout_one->debug_message ('Returning:' . print_r ($return_array, true));

        return $return_array;
    }
}
