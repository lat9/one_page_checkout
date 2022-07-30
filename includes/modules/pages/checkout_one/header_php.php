<?php
// -----
// Part of the One-Page Checkout plugin, provided under GPL 2.0 license by lat9
// Copyright (C) 2013-2022, Vinos de Frutas Tropicales.  All rights reserved.
//
// Last updated for OPC v2.4.4.
//
// -----
// This should be first line of the script:
$zco_notifier->notify('NOTIFY_HEADER_START_CHECKOUT_ONE');

// -----
// Use "normal" checkout if not enabled.
//
if (!(defined('CHECKOUT_ONE_ENABLED') && isset($checkout_one) && $checkout_one->isEnabled())) {
    $zco_notifier->notify('NOTIFY_CHECKOUT_ONE_NOT_ENABLED');
    zen_redirect(zen_href_link(FILENAME_CHECKOUT_SHIPPING, '', 'SSL'));
}

require_once DIR_WS_CLASSES . 'http_client.php';

require DIR_WS_MODULES . zen_get_module_directory('require_languages.php');

$checkout_one->debug_message(sprintf('CHECKOUT_ONE_ENTRY, version (%s), Zen Cart version (%s), template (%s)', CHECKOUT_ONE_MODULE_VERSION, PROJECT_VERSION_MAJOR . '.' . PROJECT_VERSION_MINOR, $template_dir));

// -----
// If the plugin's debug-mode is set to "full", then enable ALL error reporting for the checkout_one page.
//
if (CHECKOUT_ONE_DEBUG === 'full') {
    @ini_set('error_reporting', -1);
}

// if there is nothing in the customers cart, redirect them to the shopping cart page
if ($_SESSION['cart']->count_contents() <= 0) {
    zen_redirect(zen_href_link(FILENAME_SHOPPING_CART, '', 'NONSSL'));
}

// -----
// Check the customer's login status.
//
$is_guest_checkout = $_SESSION['opc']->startGuestOnePageCheckout();
if (!zen_is_logged_in()) {
    if (!$is_guest_checkout) {
        $_SESSION['navigation']->set_snapshot();
        zen_redirect(zen_href_link(FILENAME_LOGIN, '', 'SSL'));
    }
} else {
    if (zen_get_customer_validate_session($_SESSION['customer_id']) == false) {
        $_SESSION['navigation']->set_snapshot(array ('mode' => 'SSL', 'page' => FILENAME_CHECKOUT_ONE));
        zen_redirect(zen_href_link(FILENAME_LOGIN, '', 'SSL'));
    }
}

// -----
// In the "normal" Zen Cart checkout flow, the module /includes/init_includes/init_customer_auth.php performs the
// following check to see that the customer is authorized to checkout.  Rather than changing the code in that
// core-file, we'll repeat that check here.
//
if ($_SESSION['customers_authorization'] != 0) {
    $messageStack->add_session('header', TEXT_AUTHORIZATION_PENDING_CHECKOUT, 'caution');
    zen_redirect(zen_href_link(FILENAME_DEFAULT));
}

// Validate Cart for checkout
$_SESSION['valid_to_checkout'] = true;
$products_array = $_SESSION['cart']->get_products(true);
if ($_SESSION['valid_to_checkout'] == false) {
    $messageStack->add('header', ERROR_CART_UPDATE, 'error');
    zen_redirect(zen_href_link(FILENAME_SHOPPING_CART));
}

// -----
// Stock Check.  Re-formulated based on the checkout_confirmation page's processing,
// with modifications to ensure that the array is always populated to prevent PHP Notice
// generation for an undefined variable.
//
// Note: The "assumption" here is that the cart products are a one-to-one map
// to the products in the to-be-generated order.
//
$stock_check = [];
for ($i = 0, $n = count($products_array); $i < $n; $i++) {
    $stock_check[$i] = zen_check_stock($products_array[$i]['id'], $products_array[$i]['quantity']);
    if (STOCK_CHECK === 'true' && STOCK_ALLOW_CHECKOUT !== 'true' && !empty($stock_check[$i])) {
        zen_redirect(zen_href_link(FILENAME_SHOPPING_CART));
    }
}
//unset($products_array);

