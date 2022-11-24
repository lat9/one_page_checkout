<?php
// -----
// Part of the One-Page Checkout plugin, provided under GPL 2.0 license by lat9
// Copyright (C) 2013-2022, Vinos de Frutas Tropicales.  All rights reserved.
//
// Last updated: OPC v2.4.4.
//
if (!defined('IS_ADMIN_FLAG')) {
    die('Illegal Access');
}

class checkout_one_observer extends base
{
    private
        $browser,
        $enabled = false,
        $debug = false,
        $debug_logfile,
        $current_page_base,
        $needUnsupportedPageMessage = false,
        $needGuestCheckoutUnavailableMessage = false;
            
    public function __construct() 
    {
        global $current_page_base;

        // -----
        // Determine if the current session browser is an Internet Explorer version less than 9 (that don't properly support
        // jQuery).
        //
        if (!class_exists('Vinos_Browser')) {
            require DIR_WS_CLASSES . 'Vinos_Browser.php';
        }
        $browser = new Vinos_Browser();
        $unsupported_browser = ($browser->getBrowser() == Vinos_Browser::BROWSER_IE && $browser->getVersion() < 9);
        $this->browser = $browser->getBrowser() . '::' . $browser->getVersion();

        // -----
        // If the session-based OPC 'brains' aren't available, there's nothing to be done.
        // The plugin will not attach to its various notifications.
        //
        if (empty($_SESSION['opc']) || !is_object($_SESSION['opc'])) {
//            trigger_error('Missing $_SESSION[\'opc\']:' . var_export($_SERVER, true) . PHP_EOL . var_export($_GET, true) . PHP_EOL . var_export($_POST, true), E_USER_WARNING);
            return;
        }

        // -----
        // If the plugin's configuration is not set or not enabled or if the browser/access is not supported, perform
        // a quick return.  That will result in an overall 'OPC' disablement.
        //
        if (!defined('CHECKOUT_ONE_ENABLED') || CHECKOUT_ONE_ENABLED === 'false' || $unsupported_browser || $browser->isRobot()) {
            // -----
            // If the OPC session is present, ensure that any previous guest-related accesses are cleared.
            //
            if (isset($_SESSION['opc']) && is_object($_SESSION['opc'])) {
                $_SESSION['opc']->resetGuestSessionValues();
            }
            return;
        }

        // -----
        // The 'opctype' variable is applied to the checkout_shipping page's link by the checkout_one page's alternate link
        // (available if there's a jQuery error affecting that page's ability to perform a 1-page checkout).
        //
        // If that's set, set a session variable to override the OPC processing, allowing the customer to check out via the
        // built-in 3-page checkout!
        //
        if (isset($_GET['opctype'])) {
            if ($_GET['opctype'] === 'jserr') {
                $_SESSION['opc_error'] = OnePageCheckout::OPC_ERROR_NO_JS;
            }

            // -----
            // Un-comment the following three lines (during testing) to enable a developer "assist", 
            // allowing the above value to be reset by supplying &opctype=retry to any link to try again.
            //
//            if ($_GET['opctype'] == 'retry') {
//                unset($_SESSION['opc_error']);
//            }
        }

        // -----
        // Initialize the plugin's debug filename and enabled control.
        //
        $this->debug = (defined('CHECKOUT_ONE_DEBUG') && (CHECKOUT_ONE_DEBUG === 'true' || CHECKOUT_ONE_DEBUG === 'full'));
        if ($this->debug && defined('CHECKOUT_ONE_DEBUG_EXTRA') && CHECKOUT_ONE_DEBUG_EXTRA !== '' && CHECKOUT_ONE_DEBUG_EXTRA !== '*') {
            $debug_customers = explode(',', str_replace(' ', '', CHECKOUT_ONE_DEBUG_EXTRA));
            if (!in_array($_SESSION['customer_id'], $debug_customers)) {
                $this->debug = false;
            }
        }
        $this->debug_logfile = $_SESSION['opc']->getDebugLogFileName();

        // -----
        // Perform a little "session-cleanup".  If a guest just placed an order and has navigated off
        // the checkout_success or other, customizable, pages, need to remove all session-variables associated with that
        // guest checkout.
        //
        $post_checkout_pages = explode(',', str_replace(' ', '', CHECKOUT_ONE_GUEST_POST_CHECKOUT_PAGES_ALLOWED));
        $post_checkout_pages[] = FILENAME_CHECKOUT_SUCCESS;
        if (isset($_SESSION['order_placed_by_guest']) && !in_array($current_page_base, $post_checkout_pages)) {
            unset($_SESSION['order_placed_by_guest'], $_SESSION['order_number_created']);
            $_SESSION['opc']->resetGuestSessionValues();
        }

        // -----
        // If the plugin's environment is supportable, then the processing for the OPC is enabled.
        // We'll attach notifiers to the various elements of the 3-page checkout to consolidate that
        // processing into a single page.
        //
        if ($_SESSION['opc']->checkEnabled()) {
            $this->enabled = true;
            $this->current_page_base = $current_page_base;

            // -----
            // If the customer is currently active in a guest-checkout ...
            //
            if ($_SESSION['opc']->isGuestCheckout()) {
                // -----
                // ... check to see that guest-checkout is **still** enabled.  If so, check to see that
                // the current page is "allowed" during a guest-checkout; otherwise, reset the
                // OPC's guest-checkout settings so that the checkout-process will revert to the
                // built-in 3-page version.
                //
                if ($_SESSION['opc']->guestCheckoutEnabled()) {
                    $disallowed_pages = explode(',', str_replace(' ', '', CHECKOUT_ONE_GUEST_PAGES_DISALLOWED));
                    if (in_array($this->current_page_base, $disallowed_pages)) {
                        $this->needUnsupportedPageMessage = true;
                    }
                } else {
                    $_SESSION['opc']->resetGuestSessionValues();
                    $this->needGuestCheckoutUnavailableMessage = true;
                }
            }

            $this->attach(
                $this, 
                [
                    'NOTIFY_LOGIN_SUCCESS',
                    'NOTIFY_LOGIN_SUCCESS_VIA_CREATE_ACCOUNT',
                    'NOTIFY_HEADER_START_CHECKOUT_SHIPPING', 
                    'NOTIFY_HEADER_START_CHECKOUT_PAYMENT', 
                    'NOTIFY_HEADER_START_CHECKOUT_SHIPPING_ADDRESS', 
                    'NOTIFY_HEADER_START_CHECKOUT_CONFIRMATION',
                    'NOTIFY_HEADER_START_ADDRESS_BOOK_PROCESS',
                    'NOTIFY_CHECKOUT_PROCESS_BEFORE_CART_RESET',
                    'NOTIFY_ZEN_IN_GUEST_CHECKOUT',
                    'NOTIFY_ZEN_IS_LOGGED_IN',
                    'NOTIFY_ZEN_ADDRESS_LABEL',
                ]
            );
        }

        // -----
        // If the OPC's guest-/account-registration is enabled, some additional notifications
        // need to be monitored.
        //
        // Note: This is left as "legacy", just in case they need to be 'unobserved' in the future.
        // Right now (v2.1.0), that opc method returns an unconditional (bool)true.
        //
        if ($this->enabled && $_SESSION['opc']->initTemporaryAddresses()) {
            $this->attach(
                $this, 
                [
                    'NOTIFY_ORDER_CART_AFTER_ADDRESSES_SET',
                    'NOTIFY_ORDER_DURING_CREATE_ADDED_ORDER_HEADER',
                    'NOTIFY_ORDER_INVOICE_CONTENT_READY_TO_SEND',
                    'NOTIFY_HEADER_START_CHECKOUT_SUCCESS',
                    'NOTIFY_OT_COUPON_USES_PER_USER_CHECK',
                    'NOTIFY_PAYMENT_PAYPALEC_BEFORE_SETEC',
                    'NOTIFY_PAYPALEXPRESS_BYPASS_ADDRESS_CREATION',
                    'NOTIFY_PAYPALWPP_BEFORE_DOEXPRESSCHECKOUT',
                    'NOTIFY_PAYPALWPP_DISABLE_GET_OVERRIDE_ADDRESS',
                    'NOTIFY_HEADER_START_SHOPPING_CART',
                    'NOTIFY_HEADER_START_CHECKOUT_PAYMENT_ADDRESS',
                    'ZEN_GET_TAX_LOCATIONS',
                ]
            );
        }

        // -----
        // If the One-Page Checkout processing is **not** enabled, make sure that the customer's
        // session is cleaned of any 'left-over' settings, in case OPC was enabled for some portion
        // of the customer's checkout process.
        //
        if (!$this->enabled) {
            $_SESSION['opc']->resetGuestSessionValues();
        }

        // -----
        // Finally, need to "clean up" any email_address value that was injected into
        // the session by the order_status page's processing, in support of not-logged-in
        // customers whose orders included downloads.
        //
        // If the customer has navigated off of the order_status/download pages, remove
        // those variables from the session.
        //
        if (isset($_SESSION['email_is_os']) && ($current_page_base !== FILENAME_ORDER_STATUS && $current_page_base !== FILENAME_DOWNLOAD)) {
            unset($_SESSION['email_is_os'], $_SESSION['email_address']);
        }
    }

