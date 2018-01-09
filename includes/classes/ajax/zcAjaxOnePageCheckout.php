<?php
// -----
// Part of the One-Page Checkout plugin, provided under GPL 2.0 license by lat9 (cindy@vinosdefrutastropicales.com).
// Copyright (C) 2013-2017, Vinos de Frutas Tropicales.  All rights reserved.
//
class zcAjaxOnePageCheckout extends base
{
    // -----
    // Update the order's shipping method when the selection has changed on the checkout_one page.
    //
    public function updateShipping()
    {
        if (!(defined('CHECKOUT_ONE_ENABLED') && (CHECKOUT_ONE_ENABLED == 'true' || CHECKOUT_ONE_ENABLED == 'conditional'))) {
            trigger_error('Invalid request; One-Page Checkout processing is not installed or not enabled.', E_USER_ERROR);
        }
        // -----
        // Since we're running as a function, need to declare the objects we're instantiating here, for use by the various classes
        // involved in creating the order's total-block.
        //
        global $db, $order, $currencies, $checkout_one, $total_weight, $total_count, $discount_coupon, $messageStack;
        global $shipping_weight, $uninsurable_value, $shipping_quoted, $shipping_num_boxes, $template, $template_dir;
        global $language_page_directory;

        // -----
        // Load the One-Page Checkout page's language files.
        //
        $this->loadLanguageFiles();       
        
        $error_message = $order_total_html = $shipping_html = '';
        $status = 'ok';
        $shipping_choose_message = '';
        
        // -----
        // Check for a session timeout (i.e. no more customer_id in the session), returning a specific
        // status and message for that case.
        //
        if (!isset($_SESSION['customer_id'])) {
            $status = 'timeout';
            $checkout_one->debug_message("Session time-out detected.", 'zcAjaxOnePageCheckout::updateShipping');
        } else {
            // -----
            // Include the class required by some of the shipping methods, e.g. USPS.
            //
            require_once DIR_WS_CLASSES . 'http_client.php'; 

            $total_count = $_SESSION['cart']->count_contents();
            $total_weight = $_SESSION['cart']->show_weight();
            
            if (isset($_SESSION['cc_id'])) {
                $discount_coupon_query = "SELECT coupon_code FROM " . TABLE_COUPONS . " WHERE coupon_id = :couponID LIMIT 1";
                $discount_coupon_query = $db->bindVars($discount_coupon_query, ':couponID', $_SESSION['cc_id'], 'integer');
                $discount_coupon = $db->Execute($discount_coupon_query);
            }
            
            // -----
            // Manage the shipping-address, based on the "Shipping Address, Same as Billing?" checkbox value submitted.
            //
            if ($_POST['shipping_is_billing'] == 'true') {
                $_SESSION['sendto'] = $_SESSION['billto'];
                $_SESSION['shipping_billing'] = true;
                $ship_to = 'billing';
            } else {
                $_SESSION['sendto'] = $_SESSION['opc_sendto_saved'];
                $ship_to = 'shipping';
                $_SESSION['shipping_billing'] = false;
            }
            
            require DIR_WS_CLASSES . 'order.php';
            $order = new order();
            list($module, $method) = explode('_', $_POST['shipping']);
            
            $pass = false;
            $free_shipping = false;
            if (defined('MODULE_ORDER_TOTAL_SHIPPING_FREE_SHIPPING') && MODULE_ORDER_TOTAL_SHIPPING_FREE_SHIPPING == 'true') {
                switch (MODULE_ORDER_TOTAL_SHIPPING_DESTINATION) {
                    case 'national':
                        if ($order->delivery['country_id'] == STORE_COUNTRY) {
                            $pass = true;
                        }
                        break;

                    case 'international':
                        if ($order->delivery['country_id'] != STORE_COUNTRY) {
                            $pass = true;
                        }
                        break;

                    case 'both':
                        $pass = true;
                        break;
                }
                if ($pass && $_SESSION['cart']->show_total() >= MODULE_ORDER_TOTAL_SHIPPING_FREE_SHIPPING_OVER) {
                    $free_shipping = true;
                }
            }        
            $is_virtual_order = ($_SESSION['cart']->get_content_type() == 'virtual');
            
            $checkout_one->debug_message(
                "Shipping method change to $module ($method), sendto ($ship_to), free_shipping ($free_shipping), virtual order ($is_virtual_order). Current values: " .
                var_export($_SESSION['shipping'], true) . PHP_EOL . 
                var_export($_POST, true), true, 'zcAjaxOnePageCheckout'
            );

            if ($free_shipping || $is_virtual_order) {
                if ($_POST['shipping'] != 'free_free') {
                    $checkout_one->debug_message('Invalid shipping method (' . $_POST['shipping'] . ') submitted; free-shipping and virtual orders should be free_free.', false, 'zcAjaxOnePageCheckout');
                    $status = 'error';
                    $error_message = ERROR_UNKNOWN_SHIPPING_SELECTION;
                }
            } else {
                global ${$module};           
                require DIR_WS_CLASSES . 'shipping.php';
                $shipping_modules = new shipping;
            
//-bof-product_delivery_by_postcode (PDP) integration
                if (function_exists('zen_get_UKPostcodeFirstPart')) {
                    global $localdelivery, $storepickup;
                    
                    $check_delivery_postcode = $order->delivery['postcode'];
              
                    // shorten UK / Canada postcodes to use first part only
                    $check_delivery_postcode = zen_get_UKPostcodeFirstPart($check_delivery_postcode);

                    // now check db for allowed postcodes and enable / disable relevant shipping modules
                    if (!in_array($check_delivery_postcode, explode(",", MODULE_SHIPPING_LOCALDELIVERY_POSTCODE))) {
                         $localdelivery = false;
                    }
                  
                    if (!in_array($check_delivery_postcode, explode(",", MODULE_SHIPPING_STOREPICKUP_POSTCODE))) {
                        $storepickup = false;
                    }
                }
//-eof-product_delivery_by_postcode (PDP) integration
    
                $quote = $shipping_modules->quote($method, $module);
                $checkout_one->debug_message ("Current quote for " . $_POST['shipping'] . ": " . var_export ($quote, true) . PHP_EOL);
                if (isset($quote[0]['methods'][0]['title']) && isset($quote[0]['methods'][0]['cost'])) {
                    $_SESSION['shipping'] = array(
                        'id' => $_POST['shipping'],
                        'title' => $quote[0]['module'] . ' (' . $quote[0]['methods'][0]['title'] . ')',
                        'cost' => $quote[0]['methods'][0]['cost']
                    );
                    if (isset($quote[0]['extras'])) {
                        $_SESSION['shipping']['extras'] = $quote[0]['extras'];
                    }
                } else {
                    $checkout_one->debug_message('Shipping method returned empty result; no longer valid.');
                    $error_message = ERROR_PLEASE_RESELECT_SHIPPING_METHOD;
                    $status = 'invalid';
                    unset($GLOBALS[_SESSION]['shipping']);
                }
                $order = new order();
                $shipping_modules = new shipping(isset ($_SESSION['shipping']) ? $_SESSION['shipping'] : '');
                
//-bof-product_delivery_by_postcode (PDP) integration
                if (function_exists('zen_get_UKPostcodeFirstPart')) {
                    global $localdelivery, $storepickup;
                    
                    $check_delivery_postcode = $order->delivery['postcode'];
              
                    // shorten UK / Canada postcodes to use first part only
                    $check_delivery_postcode = zen_get_UKPostcodeFirstPart($check_delivery_postcode);

                    // now check db for allowed postcodes and enable / disable relevant shipping modules
                    if (!in_array($check_delivery_postcode, explode(",", MODULE_SHIPPING_LOCALDELIVERY_POSTCODE))) {
                         $localdelivery = false;
                    }
                  
                    if (!in_array($check_delivery_postcode, explode(",", MODULE_SHIPPING_STOREPICKUP_POSTCODE))) {
                        $storepickup = false;
                    }
                }
//-eof-product_delivery_by_postcode (PDP) integration  
              
                $shipping_html = '';
                if ($status == 'invalid' || $_POST['shipping_request'] == 'shipping-billing') {
                    $quotes = $shipping_modules->quote();
                    if (count($quotes) > 1 && count($quotes[0]) > 1) {
                        $shipping_choose_message = TEXT_CHOOSE_SHIPPING_METHOD;
                    } else {
                        $shipping_choose_message = TEXT_ENTER_SHIPPING_INFORMATION;
                    }
                    $checkout_one->debug_message("Updating shipping section, message ($shipping_choose_message), quotes:" . var_export($quotes, true), false, 'zcAjaxOnePageCheckout');
                    
                    ob_start ();
                    require $template->get_template_dir('tpl_modules_checkout_one_shipping.php', DIR_WS_TEMPLATE, $GLOBALS['current_page_base'], 'templates'). '/tpl_modules_checkout_one_shipping.php';
                    $shipping_html = ob_get_clean();
                    ob_flush();
                }
                
                if ($status == 'ok' && isset ($quote[0]['error'])) {
                    $status = 'error';
                    if (count ($messageStack->messages) > 0) {
                        foreach ($messageStack->messages as $current_message) {
                            if ($current_message['class'] == 'checkout_shipping') {
                                $error_message = strip_tags($current_message['text']);
                                break;
                            }
                        }
                        $messageStack->reset();
                    }
                }
                unset($GLOBALS[_SESSION]['messageToStack']);

                $checkout_one->debug_message("Shipping method changed: " . var_export($quote, true) . var_export($_SESSION['shipping'], true), false, 'zcAjaxOnePageCheckout');
            }

            require DIR_WS_CLASSES . 'order_total.php';
            $order_total_modules = new order_total();

            ob_start();
            $order_total_modules->process();
            $order_total_modules->output();
            $order_total_html = ob_get_clean();
            ob_flush();
        }
        
        $return_array = array(
            'status' => $status,
            'errorMessage' => $error_message,
            'orderTotalHtml' => $order_total_html,
            'shippingHtml' => $shipping_html,
            'shippingMessage' => $shipping_choose_message,
        );
        $checkout_one->debug_message('updateShipping, returning:' . var_export($return_array, true) . var_export($_SESSION['shipping'], true));

        return $return_array;
    }
    