// get coupon code
if (isset($_SESSION['cc_id'])) {
    $discount_coupon_query = "SELECT coupon_code FROM " . TABLE_COUPONS . " WHERE coupon_id = :couponID LIMIT 1";
    $discount_coupon_query = $db->bindVars($discount_coupon_query, ':couponID', $_SESSION['cc_id'], 'integer');
    $discount_coupon = $db->Execute($discount_coupon_query);

    if ($discount_coupon->EOF) {
        unset($_SESSION['cc_id'], $discount_coupon); 
    }
}

$shipping_billing = $_SESSION['opc']->getShippingBilling();

// -----
// If the customer's 'billto' address has not yet been set, it's set based on the site's
// 'Shipping=Billing' configuration.  If the site's set shipping=billing and the customer's
// 'sendto' address is set via a shipping-estimator selection, 'billto' will be set to the
// selected 'sendto'; otherwise, the 'billto' address is set to the customer's default address.
//
if (!isset($_SESSION['billto'])) {
    $_SESSION['billto'] = ($shipping_billing === true && isset($_SESSION['sendto'])) ? $_SESSION['sendto'] : $_SESSION['customer_default_address_id'];
// -----
// ... otherwise, make sure the billto address is valid.
//
} else {
    $_SESSION['opc']->validateBilltoSendto('bill');
}

// if no shipping destination address was selected, use the customers own address as default
if (!isset($_SESSION['sendto'])) {
    $_SESSION['sendto'] = $_SESSION['customer_default_address_id'];
} else {
    $_SESSION['opc']->validateBilltoSendto('ship');
}

// register a random ID in the session to check throughout the checkout procedure
// against alterations in the shopping cart contents
if (isset($_SESSION['cart']->cartID)) {
    if (!isset($_SESSION['cartID']) || $_SESSION['cart']->cartID != $_SESSION['cartID']) {
        $_SESSION['cartID'] = $_SESSION['cart']->cartID;
    }
} else {
    zen_redirect(zen_href_link(FILENAME_TIME_OUT, '', 'SSL'));
}

// -----
// Check to see if the current cart contains only virtual products and, if so, pre-set the order's shipping method
// to indicate free shipping.  This processing is moved before the order-object's creation to also cover the
// case where the order started out as a mixed/physical one (with a "real" shipping method set) and was converted
// to a virtual order after a prior entry to the checkout_one processing.
//
$is_virtual_order = false;
if ($_SESSION['cart']->get_content_type() === 'virtual') {
    $is_virtual_order = true;
    $shipping_billing = false;

    $_SESSION['shipping'] = [
        'id' => 'free_free', 
        'title' => FREE_SHIPPING_TITLE, 
        'cost' => 0
    ];
    $_SESSION['sendto'] = false;
}

// -----
// Check to see if the order qualifies for free-shipping and, if so, set that shipping method into the customer's session.
//
$free_shipping = $_SESSION['opc']->isOrderFreeShipping($_SESSION['sendto']);
if ($free_shipping) {
    $_SESSION['shipping'] = [
        'id' => 'free_free', 
        'title' => FREE_SHIPPING_TITLE, 
        'cost' => 0 
    ];
}

require DIR_WS_CLASSES . 'order.php';
$order = new order;

$total_weight = $_SESSION['cart']->show_weight();
$total_count = $_SESSION['cart']->count_contents();

$comments = (isset($_SESSION['comments'])) ? $_SESSION['comments'] : '';
$quotes = [];

