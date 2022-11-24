<?php
// -----
// Part of the One-Page Checkout plugin, provided under GPL 2.0 license by lat9
// Copyright (C) 2013-2022, Vinos de Frutas Tropicales.  All rights reserved.
//
// Last updated: OPC v2.4.4
//

// This should be first line of the script:
$zco_notifier->notify('NOTIFY_HEADER_START_CHECKOUT_ONE_CONFIRMATION');

require DIR_WS_MODULES . zen_get_module_directory('require_languages.php');
require_once DIR_WS_CLASSES . 'http_client.php';

// -----
// Use "normal" checkout if not enabled.
//
if (!(defined('CHECKOUT_ONE_ENABLED') && isset($checkout_one) && $checkout_one->isEnabled())) {
    $zco_notifier->notify('NOTIFY_CHECKOUT_ONE_NOT_ENABLED');
    zen_redirect(zen_href_link(FILENAME_CHECKOUT_SHIPPING, '', 'SSL'));
}

// -----
// There are some payment methods (like eWay) that "replace" the confirmation form via the HTML they return on
// the "process_button" payment-class function.  Rather than hard-code the list in code, below, the following
// constant will be updated as additional payment-methods that make use of that interface are identified.
//
if (!defined('CHECKOUT_ONE_CONFIRMATION_REQUIRED')) {
    define('CHECKOUT_ONE_CONFIRMATION_REQUIRED', 'eway_rapid,stripepay,gps');
}

// if there is nothing in the customers cart, redirect them to the shopping_cart page
if ($_SESSION['cart']->count_contents() <= 0) {
    zen_redirect(zen_href_link(FILENAME_SHOPPING_CART, '', 'NONSSL'));
}

