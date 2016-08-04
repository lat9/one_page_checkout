<?php
// -----
// Part of the One-Page Checkout plugin, provided under GPL 2.0 license by lat9 (cindy@vinosdefrutastropicales.com).
// Copyright (C) 2013-2016, Vinos de Frutas Tropicales.  All rights reserved.
//
// -----
// This should be first line of the script:
$zco_notifier->notify('NOTIFY_HEADER_START_CHECKOUT_ONE');

// -----
// Use "normal" checkout if not enabled.
//
if (!(defined ('CHECKOUT_ONE_ENABLED') && CHECKOUT_ONE_ENABLED == 'true')) {
    $zco_notifier->notify ('NOTIFY_CHECKOUT_ONE_NOT_ENABLED');
    zen_redirect (zen_href_link (FILENAME_CHECKOUT_SHIPPING, '', 'SSL'));
}

require_once(DIR_WS_CLASSES . 'http_client.php');

require (DIR_WS_MODULES . zen_get_module_directory ('require_languages.php'));

// -----
// In the "normal" Zen Cart checkout flow, the module /includes/init_includes/init_customer_auth.php performs the
// following check to see that the customer is authorized to checkout.  Rather than changing the code in that
// core-file, we'll repeat that check here.
//
if ($_SESSION['customers_authorization'] != 0) {
    $messageStack->add_session ('header', TEXT_AUTHORIZATION_PENDING_CHECKOUT, 'caution');
    zen_redirect (zen_href_link (FILENAME_DEFAULT));
}

// if there is nothing in the customers cart, redirect them to the shopping cart page
if ($_SESSION['cart']->count_contents() <= 0) {
    zen_redirect (zen_href_link (FILENAME_SHOPPING_CART, '', 'NONSSL'));
}

// if the customer is not logged on, redirect them to the login page
if (!isset($_SESSION['customer_id']) || !$_SESSION['customer_id']) {
    $_SESSION['navigation']->set_snapshot();
    zen_redirect (zen_href_link (FILENAME_LOGIN, '', 'SSL'));
  
} else {
    // validate customer
    if (zen_get_customer_validate_session ($_SESSION['customer_id']) == false) {
        $_SESSION['navigation']->set_snapshot (array ('mode' => 'SSL', 'page' => FILENAME_CHECKOUT_ONEPAGE));
        zen_redirect (zen_href_link (FILENAME_LOGIN, '', 'SSL'));
    
    }
}

// Validate Cart for checkout
$_SESSION['valid_to_checkout'] = true;
$_SESSION['cart']->get_products(true);
if ($_SESSION['valid_to_checkout'] == false) {
    $messageStack->add('header', ERROR_CART_UPDATE, 'error');
    zen_redirect (zen_href_link (FILENAME_SHOPPING_CART));
}

// Stock Check
if ( (STOCK_CHECK == 'true') && (STOCK_ALLOW_CHECKOUT != 'true') ) {
    $products = $_SESSION['cart']->get_products();
    for ($i=0, $n=sizeof($products); $i<$n; $i++) {
        $qtyAvailable = zen_get_products_stock($products[$i]['id']);
        // compare against product inventory, and against mixed=YES
        if ($qtyAvailable - $products[$i]['quantity'] < 0 || $qtyAvailable - $_SESSION['cart']->in_cart_mixed($products[$i]['id']) < 0) {
            zen_redirect (zen_href_link (FILENAME_SHOPPING_CART));
            break;
        }
    }
}

// get coupon code
if (isset ($_SESSION['cc_id'])) {
    $discount_coupon_query = "SELECT coupon_code FROM " . TABLE_COUPONS . " WHERE coupon_id = :couponID";
    $discount_coupon_query = $db->bindVars($discount_coupon_query, ':couponID', $_SESSION['cc_id'], 'integer');
    $discount_coupon = $db->Execute ($discount_coupon_query);

    if ($discount_coupon->EOF) {
        unset ($_SESSION['cc_id'], $discount_coupon); 
    }
}

$shipping_billing = (isset ($_SESSION['shipping_billing'])) ? $_SESSION['shipping_billing'] : true;

// if no billing destination address was selected, use the customers own address as default
if (!isset ($_SESSION['billto'])) {
    $_SESSION['billto'] = $_SESSION['customer_default_address_id'];
} else {
    // verify the selected billing address
    $check_address_query = "SELECT count(*) AS total FROM " . TABLE_ADDRESS_BOOK . "
                            WHERE customers_id = :customersID
                            AND address_book_id = :addressBookID LIMIT 1";

    $check_address_query = $db->bindVars($check_address_query, ':customersID', $_SESSION['customer_id'], 'integer');
    $check_address_query = $db->bindVars($check_address_query, ':addressBookID', $_SESSION['billto'], 'integer');
    $check_address = $db->Execute($check_address_query);

    if ($check_address->fields['total'] != '1') {
        $_SESSION['billto'] = $_SESSION['customer_default_address_id'];
        $_SESSION['payment'] = '';
    }
}