    // -----
    // This method performs some additional initialization checks which require the $messageStack to
    // be instantiated and for the session's language to be set (it's required by the call to the cart's
    // get_products method.  The 'messageCheck' method is invoked via OPC's auto_loader's 'call' via an 'objectMethod' record.
    //
    public function messageCheck()
    {
        global $messageStack, $current_page_base;

        // -----
        // If no previous jQuery error was noted and an account-holder is not logged in,
        // check the shopping-cart's current contents to see if one or more Gift Certificates
        // are present (only account-holders can purchase GC's).
        //
        // If one or more GC is present in the customer's cart:
        // - If the customer is not on the login or checkout_one page, let them know that they'll need
        // to create an account (or sign in) to make that purchase.  If the customer is in the
        // middle of a guest-checkout (e.g. they started without a GC in-cart and added one after
        // the guest-checkout started), let them know that continuing with the checkout will
        // result in a loss of the information that they previously entered.
        //
        // - If the customer has continued (via button- or link-click) to the login/checkout_one
        // page, reset any guest-related information that was previously entered.  The guest-checkout
        // will be disabled.
        //
        // NOTE: Using the session-based OPC class to determine logged-in/guest-checkout status, since
        // the observers for the zen_is_logged_in/zen_in_guest_checkout functions haven't yet been
        // attached!
        //
        if (!(isset($_SESSION['opc_error']) && $_SESSION['opc_error'] == OnePageCheckout::OPC_ERROR_NO_JS) && $_SESSION['opc']->guestCheckoutEnabled()) {
            if (!$_SESSION['opc']->isLoggedIn() || $_SESSION['opc']->isGuestCheckout()) {
                unset($_SESSION['opc_error']);
                $cart_products = $_SESSION['cart']->get_products();
                foreach ($cart_products as $current_product) {
                    if (strpos($current_product['model'], 'GIFT') === 0) {
                        $pages_to_reset_for_gc = [
                            FILENAME_LOGIN,
                            FILENAME_CHECKOUT_ONE
                        ];
                        if (!in_array($current_page_base, $pages_to_reset_for_gc)) {
                            $gift_certificate_message = WARNING_GUEST_NO_GCS;
                            if ($_SESSION['opc']->isGuestCheckout()) {
                                $gift_certificate_message .= ' ' . WARNING_GUEST_GCS_RESET . '<br><br>' . WARNING_GUEST_REMOVE_GC;
                            }
                            $messageStack->add('header', $gift_certificate_message, 'caution');
                        } else {
                            $_SESSION['opc']->resetGuestSessionValues();
                            $_SESSION['opc_error'] = OnePageCheckout::OPC_ERROR_NO_GC;
                        }
                        break;
                    }
                }
            }
        }
        if ($this->needUnsupportedPageMessage === true) {
            $messageStack->add_session('header', ERROR_GUEST_CHECKOUT_PAGE_DISALLOWED, 'error');
            zen_redirect(zen_href_link(FILENAME_DEFAULT));
        }
        if ($this->needGuestCheckoutUnavailableMessage === true) {
            $messageStack->add_session('header', WARNING_GUEST_CHECKOUT_NOT_AVAILABLE, 'warning');
            zen_redirect(zen_href_link(FILENAME_LOGIN, '', 'SSL'));
        }
    }

