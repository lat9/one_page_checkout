<?php
// -----
// Part of the One-Page Checkout plugin, provided under GPL 2.0 license by lat9 (cindy@vinosdefrutastropicales.com).
// Copyright (C) 2013-2016, Vinos de Frutas Tropicales.  All rights reserved.
//

// This should be first line of the script:
$zco_notifier->notify('NOTIFY_HEADER_START_CHECKOUT_ONE_CONFIRMATION');

require (DIR_WS_MODULES . zen_get_module_directory ('require_languages.php'));
require_once (DIR_WS_CLASSES . 'http_client.php');

// -----
// Use "normal" checkout if not enabled.
//
if (!(defined ('CHECKOUT_ONE_ENABLED') && CHECKOUT_ONE_ENABLED == 'true')) {
    $zco_notifier->notify ('NOTIFY_CHECKOUT_ONE_NOT_ENABLED');
    zen_redirect (zen_href_link (FILENAME_CHECKOUT_SHIPPING, '', 'SSL'));
}

// -----
// There are some payment methods (like eWay) that "replace" the confirmation form via the HTML they return on
// the "process_button" payment-class function.  Rather than hard-code the list in code, below, the following
// constant will be updated as additional payment-methods that make use of that interface are identified.
//
if (!defined ('CHECKOUT_ONE_CONFIRMATION_REQUIRED')) define ('CHECKOUT_ONE_CONFIRMATION_REQUIRED', 'eway_rapid');

// -----
// In the "normal" Zen Cart checkout flow, the module /includes/init_includes/init_customer_auth.php performs the
// following check to see that the customer is authorized to checkout.  Rather than changing the code in that
// core-file, we'll repeat that check here.
//
if ($_SESSION['customers_authorization'] != 0) {
    $messageStack->add_session ('header', TEXT_AUTHORIZATION_PENDING_CHECKOUT, 'caution');
    zen_redirect (zen_href_link (FILENAME_DEFAULT));
}

// if there is nothing in the customers cart, redirect them to the shopping_cart page
if ($_SESSION['cart']->count_contents() <= 0) {
    zen_redirect (zen_href_link (FILENAME_SHOPPING_CART, '', 'NONSSL'));
}

// if the customer is not logged on, redirect them to the login page
if (!$_SESSION['customer_id']) {
    $_SESSION['navigation']->set_snapshot(array('mode' => 'SSL', 'page' => FILENAME_CHECKOUT_ONE));
    zen_redirect(zen_href_link (FILENAME_LOGIN, '', 'SSL'));
} else {
    // validate customer
    if (zen_get_customer_validate_session ($_SESSION['customer_id']) == false) {
        $_SESSION['navigation']->set_snapshot();
        zen_redirect(zen_href_link (FILENAME_LOGIN, '', 'SSL'));
    }
}

// avoid hack attempts during the checkout procedure by checking the internal cartID
if (isset ($_SESSION['cart']->cartID) && $_SESSION['cartID']) {
    if ($_SESSION['cart']->cartID != $_SESSION['cartID']) {
        $checkout_one->debug_message ('NOTIFY_CHECKOUT_ONE_CONFIRMATION_CARTID_MISMATCH');
        zen_redirect (zen_href_link (FILENAME_CHECKOUT_ONE, '', 'SSL'));
    }
}

// if no shipping method has been selected, redirect the customer to the shipping method selection page
if (!isset ($_SESSION['shipping'])) {
    $checkout_one->debug_message ('NOTIFY_CHECKOUT_ONE_CONFIRMATION_NO_SHIPPING');
    zen_redirect (zen_href_link (FILENAME_CHECKOUT_ONE, '', 'SSL'));
}

$checkout_one->debug_message ('Starting confirmation, shipping and request data follows:' . print_r ($_SESSION['shipping'], true), true);

