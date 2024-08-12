<?php
// -----
// Part of the One-Page Checkout plugin, provided under GPL 2.0 license by lat9.
// Copyright (C) 2013-2024, Vinos de Frutas Tropicales.  All rights reserved.
//
// Last updated: OPC v2.5.4
//
class zcAjaxOnePageCheckout extends base
{
    // -----
    // If OPC's guest checkout is active and the customer has requested to use PayPal Express
    // Checkout instead, reset OPC to indicate that its guest checkout is no longer active.
    //
    public function resetGuestCheckout()
    {
        if (isset($_SESSION['opc'])) {
            $_SESSION['opc']->resetGuestSessionValues();
        }
    }

    // -----
    // Update the order's shipping module/method when the shipping selection was
    // changed on the checkout_one page.
    //
    public function updateShippingSelection()
    {
        global $checkout_one;

        // -----
        // Load the One-Page Checkout page's language files. Note that this method also sets the
        // page being processed to 'checkout_one'!
        //
        $this->loadLanguageFiles();

        $error_message = '';
        $order_total_html = '';

        // -----
        // Initialize the response's status code, continuing only if all is 'ok'.
        //
        $status = $this->initializeResponseStatus('updateShippingSelection', $error_message);
        if ($status === 'ok') {
            // -----
            // Check to ensure that all the required posted values are present; returning an
            // error back to the OPC jQuery if not. Note that this condition could result if
            // OPC's jquery.checkout_one{.min}.js was not properly updated.
            //
            if (!isset($_POST['shipping_selection'])) {
                $status = 'error';
                $error_message = ERROR_INVALID_REQUEST;
            } else {
                // -----
                // The 'shipping_selection' is the value associated with the shipping-method
                // selection just made.  That value is in the form of {module}_{method}. Grab those
                // two elements and make sure that there's a record of that combination in the
                // session. If not, a message will be displayed to the customer and a page-reload
                // issued to try to put the session/page-elements back.
                //
                $shipping_elements = explode('_', $_POST['shipping_selection']);
                if (count($shipping_elements) !== 2 || !isset($_SESSION['opc_shipping_quotes'][$shipping_elements[0]][$shipping_elements[1]])) {
                    $status = 'reload';
                    $error_message = ERROR_AJAX_SHIPPING_SELECTION;
                    $checkout_one->debug_message(
                        "Couldn't find requested shipping selection (" . $_POST['shipping_selection'] . "):\n" .
                        json_encode($_SESSION['opc_shipping_quotes'], JSON_PRETTY_PRINT),
                        false,
                        'zcAjaxOnePageCheckout::updateShippingSelection'
                    );
                } else {
                    // -----
                    // Mimic the processing in the checkout_one page's header processing, setting the selected
                    // shipping module/method into the order's information array as well
                    // as the session.  That information is used by a subsequent call to the ot_shipping.php
                    // module.
                    //
                    // Grab the previously-saved quote information for the currently-selected
                    // quote (saved by the checkout_one page's jscript_main.php's processing).
                    //
                    $shipping_module_id = $shipping_elements[0];
                    $shipping_module_title = $_SESSION['opc_shipping_quotes'][$shipping_module_id]['title'];

                    $shipping_method_id = $shipping_elements[1];
                    ['title' => $shipping_method_title, 'cost' => $shipping_method_cost] = $_SESSION['opc_shipping_quotes'][$shipping_module_id][$shipping_method_id];

                    $_SESSION['shipping'] = [
                        'id' => $shipping_module_id . '_' . $shipping_method_id,
                        'title' => "$shipping_module_title ($shipping_method_title)",
                        'module' => $shipping_module_id,
                        'cost' => $shipping_method_cost,
                    ];

                    $order_total_html = $this->createOrderTotalHtml();
                }
            }
        }

        // -----
        // Return the re-formatted HTML to be updated into the order's "Totals" section.
        //
        return [
            'status' => $status,
            'errorMessage' => $error_message,
            'orderTotalHtml' => $order_total_html,
            'total' => $this->formatOrderTotal(),
        ];
    }

    // -----
    // Called to format methods' return of the order's current total,
    // rounding to the number of decimal digits in the session's active
    // currency.
    //
    protected function formatOrderTotal()
    {
        return $_SESSION['opc_saved_order_total'] ?? '0.00';
    }

    // -----
    // Called by jQuery handling when the shipping=billing checkbox
    // is changed such that billing and shipping are now the same.
    //
    public function setShippingEqualBilling()
    {
        // -----
        // Load the One-Page Checkout page's language files.
        //
        $this->loadLanguageFiles();

        $error_message = '';

        // -----
        // Initialize the response's status code, continuing only if all is 'ok'.  If
        // no issues found during status initialization, instruct the jQuery to
        // reload the checkout_one page if the shipping address was changed by this
        // request.
        //
        $status = $this->initializeResponseStatus('setShippingEqualBilling', $error_message);
        if ($status === 'ok' && $_SESSION['opc']->setShippingEqualBilling() === true) {
            $status = 'reload';
        }

        return [
            'status' => $status,
            'errorMessage' => $error_message,
        ];
    }