    public function update(&$class, $eventID, $p1, &$p2, &$p3, &$p4, &$p5, &$p6, &$p7) 
    {
        switch ($eventID) {
            // -----
            // If a customer has just successfully logged in, they might have logged in after
            // starting a guest-checkout.  Let the session-based OPC controller perform any
            // clean-up required.
            //
            case 'NOTIFY_LOGIN_SUCCESS':
            case 'NOTIFY_LOGIN_SUCCESS_VIA_CREATE_ACCOUNT':     //-Fall-through from above ...
                $_SESSION['opc']->cleanupGuestSession();
                break;

            // -----
            // Redirect any accesses to the "3-page" non-confirmation pages to the one-page version.
            //
            case 'NOTIFY_HEADER_START_CHECKOUT_SHIPPING':
            case 'NOTIFY_HEADER_START_CHECKOUT_PAYMENT':
                $this->debug_message('checkout_one redirect 1a: ', true, 'checkout_one_observer');
                zen_redirect(zen_href_link(FILENAME_CHECKOUT_ONE, zen_get_all_get_params(), 'SSL'));
                break;

            // -----
            // Redirect any accesses to the "3-page" checkout confirmation to the one-page version.
            //
            case 'NOTIFY_HEADER_START_CHECKOUT_CONFIRMATION':
                $this->debug_message('checkout_one redirect 1b: ', true, 'checkout_one_observer');
                zen_redirect(zen_href_link(FILENAME_CHECKOUT_ONE_CONFIRMATION, zen_get_all_get_params() . 'redirect=true', 'SSL'));
                break;

            // -----
            // If the customer leaves the checkout process to view and/or make changes to their
            // cart, the shipping-estimator might change the order's ship-to address-book-id, causing
            // OPC's processing to get out-of-sync.  On entry to the 'shopping_cart' page, record
            // the order's current ship-to address for restoration when/if the customer re-enters
            // the checkout processing.
            //
            case 'NOTIFY_HEADER_START_SHOPPING_CART':
                $_SESSION['opc']->saveOrdersSendtoAddress();
                break;

            // -----
            // When a *logged-in* customer navigates to the 'checkout_shipping_address' page, reset the
            // shipping=billing flag to indicate that shipping is no longer the same as billing.
            //
            // If a _guest_ customer navigates here, redirect back to the main checkout_one page to 
            // allow that address change.
            //
            case 'NOTIFY_HEADER_START_CHECKOUT_SHIPPING_ADDRESS':
                if ($_SESSION['opc']->isGuestCheckout()) {
                    $this->debug_message('checkout_one redirect 2: ', true, 'checkout_one_observer');
                    zen_redirect(zen_href_link(FILENAME_CHECKOUT_ONE, zen_get_all_get_params(), 'SSL'));
                }
                $_SESSION['shipping_billing'] = false;
                break;

            // -----
            // When a _guest_ customer navigates to the 'checkout_payment_address' page, redirect
            // to the main checkout_one page to allow that address change.
            //
            case 'NOTIFY_HEADER_START_CHECKOUT_PAYMENT_ADDRESS':
                if ($_SESSION['opc']->isGuestCheckout()) {
                    $this->debug_message('checkout_one redirect 3: ', true, 'checkout_one_observer');
                    zen_redirect(zen_href_link(FILENAME_CHECKOUT_ONE, zen_get_all_get_params(), 'SSL'));
                }
                break;

            // -----
            // Issued by the zen_in_guest_checkout function, allowing an observer to note that
            // the store is "in-guest-checkout".
            //
            // On entry:
            //
            // $p1 ... n/a
            // $p2 ... (r/w) Value is set to boolean true/false to indicate the condition.
            //
            case 'NOTIFY_ZEN_IN_GUEST_CHECKOUT':
                $p2 = $_SESSION['opc']->isGuestCheckout();
                break;

            // -----
            // Issued by the zen_is_logged_in function, allowing an observer to note whether
            // a customer is currently logged into the store.
            //
            // The "Shipping Estimator", present on either the shopping_cart page or as the
            // popup_shipping_estimator page, requires some special handling for guests and
            // registered account-holders, since a pseudo-address-book entry is available,
            // but incomplete.
            //
            // Any requests for logged-in status on either of those two pages will indicate
            // a not-logged-in status for the above conditions.
            //
            // On entry:
            //
            // $p1 ... n/a
            // $p2 ... (r/w) Value is set to boolean true/false to indicate the condition.
            //
            case 'NOTIFY_ZEN_IS_LOGGED_IN':
                global $current_page_base;

                $is_logged_in = $_SESSION['opc']->isLoggedIn();
                if ($is_logged_in && isset($current_page_base)) {
                    if ($current_page_base === FILENAME_POPUP_SHIPPING_ESTIMATOR) {
                        $is_logged_in = !$_SESSION['opc']->isGuestCheckout() && !$_SESSION['opc']->customerAccountNeedsPrimaryAddress();
                    } elseif ($current_page_base === FILENAME_SHOPPING_CART && SHOW_SHIPPING_ESTIMATOR_BUTTON === '2') {
                        $calling_list = debug_backtrace();
                        $is_shipping_estimator = false;
                        foreach ($calling_list as $next_caller) {
                            if (strpos($next_caller['file'], 'tpl_modules_shipping_estimator.php') !== false) {
                                $is_shipping_estimator = true;
                                break;
                            }
                        }
                        if ($is_shipping_estimator) {
                            $is_logged_in = !$_SESSION['opc']->isGuestCheckout() && !$_SESSION['opc']->customerAccountNeedsPrimaryAddress();
                        }
                    }
                }
                $p2 = $is_logged_in;
                break;

            // -----
            // Issued by the zen_address_label function to format a customer's address.
            // Since this is called both by and outside of the OPC processing, the
            // shipping-address details will be replaced to correct various PHP Warnings
            // issued.
            //
            // OPC's getAddressLabelFields will return either (bool)false, if the supplied
            // address_book_id isn't one of its temporary addresses, or an array of address-related
            // fields to replace the information that zen_address_label has previously gathered.
            //
            // NOTE: Using array_merge to overwrite any base address_book fields gathered, but
            // preserving any additional fields potentially added by another observer.
            //
            // On entry:
            //
            // $p2 ... (r/w) The customers_id for which the address is being formatted.
            // $p3 ... (r/w) The address_book_id identifying the address to format
            // $p4 ... (r/w) An array of 'address_book' fields associated with the above customer's address.
            //
            case 'NOTIFY_ZEN_ADDRESS_LABEL':
                $address_fields = $_SESSION['opc']->getAddressLabelFields((int)$p3);
                if ($address_fields !== false) {
                    $p4 = array_merge($p4, $address_fields);
                }
                break;

            // -----
            // If the customer has just added an address, force that address to be the
            // primary if the customer currently has no permanent addresses.
            //
            case 'NOTIFY_HEADER_START_ADDRESS_BOOK_PROCESS':
                global $db;

                if (zen_is_logged_in()) {
                    if (isset($_POST['action']) && $_POST['action'] == 'process') {
                        $check = $db->Execute(
                            "SELECT address_book_id
                               FROM " . TABLE_ADDRESS_BOOK . "
                              WHERE customers_id = " . (int)$_SESSION['customer_id'] . "
                              LIMIT 1"
                        );
                        if ($check->EOF) {
                            $_POST['primary'] = 'on';
                        }
                    }
                }
                break;

            // -----
            // Issued by the order-class at the beginning of the order-creation process (i.e.
            // the cart contents are "converted" to an order.  Gives us the chance to see
            // if this is a guest-checkout and/or an order using a temporary address.
            //
            // If so, the address section(s) of the base order could be modified and the
            // order's tax-basis is re-determined.
            //
            // On entry:
            //
            // $p1 ... n/a
            // $p2 ... (r/w) A reference to the order's $taxCountryId value
            // $p3 ... (r/w) A reference to the order's $taxZoneId value
            //
            case 'NOTIFY_ORDER_CART_AFTER_ADDRESSES_SET':
                $_SESSION['opc']->updateOrderAddresses($class, $p2, $p3);
                break;

            // -----
            // Issued by the order-class just after creating a new order's "header",
            // i.e. the information in the orders table.  This gives us the opportunity
            // to note that the order was created via guest-checkout, if needed.
            //
            // If the order was placed via paypalwpp and a temporary shipping address
            // and the address returned by PayPal was different from that specified
            // during the order's data-gathering, the order's comments will be updated
            // to identify the pre-PayPal address and a message will be recorded in the
            // session for display on the 'checkout_success' page.
            //
            // On entry:
            //
            // $class ... (r/w) A reference to the current order's information.
            // $p1 ...... (r/o) A copy of the SQL data-array used to create the header.
            // $p2 ...... (r/w) A reference to the newly-created order's ID value.
            //
            case 'NOTIFY_ORDER_DURING_CREATE_ADDED_ORDER_HEADER':
                global $db;
                if (zen_in_guest_checkout()) {
                    $db->Execute(
                        "UPDATE " . TABLE_ORDERS . "
                            SET is_guest_order = 1
                          WHERE orders_id = " . (int)$p2 . "
                          LIMIT 1"
                    );
                }
                $_SESSION['opc']->identifyPayPalAddressChange($class);
                break;

            // -----
            // Issued at the very end of the checkout_process page's handling.  If the
            // order was placed by a guest, capture the order-number created to allow
            // the OPC's guest checkout_success processing to offer the guest the
            // opportunity to create an account using the information in the just-placed
            // order.
            //
            // These values remain in the session ... so long as the guest-customer
            // doesn't navigate off of the checkout_success page.  This class'
            // constructor will remove this (and other guest-related values) from
            // the session if the variable is set and the current page is other
            // than the checkout_success one.
            //
            // Unconditionally, reset the OPC's "common" session variables.
            //
            // On entry:
            //
            // $p1 ... (r/o) The just-created order's order_id.
            //
            case 'NOTIFY_CHECKOUT_PROCESS_BEFORE_CART_RESET':
                if (zen_in_guest_checkout()) {
                    $_SESSION['order_placed_by_guest'] = (int)$p1;
                }
                $_SESSION['opc']->resetSessionVariables();
                break;

            // -----
            // At the start of the checkout_success page, check to see if we're
            // at the tail-end of a guest-checkout.  If so, it's possible that the
            // customer is attempting to create an account from the information in
            // the just-placed order.
            //
            // If so, check to see if the page's processing has previously removed
            // the "normal" order-id from the session and restore that value so that
            // the base page-header processing will continue to properly gather the
            // information from that order.
            //
            case 'NOTIFY_HEADER_START_CHECKOUT_SUCCESS':
                if (zen_in_guest_checkout() && !isset($_SESSION['order_number_created'])) {
                    $_SESSION['order_number_created'] = $_SESSION['order_placed_by_guest'];
                }
                break;

            // -----
            // Issued by the order-class just prior to sending the order-confirmation email.
            //
            // If either the sendto or billto addresses are "temporary", we'll do some reconstruction.
            //
            // On entry:
            //
            // $p1 ... (r/o) An associative array; the order's order_id is in the zf_insert_id element.
            // $p2 ... (r/w) The current text email string.
            // $p3 ... (r/w) The current HTML email array.
            //
            case 'NOTIFY_ORDER_INVOICE_CONTENT_READY_TO_SEND':
                $temp_addresses = [
                    CHECKOUT_ONE_GUEST_SENDTO_ADDRESS_BOOK_ID,
                    CHECKOUT_ONE_GUEST_BILLTO_ADDRESS_BOOK_ID
                ];
                if (in_array($_SESSION['sendto'], $temp_addresses) || $_SESSION['billto'] == CHECKOUT_ONE_GUEST_BILLTO_ADDRESS_BOOK_ID) {
                    $order_id = (int)$p1['zf_insert_id'];
                    $email_text = $p2;
                    $html_msg = $p3;

                    // -----
                    // If the checkout is for a guest, change the "invoice" link to reference the 'order_status' page.
                    //
                    if (zen_in_guest_checkout()) {
                        $account_history_link = zen_href_link(FILENAME_ACCOUNT_HISTORY_INFO, "order_id=$order_id", 'SSL', false);
                        $account_history_link_text = EMAIL_TEXT_INVOICE_URL . ' ' . $account_history_link;
                        
                        $order_status_link = zen_href_link(FILENAME_ORDER_STATUS, '', 'SSL');
                        $order_status_link_text = EMAIL_TEXT_INVOICE_URL_GUEST . ' ' . $order_status_link;

                        $email_text = str_replace($account_history_link_text, $order_status_link_text, $email_text);
                        
                        $html_msg['INTRO_URL_TEXT'] = EMAIL_TEXT_INVOICE_URL_CLICK_GUEST;
                        $html_msg['INTRO_URL_VALUE'] = $order_status_link;
                    }

                    $p2 = $email_text;
                    $p3 = $html_msg;
                }
                break;

            // -----
            // Issued by the ot_coupon handling when determining if the uses_per_user defined in the active
            // coupon is restricted.  The main OPC controller will check to see if the uses "per email address"
            // is acceptable.
            //
            // On entry,
            //
            // $p1 ... (r/o) The result of a SQL query gathering information about the to-be-checked coupon.
            // $p2 ... (r/w) A reference to the (boolean) processing flag that indicates whether (true) or
            //               not (false) the coupon's use has been exceeded.
            //
            case 'NOTIFY_OT_COUPON_USES_PER_USER_CHECK':
                $p2 = $_SESSION['opc']->validateUsesPerUserCoupon($p1, $p2);
                break;

            // -----
            // Issued by paypalwpp::ec_step1 just before sending the customer up to PayPal for payment
            // fulfilment.  Gives us the opportunity to record any temporary shipping address into the
            // request and to save the order's current total in an OPC-class variable.
            //
            // On entry,
            //
            // $p1 ... n/a
            // $p2 ... (r/w) A reference to PayPal's current $options, possibly updated with any temporary address values.
            // $p3 ... (r/w) A reference to the current order-object
            // $p4 ... (r/w) A reference to the order's current totals array.
            //
            case 'NOTIFY_PAYMENT_PAYPALEC_BEFORE_SETEC':
                $p2 = array_merge($p2, $_SESSION['opc']->createPayPalTemporaryAddressInfo($p2, $p3));
                break;

            // -----
            // Issued by paypalwpp::ec_step2_finish just before its check/creation of an address-book
            // entry for the customer's address values sent back from PayPal.  Gives us the opportunity
            // to bypass that processing if the order currently is using temporary addresses.
            //
            // Note: Not in core for Zen Cart versions prior to 1.5.6b!
            //
            // $p1 ... (r/o) A copy of the PayPal payer/shipto-address information returned.
            // $p2 ... (r/w) A reference to a boolean flag that indicates whether or not the default processing should proceed.
            //
            case 'NOTIFY_PAYPALEXPRESS_BYPASS_ADDRESS_CREATION':
                if ($p2 !== false) {
                    $this->debug_message('NOTIFY_PAYPALEXPRESS_BYPASS_ADDRESS_CREATION previously handled!');
                } else {
                    $p2 = $_SESSION['opc']->setPayPalAddressCreationBypass($p1);
                }
                break;

            // -----
            // Issued by paypalwpp::before_process just prior to sending the final order record off
            // to PayPal for fulfilment.  If the session-based OPC class determines that a change
            // in the order's total was made after the customer's authorization of the order via
            // PayPal, we'll redirect back to the OPC data-gathering page to let the customer know.
            //
            case 'NOTIFY_PAYPALWPP_BEFORE_DOEXPRESSCHECKOUT':
                global $order, $messageStack;

                if ($_SESSION['opc']->didPayPalOrderTotalValueChange($order)) {
                    $this->debug_message('checkout_one redirect 4: ', true, 'checkout_one_observer');
                    $messageStack->add_session('checkout_shipping', WARNING_PAYPALWPP_TOTAL_CHANGED, 'caution');
                    zen_redirect(zen_href_link(FILENAME_CHECKOUT_ONE, zen_get_all_get_params(), 'SSL'));
                }
                break;

            // -----
            // Issued by payaplwpp::getOverrideAddress at the beginning of its address-override check.  This
            // gives OPC the chance to 'deny' that address-override during guest-checkout since there is no
            // valid address-book table entry for a guest customer.
            //
            // Note: Not in core for Zen Cart versions prior to 1.5.7a!
            //
            // $p1 ... (r/o) The current address_book_id that will be used, if not overridden.
            // $p2 ... (r/w) A reference to a boolean flag that indicates whether or not the default processing should proceed.
            //
            case 'NOTIFY_PAYPALWPP_DISABLE_GET_OVERRIDE_ADDRESS':
                $p2 = $_SESSION['opc']->isGuestCheckout();
                break;

            // -----
            // Issued by the zen_get_tax_locations function, allowing us to modify the taxed location
            // when temporary addresses are in effect for the current order.
            //
            // On entry:
            //
            // $p1 ... (r/o) An associative array, containing the 'country' and 'zone' values passed to that function.
            // $p2 ... (r/w) A reference to the $tax_address variable, initialized to (bool)false; set to an array containing
            //               the overriding 'country_id' and 'zone_id' if temporary addresses are in effect.
            //
            case 'ZEN_GET_TAX_LOCATIONS':
                // -----
                // If the tax locations are already overridden, log a PHP warning identifying the condition.  Note
                // that follow-on order processing **might result** in a PHP error!
                //
                if ($p2 !== false) {
                    trigger_error('zen_get_tax_locations, overridden by another observer; OPC processing bypassed.', E_USER_WARNING);
                } else {
                    $p2 = $_SESSION['opc']->getTaxLocations();
                }
                break;

            default:
                break;
        }
    }

