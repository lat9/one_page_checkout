<?php
// -----
// Part of the One-Page Checkout plugin, provided under GPL 2.0 license by lat9.
// Copyright (C) 2013-2022, Vinos de Frutas Tropicales.  All rights reserved.
//
class zcAjaxOnePageCheckout extends base
{
    // -----
    // Update the order's shipping method when the selection has changed on the checkout_one page.
    //
    public function updateShipping()
    {
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
        $shipping_choose_message = '';

        // -----
        // Initialize the response's status code, continuing only if all is 'ok'.
        //
        $status = $this->initializeResponseStatus('updateShipping');
        if ($status === 'ok') {
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
            $checkout_one->debug_message("Billing/shipping, entry (" . json_encode($_POST['shipping_is_billing']) . "), " . $_SESSION['sendto'] . ", " . $_SESSION['billto'] . ", (" . $_SESSION['shipping_billing'] . ")", 'zcAjaxOnePageCheckout::updateShipping');
            if ($_POST['shipping_is_billing'] == 'true') {
                $_SESSION['sendto'] = $_SESSION['billto'];
                $_SESSION['shipping_billing'] = true;
                $ship_to = 'billing';
                $_SESSION['opc']->setTempShippingToBilling();
            } else {
                if (!isset($_SESSION['sendto'])) {
                    $_SESSION['sendto'] = $_SESSION['customer_default_address_id'];
                } else {
                    $_SESSION['opc']->validateBilltoSendto('ship');
                }
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

            if (!isset($_POST['shipping'])) {
                $module = '';
                $method = '';
                $_POST['shipping'] = '';
            } else {
                list($module, $method) = explode('_', $_POST['shipping']);
            }

            $free_shipping = $_SESSION['opc']->isOrderFreeShipping($_SESSION['sendto']);
            $is_virtual_order = $_SESSION['opc']->isVirtualOrder();
            $current_shipping = (isset($_SESSION['shipping'])) ? json_encode($_SESSION['shipping']) : 'shipping not set';

            $checkout_one->debug_message(
                "Shipping method change to $module ($method), sendto ($ship_to), free_shipping ($free_shipping), virtual order ($is_virtual_order). Current values: " .
                $current_shipping . PHP_EOL . 
                json_encode($order->info) . PHP_EOL .
                json_encode($_POST), true, 'zcAjaxOnePageCheckout'
            );

            if ($free_shipping || $is_virtual_order) {
                $shipping_module_available = true;
                $_SESSION['shipping'] = [
                    'id' => 'free_free', 
                    'title' => FREE_SHIPPING_TITLE, 
                    'cost' => 0 
                ];
                $order->info['shipping_method'] = 'free_free';
                if ($is_virtual_order) {
                    $_SESSION['sendto'] = false;
                } elseif ($_POST['shipping'] != 'free_free') {
                    $_POST['shipping'] = 'free_free';
                    $checkout_one->debug_message('Modifying shipping method, (' . $_POST['shipping'] . ') submitted; free-shipping and virtual orders should be free_free.', false, 'zcAjaxOnePageCheckout');

                    // -----
                    // Re-render the shipping choices to show free shipping.
                    //
                    $this->disableGzip();
                    $quotes = [];
                    $quotes[0]['methods'][0]['title'] = FREE_SHIPPING_TITLE;
                    $quotes[0]['methods'][0]['cost'] = '0';
                    $quotes[0]['methods'][0]['icon'] = '';
                    ob_start ();
                    require $template->get_template_dir('tpl_modules_checkout_one_shipping.php', DIR_WS_TEMPLATE, $current_page_base, 'templates') . '/tpl_modules_checkout_one_shipping.php';
                    $shipping_html = ob_get_clean();
                }
            } else {
                // -----
                // Got here if the previous address/cart qualified for 'free shipping' (as defined by ot_shipping), but
                // a change has now invalidated that shipping method.  Perform some 'clean-up' so that a non-free
                // shipping method will be used.
                //
                if (isset($_SESSION['shipping']) && $_SESSION['shipping'] == 'free_free') {
                    unset($_SESSION['shipping']);
                    $method = '';
                    $module = '';
                    $order->info['shipping_method'] = '';
                }

                if ($module !== '') {
                    global ${$module};
                }
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
                $session_shipping = (isset($_SESSION['shipping'])) ? json_encode($_SESSION['shipping']) : 'Shipping not set';
                $checkout_one->debug_message("Current quote for " . $_POST['shipping'] . ": " . PHP_EOL . json_encode($quote) . PHP_EOL . $session_shipping);

                if (!isset($quote[0]['methods'][0]['title']) || !isset($quote[0]['methods'][0]['cost'])) {
                    $shipping_invalid = true;
                } else {
                    $shipping_cost = $quote[0]['methods'][0]['cost'];
                    $shipping_title = $quote[0]['module'] . ' (' . $quote[0]['methods'][0]['title'] . ')';
                    if (isset($_SESSION['shipping']) && $_SESSION['shipping']['id'] == $_POST['shipping'] && ($_SESSION['shipping']['title'] != $shipping_title || $_SESSION['shipping']['cost'] != $shipping_cost)) {
                        $shipping_invalid = true;
                    } else {
                        $shipping_invalid = false;
                        $_SESSION['shipping'] = [
                            'id' => $_POST['shipping'],
                            'title' => $shipping_title,
                            'cost' => $shipping_cost
                        ];
                        if (isset($quote[0]['extras'])) {
                            $_SESSION['shipping']['extras'] = $quote[0]['extras'];
                        }
                    }
                }

                if ($shipping_invalid) {
                    $checkout_one->debug_message('Shipping method returned empty result; no longer valid.');
                    $error_message = ERROR_PLEASE_RESELECT_SHIPPING_METHOD;
                    $status = 'invalid';
                    unset($_SESSION['shipping']);
                }
                $order = new order();
                $shipping_modules = new shipping(isset($_SESSION['shipping']) ? $_SESSION['shipping'] : '');

//-bof-product_delivery_by_postcode (PDP) integration
                if ($is_localdelivery_enabled) {
                    global $localdelivery, $storepickup;

                    $check_delivery_postcode = $order->delivery['postcode'];

                    // shorten UK / Canada postcodes to use first part only
                    $check_delivery_postcode = zen_get_UKPostcodeFirstPart($check_delivery_postcode);

                    // now check db for allowed postcodes and enable / disable relevant shipping modules
                    if (!in_array($check_delivery_postcode, explode(',', MODULE_SHIPPING_LOCALDELIVERY_POSTCODE))) {
                         $localdelivery = false;
                    }

                    if (!in_array($check_delivery_postcode, explode(',', MODULE_SHIPPING_STOREPICKUP_POSTCODE))) {
                        $storepickup = false;
                    }
                }
//-eof-product_delivery_by_postcode (PDP) integration

                $this->disableGzip();

                $shipping_html = '';

                if ($_POST['shipping_request'] === 'shipping-billing') {
                    $quotes = $shipping_modules->quote();
                    if (count($quotes) > 1 && count($quotes[0]) > 1) {
                        $shipping_choose_message = TEXT_CHOOSE_SHIPPING_METHOD;
                    } else {
                        $shipping_choose_message = TEXT_ENTER_SHIPPING_INFORMATION;
                    }
                    $checkout_one->debug_message("Updating shipping section, message ($shipping_choose_message), quotes:" . json_encode($quotes), false, 'zcAjaxOnePageCheckout');
                    
                    if ((!isset($_SESSION['shipping']) || (!isset($_SESSION['shipping']['id']) || $_SESSION['shipping']['id'] === '') && zen_count_shipping_modules() >= 1)) {
                        $_SESSION['shipping'] = $shipping_modules->cheapest();
                    }

                    ob_start ();
                    require $template->get_template_dir('tpl_modules_checkout_one_shipping.php', DIR_WS_TEMPLATE, $current_page_base, 'templates'). '/tpl_modules_checkout_one_shipping.php';
                    $shipping_html = ob_get_clean();
                }

                if ($status === 'ok' && isset($quote[0]['error'])) {
                    $status = 'error';
                    if (count($messageStack->messages) > 0) {
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
                $session_shipping = (isset($_SESSION['shipping'])) ? json_encode($_SESSION['shipping']) : 'Shipping not set';
                $checkout_one->debug_message("Shipping method changed: " . json_encode($quote) . PHP_EOL . $session_shipping, false, 'zcAjaxOnePageCheckout');
            }

            $checkout_one->debug_message("Billing/shipping, exit (" . json_encode($_POST['shipping_is_billing']) . "), " . $_SESSION['sendto'] . ", " . $_SESSION['billto']  . ", (" . $_SESSION['shipping_billing'] . ")", 'zcAjaxOnePageCheckout::updateShipping');

            // -----
            // Pull in the payment-class processing at this point (was previously after the order-totals) to ensure
            // that any order-total modules that are 'keyed to' a payment method are properly included.
            //
            if (!class_exists('payment')) {
                require DIR_WS_CLASSES . 'payment.php';
            }
            $payment_modules = new payment();
            $enabled_payment_modules = $_SESSION['opc']->validateGuestPaymentMethods($payment_modules->selection());
            $display_payment_block = ($_SESSION['opc']->validateCustomerInfo() && $_SESSION['opc']->validateTempBilltoAddress());
            ob_start ();
            require $template->get_template_dir('tpl_modules_opc_payment_choices.php', DIR_WS_TEMPLATE, $current_page_base, 'templates'). '/tpl_modules_opc_payment_choices.php';
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
        $return_array = [
            'status' => $status,
            'errorMessage' => $error_message,
            'orderTotalHtml' => $order_total_html,
            'shippingHtml' => $shipping_html,
            'shippingMessage' => $shipping_choose_message,
            'paymentHtml' => $payment_html,
            'paymentHtmlAction' => CHECKOUT_ONE_PAYMENT_BLOCK_ACTION,
        ];
        $session_shipping = (isset($_SESSION['shipping'])) ? json_encode($_SESSION['shipping']) : 'Shipping not set';
        $checkout_one->debug_message('updateShipping, returning:' . json_encode($return_array) . PHP_EOL . $session_shipping);

        return $return_array;
    }

    // -----
    // Function to return the current value for an address (either bill-to or send-to) block.  Used
    // within the plugin's jQuery when the customer has cancelled an address-block change.
    //
    public function restoreAddressValues()
    {
        global $checkout_one;

        $error_message = $address_html = '';
 
        // -----
        // Initialize the response's status code, continuing only if all is 'ok'.
        //
        $status = $this->initializeResponseStatus('restoreAddressValues');
        if ($status == 'ok') {
            $this->loadLanguageFiles();
            if (!isset($_POST['which']) || ($_POST['which'] !== 'bill' && $_POST['which'] !== 'ship')) {
                $status = 'error';
                $error_message = ERROR_INVALID_REQUEST;
                trigger_error('$_POST[\'which\'] not set or invalid, nothing to do.', E_USER_WARNING);
            } else {
                $address_html = $this->renderAddressBlock($_POST['which']);
            }
        }

        $return_array = [
            'status' => $status,
            'errorMessage' => $error_message,
            'addressHtml' => $address_html,
        ];
        $checkout_one->debug_message('restoreAddressValues, returning:' . json_encode($return_array), true);
        
        return $return_array;
    }

    // -----
    // Function to validate the current value for an address (either bill-to or send-to) block.  Used
    // within the plugin's jQuery when the customer has requested that changes to an address-block
    // be saved.
    //
    public function validateAddressValues()
    {
        global $checkout_one;

        $error_message = $address_html = '';
        $messages = [];

        // -----
        // Initialize the response's status code, continuing only if all is 'ok'.
        //
        $status = $this->initializeResponseStatus('validateAddressValues');
        if ($status === 'ok') {
            $this->loadLanguageFiles();
            if (!isset($_POST['which']) || ($_POST['which'] !== 'bill' && $_POST['which'] !== 'ship')) {
                $status = 'error';
                $error_message = ERROR_INVALID_REQUEST;
                trigger_error('$_POST[\'which\'] not set or invalid, nothing to do.', E_USER_WARNING);
            } else {
                $_SESSION['opc']->validateAndSaveAjaxPostedAddress($_POST['which'], $messages);
            }
        }

        $return_array = [
            'status' => $status,
            'errorMessage' => $error_message,
            'messages' => $messages
        ];
        $checkout_one->debug_message('validateAddressValues, returning:' . json_encode($return_array) . PHP_EOL . json_encode($_SESSION['opc'], true));

        return $return_array;
    }
    
    // -----
    // This function validates and updates any guest-customer's contact information.
    //
    public function validateCustomerInfo()
    {
        global $checkout_one;

        $error_message = $address_html = '';
        $messages = [];

        // -----
        // Initialize the response's status code, continuing only if all is 'ok'.
        //
        $status = $this->initializeResponseStatus('validateCustomerInfo');
        if ($status === 'ok') {
            $this->loadLanguageFiles();
            $messages = $_SESSION['opc']->validateAndSaveAjaxCustomerInfo();
        }

        $return_array = [
            'status' => $status,
            'errorMessage' => $error_message,
            'messages' => $messages
        ];
        $checkout_one->debug_message('validateAddressValues, returning:' . json_encode($return_array) . PHP_EOL . json_encode($_SESSION['opc']));

        return $return_array;
    }

    // -----
    // This function restores the guest-customer's previously-entered contact information.
    //
    public function restoreCustomerInfo()
    {
        global $checkout_one;

        $error_message = $info_html = '';

        // -----
        // Initialize the response's status code, continuing only if all is 'ok'.
        //
        $status = $this->initializeResponseStatus('restoreCustomerInfo');
        if ($status === 'ok') {
            $this->loadLanguageFiles();
            global $current_page_base, $template;
            $template_file = 'tpl_modules_opc_customer_info.php';

            $this->disableGzip();

            ob_start();
            require $template->get_template_dir($template_file, DIR_WS_TEMPLATE, $current_page_base, 'templates'). "/$template_file";
            $info_html = ob_get_clean();
        }

        $return_array = [
            'status' => $status,
            'errorMessage' => $error_message,
            'infoHtml' => $info_html,
        ];
        $checkout_one->debug_message('restoreContactInfo, returning:' . json_encode($return_array), true);

        return $return_array;
    }

    // -----
    // Public function to update the requested address based on a change in the saved-addresses'
    // dropdown menu.
    //
    public function setAddressFromSavedSelections()
    {
        global $checkout_one;

        $error_message = $address_html = '';
        $messages = [];

        // -----
        // Initialize the response's status code, continuing only if all is 'ok'.
        //
        $status = $this->initializeResponseStatus('setAddressFromSavedSelections');
        if ($status === 'ok') {
            $this->loadLanguageFiles();
            if (!isset($_POST['which']) || ($_POST['which'] !== 'bill' && $_POST['which'] !== 'ship')) {
                $status = 'error';
                $error_message = ERROR_INVALID_REQUEST;
                trigger_error('$_POST[\'which\'] not set or invalid, nothing to do.', E_USER_WARNING);
            } else {
                $_SESSION['opc']->setAddressFromSavedSelections($_POST['which'], $_POST['address_id']);
            }
        }

        $return_array = [
            'status' => $status,
            'errorMessage' => $error_message,
        ];
        $checkout_one->debug_message('setAddressFromSavedSelections, returning:' . json_encode($return_array), true);

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
        global $language_page_directory, $shipping_modules, $payment_modules, $current_page_base;

        // -----
        // Load the One-Page Checkout page's language files.
        //
        $this->loadLanguageFiles();

        $error_message = $order_total_html = '';

        // -----
        // Initialize the response's status code, continuing only if all is 'ok'.
        //
        $status = $this->initializeResponseStatus('updatePaymentMethod');
        if ($status === 'ok') {
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

        $return_array = [
            'status' => $status,
            'errorMessage' => $error_message,
            'orderTotalHtml' => $order_total_html,
        ];
        $checkout_one->debug_message('updateOrderTotals, returning:' . json_encode($return_array));

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
        global $current_page, $current_page_base, $template, $language_page_directory, $template_dir, $languageLoader;
        $_GET['main_page'] = $current_page_base = $current_page = FILENAME_CHECKOUT_ONE;
        
        require DIR_WS_MODULES . zen_get_module_directory('require_languages.php');
    }

    // -----
    // Common, for each AJAX request, checking for timeout and OPC-unavailable conditions.
    //
    protected function initializeResponseStatus($method_name)
    {
        global $checkout_one;

        $status = 'ok';

        // -----
        // If One-Page Checkout is no longer available, return a status code to the jQuery handler which, in turn,
        // will result in the customer being redirected to the checkout_shipping page.
        //
        if (!$_SESSION['opc']->checkEnabled()) {
            $status = 'unavailable';
            $checkout_one->debug_message('OPC is no longer available.', "zcAjaxOnePageCheckout::$method_name");
        // -----
        // Check for a session timeout (i.e. no more customer_id in the session), returning a specific
        // status and message for that case.
        //
        } elseif (!isset($_SESSION['customer_id'])) {
            $status = 'timeout';
            $checkout_one->debug_message("Session time-out detected.", "zcAjaxOnePageCheckout::$method_name");
        }

        return $status;
    }
}