// -----
// If the order DOES NOT contain only virtual products, a guest-checkout has supplied
// validated guest-information and any temporary addresses for the checkout have been
// validated, then we need to get shipping quotes.
//
$customer_info_ok = $_SESSION['opc']->validateCustomerInfo();
$temp_shipto_addr_ok = $_SESSION['opc']->validateTempShiptoAddress();
if (!$is_virtual_order && $customer_info_ok && $temp_shipto_addr_ok) {
    // load all enabled shipping modules
    require DIR_WS_CLASSES . 'shipping.php';
    $shipping_modules = new shipping;
    
//-bof-product_delivery_by_postcode (PDP) integration
    if (defined('MODULE_SHIPPING_LOCALDELIVERY_POSTCODE') && defined('MODULE_SHIPPING_STOREPICKUP_POSTCODE') && function_exists('zen_get_UKPostcodeFirstPart')) {
        $check_delivery_postcode = $order->delivery['postcode'];

        // shorten UK / Canada postcodes to use first part only
        $check_delivery_postcode = zen_get_UKPostcodeFirstPart($check_delivery_postcode);

        // now check db for allowed postcodes and enable / disable relevant shipping modules
        if (in_array($check_delivery_postcode, explode(",", MODULE_SHIPPING_LOCALDELIVERY_POSTCODE))) {
        // continue as normal
        } else {
            $localdelivery = false;
        }

        if (in_array($check_delivery_postcode, explode(",", MODULE_SHIPPING_STOREPICKUP_POSTCODE))) {
        // continue as normal
        } else {
            $storepickup = false;
        }
    }
//-eof-product_delivery_by_postcode (PDP) integration

    $extra_message = (isset($_SESSION['shipping'])) ? var_export($_SESSION['shipping'], true) : ' (not set)';

    // -----
    // Detect (and log) the condition where the store's configuration shows "Shipping/Packaging->Order Free Shipping 0 Weight Status"
    // has been enabled, but the 'freeshipper' shipping method isn't when the order's weight is 0.
    //
    if ($total_weight == 0 && ORDER_WEIGHT_ZERO_STATUS === '1' && (!defined('MODULE_SHIPPING_FREESHIPPER_STATUS') || MODULE_SHIPPING_FREESHIPPER_STATUS !== 'True')) {
        $message = "0 weight is configured for Free Shipping and Free Shipping Module is not enabled, product IDs in cart: " . $_SESSION['cart']->get_product_id_list();
        trigger_error($message, E_USER_WARNING);
        $extra_message .= (' ' . $message);
    }
    $checkout_one->debug_message("CHECKOUT_ONE_AFTER_SHIPPING_CALCULATIONS, free_shipping ($free_shipping), $extra_message");

    // get all available shipping quotes
    $quotes = $shipping_modules->quote();

    // -----
    // If a shipping-method was previously selected, check that it is still valid (in case a zone restriction has disabled it, etc). 
    //
    // Also take this opportunity to see if the selected module's cost has changed.  If it has, just update the current method's
    // price for the display.
    //
    $shipping_selection_changed = false;
    if (!empty($_SESSION['shipping'])) {
        $selected_shipping_cost = 0;
        $checklist = [];
        foreach ($quotes as $quote) {
            if (!empty($quote['methods'])) {
                foreach ($quote['methods'] as $method) {
                    if ($_SESSION['shipping']['id'] === $quote['id'] . '_' . $method['id']) {
                        if ($_SESSION['shipping']['cost'] == $method['cost']) {
                            $checklist[] = $quote['id'] . '_' . $method['id'];
                        }
                    } else {
                        $checklist[] = $quote['id'] . '_' . $method['id'];
                    }
                }
            }
        }

        $checkval = $_SESSION['shipping']['id'];
        $checkout_one->debug_message("CHECKOUT_ONE_SHIPPING_CHECK ($checkval)\n" . json_encode($quotes) . "\n" . json_encode($checklist));
        if (!in_array($checkval, $checklist) && !($_SESSION['shipping']['id'] === 'free_free' && ($is_virtual_order || $free_shipping))) {
            // -----
            // Since the available shipping methods have changed, need to kill the current shipping method and display a
            // message to the customer to let them know what's up.
            //
            unset($_SESSION['shipping']);
            $shipping_selection_changed = true;
            $messageStack->add('checkout_shipping', ERROR_PLEASE_RESELECT_SHIPPING_METHOD, 'error');
        }
    }

    // if no shipping method has been selected, automatically select the cheapest method.
    // if the modules status was changed when none were available, to save on implementing
    // a javascript force-selection method, also automatically select the cheapest shipping
    // method if more than one module is now enabled
    if (empty($_SESSION['shipping']) || !isset($_SESSION['shipping']['id']) || $_SESSION['shipping']['id'] === '') {
        if (zen_count_shipping_modules() >= 1) {
            $_SESSION['shipping'] = $shipping_modules->cheapest();
        } elseif (count($quotes) > 0 && count($quotes[0]['methods']) > 0 && !$shipping_selection_changed) {
            $_SESSION['shipping'] = [
                'id' => $quotes[0]['id'] . '_' . $quotes[0]['methods'][0]['id'], 
                'title' => $quotes[0]['title'] . ' (' . $quotes[0]['methods'][0]['title'] . ')', 
                'cost' => $quotes[0]['methods'][0]['cost'] 
            ];
        }
    }
}