// if no shipping destination address was selected, use the customers own address as default
if (!isset ($_SESSION['sendto'])) {
    $_SESSION['sendto'] = $_SESSION['customer_default_address_id'];
} elseif ($shipping_billing) {
    $_SESSION['sendto'] = $_SESSION['billto'];
} else {
// verify the selected shipping address
    $check_address_query = "SELECT count(*) AS total
                            FROM   " . TABLE_ADDRESS_BOOK . "
                            WHERE  customers_id = :customersID
                            AND    address_book_id = :addressBookID LIMIT 1";

    $check_address_query = $db->bindVars($check_address_query, ':customersID', $_SESSION['customer_id'], 'integer');
    $check_address_query = $db->bindVars($check_address_query, ':addressBookID', $_SESSION['sendto'], 'integer');
    $check_address = $db->Execute($check_address_query);

    if ($check_address->fields['total'] != '1') {
        $_SESSION['sendto'] = $_SESSION['customer_default_address_id'];
        unset($_SESSION['shipping']);
    }
}

// register a random ID in the session to check throughout the checkout procedure
// against alterations in the shopping cart contents
if (isset($_SESSION['cart']->cartID)) {
    if (!isset($_SESSION['cartID']) || $_SESSION['cart']->cartID != $_SESSION['cartID']) {
        $_SESSION['cartID'] = $_SESSION['cart']->cartID;
    }
} else {
    zen_redirect(zen_href_link(FILENAME_TIME_OUT));
}

// -----
// Check to see if the current cart contains only virtual products and, if so, pre-set the order's shipping method
// to indicate free shipping.  This processing is moved before the order-object's creation to also cover the
// case where the order started out as a mixed/physical one (with a "real" shipping method set) and was converted
// to a virtual order after a prior entry to the checkout_one processing.
//
$is_virtual_order = false;
if ($_SESSION['cart']->get_content_type () == 'virtual') {
    $is_virtual_order = true;
    $shipping_billing = false;

    $_SESSION['shipping'] = array ( 'id' => 'free_free', 'title' => 'free_free', 'cost' => 0 );
    $_SESSION['sendto'] = false;
}

require (DIR_WS_CLASSES . 'order.php');
$order = new order;

$total_weight = $_SESSION['cart']->show_weight();
$total_count = $_SESSION['cart']->count_contents();

$comments = (isset ($_SESSION['comments'])) ? $_SESSION['comments'] : '';