    public function debug_message($message, $include_request = false, $other_caller = '')
    {
        if ($this->debug) {
            $extra_info = '';
            if ($include_request) {
                $the_request = $_REQUEST;
                foreach ($the_request as $name => $value) {
                    if (strpos($name, 'cc_number') !== false || strpos($name, 'cc_cvv') !== false || strpos($name, 'card-number') !== false || strpos($name, 'cv2-number') !== false) {
                        unset($the_request[$name]);
                    }
                }
                $extra_info = print_r($the_request, true) . "\n\n" . print_r($_SESSION, true);
            }

            // -----
            // Change any occurrences of [code] to ["code"] in the logs so that they can be properly posted between [CODE} tags on the Zen Cart forums.
            //
            $message = str_replace('[code]', '["code"]', $message);
            error_log(date('Y-m-d H:i:s') . ' ' . (($other_caller != '') ? $other_caller : $this->current_page_base) . ": $message$extra_info" . PHP_EOL, 3, $this->debug_logfile);
            $this->notify($message);
        }
    }

    public function hashSession($current_order_total)
    {
        $session_data = $_SESSION;
        if (isset($session_data['shipping'])) {
           unset($session_data['shipping']['extras']);
        }
        unset($session_data['shipping_billing'], $session_data['comments'], $session_data['navigation']);

        // -----
        // The ot_gv order-total in Zen Cart 1.5.4 sets its session-variable to either 0 or '0.00', which results in
        // false change-detection by this function.  As such, if the order-total's variable is present in the session
        // and is 0, set the variable to a "known" representation of 0!
        //
        if (isset($session_data['cot_gv']) && $session_data['cot_gv'] == 0) {
            $session_data['cot_gv'] = '0.00';
        }

        // -----
        // Some of the payment methods (e.g. ceon_manual_card and ceon_sage_pay_direct) and possibly shipping/order_totals update
        // information into the session upon their processing ... and ultimately cause the hash on entry
        // to be different from the hash on exit.  Simply update the following list with the variables that
        // can be safely ignored in the hash.
        //
        unset(
            $session_data['ceon_manual_card_card_holder'],
            $session_data['ceon_manual_card_card_type'],
            $session_data['ceon_manual_card_card_expiry_month'],
            $session_data['ceon_manual_card_card_expiry_year'],
            $session_data['ceon_manual_card_card_cv2_number_not_present'],
            $session_data['ceon_manual_card_card_start_month'],
            $session_data['ceon_manual_card_card_start_year'],
            $session_data['ceon_manual_card_card_issue_number'],
            $session_data['ceon_manual_card_data_entered'],
            $session_data['ceon_sage_pay_direct_card_holder'],
            $session_data['ceon_sage_pay_direct_card_type'],
            $session_data['ceon_sage_pay_direct_card_expiry_month'],
            $session_data['ceon_sage_pay_direct_card_expiry_year'],
            $session_data['ceon_sage_pay_direct_card_start_month'] ,
            $session_data['ceon_sage_pay_direct_card_start_year'] ,
            $session_data['ceon_sage_pay_direct_card_issue_number'],
            $session_data['ceon_sage_pay_direct_data_entered'],
            $session_data['ceon_sage_pay_direct_error_encountered'],
            $session_data['ceon_sage_pay_direct_debug_file']
        );

        // -----
        // Add the order's current total to the blob that's being hashed, so that changes in the total based on
        // payment-module selection can be properly detected (e.g. COD fee).
        //
        // Some currencies use a non-ASCII symbol for its symbol, e.g. £.  To ensure that we don't get into
        // a checkout-loop, make sure that the order's current total is scrubbed to convert any "HTML entities"
        // into their character representation.
        //
        // This is needed since the order's current total, as passed into the confirmation page, is created by
        // javascript that captures the character representation of any symbols.
        //
        // Note: Some templates also include carriage-returns within the total's display, so remove them from
        // the mix, too!
        //
        $current_order_total = str_replace(["\n", "\r"], '', $current_order_total);
        $session_data['order_current_total'] = html_entity_decode($current_order_total, ENT_COMPAT, CHARSET);

        // -----
        // The order's payment-method (e.g. moneyorder) might not be present in the session after the
        // order-confirmation pre-checks.  Specifically, if a credit-class order-totals 'credit' covers
        // the full order payment (e.g. a 100%+free-shipping coupon), the payment-method is removed from
        // the session ... after the first session-hash is computed.
        //
        // Since we're looking for a **monetary** change in the session values, that payment-method will be
        // disregarded.
        //
        unset($session_data['payment']);

        // -----
        // Give a watching observer the opportunity to provide fix-ups over-and-above the prior processing.
        //
        $saved_session_data = $session_data;
        $this->notify('NOTIFY_OPC_OBSERVER_SESSION_FIXUPS', '', $session_data);
        if ($session_data !== $saved_session_data) {
            $this->debug_message("hashSession, observer override: " . PHP_EOL . json_encode($session_data) . PHP_EOL . json_encode($saved_session_data));
        }

        $hash_values = print_r($session_data, true);
        $this->debug_message("hashSession returning an md5 of $hash_values", false, 'checkout_one_observer');
        return md5($hash_values);
    }

    public function isEnabled()
    {
        return $this->enabled;
    }
}