// -----
// Determine whether shipping-modules are available, noting that they're not available if either
// the guest customer-information or temporary shipping address hasn't been set.
//
$display_shipping_block = ($customer_info_ok && $temp_shipto_addr_ok);
$shipping_module_available = $is_virtual_order || ($display_shipping_block && !empty($_SESSION['shipping']) && ($free_shipping || zen_count_shipping_modules() > 0));

// -----
// If the session-based shipping information is set, sync that information up with the order.
//
$shipping_debug = '';
if (isset($_SESSION['shipping']) && is_array($_SESSION['shipping'])) {
    $shipping_debug = var_export($_SESSION['shipping'], true);
    $order->info['shipping_method'] = $_SESSION['shipping']['title'];
    $order->info['shipping_module_code'] = $_SESSION['shipping']['id'];
    $order->info['shipping_cost'] = $_SESSION['shipping']['cost'];
}

$checkout_one->debug_message("CHECKOUT_ONE_AFTER_SHIPPING_QUOTES\n" . $shipping_debug . var_export($order, true) . json_encode($messageStack) . json_encode($quotes));

// Should address-edit button be offered?
$address_can_be_changed = (MAX_ADDRESS_BOOK_ENTRIES > 1);

// -----
// Now that the shipping information has been gathered, reset the order's total based on that shipping-cost.
//
$order->info['total'] = $order->info['subtotal'] + $order->info['shipping_cost'];
if (DISPLAY_PRICE_WITH_TAX !== 'true') {
    $order->info['total'] += $order->info['tax'];
}

// -----
// The ot_gv "assumes" that its processing happens on the confirmation page (with POSTed values).  Since this processing pushes the handling
// to the checkout_one_confirmation page, need to fake-out a $_POST value for the Gift Certificate value to be applied.
//
if (isset($_SESSION['cot_gv'])) {
    $_POST['cot_gv'] = $_SESSION['cot_gv'];
}

if (!class_exists('order_total')) {
    require DIR_WS_CLASSES . 'order_total.php';
}
$order_total_modules = new order_total;
$order_total_modules->collect_posts();
$order_total_modules->pre_confirmation_check();

$checkout_one->debug_message("CHECKOUT_ONE_AFTER_ORDER_TOTAL_PROCESSING\n" . var_export($order_total_modules, true) . var_export($order, true) . var_export($messageStack, true));