    // -----
    // Function to return the current value for an address (either bill-to or send-to) block.  Used
    // within the plugin's jQuery when the customer has cancelled an address-block change.
    //
    public function restoreAddressValues()
    {
        $error_message = $address_html = '';
        $status = 'ok';
        
        // -----
        // Check for a session timeout (i.e. no more customer_id in the session), returning a specific
        // status and message for that case.
        //
        if (!isset($_SESSION['customer_id'])) {
            $status = 'timeout';
            $checkout_one->debug_message("Session time-out detected.", 'zcAjaxOnePageCheckout::restoreAddressValues');
        } else {
            $this->loadLanguageFiles();
            if (!isset($_POST['which']) || ($_POST['which'] != 'bill' && $_POST['which'] != 'ship')) {
                $status = 'error';
                $error_message = ERROR_INVALID_REQUEST;
                trigger_error('$_POST[\'which\'] not set or invalid, nothing to do.', E_USER_WARNING);
            } else {
                $flagDisablePaymentAddressChange = false;
                ob_start();
                require $GLOBALS['template']->get_template_dir('tpl_modules_opc_billing_address.php', DIR_WS_TEMPLATE, $GLOBALS['current_page_base'], 'templates'). '/tpl_modules_opc_billing_address.php';
                $address_html = ob_get_clean();
                ob_flush();
            }
        }
        
        $return_array = array(
            'status' => $status,
            'errorMessage' => $error_message,
            'addressHtml' => $order_total_html,
        );
        $checkout_one->debug_message('restoreAddressValues, returning:' . var_export($return_array, true), true);
        
        return $return_array;
    }
    
    // -----
    // Load the One-Page Checkout page's language files.
    //
    protected function loadLanguageFiles()
    {
        // -----
        // Set up some globals for use by 'require_languages.php'.
        //
        global $current_page, $current_page_base, $template, $language_page_directory, $template_dir;
        $_GET['main_page'] = $current_page_base = $current_page = FILENAME_CHECKOUT_ONE;
        
        require DIR_WS_MODULES . zen_get_module_directory('require_languages.php');      
    }
}
