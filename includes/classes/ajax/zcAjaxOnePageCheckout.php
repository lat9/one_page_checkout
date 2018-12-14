<?php
// -----
// Part of the One-Page Checkout plugin, provided under GPL 2.0 license by lat9 (cindy@vinosdefrutastropicales.com).
// Copyright (C) 2013-2018, Vinos de Frutas Tropicales.  All rights reserved.
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
        global $language_page_directory, $current_page_base;

        // -----
        // Load the One-Page Checkout page's language files.
        //
        $this->loadLanguageFiles();       
        
        $error_message = $order_total_html = $shipping_html = $payment_html = '';
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
            
            if (!empty($_SESSION['cc_id'])) {
                $discount_coupon_query = "SELECT coupon_code FROM " . TABLE_COUPONS . " WHERE coupon_id = :couponID LIMIT 1";
                $discount_coupon_query = $db->bindVars($discount_coupon_query, ':couponID', $_SESSION['cc_id'], 'integer');
                $discount_coupon = $db->Execute($discount_coupon_query);
            }
            
            // -----
            // Manage the shipping-address, based on the "Shipping Address, Same as Billing?" checkbox value submitted.
            //
            $checkout_one->debug_message("Billing/shipping, entry (" . var_export($_POST['shipping_is_billing'], true) . "), " . $_SESSION['sendto'] . ", " . $_SESSION['billto'] . ", " . $_SESSION['opc_sendto_saved'] . ", (" . $_SESSION['shipping_billing'] . ")", 'zcAjaxOnePageCheckout::updateShipping');
            if ($_POST['shipping_is_billing'] == 'true') {
                $_SESSION['sendto'] = $_SESSION['billto'];
                $_SESSION['shipping_billing'] = true;
                $ship_to = 'billing';
            } else {
                $_SESSION['sendto'] = $_SESSION['opc_sendto_saved'];
                $ship_to = 'shipping';
                $_SESSION['shipping_billing'] = false;
            }
            
            if (empty($_POST['payment'])) {
                unset($_SESSION['payment']);
            } else {
                $_SESSION['payment'] = $_POST['payment'];
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
                $shipping_module_available = true;
                if ($_POST['shipping'] != 'free_free') {
                    $shipping_module_available = false;
                    $checkout_one->debug_message('Invalid shipping method (' . $_POST['shipping'] . ') submitted; free-shipping and virtual orders should be free_free.', false, 'zcAjaxOnePageCheckout');
                    $status = 'error';
                    $error_message = ERROR_UNKNOWN_SHIPPING_SELECTION;
                }
            } else {
                global ${$module};           
                require DIR_WS_CLASSES . 'shipping.php';
                $shipping_modules = new shipping;
            
//-bof-product_delivery_by_postcode (PDP) integration
                $is_localdelivery_enabled = false;
                if (defined('MODULE_SHIPPING_LOCALDELIVERY_POSTCODE') && defined('MODULE_SHIPPING_STOREPICKUP_POSTCODE') && function_exists('zen_get_UKPostcodeFirstPart')) {
                    global $localdelivery, $storepickup;
                    
                    $is_localdelivery_enabled = true;
                    
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
                if ($is_localdelivery_enabled) {
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

                $this->disableGzip();
              
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
                unset($_SESSION['messageToStack']);
               
                // -----
                // Pull in, also, any changes to the shipping-methods available, given the change to shipping.
                //
                $shipping_module_available = ($free_shipping || $is_virtual_order || zen_count_shipping_modules() > 0);

                $checkout_one->debug_message("Shipping method changed: " . var_export($quote, true) . var_export($_SESSION['shipping'], true), false, 'zcAjaxOnePageCheckout');
            }
            
            $checkout_one->debug_message("Billing/shipping, exit (" . var_export($_POST['shipping_is_billing'], true) . "), " . $_SESSION['sendto'] . ", " . $_SESSION['billto'] . ", " . $_SESSION['opc_sendto_saved'] . ", (" . $_SESSION['shipping_billing'] . ")", 'zcAjaxOnePageCheckout::updateShipping');

            // -----
            // Pull in the payment-class processing at this point (was previously after the order-totals) to ensure
            // that any order-total modules that are 'keyed to' a payment method are properly included.
            //
            if (!class_exists('payment')) {
                require DIR_WS_CLASSES . 'payment.php';
            }
            $payment_modules = new payment();
            $enabled_payment_modules = $_SESSION['opc']->validateGuestPaymentMethods($payment_modules->selection());
            ob_start ();
            require $template->get_template_dir('tpl_modules_opc_payment_choices.php', DIR_WS_TEMPLATE, $GLOBALS['current_page_base'], 'templates'). '/tpl_modules_opc_payment_choices.php';
            $payment_html = ob_get_clean();

            // -----
            // Now, pull in any changes/re-calculations for the order's totals based on any shipping/payment
            // processing.
            //
            if (!class_exists('order_total')) {
                require DIR_WS_CLASSES . 'order_total.php';
            }
            $order_total_modules = new order_total;

            ob_start();
            $order_total_modules->process();
            $order_total_modules->output();
            $order_total_html = ob_get_clean();
        }
        
        // -----
        // Some payment methods, e.g. square, have some external jQuery that is loaded and run on initial
        // page-load **only**.  Set a processing flag for use by the 'checkout_one' page's jQuery to indicate what
        // action should be taken for the payment-method HTML on a shipping-method update.
        //
        // Possible values are 'update', 'no-update' or 'refresh'.
        //
        $return_array = array(
            'status' => $status,
            'errorMessage' => $error_message,
            'orderTotalHtml' => $order_total_html,
            'shippingHtml' => $shipping_html,
            'shippingMessage' => $shipping_choose_message,
            'paymentHtml' => $payment_html,
            'paymentHtmlAction' => CHECKOUT_ONE_PAYMENT_BLOCK_ACTION,
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
            $GLOBALS['checkout_one']->debug_message("Session time-out detected.", 'zcAjaxOnePageCheckout::restoreAddressValues');
        } else {
            $this->loadLanguageFiles();
            if (!isset($_POST['which']) || ($_POST['which'] != 'bill' && $_POST['which'] != 'ship')) {
                $status = 'error';
                $error_message = ERROR_INVALID_REQUEST;
                trigger_error('$_POST[\'which\'] not set or invalid, nothing to do.', E_USER_WARNING);
            } else {
                $address_html = $this->renderAddressBlock($_POST['which']);
            }
        }
        
        $return_array = array(
            'status' => $status,
            'errorMessage' => $error_message,
            'addressHtml' => $address_html,
        );
        $GLOBALS['checkout_one']->debug_message('restoreAddressValues, returning:' . var_export($return_array, true), true);
        
        return $return_array;
    }
    
    // -----
    // Function to validate the current value for an address (either bill-to or send-to) block.  Used
    // within the plugin's jQuery when the customer has requested that changes to an address-block
    // be saved.
    //
    public function validateAddressValues()
    {
        $error_message = $address_html = '';
        $messages = array();
        $status = 'ok';
        
        // -----
        // Check for a session timeout (i.e. no more customer_id in the session), returning a specific
        // status and message for that case.
        //
        if (!isset($_SESSION['customer_id'])) {
            $status = 'timeout';
            $GLOBALS['checkout_one']->debug_message("Session time-out detected.", 'zcAjaxOnePageCheckout::restoreAddressValues');
        } else {
            $this->loadLanguageFiles();
            if (!isset($_POST['which']) || ($_POST['which'] != 'bill' && $_POST['which'] != 'ship')) {
                $status = 'error';
                $error_message = ERROR_INVALID_REQUEST;
                trigger_error('$_POST[\'which\'] not set or invalid, nothing to do.', E_USER_WARNING);
            } else {
                $_SESSION['opc']->validateAndSaveAjaxPostedAddress($_POST['which'], $messages);
            }
        }
        
        $return_array = array(
            'status' => $status,
            'errorMessage' => $error_message,
            'messages' => $messages
        );
        $GLOBALS['checkout_one']->debug_message('validateAddressValues, returning:' . var_export($return_array, true) . var_export($_SESSION['opc'], true));
        
        return $return_array;
    }
    
    // -----
    // This function validates and updates any guest-customer's contact information.
    //
    public function validateCustomerInfo()
    {
        $error_message = $address_html = '';
        $messages = array();
        $status = 'ok';
        
        // -----
        // Check for a session timeout (i.e. no more customer_id in the session), returning a specific
        // status and message for that case.
        //
        if (!isset($_SESSION['customer_id'])) {
            $status = 'timeout';
            $GLOBALS['checkout_one']->debug_message("Session time-out detected.", 'zcAjaxOnePageCheckout::restoreAddressValues');
        } else {
            $this->loadLanguageFiles();
            $messages = $_SESSION['opc']->validateAndSaveAjaxCustomerInfo();
        }
        
        $return_array = array(
            'status' => $status,
            'errorMessage' => $error_message,
            'messages' => $messages
        );
        $GLOBALS['checkout_one']->debug_message('validateAddressValues, returning:' . var_export($return_array, true) . var_export($_SESSION['opc'], true));
        
        return $return_array;
    }
    
    // -----
    // This function restores the guest-customer's previously-entered contact information.
    //
    public function restoreCustomerInfo()
    {
        $error_message = $info_html = '';
        $status = 'ok';
        
        // -----
        // Check for a session timeout (i.e. no more customer_id in the session), returning a specific
        // status and message for that case.
        //
        if (!isset($_SESSION['customer_id'])) {
            $status = 'timeout';
            $GLOBALS['checkout_one']->debug_message("Session time-out detected.", 'zcAjaxOnePageCheckout::restoreAddressValues');
        } else {
            $this->loadLanguageFiles();
            global $current_page_base, $template;
            $template_file = 'tpl_modules_opc_customer_info.php';
            
            $this->disableGzip();
            
            ob_start();
            require $template->get_template_dir($template_file, DIR_WS_TEMPLATE, $current_page_base, 'templates'). "/$template_file";
            $info_html = ob_get_clean();
        }
        
        $return_array = array(
            'status' => $status,
            'errorMessage' => $error_message,
            'infoHtml' => $info_html,
        );
        $GLOBALS['checkout_one']->debug_message('restoreContactInfo, returning:' . var_export($return_array, true), true);
        
        return $return_array;
    }
        
    // -----
    // Public function to update the requested address based on a change in the saved-addresses'
    // dropdown menu.
    //
    public function setAddressFromSavedSelections()
    {
        $error_message = $address_html = '';
        $messages = array();
        $status = 'ok';
        
        // -----
        // Check for a session timeout (i.e. no more customer_id in the session), returning a specific
        // status and message for that case.
        //
        if (!isset($_SESSION['customer_id'])) {
            $status = 'timeout';
            $GLOBALS['checkout_one']->debug_message("Session time-out detected.", 'zcAjaxOnePageCheckout::restoreAddressValues');
        } else {
            $this->loadLanguageFiles();
            if (!isset($_POST['which']) || ($_POST['which'] != 'bill' && $_POST['which'] != 'ship')) {
                $status = 'error';
                $error_message = ERROR_INVALID_REQUEST;
                trigger_error('$_POST[\'which\'] not set or invalid, nothing to do.', E_USER_WARNING);
            } else {
                $_SESSION['opc']->setAddressFromSavedSelections($_POST['which'], $_POST['address_id']);
            }
        }
        
        $return_array = array(
            'status' => $status,
            'errorMessage' => $error_message,
        );
        $GLOBALS['checkout_one']->debug_message('setAddressFromSavedSelections, returning:' . var_export($return_array, true), true);
        
        return $return_array;
    }
    
    // -----
    // Public function to update the payment-method and the order-totals block; used when the payment method is changed
    // so that payment-related totals (like ot_cod_fee) are properly updated.
    //
    public function updatePaymentMethod()
    {
        // -----
        // Since we're running as a function, need to declare the objects we're instantiating here, for use by the various classes
        // involved in creating the order's total-block.
        //
        global $db, $order, $currencies, $checkout_one, $total_weight, $total_count, $discount_coupon, $messageStack;
        global $shipping_weight, $uninsurable_value, $shipping_quoted, $shipping_num_boxes, $template, $template_dir;
        global $language_page_directory, $shipping_modules, $payment_modules;

        // -----
        // Load the One-Page Checkout page's language files.
        //
        $this->loadLanguageFiles();       
        
        $error_message = $order_total_html = '';
        $status = 'ok';
        
        // -----
        // Check for a session timeout (i.e. no more customer_id in the session), returning a specific
        // status and message for that case.
        //
        if (!isset($_SESSION['customer_id'])) {
            $status = 'timeout';
            $checkout_one->debug_message("Session time-out detected.", 'zcAjaxOnePageCheckout::updateOrderTotals');
        } else {
            $this->disableGzip();
            
            if (empty($_POST['payment'])) {
                unset($_SESSION['payment']);
            } else {
                $_SESSION['payment'] = $_POST['payment'];
            }
            
            require DIR_WS_CLASSES . 'order.php';
            $order = new order();
            
            require DIR_WS_CLASSES . 'shipping.php';
            $shipping_modules = new shipping($_SESSION['shipping']);
            
            require DIR_WS_CLASSES . 'payment.php';
            $payment_modules = new payment;

            if (!class_exists('order_total')) {
                require DIR_WS_CLASSES . 'order_total.php';
            }
            $order_total_modules = new order_total;

            ob_start();
            $order_total_modules->process();
            $order_total_modules->output();
            $order_total_html = ob_get_clean();
        }
        
        $return_array = array(
            'status' => $status,
            'errorMessage' => $error_message,
            'orderTotalHtml' => $order_total_html,
        );
        $checkout_one->debug_message('updateOrderTotals, returning:' . var_export($return_array, true));

        return $return_array;
    }
    
    protected function renderAddressBlock($which)
    {
        global $current_page_base, $template;
        $template_file = ($which == 'bill') ? 'tpl_modules_opc_billing_address.php' : 'tpl_modules_opc_shipping_address.php';
        $flagDisablePaymentAddressChange = !$_SESSION['opc']->isBilltoAddressChangeable();
        $editShippingButtonLink = $_SESSION['opc']->isSendtoAddressChangeable();
        $is_virtual_order = $_SESSION['opc']->isVirtualOrder();
        $shipping_billing = $_SESSION['opc']->getShippingBilling();
        
        $this->disableGzip();
        
        ob_start();
        require $template->get_template_dir($template_file, DIR_WS_TEMPLATE, $current_page_base, 'templates'). "/$template_file";
        $address_html = ob_get_clean();
        
        return $address_html;
    }
    
    // -----
    // Gzip compression can "get in the way" of the AJAX requests on current versions of IE and
    // Chrome.
    //
    // This internal method sets that compression "off" for the AJAX responses.
    //
    protected function disableGzip()
    {
        @ob_end_clean();
        @ini_set('zlib.output_compression', '0');
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