// -----
// Ensure that the customer-information and temporary shipto/billto addresses have been
// validated prior to loading the enabled payment modules.
//
$temp_billto_addr_ok = $_SESSION['opc']->validateTempBilltoAddress();
$payment_module_available = false;
$enabled_payment_modules = [];
$payment_modules = false;
$display_payment_block = ($customer_info_ok && $temp_billto_addr_ok);
$flagOnSubmit = 0;
if (!($display_payment_block && $shipping_module_available)) {
    require DIR_WS_CLASSES . 'OnePageCheckoutNoPayment.php';
    $payment_modules = new OnePageCheckoutNoPayment;
} else {
    require DIR_WS_CLASSES . 'payment.php';
    $payment_modules = new payment;

    // -----
    // Check to see if we're in "special checkout", i.e. the payment's being made via the PayPal Express
    // Checkout's "shortcut" button.  If so, "reset" the payment modules to include **only** the payment
    // method presumed to be recorded in the current customer's session.
    //
    if ($payment_modules->in_special_checkout()) {
        unset($payment_modules);
        $payment_modules = new payment($_SESSION['payment']);
    }
    $enabled_payment_modules = $_SESSION['opc']->validateGuestPaymentMethods($payment_modules->selection());
    $flagOnSubmit = count($enabled_payment_modules);
    $payment_module_available = ($payment_modules->in_special_checkout() || count($enabled_payment_modules) > 0);
}

// -----
// Determine if there are any payment modules that are in the confirmation-required list.
//
// The generated list is used within the /includes/modules/pages/checkout_one/jscript_main.php module to determine what text to
// display for the order-submittal text/title.
//
$required_list = '';
if (!empty(CHECKOUT_ONE_CONFIRMATION_REQUIRED)) {
    $confirmation_required = explode(',', CHECKOUT_ONE_CONFIRMATION_REQUIRED);
    $required_list = '"' . implode('", "', $confirmation_required) . '"';
}

if (isset($_GET['payment_error']) && is_object(${$_GET['payment_error']}) && ($error = ${$_GET['payment_error']}->get_error())) {
    $messageStack->add('checkout_payment', $error['error'], 'error');
}

$extra_message = (isset($_SESSION['shipping'])) ? var_export($_SESSION['shipping'], true) : ' (not set)';
$checkout_one->debug_message("CHECKOUT_ONE_AFTER_PAYMENT_MODULES_SELECTION\n" . var_export($payment_modules, true) . $extra_message);

// -----
// If the payment method has been set in the session, there are a couple more cleanup/template-setting actions that might be needed.
//
$flagDisablePaymentAddressChange = false;
$editShippingButtonLink = true;
if (isset($_SESSION['payment'])) {
    // -----
    // If the payment method previously recorded for the checkout is no longer enabled, reset the session
    // value so that the customer can re-select.
    //
    if (empty(${$_SESSION['payment']})) {
        unset($_SESSION['payment']);
    } else {
        // -----
        // Fix-up required for PayPal Express Checkout shortcut-button since the payment method is pre-set on entry to the checkout process.
        //
        if (is_object(${$_SESSION['payment']}) && $order->info['payment_method'] == '') {
            $order->info['payment_method'] = ${$_SESSION['payment']}->title;
            $order->info['payment_module_code'] = ${$_SESSION['payment']}->code;
        }

        // if shipping-edit button should be overridden, do so
        if (isset($_SESSION['payment']) && method_exists(${$_SESSION['payment']}, 'alterShippingEditButton')) {
            $theLink = ${$_SESSION['payment']}->alterShippingEditButton();
            if ($theLink) {
                $editShippingButtonLink = $theLink;
            }
        }
        
        // deal with billing address edit button
        if (isset(${$_SESSION['payment']}->flagDisablePaymentAddressChange)) {
            $flagDisablePaymentAddressChange = ${$_SESSION['payment']}->flagDisablePaymentAddressChange;
        }
    }
}

// -----
// Record various processing flags with the OPC's session-handler, for possible use by intra-page AJAX
// calls.
//
$_SESSION['opc']->saveCheckoutProcessingFlags($is_virtual_order, $flagDisablePaymentAddressChange, $editShippingButtonLink);

// -----
// Disable the right- and left-sideboxes for the one-page checkout; the space is needed to get the 2-column display.
//
$flag_disable_right = $flag_disable_left = true;

// -----
// Add the breadcrumbs to give the customer guidance.
//
$breadcrumb->add(NAVBAR_TITLE_1);
$breadcrumb->add(NAVBAR_TITLE_2);

// This should be last line of the script:
$zco_notifier->notify('NOTIFY_HEADER_END_CHECKOUT_ONE');