// if the customer is not logged on, redirect them to the login page
if (!zen_is_logged_in()) {
    $_SESSION['navigation']->set_snapshot(['mode' => 'SSL', 'page' => FILENAME_CHECKOUT_ONE]);
    zen_redirect(zen_href_link(FILENAME_LOGIN, '', 'SSL'));
} else {
    // validate customer
    if (zen_get_customer_validate_session($_SESSION['customer_id']) == false) {
        $_SESSION['navigation']->set_snapshot();
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

// avoid hack attempts during the checkout procedure by checking the internal cartID
if (!empty($_SESSION['cart']->cartID)) {
    if ($_SESSION['cart']->cartID != $_SESSION['cartID']) {
        $checkout_one->debug_message('NOTIFY_CHECKOUT_ONE_CONFIRMATION_CARTID_MISMATCH');
        zen_redirect(zen_href_link(FILENAME_CHECKOUT_ONE, '', 'SSL'));
    }
}

// if no shipping method has been selected, redirect the customer to the shipping method selection page
if (!isset($_SESSION['shipping'])) {
    $checkout_one->debug_message('NOTIFY_CHECKOUT_ONE_CONFIRMATION_NO_SHIPPING');
    zen_redirect(zen_href_link(FILENAME_CHECKOUT_ONE, '', 'SSL'));
}

$checkout_one->debug_message('Starting confirmation, shipping and request data follows:' . print_r($_SESSION['shipping'], true), true);

$free_shipping_enabled = (defined('MODULE_ORDER_TOTAL_SHIPPING_FREE_SHIPPING') && MODULE_ORDER_TOTAL_SHIPPING_FREE_SHIPPING === 'true');
$free_shipping_over = 0;
if (defined('MODULE_ORDER_TOTAL_SHIPPING_FREE_SHIPPING_OVER')) {
    $free_shipping_over = $currencies->value((float)MODULE_ORDER_TOTAL_SHIPPING_FREE_SHIPPING_OVER);
}
if (isset($_SESSION['shipping']['id']) && $_SESSION['shipping']['id'] === 'free_free' && $_SESSION['cart']->get_content_type() !== 'virtual' && $free_shipping_enabled &&  $_SESSION['cart']->show_total() < $free_shipping_over) {
    $checkout_one->debug_message('NOTIFY_CHECKOUT_ONE_CONFIRMATION_FREE_SHIPPING');
    unset($_SESSION['shipping']);
    $messageStack->add_session('checkout_shipping', ERROR_PLEASE_RESELECT_SHIPPING_METHOD, 'error');
    zen_redirect(zen_href_link(FILENAME_CHECKOUT_ONE, '', 'SSL'));
}

// -----
// If we've received control from the checkout_one page's form, the action should be 'process'.
//
if (!isset($_GET['redirect']) && (!isset($_POST['action']) || $_POST['action'] !== 'process')) {
    $checkout_one->debug_message('NOTIFY_CHECKOUT_ONE_CONFIRMATION_BAD_POST', true);
    zen_redirect(zen_href_link(FILENAME_CHECKOUT_ONE, '', 'SSL'));
}

// -----
// If a payment-method has been posted, save that payment-method in the current session.
//
if (isset($_POST['payment'])) {
    $_SESSION['payment'] = $_POST['payment'];
// -----
// Otherwise, if the session's payment-method has not yet been set, save it as an empty value
// since the order-totals' pre_confirmation_check method expects it to be set for the determination
// of whether/not a GV/DC credit 'covers' the order's cost.
//
} elseif (!isset($_SESSION['payment'])) {
    $_SESSION['payment'] = '';
}

// -----
// Start order-entry validation ...
//
$error = false;

// -----
// Check to ensure that any guest-customer information and/or temporary addresses (if used) have been
// entered and validated.  This should not occur unless "script kiddies" are messing with the CSS
// overlay used to guide the customer through the information-entry process.
//
if ($_SESSION['opc']->validateTemporaryEntries() === false) {
    $error = true;
    $messageStack->add_session('checkout_payment', ERROR_INVALID_TEMPORARY_ENTRIES, 'error');
}

if (!empty($_SESSION['shipping_billing'])) {
    $_SESSION['sendto'] = $_SESSION['billto'];
}

$_SESSION['comments'] = (!empty($_POST['comments'])) ? htmlspecialchars($_POST['comments'], ENT_NOQUOTES, CHARSET, true) : '';
$comments = $_SESSION['comments'];

$total_weight = $_SESSION['cart']->show_weight();
$total_count = $_SESSION['cart']->count_contents();

require DIR_WS_CLASSES . 'order.php';
$order = new order;

// -----
// Generate a starting hash of the session information, so that we can check to see if anything has changed
// after processing the order-total modules.
//
// Note: The posted data won't be present for some payment methods (notably gps), if they bypass the checkout_one
// page's transition to this page.
//
$session_start_hash = $checkout_one->hashSession(!empty($_POST['current_order_total']) ? $_POST['current_order_total'] : 0);

$checkout_one->debug_message('Initial order information:' . var_export($order, true));

// -----
// If the order's all-virtual, then the shipping (free) has already been set; no need to go through all
// the shipping-related handling.
//
$shipping_modules_debug = '';
if ($order->content_type !== 'virtual') {
    require DIR_WS_CLASSES . 'shipping.php';
    $shipping_modules = new shipping($_SESSION['shipping']);

    // -----
    // Determine free shipping conditions.
    //
    $free_shipping = $_SESSION['opc']->isOrderFreeShipping();

    // -----
    // Handle selected shipping module quote.
    //
    $quote = [];
    if (zen_count_shipping_modules() > 0 || $free_shipping) {
        if (isset($_POST['shipping']) && strpos($_POST['shipping'], '_') !== false) {
            /**
            * check to be sure submitted data hasn't been tampered with
            */
            if ($_POST['shipping'] === 'free_free' && ($order->content_type !== 'virtual' && !$free_shipping)) {
                $error = true;
                $messageStack->add_session('checkout_shipping', ERROR_INVALID_SHIPPING_SELECTION, 'error');
            }
            list($module, $method) = explode('_', $_POST['shipping']);
            if (is_object(${$module}) || $_POST['shipping'] === 'free_free') {
                if ($_POST['shipping'] === 'free_free') {
                    $quote[0]['methods'][0]['title'] = FREE_SHIPPING_TITLE;
                    $quote[0]['methods'][0]['cost'] = 0;
                    $quote[0]['methods'][0]['icon'] = '';
                } else {
                    $quote = $shipping_modules->quote($method, $module);

                }
                $checkout_one->debug_message("SHIPPING_QUOTE for " . $_POST['shipping'] . ":\n" . var_export($quote, true));
                if (isset($quote['error'])) {
                    $error = true;
                    $messageStack->add_session('checkout_shipping', $quote['error'], 'error');
                } else {
                    if (isset($quote[0]) && isset($quote[0]['error'])) {
                        $error = true;
                    }
                    if (isset($quote[0]['methods'][0]['title']) && isset($quote[0]['methods'][0]['cost'])) {
                        $_SESSION['shipping'] = [
                            'id' => $_POST['shipping'],
                            'title' => ($free_shipping) ?  $quote[0]['methods'][0]['title'] : ($quote[0]['module'] . ' (' . $quote[0]['methods'][0]['title'] . ')'),
                            'cost' => $quote[0]['methods'][0]['cost'] 
                        ];
                        $_SESSION['shipping']['extras'] = (isset($quote[0]['extras'])) ? $quote[0]['extras'] : '';
                    }
                }
            } else {
                unset($_SESSION['shipping']);
                $checkout_one->debug_message("Missing shipping module ($module/$method)? is_object (" . var_export(is_object(${$module}), true) . ')');
                $error = true;
            }
        }
    } else {
        unset($_SESSION['shipping']);
        $error = true;
    }
    $shipping_modules_debug = json_encode($shipping_modules) . PHP_EOL . json_encode($quote);
}
$checkout_one->debug_message('Shipping setup, preparing to call order-totals.' . $shipping_modules_debug . ((isset($_SESSION['shipping'])) ? json_encode($_SESSION['shipping']) : 'Shipping not set'));

if (!class_exists('order_total')) {
    require DIR_WS_CLASSES . 'order_total.php';
}
$order_total_modules = new order_total;
$order_total_modules->collect_posts();
$order_total_modules->pre_confirmation_check();
$checkout_one->debug_message('Returned from call to order-totals:' . json_encode($order_total_modules));

if (!isset($credit_covers)) {
    $credit_covers = false;
}

// -----
// Process the payment modules **only if** the order has been confirmed.  Don't want/need this processing for coupon/GC actions.
//
// Note: The order is considered 'confirmed' if we've been redirected from the 'checkout_confirmation' page, too.
//
require DIR_WS_CLASSES . 'payment.php';

$order_confirmed = (!empty($_GET['redirect']) || !empty($_POST['order_confirmed']));
if ($order_confirmed) {
    if ($credit_covers === true) {
        unset($_SESSION['payment']);
        $_SESSION['payment'] = '';
        $payment_title = PAYMENT_METHOD_GV;
    } else {
        if (!empty($_SESSION['payment'])) {
            $payment_modules = new payment($_SESSION['payment']);
            $payment_modules->update_status();
            if (is_array($payment_modules->modules)) {
                $payment_modules->pre_confirmation_check();
            }
        }
        if (!empty($_SESSION['payment']) && is_object(${$_SESSION['payment']})) {
            $payment_title = ${$_SESSION['payment']}->title;
        } else {
            $messageStack->add_session('checkout_payment', ERROR_NO_PAYMENT_MODULE_SELECTED, 'error');
        }
    }
}

// -----
// Now, process the order-totals so that the order's total is properly calculated for the hash-check below. Some payment modules 
// (notably firstdata_hco) make use of the $order_totals object, so make sure it's available whether or not confirmation is required on this page.
//
$order_totals = $order_total_modules->process();

// -----
// Check to see that the order's total value hasn't been changed by the confirmation-page's processing.  This can happen if:
//
// 1) The customer's disabled javascript in their browser, check to see if the session-related information has changed.  This would
//    occur, for instance, if the customer has chosen a different shipping method or applied a coupon/GB to their order.
// 2) An order-total (e.g. ot_cod_fee) has added its cost to the order as a result of the previous processing on this page.
//
// If so, redirect back to the checkout_one page so that the customer sees what they're confirming on the next pass through the
// confirmation page.
//
// Note: Some payment methods, notably gps, perform a redirect to 'checkout_confirmation' which is redirected
// here by the OPC observer.  Bypass the hash-check if the current payment method is in the list of those requiring
// confirmation.
//
$confirmation_required = false;
if ($credit_covers === true && strpos(CHECKOUT_ONE_CONFIRMATION_REQUIRED, 'credit_covers') !== false) {
    $confirmation_required = true;
} elseif (!empty($_SESSION['payment']) && in_array($_SESSION['payment'], explode(',', str_replace(' ', '', CHECKOUT_ONE_CONFIRMATION_REQUIRED)))) {
    $confirmation_required = true;
}

$session_end_hash = $checkout_one->hashSession($currencies->format($order->info['total']));
if ($confirmation_required === false && $order_confirmed === true && $session_end_hash !== $session_start_hash) {
    $error = true;
    $messageStack->add_session('checkout_payment', ERROR_NOJS_ORDER_CHANGED, 'error');
}

// -----
// Check to see if any session-based messages exist for the 'checkout' (issued by credit-class order-totals) or the 'checkout_payment'
// page; that will also result in a redirect back to the 'checkout_one' main page.
//
// Note: Don't want to use $messageStack->size, since that resets any session-based messages.
//
$session_messages = [];
if ($error === false && !empty($_SESSION['messageToStack'])) {
    $session_messages = $_SESSION['messageToStack'];
    foreach ($session_messages as $next_message) {
        if ($next_message['class'] === 'checkout' || $next_message['class'] === 'checkout_payment') {
            $error = true;
            break;
        }
    }
}

// -----
// Issue a notification to enable an observer to perform additional checks and indicate an error.
//
$zco_notifier->notify('NOTIFY_CHECKOUT_ONE_CONFIRMATION_PRE_ORDER_CHECK', '', $error);

// -----
// If no previous errors and the order has been confirmed, check to see if either the
// terms-and-conditions or privacy-terms agreement need to be ticked.
//
if ($error === false && $order_confirmed === true) {
    if (DISPLAY_CONDITIONS_ON_CHECKOUT === 'true') {
        if (!isset($_POST['conditions']) || $_POST['conditions'] !== '1') {
            $error = true;
            $messageStack->add_session('checkout_payment', ERROR_CONDITIONS_NOT_ACCEPTED, 'error');
        }
    }

    if ($_SESSION['opc']->isGuestCheckout() && DISPLAY_PRIVACY_CONDITIONS === 'true') {
        if (!isset($_POST['privacy_conditions']) || ($_POST['privacy_conditions'] !== '1')) {
            $error = true;
            $messageStack->add_session('checkout_payment', ERROR_PRIVACY_STATEMENT_NOT_ACCEPTED, 'error');
        }
    }
}

// -----
// If an error was detected or the order hasn't been confirmed, redirect back to the
// main data-gathering page.
//
if ($error === true || $order_confirmed === false) {
    $checkout_one->debug_message("Something causing redirection back to checkout_one, error ($error), order_confirmed ($order_confirmed)" . json_encode($messageStack->messages) . json_encode($session_messages) . json_encode($ot_total));
    zen_redirect(zen_href_link(FILENAME_CHECKOUT_ONE, '', 'SSL'));
}

// Stock Check
$flagAnyOutOfStock = false;
$stock_check = [];
if (STOCK_CHECK === 'true') {
    for ($i = 0, $n = count($order->products); $i < $n; $i++) {
        if ($stock_check[$i] = zen_check_stock($order->products[$i]['id'], $order->products[$i]['qty'])) {
            $flagAnyOutOfStock = true;
        }
    }
    // Out of Stock
    if (STOCK_ALLOW_CHECKOUT !== 'true' && $flagAnyOutOfStock === true) {
        zen_redirect(zen_href_link(FILENAME_SHOPPING_CART));
    }
}

// update customers_referral with $_SESSION['gv_id']
if (!empty($_SESSION['cc_id'])) {
    $discount_coupon_query =
        "SELECT coupon_code
           FROM " . TABLE_COUPONS . "
          WHERE coupon_id = :couponID LIMIT 1";
    $discount_coupon_query = $db->bindVars($discount_coupon_query, ':couponID', $_SESSION['cc_id'], 'integer');
    $discount_coupon = $db->Execute($discount_coupon_query);

    $customers_referral_query =
        "SELECT customers_referral
           FROM " . TABLE_CUSTOMERS . "
          WHERE customers_id = :customersID LIMIT 1";
    $customers_referral_query = $db->bindVars($customers_referral_query, ':customersID', $_SESSION['customer_id'], 'integer');
    $customers_referral = $db->Execute($customers_referral_query);

    // only use discount coupon if set by coupon
    if ($customers_referral->fields['customers_referral'] === '' && CUSTOMERS_REFERRAL_STATUS === '1') {
        $sql =
            "UPDATE " . TABLE_CUSTOMERS . "
                SET customers_referral = :customersReferral
              WHERE customers_id = :customersID LIMIT 1";
        $sql = $db->bindVars($sql, ':customersID', $_SESSION['customer_id'], 'integer');
        $sql = $db->bindVars($sql, ':customersReferral', $discount_coupon->fields['coupon_code'], 'string');
        $db->Execute($sql);
    }
}

// -----
// Some additional variables set for use during the template 'phase'; defaults
// are set since there might not be a 'real' payment method ... as is the case when
// a Gift Voucher or coupon fully 'covers' the cost of the order.
//
$form_action_url = zen_href_link(FILENAME_CHECKOUT_PROCESS, '', 'SSL');
$editShippingButtonLink = zen_href_link(FILENAME_CHECKOUT_SHIPPING_ADDRESS, '', 'SSL');
$flagDisablePaymentAddressChange = false;
$payment_process_button = '';
$confirmation = false;
if (!empty($_SESSION['payment']) && is_object(${$_SESSION['payment']})) {
    if (!empty(${$_SESSION['payment']}->form_action_url)) {
        $form_action_url = ${$_SESSION['payment']}->form_action_url;
    }
    if (method_exists(${$_SESSION['payment']}, 'alterShippingEditButton')) {
        $theLink = ${$_SESSION['payment']}->alterShippingEditButton();
        if ($theLink) {
            $editShippingButtonLink = $theLink;
        }
    }
    if (isset(${$_SESSION['payment']}->flagDisablePaymentAddressChange)) {
        $flagDisablePaymentAddressChange = ${$_SESSION['payment']}->flagDisablePaymentAddressChange;
    }
    $payment_process_button = $payment_modules->process_button();
    $confirmation = $payment_modules->confirmation();
}

// -----
// Now, set the $confirmation information into a standard format.  The 'fully-formed' format
// for that return from a payment-module's 'confirmation' method *should* be:
//
// $confirmation = [
//     'title' => 'A string title',
//     'fields' => [
//         ['title' => 'A label for ...', 'field' => '... the associated field'],
//     ],
// ];
//
// Unfortunately, some payment methods return (bool)false, some return an empty array, some return
// only the main 'title' and some return only the 'fields' array!
//
$confirmation_title = '';
$confirmation_fields = [];
if (is_array($confirmation)) {
    if (isset($confirmation['title'])) {
        $confirmation_title = $confirmation['title'];
    }
    if (isset($confirmation['fields']) && is_array($confirmation['fields'])) {
        $confirmation_fields = $confirmation['fields'];
    }
}

// -----
// If the currently-selected payment method requires the order-confirmation page to be displayed, then the
// header/footer are displayed too; otherwise, all elements of the display are hidden.
//
$flag_disable_left = $flag_disable_right = true;
if ($confirmation_required === false) {
    $flag_disable_header = $flag_disable_footer = true;
}

$breadcrumb->add(NAVBAR_TITLE_1, zen_href_link(FILENAME_CHECKOUT_ONE, '', 'SSL'));
$breadcrumb->add(NAVBAR_TITLE_2);

// This should be last line of the script:
$zco_notifier->notify('NOTIFY_HEADER_END_CHECKOUT_ONE_CONFIRMATION');