if (isset ($_SESSION['shipping']['id']) && $_SESSION['shipping']['id'] == 'free_free' && $_SESSION['cart']->get_content_type() != 'virtual' && defined ('MODULE_ORDER_TOTAL_SHIPPING_FREE_SHIPPING') && MODULE_ORDER_TOTAL_SHIPPING_FREE_SHIPPING == 'true' && defined ('MODULE_ORDER_TOTAL_SHIPPING_FREE_SHIPPING_OVER') && $_SESSION['cart']->show_total() < MODULE_ORDER_TOTAL_SHIPPING_FREE_SHIPPING_OVER) {
    $checkout_one->debug_message ('NOTIFY_CHECKOUT_ONE_CONFIRMATION_FREE_SHIPPING');
    unset ($_SESSION['shipping']);
    $messageStack->add_session ('checkout_shipping', ERROR_PLEASE_RESELECT_SHIPPING_METHOD, 'error');
    zen_redirect (zen_href_link (FILENAME_CHECKOUT_ONE, '', 'SSL'));
}

// -----
// If we've received control from the checkout_one page's form, the action should be 'process'.
//
if (!isset ($_POST['action']) || $_POST['action'] != 'process') {
    $checkout_one->debug_message ('NOTIFY_CHECKOUT_ONE_CONFIRMATION_BAD_POST', true);
    zen_redirect (zen_href_link (FILENAME_CHECKOUT_ONE, '', 'SSL'));
}

if (isset($_POST['payment'])) {
    $_SESSION['payment'] = $_POST['payment'];
}

$error = false;
if (DISPLAY_CONDITIONS_ON_CHECKOUT == 'true') {
    if (!isset ($_POST['conditions']) || $_POST['conditions'] != '1') {
        $messageStack->add_session ('checkout_payment', ERROR_CONDITIONS_NOT_ACCEPTED, 'error');
    }
}

$session_start_hash = $checkout_one->hashSession ();

$shipping_billing = ($_POST['javascript_enabled'] != '0' && isset ($_POST['shipping_billing']) && $_POST['shipping_billing'] == '1');
$_SESSION['shipping_billing'] = $shipping_billing;

$_SESSION['comments'] = (zen_not_null ($_POST['comments'])) ? zen_clean_html ($_POST['comments']) : '';
$comments = $_SESSION['comments'];

$total_weight = $_SESSION['cart']->show_weight();
$total_count = $_SESSION['cart']->count_contents();

require (DIR_WS_CLASSES . 'order.php');
$order = new order;

$checkout_one->debug_message ('Initial order information:' . print_r ($order, true));