    // -----
    // Function that returns the order's currently-recorded, as saved in the session when
    // an order-hash is calculated.
    //
    public function getOrderTotal(): array
    {
        // -----
        // Load the One-Page Checkout page's language files.
        //
        $this->loadLanguageFiles();

        $error_message = '';
        $status = $this->initializeResponseStatus('setShippingEqualBilling', $error_message);
        return [
            'status' => $status,
            'errorMessage' => $error_message,
            'total' => $this->formatOrderTotal(),
        ];
    }

    // -----
    // Function to return the current value for an address (either bill-to or send-to) block.  Used
    // within the plugin's jQuery when the customer has cancelled an address-block change.
    //
    public function restoreAddressValues()
    {
        global $checkout_one;

        $this->loadLanguageFiles();
        $error_message = '';
        $address_html = '';

        // -----
        // Initialize the response's status code, continuing only if all is 'ok'.
        //
        $status = $this->initializeResponseStatus('restoreAddressValues', $error_message);
        if ($status === 'ok') {
            if (!isset($_POST['which']) || ($_POST['which'] !== 'bill' && $_POST['which'] !== 'ship')) {
                $status = 'error';
                $error_message = ERROR_INVALID_REQUEST;
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

        $this->loadLanguageFiles();
        $error_message = '';
        $address_html = '';
        $messages = [];

        // -----
        // Initialize the response's status code, continuing only if all is 'ok'.
        //
        $status = $this->initializeResponseStatus('validateAddressValues', $error_message);
        if ($status === 'ok') {
            if (!isset($_POST['which']) || ($_POST['which'] !== 'bill' && $_POST['which'] !== 'ship')) {
                $status = 'error';
                $error_message = ERROR_INVALID_REQUEST;
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

        $this->loadLanguageFiles();

        $error_message = '';
        $address_html = '';
        $messages = [];

        // -----
        // Initialize the response's status code, continuing only if all is 'ok'.
        //
        $status = $this->initializeResponseStatus('validateCustomerInfo', $error_message);
        if ($status === 'ok') {
            $messages = $_SESSION['opc']->validateAndSaveAjaxCustomerInfo();
        }

        $return_array = [
            'status' => $status,
            'errorMessage' => $error_message,
            'messages' => $messages
        ];
        $checkout_one->debug_message('validateCustomerInfo, returning:' . json_encode($return_array) . PHP_EOL . json_encode($_SESSION['opc']));

        return $return_array;
    }

    // -----
    // This function restores the guest-customer's previously-entered contact information.
    //
    public function restoreCustomerInfo()
    {
        global $checkout_one;

        $this->loadLanguageFiles();

        $error_message = '';
        $info_html = '';

        // -----
        // Initialize the response's status code, continuing only if all is 'ok'.
        //
        $status = $this->initializeResponseStatus('restoreCustomerInfo', $error_message);
        if ($status === 'ok') {
            global $current_page_base, $template;

            $template_file = 'tpl_modules_opc_customer_info.php';

            $this->disableGzip();

            ob_start();
            require $template->get_template_dir($template_file, DIR_WS_TEMPLATE, $current_page_base, 'templates') . "/$template_file";
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

        $this->loadLanguageFiles();

        $error_message = '';
        $address_html = '';

        // -----
        // Initialize the response's status code, continuing only if all is 'ok'.
        //
        $status = $this->initializeResponseStatus('setAddressFromSavedSelections', $error_message);
        if ($status === 'ok') {
            if (!isset($_POST['which'], $_POST['address_id'], $_POST['shipping_is_billing']) || ($_POST['which'] !== 'bill' && $_POST['which'] !== 'ship')) {
                $status = 'error';
                $error_message = ERROR_INVALID_REQUEST;
            } else {
                $_SESSION['opc']->setAddressFromSavedSelections($_POST['which'], (int)$_POST['address_id'], $_POST['shipping_is_billing']);
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
        // Load the One-Page Checkout page's language files.
        //
        $this->loadLanguageFiles();

        $error_message = '';
        $order_total_html = '';

        // -----
        // Initialize the response's status code, continuing only if all is 'ok'.
        //
        $status = $this->initializeResponseStatus('updatePaymentMethod', $error_message);
        if ($status === 'ok') {
            if (empty($_POST['payment'])) {
                unset($_SESSION['payment']);
            } else {
                $_SESSION['payment'] = $_POST['payment'];
            }

            $order_total_html = $this->createOrderTotalHtml();
        }

        return [
            'status' => $status,
            'errorMessage' => $error_message,
            'orderTotalHtml' => $order_total_html,
            'total' => $this->formatOrderTotal(),
        ];
    }

    protected function createOrderTotalHtml(): string
    {
        global $db, $order, $shipping_modules, $payment_modules, $checkout_one, $discount_coupon, $currencies;

        $this->disableGzip();

        // -----
        // Create an instance of the order-class, setting the in-cart products
        // and their pricing/taxes as well as the updated shipping-selection recorded
        // in the session, above.
        //
        require DIR_WS_CLASSES . 'order.php';
        $order = new order();

        // -----
        // Create an instance of the shipping-class; that'll pull in the language files and make
        // an instance of the currently-selected shipping modules.
        //
        require DIR_WS_CLASSES . 'shipping.php';
        $shipping_modules = new shipping($_SESSION['shipping'] ?? null);

        // -----
        // Create an instance of the payment-class; that'll pull in the language file(s) and create
        // an instance of the currently-selected payment module(s).
        //
        require DIR_WS_CLASSES . 'payment.php';
        $payment_modules = new payment($_SESSION['payment'] ?? '');

        // -----
        // The ot_coupon order-total expects the $discount_coupon variable to be present if
        // there's a coupon associated with the order.
        //
        if (!empty($_SESSION['cc_id'])) {
            $discount_coupon_query = "SELECT coupon_code FROM " . TABLE_COUPONS . " WHERE coupon_id = :couponID LIMIT 1";
            $discount_coupon_query = $db->bindVars($discount_coupon_query, ':couponID', $_SESSION['cc_id'], 'integer');
            $discount_coupon = $db->Execute($discount_coupon_query);
        }

        // -----
        // Pull in changes/re-calculations for the order's totals based on the change in
        // shipping method.
        //
        require DIR_WS_CLASSES . 'order_total.php';
        $order_total_modules = new order_total();

        ob_start();
        $order_total_modules->process();
        $_SESSION['opc_saved_order_total'] = $currencies->value($order->info['total']);
        $order_total_modules->output();
        $order_total_html = ob_get_clean();

        $checkout_one->debug_message(
            "Returning:\n" .
            json_encode($order->info, JSON_PRETTY_PRINT) . "\n" .
            json_encode($_SESSION['shipping'] ?? [], JSON_PRETTY_PRINT) . "\n" .
            ($_SESSION['payment'] ?? '[not set]'),
            false,
            'zcAjaxOnePageCheckout::createOrderTotalHtml'
        );

        return $order_total_html;
    }

    protected function renderAddressBlock($which)
    {
        global $current_page_base, $template;

        $template_file = ($which === 'bill') ? 'tpl_modules_opc_billing_address.php' : 'tpl_modules_opc_shipping_address.php';
        $flagDisablePaymentAddressChange = !$_SESSION['opc']->isBilltoAddressChangeable();
        $editShippingButtonLink = $_SESSION['opc']->isSendtoAddressChangeable();
        $is_virtual_order = $_SESSION['opc']->isVirtualOrder();
        $shipping_billing = $_SESSION['opc']->getShippingBilling();

        $this->disableGzip();

        ob_start();
        require $template->get_template_dir($template_file, DIR_WS_TEMPLATE, $current_page_base, 'templates') . "/$template_file";
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

        $current_page = FILENAME_CHECKOUT_ONE;
        $current_page_base = FILENAME_CHECKOUT_ONE;
        $_GET['main_page'] = FILENAME_CHECKOUT_ONE;

        require DIR_WS_MODULES . zen_get_module_directory('require_languages.php');
    }

    // -----
    // Common, for each AJAX request, checking for timeout and OPC-unavailable conditions.
    //
    protected function initializeResponseStatus($method_name, &$error_message)
    {
        global $checkout_one;

        $status = 'ok';
        $error_message = '';

        // -----
        // If One-Page Checkout is no longer available, return a status code to the jQuery handler which, in turn,
        // will result in the customer being redirected to the checkout_shipping page.
        //
        if (!isset($_SESSION['opc']) || !is_object($_SESSION['opc']) || $_SESSION['opc']->checkEnabled() === false) {
            $status = 'unavailable';
            $checkout_one->debug_message('OPC is no longer available.', "zcAjaxOnePageCheckout::$method_name");
        // -----
        // Check for a session timeout (i.e. no more customer_id in the session), returning a specific
        // status and message for that case.
        //
        } elseif (!isset($_SESSION['customer_id'])) {
            $status = 'timeout';
            $checkout_one->debug_message("Session time-out detected.", "zcAjaxOnePageCheckout::$method_name");
        // -----
        // Otherwise, ensure that the OPC 'environment' is still 'sane', requesting that the jQuery portion of
        // the plugin do a full page reload in an attempt to further correct the situation if not.
        //
        } elseif ($_SESSION['opc']->sanitizeCustomerAddressInfo() === false) {
            $status = 'reload';
            $error_message = ERROR_OPC_ADDRESS_INVALID;
        }

        return $status;
    }
}