// -----
// If the order DOES NOT contain only virtual products, then we need to get shipping quotes.
//
if (!$is_virtual_order) {
    // load all enabled shipping modules
    require (DIR_WS_CLASSES . 'shipping.php');
    $shipping_modules = new shipping;

    $pass = false;
    $free_shipping = false;
    if (defined ('MODULE_ORDER_TOTAL_SHIPPING_FREE_SHIPPING') && MODULE_ORDER_TOTAL_SHIPPING_FREE_SHIPPING == 'true') {
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
    
    $extra_message = (isset ($_SESSION['shipping'])) ? var_export ($_SESSION['shipping'], true) : ' (not set)';
    $checkout_one->debug_message ("CHECKOUT_ONE_AFTER_SHIPPING_CALCULATIONS, pass ($pass), free_shipping ($free_shipping), $extra_message");

    // get all available shipping quotes
    $quotes = $shipping_modules->quote();

    // check that the currently selected shipping method is still valid (in case a zone restriction has disabled it, etc)
    if (isset ($_SESSION['shipping'])) {
        $checklist = array();
        foreach ($quotes as $key => $val) {
            if ($val['methods'] != '') {
                foreach ($val['methods'] as $key2 => $method) {
                    $checklist[] = $val['id'] . '_' . $method['id'];
                }
            }
        }

        $checkval = $_SESSION['shipping']['id'];
        $checkout_one->debug_message ("CHECKOUT_ONE_SHIPPING_CHECK ($checkval)\n" . print_r ($quotes, true) . "\n" . print_r ($checklist, true));
        if (!in_array ($checkval, $checklist) && !($_SESSION['shipping']['id'] == 'free_free' && $is_virtual_order)) {
            // -----
            // Since the available shipping methods have changed, need to kill the current shipping method and display a
            // message to the customer to let them know what's up.
            //
            unset ($_SESSION['shipping']);
            $messageStack->add ('checkout_shipping', ERROR_PLEASE_RESELECT_SHIPPING_METHOD, 'error');
        }
    }

    // if no shipping method has been selected, automatically select the cheapest method.
    // if the modules status was changed when none were available, to save on implementing
    // a javascript force-selection method, also automatically select the cheapest shipping
    // method if more than one module is now enabled
    if (!isset ($_SESSION['shipping']) || !isset($_SESSION['shipping']['id']) || $_SESSION['shipping']['id'] == '') {
        if (zen_count_shipping_modules() > 1) {
            $_SESSION['shipping'] = $shipping_modules->cheapest();
        } elseif (count ($quotes) == 1 && count ($quotes[0]['methods']) == 1) {
            $_SESSION['shipping'] = array ( 'id' => $quotes[0]['id'] . '_' . $quotes[0]['methods'][0]['id'], 'title' => $quotes[0]['title'] . ' (' . $quotes[0]['methods'][0]['title'] . ')', 'cost' => $quotes[0]['methods'][0]['cost'] );
        }
    }
}

// -----
// If the shipping-information is valid, but hasn't yet been entered into the order, set those values now so that
// the shipping order-total has something to total!
//
if ($order->info['shipping_method'] == '' && isset ($_SESSION['shipping']) && is_array ($_SESSION['shipping'])) {
    $order->info['shipping_method'] = $_SESSION['shipping']['title'];
    $order->info['shipping_module_code'] = $_SESSION['shipping']['id'];
    $order->info['shipping_cost'] = $_SESSION['shipping']['cost'];
}

$checkout_one->debug_message ("CHECKOUT_ONE_AFTER_SHIPPING_QUOTES\n" . var_export ($_SESSION['shipping'], true) . print_r ($order, true) . print_r ($messageStack, true) . print_r ($quotes, true));

// Should address-edit button be offered?
$address_can_be_changed = (MAX_ADDRESS_BOOK_ENTRIES > 1);

// -----
// The ot_gv "assumes" that its processing happens on the confirmation page (with POSTed values).  Since this processing pushes the handling
// to the checkout_one_confirmation page, need to fake-out a $_POST value for the Gift Certificate value to be applied.
//
if (isset ($_SESSION['cot_gv'])) {
    $_POST['cot_gv'] = $_SESSION['cot_gv'];
}

require (DIR_WS_CLASSES . 'order_total.php');
$order_total_modules = new order_total;
$order_total_modules->collect_posts();
$order_total_modules->pre_confirmation_check();

$checkout_one->debug_message ("CHECKOUT_ONE_AFTER_ORDER_TOTAL_PROCESSING\n" . print_r ($order_total_modules, true) . print_r ($order, true) . print_r ($messageStack, true));

// load all enabled payment modules
require (DIR_WS_CLASSES . 'payment.php');
$payment_modules = new payment;
$flagOnSubmit = count ($payment_modules->selection());

if (isset($_GET['payment_error']) && is_object(${$_GET['payment_error']}) && ($error = ${$_GET['payment_error']}->get_error())) {
    $messageStack->add('checkout_payment', $error['error'], 'error');
}

$extra_message = (isset ($_SESSION['shipping'])) ? var_export ($_SESSION['shipping'], true) : ' (not set)';
$checkout_one->debug_message ("CHECKOUT_ONE_AFTER_PAYMENT_MODULES_SELECTION\n" . print_r ($payment_modules, true) . $extra_message);

// -----
// If the payment method has been set in the session, there are a couple more cleanup/template-setting actions that might be needed.
//
$flagDisablePaymentAddressChange = false;
$editShippingButtonLink = zen_href_link (FILENAME_CHECKOUT_SHIPPING_ADDRESS, '', 'SSL');
if (isset ($_SESSION['payment'])) {
    // -----
    // Fix-up required for PayPal Express Checkout shortcut-button since the payment method is pre-set on entry to the checkout process.
    //
    if (is_object (${$_SESSION['payment']}) && $order->info['payment_method'] == '') {
        $order->info['payment_method'] = ${$_SESSION['payment']}->title;
        $order->info['payment_module_code'] = ${$_SESSION['payment']}->code;
    }

    // if shipping-edit button should be overridden, do so
    if (isset ($_SESSION['payment']) && method_exists (${$_SESSION['payment']}, 'alterShippingEditButton')) {
        $theLink = ${$_SESSION['payment']}->alterShippingEditButton();
        if ($theLink) {
            $editShippingButtonLink = $theLink;
        }
    }
    
    // deal with billing address edit button
    if (isset (${$_SESSION['payment']}->flagDisablePaymentAddressChange)) {
        $flagDisablePaymentAddressChange = ${$_SESSION['payment']}->flagDisablePaymentAddressChange;
    }
}

// -----
// Disable the right- and left-sideboxes for the one-page checkout; the space is needed to get the 2-column display.
//
$flag_disable_right = $flag_disable_left = true;

// -----
// Add the breadcrumbs to give the customer guidance.
//
$breadcrumb->add (NAVBAR_TITLE_1);
$breadcrumb->add (NAVBAR_TITLE_2);

// This should be last line of the script:
$zco_notifier->notify('NOTIFY_HEADER_END_CHECKOUT_ONE');