// -----
// If the order's all-virtual, then the shipping (free) has already been set; no need to go through all
// the shipping-related handling.
//
$error = false;
if ($order->content_type != 'virtual') {
    require (DIR_WS_CLASSES . 'shipping.php');
    $shipping_modules = new shipping ($_SESSION['shipping']);

    // -----
    // Determine free shipping conditions.
    //
    $free_shipping = false;
    $pass = false;
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

    // -----
    // Handle selected shipping module quote.
    //
    $quote = array();
    if (zen_count_shipping_modules() > 0 || $free_shipping) {
        if (isset ($_POST['shipping']) && strpos ($_POST['shipping'], '_')) {
            /**
            * check to be sure submitted data hasn't been tampered with
            */
            if ($_POST['shipping'] == 'free_free' && ($order->content_type != 'virtual' && !$pass)) {
                $error = true;
                $messageStack->add_session ('checkout_shipping', ERROR_INVALID_SHIPPING_SELECTION, 'error');
            }
            list($module, $method) = explode('_', $_POST['shipping']);
            if (is_object (${$module}) || $_POST['shipping'] == 'free_free') {
                if ($_POST['shipping'] == 'free_free') {
                    $quote[0]['methods'][0]['title'] = FREE_SHIPPING_TITLE;
                    $quote[0]['methods'][0]['cost'] = '0';
                    $quote[0]['methods'][0]['icon'] = '';
            
                } else {
                    $quote = $shipping_modules->quote ($method, $module);
            
                }
                $checkout_one->debug_message ("SHIPPING_QUOTE:\n" . print_r ($quote, true));
                if (isset ($quote['error'])) {
                    $error = true;
                    $messageStack->add_session ('checkout_shipping', $quote['error'], 'error');
            
                } else {
                    if (isset ($quote[0]) && isset ($quote[0]['error'])) {
                        $error = true;
                    }
                    if (isset ($quote[0]['methods'][0]['title']) && isset ($quote[0]['methods'][0]['cost'])) {
                        $_SESSION['shipping'] = array ( 
                            'id' => $_POST['shipping'],
                            'title' => ($free_shipping) ?  $quote[0]['methods'][0]['title'] : ($quote[0]['module'] . ' (' . $quote[0]['methods'][0]['title'] . ')'),
                            'cost' => $quote[0]['methods'][0]['cost'] 
                        );
                        $_SESSION['shipping']['extras'] = (isset($quote[0]['extras'])) ? $quote[0]['extras'] : '';
                    }
                }
            } else {
                unset ($_SESSION['shipping']);
                $checkout_one->debug_message ("Missing shipping module ($module/$method)? is_object (" . is_object (${$module}) . ')');
                $error = true;
            }
        }
    } else {
        unset($_SESSION['shipping']);
        $error = true;
    }
}
$checkout_one->debug_message ('Shipping setup, preparing to call order-totals.' . print_r ($shipping_modules, true) . print_r ($quote, true) . ((isset ($_SESSION['shipping'])) ? print_r ($_SESSION['shipping'], true) : 'Shipping not set'));

require (DIR_WS_CLASSES . 'order_total.php');
$order_total_modules = new order_total;
$order_total_modules->collect_posts();
$order_total_modules->pre_confirmation_check();

// load the selected payment module
require (DIR_WS_CLASSES . 'payment.php');
if (!isset ($credit_covers)) {
    $credit_covers = FALSE;
}
if ($credit_covers) {
    unset ($_SESSION['payment']);
}

$checkout_one->debug_message ('Returned from call to order-totals:' . print_r ($order_total_modules, true));

// -----
// Process the payment modules **only if** the order has been confirmed.  Don't want/need this processing for coupon/GC actions.
//
$order_confirmed = isset ($_POST['order_confirmed']) && $_POST['order_confirmed'] == 1;
if ($order_confirmed) {
    $payment_modules = new payment ($_SESSION['payment']);
    $payment_modules->update_status();
    if ( (!isset ($_SESSION['payment']) || $_SESSION['payment'] == '' || !is_object (${$_SESSION['payment']}) ) && $credit_covers === FALSE) {
        $messageStack->add_session ('checkout_payment', ERROR_NO_PAYMENT_MODULE_SELECTED, 'error');
    }

    if (is_array ($payment_modules->modules)) {
        $payment_modules->pre_confirmation_check();
    }
}

// -----
// If the customer's disabled javascript in their browser, check to see if the session-related information has changed.  This would
// occur, for instance, if the customer has chosen a different shipping method or applied a coupon/GB to their order.
//
// If so, redirect back to the checkout_one page so that the customer sees what they're confirming on the next pass through the
// confirmation page.
//
if ($_POST['javascript_enabled'] == '0' && $checkout_one->hashSession () != $session_start_hash) {
    $error = true;
    $messageStack->add_session ('checkout_payment', ERROR_NOJS_ORDER_CHANGED, 'error');
}

if ($error || $messageStack->size('checkout_payment') > 0 || !$order_confirmed) {
    // -----
    // Need to "redirect" any messages to 'checkout' (issued by ot_coupon and possibly others) so they display properly
    // on the checkout_one page.
    //
    if ($messageStack->size ('checkout') > 0) {
        foreach ($messageStack->messages as $current_message) {
            if ($current_message['class'] == 'checkout' && preg_match ('^messageStack(.*) larger^', $current_message['params'], $matches)) {
                $severity = strtolower ($matches[1]);
                if (preg_match ('^(<img(.*)>)?(.*)^', $current_message['text'], $matches)) {
                    $messageStack->add_session ('checkout_payment', $matches[3], $severity);
                }
            }
        }
    }
    $checkout_one->debug_message ("Something causing redirection back to checkout_one, error ($error), order_confirmed ($order_confirmed)" . print_r ($messageStack->messages, true));
    zen_redirect (zen_href_link (FILENAME_CHECKOUT_ONE, '', 'SSL'));
}

// Stock Check
$flagAnyOutOfStock = false;
$stock_check = array();
if (STOCK_CHECK == 'true') {
    for ($i=0, $n=sizeof ($order->products); $i<$n; $i++) {
        if ($stock_check[$i] = zen_check_stock($order->products[$i]['id'], $order->products[$i]['qty'])) {
            $flagAnyOutOfStock = true;
        }
    }
    // Out of Stock
    if ( (STOCK_ALLOW_CHECKOUT != 'true') && ($flagAnyOutOfStock == true) ) {
        zen_redirect (zen_href_link (FILENAME_SHOPPING_CART));
    }
}

// update customers_referral with $_SESSION['gv_id']
if ($_SESSION['cc_id']) {
    $discount_coupon_query = "SELECT coupon_code
                                FROM " . TABLE_COUPONS . "
                               WHERE coupon_id = :couponID LIMIT 1";

    $discount_coupon_query = $db->bindVars ($discount_coupon_query, ':couponID', $_SESSION['cc_id'], 'integer');
    $discount_coupon = $db->Execute($discount_coupon_query);

    $customers_referral_query = "SELECT customers_referral
                                   FROM " . TABLE_CUSTOMERS . "
                                  WHERE customers_id = :customersID LIMIT 1";

    $customers_referral_query = $db->bindVars($customers_referral_query, ':customersID', $_SESSION['customer_id'], 'integer');
    $customers_referral = $db->Execute($customers_referral_query);

    // only use discount coupon if set by coupon
    if ($customers_referral->fields['customers_referral'] == '' and CUSTOMERS_REFERRAL_STATUS == 1) {
        $sql = "UPDATE " . TABLE_CUSTOMERS . "
                SET customers_referral = :customersReferral
                WHERE customers_id = :customersID LIMIT 1";

        $sql = $db->bindVars($sql, ':customersID', $_SESSION['customer_id'], 'integer');
        $sql = $db->bindVars($sql, ':customersReferral', $discount_coupon->fields['coupon_code'], 'string');
        $db->Execute($sql);
    }
}

if (isset (${$_SESSION['payment']}->form_action_url)) {
    $form_action_url = ${$_SESSION['payment']}->form_action_url;
} else {
    $form_action_url = zen_href_link (FILENAME_CHECKOUT_PROCESS, '', 'SSL');
}

// -----
// If the currently-selected payment method requires the order-confirmation page to be displayed, then the
// header/footer are displayed too; otherwise, all elements of the display are hidden.
//
$flag_disable_left = $flag_disable_right = true;
if (in_array ($_SESSION['payment'], explode (',', CHECKOUT_ONE_CONFIRMATION_REQUIRED))) {
    $confirmation_required = true;
} else {
    $confirmation_required = false;
    $flag_disable_header = $flag_disable_footer = true;
}

$breadcrumb->add(NAVBAR_TITLE_1, zen_href_link (FILENAME_CHECKOUT_ONE, '', 'SSL'));
$breadcrumb->add(NAVBAR_TITLE_2);

// This should be last line of the script:
$zco_notifier->notify('NOTIFY_HEADER_END_CHECKOUT_ONE_CONFIRMATION');