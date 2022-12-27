<?php
// -----
// Part of the One-Page Checkout plugin, provided under GPL 2.0 license by lat9 (cindy@vinosdefrutastropicales.com).
// Copyright (C) 2017-2022, Vinos de Frutas Tropicales.  All rights reserved.
//
// This class, instantiated in the current customer session, keeps track of a customer's login and checkout
// progression with the aid of the OPC's observer- and AJAX-classes.
//
// Last updated: OPC v2.4.5.
//
class OnePageCheckout extends base
{
    // -----
    // Constants used to coordinate definitions for the OPC's $_SESSION['opc_error'] variable between this
    // controlling class and its associated observer.
    //
    const OPC_ERROR_NO_JS   = 'jserr';  //-jQuery/javascript error detected
    const OPC_ERROR_NO_GC   = 'no-gc';  //-Guest checkout can't be used due to a Gift Certificate being present in the cart.

    // -----
    // Various protected data elements:
    //
    // isGuestCheckoutEnabled ... Indicates whether (true) or not (false) the overall guest-checkout is enabled via configuration.
    // registeredAccounts ....... Indicates whether (true) or not (false) "registered" accounts are enabled via configuration.
    // isEnabled ................ Essentially follows the enable-value of the OPC observer.
    // guestIsActive ............ Indicates whether (true) or not (false) we're currently handling a guest-checkout
    // tempAddressValues ........ Array, if set, contains any temporary addresses used within the checkout process.
    // guestCustomerInfo ........ Array, if set, contains the customer-specific (i.e. email, phone, etc.) information for a guest customer.
    // guestCustomerId .......... Contains a sanitized/int version of the configured "guest" customer ID.
    // reset_info ............... A backtrace array to be used in debug, not currently used.
    // tempBilltoAddressBookId .. Contains a sanitized/int version of the configured "temporary" bill-to address-book ID.
    // tempSendtoAddressBookId .. Contains a sanitized/int version of the configured "temporary" ship-to address-book ID.
    // dbStringType ............. Identifies the form of string data "binding" to use on $db requests; 'string' for ZC < 1.5.5b, 'stringIgnoreNull', otherwise.
    // label_params ............. Contains, if set, an override for the label parameters used by the 'formatAddressElement' method.
    // sendtoSaved .............. If set, a 'sendto' address saved during the shipping-estimator's processing so that guests don't have to re-enter everything!
    //
    // paypalAddressOverride .... Contains, if set, the formatted version of any temporary shipping address, as entered.  Used when
    //                            paypalwpp's processing returns a shipping address different from that entered.
    // paypalTotalValue ......... Contains, for a paypalwpp-placed order, the order's total value initially sent to PayPal.  Used to
    //                            compare the total value based on the ship-to address returned by PayPal.
    // paypalTotalValueChanged .. Set, for a paypalwpp-placed order, if the order's total value after the return from PayPal
    //                            is different from its initial value.
    // paypalNoShipping ......... Set, for a paypalwpp-placed order, to the value sent to PayPal that indicates that no shipping
    //                            address is required, i.e. a virtual order.
    //
    // Some processing flags, identifying whether/not various "temporary" values have been validated.
    //
    // customerInfoOk ........... Identifies whether/not the guest-checkout 'customer information' has been validated.
    // billtoTempAddrOk ......... Identifies that entries in the temporary bill-to address have been validated.
    // sendtoTempAddrOk ......... Identifies that entries in the temporary send-to address have been validated.
    //
    // Values set at the end of the checkout_one page's header_php.php file's processing to capture processing flags for
    // use by the OPC's AJAX component.
    //
    // isVirtualOrder ........... Identifies whether or not the current order is "virtual" (i.e. no shipping address required).
    // billtoAddressChangeable .. Identifies whether (true) or not (false) the payment address can be changed.
    // sendtoAddressChangeable .. Identifies whether (true) or not (false) the shipping address can be changed.
    //
    protected
        $isGuestCheckoutEnabled,
        $registeredAccounts,
        $isEnabled,
        $guestIsActive,
        $tempAddressValues,
        $guestCustomerInfo,
        $guestCustomerId,
        $reset_info,
        $tempBilltoAddressBookId,
        $tempSendtoAddressBookId,
        $dbStringType,
        $label_params,
        $sendtoSaved,

        $paypalAddressOverride,
        $paypalTotalValue,
        $paypalTotalValueChanged,
        $paypalNoShipping,

        $customerInfoOk,
        $billtoTempAddrOk,
        $sendtoTempAddrOk,

        $isVirtualOrder,
        $billtoAddressChangeable,
        $sendtoAddressChangeable;

    public function __construct()
    {
        $this->isEnabled = false;
        $this->guestIsActive = false;
        $this->isGuestCheckoutEnabled = false;
        $this->registeredAccounts = false;
        $this->reset_info = [];
    }

    /* -----
    ** This function, called by the OPC's observer-class, provides the common-use debug filename.
    */
    public function getDebugLogFileName()
    {
        $customer_id = (isset($_SESSION['customer_id'])) ? $_SESSION['customer_id'] : 'na';
        return DIR_FS_LOGS . "/one_page_checkout-$customer_id-" . date('Y-m-d') . ".log";
    }

    /* -----
    ** This function returns whether (true) or not (false) the overall OPC functionality
    ** is enabled.
    */
    public function checkEnabled()
    {
        // -----
        // Determine whether the overall OPC processing should be enabled.  It's enabled if:
        //
        // - No previous error (missing jQuery) found to prevent OPC's use.
        // - The plugin's database configuration is available and set for either
        //   - Full enablement
        //   - Conditional enablement and the current customer is in the conditional-customers list
        //
        // Note: If we're currently in the PayPal Express Checkout's "Express Checkout" 
        // (aka in_special_checkout) processing, OPC will (currently) be disabled.
        //
        $this->isEnabled = false;
        if (defined('CHECKOUT_ONE_ENABLED') && (!isset($_SESSION['opc_error']) || $_SESSION['opc_error'] != self::OPC_ERROR_NO_JS)) {
            if (CHECKOUT_ONE_ENABLED === 'true') {
                $this->isEnabled = true;
            } elseif (CHECKOUT_ONE_ENABLED === 'conditional' && isset($_SESSION['customer_id'])) {
                if (in_array($_SESSION['customer_id'], explode(',', str_replace(' ', '', CHECKOUT_ONE_ENABLE_CUSTOMERS_LIST)))) {
                    $this->isEnabled = true;
                }
            }

            // -----
            // Perform some OPC-session cleanup if, after starting a paypalwpp-paid order, the
            // customer decides to change payment methods.
            //
            if (!empty($_SESSION['payment']) && $_SESSION['payment'] != 'paypalwpp') {
                unset(
                    $this->paypalAddressOverride,
                    $this->paypalTotalValue,
                    $this->paypalTotalValueChanged,
                    $this->paypalNoShipping
                ); 
            }

            if (empty($this->paypalTotalValueChanged) && $this->isPayPalExpressCheckout()) {
                $this->isEnabled = false;
            }
        }
        return $this->isEnabled;
    }

    // -----
    // Determine whether we're currently in the PayPal Express Checkout's "Express Checkout"
    // handling, using the detection currently (zc156a) present in the paypalwpp::in_special_checkout's
    // processing.
    //
    protected function isPayPalExpressCheckout()
    {
        global $current_page_base;
        $is_paypal_express_checkout = false;
        if ($current_page_base !== FILENAME_CHECKOUT_PROCESS && defined('MODULE_PAYMENT_PAYPALWPP_STATUS') && MODULE_PAYMENT_PAYPALWPP_STATUS === 'True') {
            if (isset($_SESSION['customer_guest_id']) || (!empty($_SESSION['paypal_ec_token']) && !empty($_SESSION['paypal_ec_payer_id']) && !empty($_SESSION['paypal_ec_payer_info']))) {
                $this->debugMessage("PayPal Express Checkout, in special checkout.  One Page Checkout is disabled.");
                $is_paypal_express_checkout = true;
            }
        }
        return $is_paypal_express_checkout;
    }

    /* -----
    ** This function returns a boolean indication as to whether (true) or not (false) OPC's
    ** guest-checkout is currently enabled.
    */
    public function guestCheckoutEnabled()
    {
        $this->initializeGuestCheckout();
        return ($this->isEnabled && $this->isGuestCheckoutEnabled);
    }

    /* -----
    ** This function returns a boolean indication as to whether (true) or not (false) the order
    ** is currently being processed with the shipping address, same as billing.
    */    
    public function getShippingBilling()
    {
        $_SESSION['shipping_billing'] = (isset($_SESSION['shipping_billing'])) ? $_SESSION['shipping_billing'] : (CHECKOUT_ONE_ENABLE_SHIPPING_BILLING === 'true');
        return $_SESSION['shipping_billing'];
    }

    /* -----
    ** This function, present in OPC's observer-class prior to v2.1.0, returns a boolean value
    ** that indicates whether or not the current order qualities for free shipping, as identified
    ** within the ot_shipping module's configuration.
    **
    ** The value supplied ($country_override) is a 'mixed' value:
    **
    ** (bool)false ... Free shipping, determined by the country currently recorded in the order's delivery address.
    ** otherwise ..... Free shipping, determined by the country associated with the specified address-book table id.
    */
    public function isOrderFreeShipping($country_override = false)
    {
        global $order, $db;
        
        $free_shipping = false;
        $address_book_id = -1;
        $order_country = -1;
        $pass = $this->isVirtualOrder();
        if (!$pass && defined('MODULE_ORDER_TOTAL_SHIPPING_FREE_SHIPPING') && MODULE_ORDER_TOTAL_SHIPPING_FREE_SHIPPING === 'true') {
            if ($country_override === false) {
                $order_country = $order->delivery['country_id'];
            } else {
                $address_book_id = (!empty($_SESSION['sendto'])) ? ((int)$_SESSION['sendto']) : false;
                if ($address_book_id === false) {
                    $order_country = false;
                } else {
                    if ($address_book_id == $this->tempSendtoAddressBookId) {
                        $order_country = $this->tempAddressValues['ship']['country'];
                    } elseif ($address_book_id == $this->tempBilltoAddressBookId) {
                        $order_country = $this->tempAddressValues['bill']['country'];
                    } else {
                        $country_check = $db->Execute(
                            "SELECT entry_country_id 
                               FROM " . TABLE_ADDRESS_BOOK . " 
                              WHERE address_book_id = " . (int)$_SESSION['sendto'] . " 
                              LIMIT 1"
                        );
                        $order_country = ($country_check->EOF) ? false : $country_check->fields['entry_country_id'];
                    }
                }
            }
            if ($order_country !== false) {
                switch (MODULE_ORDER_TOTAL_SHIPPING_DESTINATION) {
                    case 'national':
                        if ($order_country === STORE_COUNTRY) {
                            $pass = true;
                        }
                        break;

                    case 'international':
                        if ($order_country !== STORE_COUNTRY) {
                            $pass = true;
                        }
                        break;

                    case 'both':
                        $pass = true;
                        break;
                }
            }

            if ($pass && $_SESSION['cart']->show_total() >= MODULE_ORDER_TOTAL_SHIPPING_FREE_SHIPPING_OVER) {
                $free_shipping = true;
            }
        }
        $this->debugMessage("isOrderFreeShipping($country_override), address_book_id = $address_book_id, order_country = $order_country, returning ($free_shipping).");
        return $free_shipping;
    }

    /* -----
    ** Issued by the OPC's observer-class to determine whether order-related notifications need
    ** to be monitored.
    **
    ** Note: This method (currently) returns a positive indication that those notifications should
    ** always be monitored.
    */
    public function initTemporaryAddresses()
    {
        $this->initializeGuestCheckout();
        return true;
    }

    /* -----
    ** This function returns a boolean indication as to whether (true) or not (false) OPC's
    ** account-registration is enabled.  This is used by the OPC's observer class to determine
    ** whether accesses to the 'create_account' page should be redirected to the 'register' page.
    */
    public function accountRegistrationEnabled()
    {
        return $this->isEnabled && $this->registeredAccounts;
    }

    /* -----
    ** This function, called at the end of the checkout_one page's header processing, captures
    ** some processing flags to be used by subsequent calls by the OPC's AJAX processing.
    */
    public function saveCheckoutProcessingFlags($is_virtual_order, $flagDisablePaymentAddressChange, $editShippingButtonLink)
    {
        $this->isVirtualOrder = $is_virtual_order;
        $this->billtoAddressChangeable = !$flagDisablePaymentAddressChange;
        $this->sendtoAddressChangeable = $editShippingButtonLink;
    }

    /* -----
    ** These functions, used by the OPC's AJAX class, retrieve those values as saved above.
    */
    public function isVirtualOrder()
    {
        return $this->isVirtualOrder;
    }
    public function isBilltoAddressChangeable()
    {
        return $this->billtoAddressChangeable;
    }
    public function isSendtoAddressChangeable()
    {
        return $this->sendtoAddressChangeable;
    }

    /* -----
    ** This function returns a boolean indication as to whether (true) or not (false) the 
    ** currently-logged-in customer has registered (i.e. no primary address yet provided) or
    ** created a fully-fledged account.
    */
    public function customerAccountNeedsPrimaryAddress()
    {
        global $db;

        $account_needs_primary_address = true;
        $addresses_query = 
            "SELECT address_book_id, entry_firstname as firstname, entry_lastname as lastname,
                    entry_company as company, entry_street_address as street_address,
                    entry_suburb as suburb, entry_city as city, entry_postcode as postcode,
                    entry_state as state, entry_zone_id as zone_id, entry_country_id as country_id
               FROM " . TABLE_ADDRESS_BOOK . "
              WHERE customers_id = :customersID
                AND address_book_id = :addressBookID
              LIMIT 1";

        $addresses_query = $db->bindVars($addresses_query, ':customersID', $_SESSION['customer_id'], 'integer');
        $addresses_query = $db->bindVars($addresses_query, ':addressBookID', $_SESSION['customer_default_address_id'], 'integer');
        $default_address = $db->Execute($addresses_query);
        
        if (!$default_address->EOF) {
            if (strlen($default_address->fields['street_address']) >= (int)ENTRY_STREET_ADDRESS_MIN_LENGTH ||
                strlen($default_address->fields['city']) >= (int)ENTRY_CITY_MIN_LENGTH ||
                strlen($default_address->fields['postcode']) >= (int)ENTRY_POSTCODE_MIN_LENGTH) {
                $account_needs_primary_address = false;
            }
        }

        return $account_needs_primary_address;
    }

    /* -----
    ** This function returns a boolean indication as to whether (true) or not (false) OPC's
    ** "temporary addresses" (used for guest-checkout and registered-accounts) is currently
    ** enabled.
    */       
    public function temporaryAddressesEnabled()
    {
        $this->initializeGuestCheckout();
        return ($this->isEnabled && ($this->isGuestCheckoutEnabled || $this->registeredAccounts));
    }

    // -----
    // This internal function initializes the class variables associated with guest-checkout and account-registration.
    //
    protected function initializeGuestCheckout()
    {
        global $current_page_base;

        $this->checkEnabled();

        // -----
        // Give a watching observer the opportunity to disable the guest checkout, e.g. if an unwanted IP address is making the access.
        //
        $allow_guest_checkout = true;
        $this->notify('NOTIFY_OPC_GUEST_CHECKOUT_OVERRIDE', '', $allow_guest_checkout);
        if ($allow_guest_checkout !== true) {
            $this->debugMessage("Guest checkout disabled via observer.");
        }

        $this->isGuestCheckoutEnabled = $allow_guest_checkout === true && !zen_is_spider_session() && (defined('CHECKOUT_ONE_ENABLE_GUEST') && CHECKOUT_ONE_ENABLE_GUEST === 'true');
        if (isset($_SESSION['opc_error']) && ($_SESSION['opc_error'] === self::OPC_ERROR_NO_GC || $_SESSION['opc_error'] === self::OPC_ERROR_NO_JS)) {
            if ($_SESSION['opc_error'] === self::OPC_ERROR_NO_JS || in_array($current_page_base, [FILENAME_LOGIN, FILENAME_CHECKOUT_ONE, FILENAME_CHECKOUT_ONE_CONFIRMATION])) {
                $this->isGuestCheckoutEnabled = false;
            }
        }
        $this->guestCustomerId = (defined('CHECKOUT_ONE_GUEST_CUSTOMER_ID')) ? (int)CHECKOUT_ONE_GUEST_CUSTOMER_ID : 0;
        $this->tempBilltoAddressBookId = (defined('CHECKOUT_ONE_GUEST_BILLTO_ADDRESS_BOOK_ID')) ? (int)CHECKOUT_ONE_GUEST_BILLTO_ADDRESS_BOOK_ID : 0;
        $this->tempSendtoAddressBookId = (defined('CHECKOUT_ONE_GUEST_SENDTO_ADDRESS_BOOK_ID')) ? (int)CHECKOUT_ONE_GUEST_SENDTO_ADDRESS_BOOK_ID : 0;
        $this->registeredAccounts = (defined('CHECKOUT_ONE_ENABLE_REGISTERED_ACCOUNTS') && CHECKOUT_ONE_ENABLE_REGISTERED_ACCOUNTS === 'true');

        // -----
        // The 'stringIgnoreNull' type of database "bind" type was introduced in ZC1.5.5b; if the store
        // is using an earlier version of Zen Cart, we'll log a debug message and use the 'string'
        // type instead.
        //
        $zc_version = PROJECT_VERSION_MAJOR . '.' . PROJECT_VERSION_MINOR;
        $this->dbStringType = 'stringIgnoreNull';
        if ($zc_version < '1.5.5b') {
            $this->dbStringType = 'string';
            $this->debugMessage("Using 'string' database types instead of 'stringIgnoreNull' for Zen Cart $zc_version.");
        }
    }

    /* -----
    ** This function returns the current value to be used when setting string-type values into the database.
    */
    public function getDbStringType()
    {
        return $this->dbStringType;
    }

    /* -----
    ** This function returns a boolean indication as to whether (true) or not (false) the current
    ** session is in "guest-checkout" mode.
    **
    ** OPC's observer-class causes this function's return value to be returned by call to the
    ** zen_in_guest_checkout() function.
    */      
    public function isGuestCheckout()
    {
        return (isset($_SESSION['is_guest_checkout']));
    }

    /* -----
    ** This function returns a boolean indication as to whether (true) or not (false) a customer is
    ** logged in for the current session.
    **
    ** OPC's observer-class causes this function's return value to be returned by call to the
    ** zen_is_logged_in() function.
    */       
    public function isLoggedIn()
    {
        return (!empty($_SESSION['customer_id']));
    }

    /* -----
    ** This function resets the guest-related information stored in the current session,
    ** essentially restoring the session to a non-guest-checkout scenario.
    */           
    public function resetGuestSessionValues()
    {
        if (zen_in_guest_checkout() || (!empty($_SESSION['customer_id']) && $_SESSION['customer_id'] == $this->guestCustomerId)) {
            unset(
                $_SESSION['customer_id'], 
                $_SESSION['customers_email_address'],
                $_SESSION['customers_authorization'],
                $_SESSION['customer_first_name'],
                $_SESSION['customer_last_name'],
                $_SESSION['sendto'], 
                $_SESSION['billto'],
                $_SESSION['customer_default_address_id'],
                $_SESSION['customer_country_id'],
                $_SESSION['customer_zone_id'],
                $_SESSION['shipping'],
                $_SESSION['payment'],
                $_SESSION['shipping_tax_description'],
                $_SESSION['shipping_tax_amount']
            );
        }
        unset(
            $_SESSION['is_guest_checkout'],
            $_SESSION['order_placed_by_guest']
        );
        $this->resetSessionVariables();
        $this->reset();
    }

    /* -----
    ** This function resets the common (guest and account) session variables added to
    ** the session for the One Page Checkout's processing.
    */
    public function resetSessionVariables()
    {
        unset(
            $_SESSION['shipping_billing']
        );
        if (isset($_SESSION['opc_error']) && $_SESSION['opc_error'] != self::OPC_ERROR_NO_JS) {
            unset($_SESSION['opc_error']);
        }
    }

    // -----
    // This internal function resets the class' variables to their non-guest-checkout state.
    //
    protected function reset()
    {
        $this->isEnabled = false;
        $this->guestIsActive = false;
        $this->isGuestCheckoutEnabled = false;
        $this->registeredAccounts = false;
        unset(
            $this->tempAddressValues, 
            $this->guestCustomerInfo, 
            $this->sendtoSaved,
            $this->paypalAddressOverride,
            $this->paypalTotalValue,
            $this->paypalTotalValueChanged,
            $this->paypalNoShipping
        ); 

        // -----
        // Gather some information about 'who' requested the reset, included when it's
        // detected that the tempAddressValues are missing.
        //
        $this->reset_info[] = debug_backtrace();

        $this->initializeGuestCheckout();
    }

    /* -----
    ** Issued by OPC's observer class when entry to the shopping_cart page is detected.  Since the
    ** shipping-estimator (present on that page) might make modifications to the order's $_SESSION['sendto'],
    ** we'll record the OPC-set value for use on the next re-entry to the OPC process.
    */
    public function saveOrdersSendtoAddress()
    {
        if (isset($this->tempAddressValues) && !isset($this->sendtoSaved) && !empty($_SESSION['sendto'])) {
            $this->sendtoSaved = $_SESSION['sendto'];
        }
    }

    /* -----
    ** Issued (currently) by the checkout_one page's header processing to initialize any guest-checkout
    ** processing.  Since the process starts by the login page's form-submittal with the 'guest_checkout'
    ** input set, we need to recognize that condition and perform a POST-less redirect back to the
    ** page after recording the guest-related settings into the session.
    **
    ** Without this redirect, the checkout-one page's form is interpreted by the browser as having an
    ** unprocessed POST value and results in an unwanted browser message when the guest updates his/her
    ** contact information and/or addresses.
    */
    public function startGuestOnePageCheckout()
    {
        global $current_page_base;

        $this->guestIsActive = false;
        $redirect_required = false;

        // -----
        // If the order's sendto address was previously saved, restore that value and reset its clone.
        //
        if (isset($this->sendtoSaved)) {
            $_SESSION['sendto'] = $this->sendtoSaved;
            unset($this->sendtoSaved);
        }

        if ($this->guestCheckoutEnabled()) {
            $redirect_required = ($current_page_base === FILENAME_CHECKOUT_ONE && isset($_POST['guest_checkout']));
            if ($this->isGuestCheckout() || $redirect_required) {
                $this->guestIsActive = true;
                if (!isset($this->guestCustomerInfo)) {
                    $this->customerInfoOk = false;
                    $this->billtoTempAddrOk = false;
                    $this->sendtoTempAddrOk = false;
                    $this->guestCustomerInfo = [
                        'firstname' => '',
                        'lastname' => '',
                        'email_address' => '',
                        'telephone' => '',
                        'dob' => '',
                        'gender' => '',
                    ];

                    // -----
                    // Allow an observer to add fields to the guest-customer's record.
                    //
                    $additional_guest_fields = [];
                    $this->notify('NOTIFY_OPC_GUEST_CUSTOMER_INFO_INIT', '', $additional_guest_fields);
                    if (is_array($additional_guest_fields) && count($additional_guest_fields) != 0) {
                        $this->debugMessage('startGuestOnePageCheckout, added fields to customer-info: ' . json_encode($additional_guest_fields));
                        $this->guestCustomerInfo = array_merge($this->guestCustomerInfo, $additional_guest_fields);
                    }
                }
            }
        }
        $this->initializeTempAddressValues();
        if ($this->guestIsActive) {
            $_SESSION['is_guest_checkout'] = true;
            $_SESSION['customer_id'] = $this->guestCustomerId;
            $_SESSION['customer_default_address_id'] = $this->tempBilltoAddressBookId;
            $_SESSION['customer_country_id'] = $this->tempAddressValues['bill']['country'];
            $_SESSION['customer_zone_id'] = $this->tempAddressValues['bill']['zone_id'];
            $_SESSION['customers_authorization'] = 0;
        } else {
            unset($_SESSION['is_guest_checkout']);
        }

        $current_settings = print_r($this, true);
        $this->debugMessage('startGuestOnePageCheckout, exit: sendto: ' . ((isset($_SESSION['sendto'])) ? $_SESSION['sendto'] : 'not set') . ', billto: ' . ((isset($_SESSION['billto'])) ? $_SESSION['billto'] : 'not set') . PHP_EOL . $current_settings);

        if ($redirect_required) {
            zen_redirect(zen_href_link(FILENAME_CHECKOUT_ONE, '', 'SSL'));
        }
        return $this->isGuestCheckout();
    }

    /* -----
    ** This function, called upon successful login or account-creation, removes any remnants
    ** of a previously-started guest-checkout from the customer's current session.
    */
    public function cleanupGuestSession()
    {
        if ($this->guestIsActive) {
            unset(
                $_SESSION['is_guest_checkout'],
                $_SESSION['shipping_billing'], 
                $_SESSION['billto'],
                $_SESSION['sendto']
            );
            $this->reset();
        }
    }

    /* -----
    ** This function, called by the guest customer-information block's formatting, returns the
    ** guest's currently-set email address.
    */
    public function getGuestEmailAddress()
    {
        if (!isset($this->guestCustomerInfo)) {
            trigger_error("Guest customer-info not set during guest checkout.", E_USER_ERROR);
            exit();
        }
        return $this->guestCustomerInfo['email_address'];
    }

    /* -----
    ** This function, called by the guest customer-information block's formatting, returns the
    ** guest's currently-set telephone number.
    */
    public function getGuestTelephone()
    {
        if (!isset($this->guestCustomerInfo)) {
            trigger_error("Guest customer-info not set during guest checkout.", E_USER_ERROR);
            exit();
        }
        return $this->guestCustomerInfo['telephone'];
    }

    /* -----
    ** This function, called by the guest customer-information block's formatting, returns the
    ** guest's currently-set date-of-birth.
    */
    public function getGuestDateOfBirth()
    {
        if (!isset($this->guestCustomerInfo)) {
            trigger_error("Guest customer-info not set during guest checkout.", E_USER_ERROR);
            exit();
        }
        return (isset($this->guestCustomerInfo['dob_display'])) ? $this->guestCustomerInfo['dob_display'] : '';
    }

    /* -----
    ** This function, called by tpl_modules_opc_credit_selections.php, returns a boolean
    ** indication as to whether (true) or not (false) the specified order-total is
    ** "authorized" for use.  If guest-checkout is active, some order-total modules might
    ** require disablement.
    **
    ** Those values are controlled via Configuration->One-Page Checkout Settings.
    */
    public function enableCreditSelection($ot_name)
    {
        return !($this->isGuestCheckout() && in_array($ot_name, explode(',', str_replace(' ', '', CHECKOUT_ONE_ORDER_TOTALS_DISALLOWED_FOR_GUEST))));
    }

    /* -----
    ** This function, called from the OPC's observer class when the ot_coupon handling
    ** needs to know if a limited uses-per-user coupon should be honored, checks through
    ** past orders to see if the coupon has been used by the current email address when
    ** the checkout is running for a guest customer.
    */
    public function validateUsesPerUserCoupon($coupon_info, $coupon_uses_per_user_exceeded)
    {
        global $db;

        $uses_per_user = (int)$coupon_info['uses_per_user'];
        if ($uses_per_user > 0) {
            if ($this->isGuestCheckout() && isset($this->guestCustomerInfo) && !empty($this->guestCustomerInfo['email_address'])) {
                $coupon_id = (int)$coupon_info['coupon_id'];
                $email_address = zen_db_input($this->guestCustomerInfo['email_address']);
                $uses_per_user++;  //- This value now contains one more than the allowed number of uses!

                // -----
                // Check the orders that have been granted use of the coupon for those using the
                // current guest's email-address, stopping the search once we know that the number
                // of coupon-uses per user has been exceeded.
                //
                $check = $db->Execute(
                    "SELECT crt.coupon_id
                       FROM " . TABLE_ORDERS . " o
                            INNER JOIN " . TABLE_COUPON_REDEEM_TRACK . " crt
                                ON crt.coupon_id = $coupon_id
                               AND crt.order_id = o.orders_id
                      WHERE o.customers_email_address = '$email_address'
                      LIMIT $uses_per_user"
                );
                if ($check->recordCount() == $uses_per_user) {
                    $coupon_uses_per_user_exceeded = true;
                }
            }
        }
        return $coupon_uses_per_user_exceeded;
    }

    /* -----
    ** This function, called by the checkout_one page's header processing, determines if any
    ** of the currently-enabled payment methods should be disabled due to guest-checkout
    ** restraints configured by the current store.
    */
    public function validateGuestPaymentMethods($enabled_payment_modules)
    {
        if (!is_array($enabled_payment_modules)) {
            $enabled_payment_modules = [];
        }
        
        if ($this->isGuestCheckout()) {
            $disallowed_payment_methods = explode(',', str_replace(' ', '', CHECKOUT_ONE_PAYMENTS_DISALLOWED_FOR_GUEST));
            if (count($disallowed_payment_methods) > 0) {
                for ($i = 0, $n = count($enabled_payment_modules); $i < $n; $i++) {
                    if (in_array($enabled_payment_modules[$i]['id'], $disallowed_payment_methods)) {
                        if (isset($_SESSION['payment']) && $_SESSION['payment'] == $enabled_payment_modules[$i]['id']) {
                            unset($_SESSION['payment']);
                        }
                        unset($enabled_payment_modules[$i]);
                    }
                }
            }
        }

        return $enabled_payment_modules;
    }

    /* -----
    ** This function, called by the billing/shipping address blocks' formatting, instructs
    ** the module whether (true) or not (false) to include the checkbox to add the updated
    ** address.
    **
    ** The field is displayed only during an account-holder checkout, where that customer does
    ** not (yet) have the maximum number of address-book entries.
    */
    public function showAddAddressField()
    {
        global $db;

        $show_add_address = false;
        if (!zen_in_guest_checkout() && !empty($_SESSION['customer_id']) && !$this->customerAccountNeedsPrimaryAddress()) {
            $check = $db->Execute(
                "SELECT COUNT(*) as count
                   FROM " . TABLE_ADDRESS_BOOK . "
                  WHERE customers_id = " . (int)$_SESSION['customer_id']
            );
            if ($check->fields['count'] < (int)MAX_ADDRESS_BOOK_ENTRIES) {
                $show_add_address = true;
            }
        }
        return $show_add_address;
    }

    /* -----
    ** This function, called from the OPC's observer-class, provides any address/tax-basis
    ** update when an order includes one or more temporary addresses (a subset of guest
    ** checkout).
    */
    public function updateOrderAddresses($order, &$taxCountryId, &$taxZoneId)
    {
        $current_settings = print_r($this, true);
        $this->debugMessage("updateOrderAddresses, on entry:" . var_export($order, true) . PHP_EOL . $current_settings);
        $this->debugMessage("Current sendto: " . ((isset($_SESSION['sendto'])) ? $_SESSION['sendto'] : 'not set'));
        if (zen_in_guest_checkout()) {
            $address = (array)$order->customer;
            $order->customer = array_merge($address, $this->createOrderAddressFromTemporary('bill'), $this->getGuestCustomerInfo());
        }

        $temp_billing_address = $temp_shipping_address = false;
        if (isset($_SESSION['sendto']) && ($_SESSION['sendto'] == $this->tempSendtoAddressBookId || $_SESSION['sendto'] == $this->tempBilltoAddressBookId)) {
            $temp_shipping_address = true;
            if ($_SESSION['sendto'] == $this->tempSendtoAddressBookId) {
                $address = (array)$order->delivery;
                $which = 'ship';
            } else {
                $address = (array)$order->billing;
                $which = 'bill';
            }
            $order->delivery = array_merge($address, $this->createOrderAddressFromTemporary($which));
        }
        if (isset($_SESSION['billto']) && $_SESSION['billto'] == $this->tempBilltoAddressBookId) {
            $temp_billing_address = true;
            $address = (array)$order->billing;
            $order->billing = array_merge($address, $this->createOrderAddressFromTemporary('bill'));
        }
        if ($temp_shipping_address || $temp_billing_address) {
            $tax_info = $this->recalculateTaxBasis($order, $temp_billing_address, $temp_shipping_address);
            $taxCountryId = $tax_info['tax_country_id'];
            $taxZoneId = $tax_info['tax_zone_id'];
        }
        $this->debugMessage("updateOrderAddresses, $temp_billing_address, $temp_shipping_address, $taxCountryId, $taxZoneId" . PHP_EOL . json_encode($order->customer) . PHP_EOL . json_encode($order->billing) . PHP_EOL . json_encode($order->delivery));
    }

    // -----
    // This internal function returns the guest-customer information currently gathered.
    //
    protected function getGuestCustomerInfo()
    {
        if (!isset($this->guestCustomerInfo)) {
            trigger_error("Guest customer-info not set during guest checkout.", E_USER_ERROR);
            exit();
        }
        return $this->guestCustomerInfo;
    }

    // -----
    // This internal function creates an address-array in the format used by the built-in Zen Cart
    // order-class from the selected temporary address.
    //
    // Note: Updated to use zen_get_country_name as a future-proofing for the inclusion of
    // "Multi-lingual Country Names" in zc157.
    //
    protected function createOrderAddressFromTemporary($which)
    {
        global $db;

        // -----
        // Grab the country-id from the specified temporary address.  If it's empty, like
        // in some situations during the shipping-estimator's processing, perform a
        // quick return with an empty array, so that no elements of the incoming address
        // will be modified.
        //
        $country_id = $this->tempAddressValues[$which]['country'];
        if (empty($country_id)) {
            return [];
        }

        $country_info = $db->Execute(
            "SELECT *
               FROM " . TABLE_COUNTRIES . "
              WHERE countries_id = $country_id
                AND status = 1
              LIMIT 1"
        );
        if ($country_info->EOF) {
            trigger_error("Unknown or disabled country present for '$which' address ($country_id).", E_USER_ERROR);
            exit();
        }

        $address = [
            'firstname' => $this->tempAddressValues[$which]['firstname'],
            'lastname' => $this->tempAddressValues[$which]['lastname'],
            'company' => $this->tempAddressValues[$which]['company'],
            'street_address' => $this->tempAddressValues[$which]['street_address'],
            'suburb' => $this->tempAddressValues[$which]['suburb'],
            'city' => $this->tempAddressValues[$which]['city'],
            'postcode' => $this->tempAddressValues[$which]['postcode'],
            'state' => ((zen_not_null($this->tempAddressValues[$which]['state'])) ? $this->tempAddressValues[$which]['state'] : $this->tempAddressValues[$which]['zone_name']),
            'zone_id' => $this->tempAddressValues[$which]['zone_id'],
            'country' => [
                'id' => $country_id, 
                'title' => zen_get_country_name($country_id), 
                'iso_code_2' => $country_info->fields['countries_iso_code_2'], 
                'iso_code_3' => $country_info->fields['countries_iso_code_3']
            ],
            'country_id' => $country_id,
            'format_id' => (int)$country_info->fields['address_format_id']
        ];
        $this->debugMessage("createOrderAddressFromTemporary($which), returning: " . json_encode($address));
        return $address;
    }

    // -----
    // This internal function, called when an address-change is detected, determines the current tax-basis
    // used during the checkout process.
    //
    protected function recalculateTaxBasis($order, $use_temp_billing, $use_temp_shipping)
    {
        $this->debugMessage("recalculateTaxBasis(order, $use_temp_billing, $use_temp_shipping): " . var_export($order, true) . var_export($this->tempAddressValues, true));
        switch (STORE_PRODUCT_TAX_BASIS) {
            case 'Shipping':
                if ($order->content_type == 'virtual') {
                    if ($use_temp_billing) {
                        $tax_country_id = $this->tempAddressValues['bill']['country'];
                        $tax_zone_id = $this->tempAddressValues['bill']['zone_id'];
                    } else {
                        $tax_country_id = $order->billing['country_id'];
                        $tax_zone_id = $order->billing['zone_id'];
                    }
                } else {
                    if ($use_temp_shipping) {
                        $tax_country_id = $this->tempAddressValues['ship']['country'];
                        $tax_zone_id = $this->tempAddressValues['ship']['zone_id'];
                    } else {
                        $tax_country_id = $order->delivery['country_id'];
                        $tax_zone_id = $order->delivery['zone_id'];
                    }
                }
                break;

            case 'Billing':
               if ($use_temp_billing) {
                    $tax_country_id = $this->tempAddressValues['bill']['country'];
                    $tax_zone_id = $this->tempAddressValues['bill']['zone_id'];
                } else {
                    $tax_country_id = $order->billing['country_id'];
                    $tax_zone_id = $order->billing['zone_id'];
                }
                break;

            default:
                if ($use_temp_billing && $this->tempAddressValues['bill']['zone_id'] == STORE_ZONE) {
                    $tax_country_id = $this->tempAddressValues['bill']['country'];
                    $tax_zone_id = $this->tempAddressValues['bill']['zone_id'];
                } elseif ((!$use_temp_billing && $order->billing['zone_id'] == STORE_ZONE) || $order->content_type === 'virtual') {
                    $tax_country_id = $order->billing['country_id'];
                    $tax_zone_id = $order->billing['zone_id'];
                } else {
                    $tax_country_id = $order->delivery['country_id'];
                    $tax_zone_id = $order->delivery['zone_id'];
                }
                break;
        }
        
        $this->debugMessage("recalculateTaxBasis, temp_billing($use_temp_billing), temp_shipping($use_temp_shipping), returning country_id = $tax_country_id, zone_id = $tax_zone_id.");
        return[
            'tax_country_id' => $tax_country_id,
            'tax_zone_id' => $tax_zone_id
        ];
    }

    /* -----
    ** This function returns a temporary address' information for use by the
    ** 'zen_address_format' function; called from OPC's observer class when
    ** notified of a request to 'zen_address_label'.
    */
    public function getAddressLabelFields($address_book_id)
    {
        // -----
        // If the requested address_book_id isn't one of OPC's temporary addresses,
        // nothing to do here.  Quick return to let the observer know
        // that the address information isn't to be overridden.
        //
        if ($address_book_id !== $this->tempBilltoAddressBookId && $address_book_id !== $this->tempSendtoAddressBookId) {
            return false;
        }

        // -----
        // Determine which of the temporary addresses should be returned, gather the
        // information and return it.
        //
        if ($_SESSION['cart']->get_content_type() !== 'virtual' && $address_book_id === $this->tempSendtoAddressBookId) {
            $which = 'ship';
        } else {
            $which = 'bill';
        }
        return $this->createOrderAddressFromTemporary($which);
    }

    /* -----
    ** This function validates (true) or not (false) the specified order-related
    ** address ('bill' or 'ship').
    **
    ** Side-effects: Might affect the current session's sendto/billto address-book-ids if
    ** temporary addresses are being used.
    */
    public function validateBilltoSendto($which)
    {
        global $db;

        $this->inputPreCheck($which);

        // -----
        // First, determine whether the specified address is/isn't temporary.
        //
        if ($which === 'bill') {
            $address_book_id = $_SESSION['billto'];
            $is_temp_address = ($address_book_id == $this->tempBilltoAddressBookId);
        } else {
            $address_book_id = $_SESSION['sendto'];
            $is_temp_address = ($address_book_id == $this->tempSendtoAddressBookId);
            if ($this->getShippingBilling()) {
                $is_temp_address = $is_temp_address || ($address_book_id == $this->tempBilltoAddressBookId);
            }
        }

        // -----
        // Next, determine if a temporary address is valid for the current customer session.
        //
        $is_valid = true;
        if (zen_in_guest_checkout()) {
            if (!$is_temp_address) {
                $is_valid = false;
            }
        } else {
            if (!$is_temp_address) {
                $check_query =
                    "SELECT customers_id
                       FROM " . TABLE_ADDRESS_BOOK . "
                      WHERE customers_id = :customersID
                        AND address_book_id = :addressBookID
                      LIMIT 1";
                $check_query = $db->bindVars($check_query, ':customersID', $_SESSION['customer_id'], 'integer');
                $check_query = $db->bindVars($check_query, ':addressBookID', $address_book_id, 'integer');
                $check = $db->Execute($check_query);
                $is_valid = !$check->EOF;
            }
        }

        // -----
        // If the address isn't valid for the current usage, reset the session's address to
        // the customer's default and kill the session-variable previously set for that
        // invalid address.
        //
        if (!$is_valid) {
            if ($which === 'bill') {
                $_SESSION['billto'] = $_SESSION['customer_default_address_id'];
                $_SESSION['payment'] = '';
            } else {
                $_SESSION['sendto'] = $_SESSION['customer_default_address_id'];
                unset($_SESSION['shipping']);
            }
        }
        return $is_valid;
    }

    /* -----
    ** This function, called from OPC's AJAX handler, requests that any temporary shipping
    ** address be reset to the currently-selected billing address, as defined in the session.
    */
    public function setTempShippingToBilling()
    {
        if (empty($_SESSION['billto'])) {
            trigger_error('Invalid access; $_SESSION[\'billto\'] is not set.', E_USER_ERROR);
            exit();
        }

        $address_book_id = $_SESSION['billto'];
        if ($address_book_id == $this->tempBilltoAddressBookId) {
            $address_values = $this->tempAddressValues['bill'];
        } else {
            $address_values = $this->getAddressValuesFromDb($address_book_id);
        }
        $this->tempAddressValues['ship'] = $address_values;
    }

    /* -----
    ** This function resets the current session's address to the specified address-book entry.
    */
    public function setAddressFromSavedSelections($which, $address_book_id)
    {
        $this->inputPreCheck($which);

        if ($which === 'bill') {
            $_SESSION['billto'] = $address_book_id;
            if ($this->getShippingBilling()) {
                $_SESSION['sendto'] = $address_book_id;
            }
        } else {
            $_SESSION['sendto'] = $address_book_id;
        }
    }

    public function getAddressValues($which)
    {
        $this->inputPreCheck($which);

        $address_book_id = (int)($which === 'bill') ? $_SESSION['billto'] : $_SESSION['sendto'];

        if ($address_book_id == $this->tempBilltoAddressBookId || $address_book_id == $this->tempSendtoAddressBookId) {
            $address_values = $this->tempAddressValues[$which];
        } else {
            $address_values = $this->getAddressValuesFromDb($address_book_id);
        }
        if (!isset($address_values['country_id'])) {
            $address_values['country_id'] = $address_values['country'];
        }

        return $this->updateStateDropdownSettings($address_values);
    }

    protected function getAddressValuesFromDb($address_book_id)
    {
        global $db;

        $address_info_query = 
            "SELECT ab.*, z.zone_name, z.zone_code
               FROM " . TABLE_ADDRESS_BOOK . "  ab
                    LEFT JOIN " . TABLE_ZONES . " z
                        ON z.zone_id = ab.entry_zone_id
                       AND z.zone_country_id = ab.entry_country_id
              WHERE ab.customers_id = :customersID 
                AND ab.address_book_id = :addressBookID 
              LIMIT 1";
        $address_info_query = $db->bindVars($address_info_query, ':customersID', $_SESSION['customer_id'], 'integer');
        $address_info_query = $db->bindVars($address_info_query, ':addressBookID', $address_book_id, 'integer');

        $address_info = $db->Execute($address_info_query);
        if ($address_info->EOF) {
            trigger_error("unknown address_book_id (" . $address_book_id . ') for customer_id (' . $_SESSION['customer_id'] . ')', E_USER_ERROR);
            exit();
        }

        foreach ($address_info->fields as $key => $value) {
            if (strpos($key, 'entry_') === 0) {
                $new_key = substr($key, 6);
                if ($new_key == 'country_id') {
                    $address_info->fields['country'] = $value;
                }
                $address_info->fields[$new_key] = $value;
                unset($address_info->fields[$key]);
            }
        }

        $address_info->fields['error_state_input'] = $address_info->fields['error'] = false;
        $address_info->fields['country_has_zones'] = $this->countryHasZones($address_info->fields['country']);
        $address_info->fields['validated'] = !$this->customerAccountNeedsPrimaryAddress();

        $address_info->fields = $this->updateStateDropdownSettings($address_info->fields);

        $this->notify('NOTIFY_OPC_INIT_ADDRESS_FROM_DB', $address_book_id, $address_info->fields);

        $this->debugMessage("getAddressValuesFromDb($address_book_id), returning: " . json_encode($address_info->fields)); 

        return $address_info->fields;
    }

    protected function initAddressValuesForGuest()
    {
        $address_values = [
            'gender' => '',
            'company' => '',
            'firstname' => '',
            'lastname' => '',
            'street_address' => '',
            'suburb' => '',
            'city' => '',
            'postcode' => '',
            'state' => '',
            'country' => (int)STORE_COUNTRY,
            'country_id' => (int)STORE_COUNTRY,
            'zone_id' => 0,
            'zone_name' => '',
            'address_book_id' => 0,
            'selected_country' => (int)STORE_COUNTRY,
            'country_has_zones' => $this->countryHasZones((int)STORE_COUNTRY),
            'state_field_label' => '',
            'show_pulldown_states' => true,
            'error' => false,
            'error_state_input' => false,
            'validated' => false,
        ];
        $address_values = $this->updateStateDropdownSettings($address_values);

        $this->notify('NOTIFY_OPC_INIT_ADDRESS_FOR_GUEST', '', $address_values);

        return $address_values;
    }

    public function formatAddressBookDropdown()
    {
        global $db;

        $select_array = [];
        if (isset($_SESSION['customer_id']) && !$this->isGuestCheckout() && !$this->customerAccountNeedsPrimaryAddress()) {
            // -----
            // Build up address list input to create a customer-specific selection list of 
            // pre-existing addresses from which to choose.
            //
            $addresses = $db->Execute(
                "SELECT address_book_id 
                   FROM " . TABLE_ADDRESS_BOOK . " 
                  WHERE customers_id = " . (int)$_SESSION['customer_id'] . "
               ORDER BY entry_company ASC, entry_firstname ASC, entry_lastname ASC, address_book_id ASC"
            );
            if (!$addresses->EOF) {
                $select_array[] = [
                    'id' => 0,
                    'text' => TEXT_SELECT_FROM_SAVED_ADDRESSES
                ];
            }
            foreach ($addresses as $address) {
                $select_array[] = [ 
                    'id' => $address['address_book_id'],
                    'text' => str_replace("\n", ', ', zen_address_label($_SESSION['customer_id'], $address['address_book_id']))
                ];
            }
        }
        return $select_array;
    }

    public function getAddressDropDownSelection($which)
    {
        $this->inputPreCheck($which);

        if ($which === 'bill') {
            $selection = (!isset($_SESSION['billto']) || $_SESSION['billto'] == $this->tempBilltoAddressBookId) ? 0 : $_SESSION['billto'];
        } else {
            $selection = (!isset($_SESSION['sendto']) || $_SESSION['sendto'] == $this->tempSendtoAddressBookId) ? 0 : $_SESSION['sendto'];
        }
        return $selection;
    }

    // -----
    // Creates the json-formatted array that maps countries to zones, for use in the
    // customer address-forms when dropdown states are enabled.
    //
    public function getCountriesZonesJavascript()
    {
        global $db;

        $countries = $db->Execute(
            "SELECT DISTINCT zone_country_id
               FROM " . TABLE_ZONES . "
                    INNER JOIN " . TABLE_COUNTRIES . "
                        ON countries_id = zone_country_id
                       AND status = 1
           ORDER BY zone_country_id"
        );

        $c2z = [];
        foreach ($countries as $country) {
            $current_country_id = $country['zone_country_id'];
            $c2z[$current_country_id] = [];

            $states = zen_get_country_zones($current_country_id);
            foreach ($states as $state) {
                $c2z[$current_country_id][$state['id']] = $state['text'];
            }
        }

        $output_string = 'var c2z = \'' . addslashes(json_encode($c2z)) . '\';' . PHP_EOL;
        return $output_string;
    }

    protected function initializeTempAddressValues()
    {
        if (!isset($this->tempAddressValues)) {
            $this->tempAddressValues = [
                'ship' => $this->initAddressValuesForGuest(),
                'bill' => $this->initAddressValuesForGuest()
            ];
        }
    }

    protected function countryHasZones($country_id)
    {
        global $db;

        $check = $db->Execute(
            "SELECT zone_id
               FROM " . TABLE_ZONES . "
              WHERE zone_country_id = $country_id
              LIMIT 1"
        );
        return !$check->EOF;
    }

    // -----
    // Updated for v2.3.2, the state dropdown is *always* displayed, regardless of configuration
    // setting.  Simplifies (and corrects) the OPC address-update processing for stores that have
    // that setting set to 'false'.
    //
    protected function updateStateDropdownSettings($address_values)
    {
        $show_pulldown_states = true;
        $address_values['selected_country'] = $address_values['country'];
        $address_values['state'] = ($show_pulldown_states) ? $address_values['state'] : $address_values['zone_name'];
        $address_values['state_field_label'] = ($show_pulldown_states) ? '' : ENTRY_STATE;
        $address_values['show_pulldown_states'] = $show_pulldown_states;

        return $address_values;
    }

    // -----
    // For template assistance (to reduce parameters needed for formatAddressElement), provide a method
    // to allow the template to set the default label parameters used for each subsequent call to
    // that method.
    //
    public function setAddressLabelParams($label_params)
    {
        $this->label_params = $label_params;
    }
    public function formatAddressElement($which, $field_name, $field_value, $field_text, $db_table, $db_fieldname, $min_length, $placeholder, $field_params = '', $label_params = '')
    {
        $this->inputPreCheck($which);

        // -----
        // Special handling for the 'company' and 'suburb' fields, to guide browser autofill operations to
        // fill in the proper fields.
        //
        $autocomplete = '';
        if ($field_name === 'company') {
            $autocomplete = ' autocomplete="organization"';
        } elseif ($field_name === 'suburb') {
            $autocomplete = ' autocomplete="address-line2"';
        }

        $field_id = str_replace('_', '-', $field_name) . "-$which";
        $field_name .= "[$which]";
        $field_len = zen_set_field_length($db_table, $db_fieldname, '40');
        $field_required = (((int)$min_length) > 0) ? ' required' : '';

        if ($label_params === '' && !empty($this->label_params)) {
            $label_params = $this->label_params;
        }
        $field_label = (empty($field_text)) ? '' : (zen_draw_label($field_text, $field_id, $label_params) . PHP_EOL);

        return
            $field_label .
            zen_draw_input_field($field_name, $field_value, "$field_len id=\"$field_id\"$autocomplete placeholder=\"$placeholder\" $field_required$field_params");
    }

    public function validateAndSaveAjaxPostedAddress($which, &$messages)
    {
        $this->inputPreCheck($which);
        $this->debugMessage("validateAndSaveAJaxPostedAddress($which, ..), POST: " . var_export($_POST, true));

        $address_info = $_POST;
        if ($address_info['shipping_billing'] == 'true') {
            $_SESSION['shipping_billing'] = true;
        } else {
            $_SESSION['shipping_billing'] = false;
        }
        unset($address_info['securityToken'], $address_info['add_address'], $address_info['shipping_billing']);
        $messages = $this->validateUpdatedAddress($address_info, $which, false);
        if ($address_info['validated']) {
            $add_address = ($this->customerAccountNeedsPrimaryAddress() || (isset($_POST['add_address']) && $_POST['add_address'] === 'true'));
            $this->saveCustomerAddress($address_info, $which, $add_address);
        }

        return !$address_info['validated'];
    }

    public function validateAndSaveAjaxCustomerInfo()
    {
        if (!isset($_POST['email_address'])) {
            trigger_error('validateAndSaveAjaxCustomerInfo, invalid POST: ' . var_export($_POST, true), E_USER_ERROR);
            exit();
        }

        $messages = [];
        $this->customerInfoOk = false;

        $email_address = zen_db_prepare_input(zen_sanitize_string($_POST['email_address']));
        if (strlen($email_address) < ENTRY_EMAIL_ADDRESS_MIN_LENGTH) {
            $messages['email_address'] = ENTRY_EMAIL_ADDRESS_ERROR;
        } elseif (!zen_validate_email($email_address) || $this->isEmailBanned($email_address)) {
            $messages['email_address'] = ENTRY_EMAIL_ADDRESS_CHECK_ERROR;
        } elseif (CHECKOUT_ONE_GUEST_EMAIL_CONFIRMATION == 'true') {
            $email_confirm = zen_db_prepare_input(zen_sanitize_string($_POST['email_address_conf']));
            if ($email_confirm !== $email_address) {
                $messages['email_address_conf'] = ERROR_EMAIL_MUST_MATCH_CONFIRMATION;
            }
        }

        $telephone = zen_db_prepare_input(zen_sanitize_string($_POST['telephone']));
        if (strlen($telephone) < ENTRY_TELEPHONE_MIN_LENGTH) {
            $messages['telephone'] = ENTRY_TELEPHONE_NUMBER_ERROR;
        }

        // -----
        // For the date-of-birth, some mobile browsers use a popup calendar that might
        // require the date's display to be in a format other than that used in the database.
        //
        // Remember the format returned by the browser and, if found to be valid, use that to
        // re-display the date value.
        //
        $dob = '';
        $dob_display = '';
        if (ACCOUNT_DOB === 'true') {
            $dob = zen_db_prepare_input($_POST['dob']);
            $dob_display = $dob;
            if (ENTRY_DOB_MIN_LENGTH > 0 || !empty($_POST['dob'])) {
                // Support ISO-8601 style date
                if (preg_match('/^([0-9]{4})(|-|\/)([0-9]{2})\2([0-9]{2})$/', $dob)) {
                    $_POST['dob'] = $dob = date(DATE_FORMAT, strtotime($dob));
                }
                if (substr_count($dob, '/') > 2 || !checkdate((int)substr(zen_date_raw($dob), 4, 2), (int)substr(zen_date_raw($dob), 6, 2), (int)substr(zen_date_raw($dob), 0, 4))) {
                    $messages['dob'] = ENTRY_DATE_OF_BIRTH_ERROR;
                }
            }
        }

        // -----
        // Give an observer the opportunity to validate any additional fields that might be required
        // for the guest-customer.  If the observer finds issues with any field, the "additional_messages"
        // array (an associative array) is set to identify the field (the "key") and the message (the "value")
        // to be displayed.  If no issues are found, the "additional_fields" array (also associative) is
        // set to contain the element names ("key") and their associated value.
        //
        $additional_messages = [];
        $additional_fields = [];
        $this->notify('NOTIFY_OPC_VALIDATE_SAVE_GUEST_INFO', $messages, $additional_messages, $additional_fields);
        if (is_array($additional_messages) && count($additional_messages) !== 0) {
            $this->debugMessage('validateAndSaveAjaxCustomerInfo, additional messages (' . json_encode($additional_messages) . '), additional fields (' . json_encode($additional_fields) . ')');
            $messages = array_merge($messages, $additional_messages);
        }

        if (count($messages) === 0) {
            $this->customerInfoOk = true;
            $this->guestCustomerInfo['email_address'] = $email_address;
            $this->guestCustomerInfo['telephone'] = $telephone;
            $this->guestCustomerInfo['dob'] = $dob;
            $this->guestCustomerInfo['dob_display'] = $dob_display;
            if (is_array($additional_fields)) {
                $this->guestCustomerInfo = array_merge($this->guestCustomerInfo, $additional_fields);
            }
        }
        return $messages;
    }

    // -----
    // See if the supplied email-address is present (and banned) within the store's
    // database.  Returns true if present and banned; false otherwise.
    //
    protected function isEmailBanned($email_address)
    {
        global $db;

        $email_address = $db->prepare_input($email_address);
        $check = $db->Execute(
            "SELECT customers_id
               FROM " . TABLE_CUSTOMERS . "
              WHERE customers_email_address = '$email_address'
                AND customers_authorization = 4
              LIMIT 1"
        );
        return !$check->EOF;
    }

    // -----
    // Called by various functions with public interfaces to validate the "environment"
    // for the caller's processing.  If either the 'which' (address-value) input is not
    // valid or the class' addressValues element is not yet initialized, there's a
    // sequencing error somewhere.
    //
    // If either condition is found, log an ERROR ... which results in the page's processing
    // to cease.
    //
    protected function inputPreCheck($which)
    {
        if ($which !== 'bill' && $which !== 'ship') {
            trigger_error("Unknown address selection ($which) received.", E_USER_ERROR);
            exit();
        }
        if (!isset($this->tempAddressValues)) {
            // -----
            // Include the information identifying whether the values were reset (and via
            // what processing path) as an aid in identifying the source of this occasional
            // error.
            //
            $extra_info = (isset($_SESSION['payment'])) ? $_SESSION['payment'] : 'not set';
            $extra_info .= ', ' . (isset($_SESSION['shipping'])) ? json_encode($_SESSION['shipping']) : 'not set';
            trigger_error('Invalid request, tempAddressValues not set: ' . $extra_info . PHP_EOL . json_encode($this), E_USER_ERROR);
            exit();
        }
    }

    // -----
    // This internal function validates (and potentially updates) the supplied address-values information,
    // returning an array of messages identifying "issues" found.  If the returned array is
    // empty, no "issues" were found.
    //
    protected function validateUpdatedAddress(&$address_values, $which, $prepend_which = true)
    {
        global $db;

        $error = false;
        $zone_id = 0;
        $zone_name = '';
        $error_state_input = false;
        $entry_state_has_zones = false;
        $messages = [];

        $message_prefix = ($prepend_which) ? (($which == 'bill') ? ERROR_IN_BILLING : ERROR_IN_SHIPPING) : '';

        $this->debugMessage("Start validateUpdatedAddress, which = $which:" . var_export($address_values, true));

        if ($which === 'bill') {
            $this->billtoTempAddrOk = false;
            if ($this->getShippingBilling()) {
                $this->sendtoTempAddrOk = false;
            }
        } else {
            $this->sendtoTempAddrOk = false;
        }

        $gender = false;
        $company = '';
        $suburb = '';

        if (ACCOUNT_COMPANY === 'true') {
            $company = zen_db_prepare_input($address_values['company']);
            if (((int)ENTRY_COMPANY_MIN_LENGTH > 0) && strlen($company) < ((int)ENTRY_COMPANY_MIN_LENGTH)) {
                $error = true;
                $messages['company'] = $message_prefix . ENTRY_COMPANY_ERROR;
            }
        }

        if (ACCOUNT_GENDER === 'true') {
            $gender = (isset($address_values['gender'])) ? zen_db_prepare_input($address_values['gender']) : '';
            if ($gender !== 'm' && $gender !== 'f') {
                $error = true;
                $messages['gender'] = $message_prefix . ENTRY_GENDER_ERROR;
            }
        }

        $firstname = zen_db_prepare_input(zen_sanitize_string($address_values['firstname']));
        if (strlen($firstname) < ENTRY_FIRST_NAME_MIN_LENGTH) {
            $error = true;
            $messages['firstname'] = $message_prefix . ENTRY_FIRST_NAME_ERROR;
        }

        $lastname = zen_db_prepare_input(zen_sanitize_string($address_values['lastname']));
        if (strlen($lastname) < ENTRY_LAST_NAME_MIN_LENGTH) {
            $error = true;
            $messages['lastname'] = $message_prefix . ENTRY_LAST_NAME_ERROR;
        }

        $street_address = zen_db_prepare_input($address_values['street_address']);
        if (strlen($street_address) < ENTRY_STREET_ADDRESS_MIN_LENGTH) {
            $error = true;
            $messages['street_address'] = $message_prefix . ENTRY_STREET_ADDRESS_ERROR;
        }

        if (ACCOUNT_SUBURB === 'true') {
            $suburb = zen_db_prepare_input($address_values['suburb']);
        }

        $city = zen_db_prepare_input($address_values['city']);
        if (strlen($city) < ENTRY_CITY_MIN_LENGTH) {
            $error = true;
            $messages['city'] = $message_prefix . ENTRY_CITY_ERROR;
        }

        $postcode = zen_db_prepare_input($address_values['postcode']);
        if (strlen($postcode) < ENTRY_POSTCODE_MIN_LENGTH) {
            $error = true;
            $messages['postcode'] = $message_prefix . ENTRY_POST_CODE_ERROR;
        }

        $country = zen_db_prepare_input($address_values['zone_country_id']);
        if (!ctype_digit($country)) {
            $error = true;
            $messages['zone_country_id'] = $message_prefix . ENTRY_COUNTRY_ERROR;
        } elseif (ACCOUNT_STATE === 'true') {
            $state = (isset($address_values['state'])) ? trim(zen_db_prepare_input($address_values['state'])) : '';
            $zone_id = (isset($address_values['zone_id'])) ? zen_db_prepare_input($address_values['zone_id']) : 0;

            $country_has_zones = $this->countryHasZones((int)$country);
            if ($country_has_zones) {
                $zone_query = 
                    "SELECT DISTINCT zone_id, zone_name, zone_code
                       FROM " . TABLE_ZONES . "
                      WHERE zone_country_id = :zoneCountryID
                        AND " .
                             (($state != '' && $zone_id == 0) ? "(UPPER(zone_name) LIKE ':zoneState%' OR UPPER(zone_code) LIKE '%:zoneState%') OR " : '') . "
                             zone_id = :zoneID
                   ORDER BY zone_code ASC, zone_name";

                $zone_query = $db->bindVars($zone_query, ':zoneCountryID', $country, 'integer');
                $zone_query = $db->bindVars($zone_query, ':zoneState', strtoupper($state), 'noquotestring');
                $zone_query = $db->bindVars($zone_query, ':zoneID', $zone_id, 'integer');
                $zone = $db->Execute($zone_query);

                //look for an exact match on zone ISO code
                $found_exact_iso_match = ($zone->RecordCount() === 1);
                if ($zone->RecordCount() > 1) {
                    foreach ($zone as $next_zone) {
                        if (strtoupper($next_zone['zone_code']) === strtoupper($state) ) {
                            $found_exact_iso_match = true;
                            break;
                        }
                    }
                }

                if ($found_exact_iso_match) {
                    $zone_id = $zone->fields['zone_id'];
                    $zone_name = $zone->fields['zone_name'];
                } else {
                    $error = true;
                    $error_state_input = true;
                    $messages['zone_id'] = $message_prefix . ENTRY_STATE_ERROR_SELECT;
                }
            } else {
                if (strlen($state) < ENTRY_STATE_MIN_LENGTH) {
                    $error = true;
                    $error_state_input = true;
                    $messages['state'] = $message_prefix . ENTRY_STATE_ERROR;
                }
            }
        }

        // -----
        // Give an observer the opportunity to check any additional fields that might be used by its
        // customizations, supplying these inputs:
        //
        // $p1 ... (r/o) An associative array containing two keys:
        //         - 'which' ............ contains either 'ship' or 'bill', identifying which address-type is being validated.
        //         - 'address_values' ... contains an array of posted values associated with the to-be-validated address.
        // $p2 ... (r/w) A reference to the, initially empty, $additional_messages associative array.  That array is keyed on the
        //         like-named key within $p1's 'address_values' array, with the associated value being a language-specific message to
        //         display to the customer.  If this array is _not empty_, the address is considered unvalidated.
        // $p3 ... (r/w) A reference to the, initially empty, $additional_address_values associative array.  That array is keyed on
        //         the like-named key within $p1's 'address_values' array, with the associated value being that to be stored for the
        //         address.
        //
        // Notes:
        //
        // 1) Messages returned in the $additional_messages array **will override** any field-specific message recorded by this method's
        //    prior processing, e.g. if a 'company' message is provided when processing has already identified a company-related issue then
        //    that message is displayed to the customer instead of the OPC-determined one.
        // 2) Values in the $additional_address_values array **will override** OPC base values, e.g. if a 'company' key is provided then
        //    that value is used for the address' 'company' entry.
        //
        $additional_messages = [];
        $additional_address_values = [];
        $this->notify('NOTIFY_OPC_ADDRESS_VALIDATION', ['which' => $which, 'address_values' => $address_values], $additional_messages, $additional_address_values);
        if (count($additional_messages) != 0) {
            $this->debugMessage('validateUpdatedAddress, observer returned errors: ' . json_encode($additional_messages));
            $error = true;
            $messages = array_merge($messages, $additional_messages);
        }

        if ($error) {
            $address_values['validated'] = false;
        } else {
            if (count($additional_address_values) != 0) {
                $this->debugMessage('validateUpdatedAddress, observer returned additional address values: ' . json_encode($additional_address_values));
            }
            $address_values = array_merge(
                $address_values,
                [
                    'company' => $company,
                    'gender' => $gender,
                    'firstname' => $firstname,
                    'lastname' => $lastname,
                    'street_address' => $street_address,
                    'suburb' => $suburb,
                    'city' => $city,
                    'state' => $state,
                    'postcode' => $postcode,
                    'country' => $country,
                    'zone_id' => $zone_id,
                    'zone_name' => $zone_name,
                    'error_state_input' => $error_state_input,
                    'country_has_zones' => $country_has_zones,
                    'show_pulldown_states' => true,
                    'error' => false,
                    'validated' => true
                ],
                $additional_address_values
            );
            $address_values = $this->updateStateDropdownSettings($address_values);
            if ($which === 'bill') {
                $this->billtoTempAddrOk = true;
                if ($this->getShippingBilling()) {
                    $this->sendtoTempAddrOk = true;
                }
            } else {
                $this->sendtoTempAddrOk = true;
            }
        }

        $this->debugMessage('Exiting validateUpdatedAddress.' . json_encode($messages) . PHP_EOL . json_encode($address_values));
        return $messages;
    }

    // -----
    // This internal function saves the requested (and previously validated!) address,
    // either to a temporary, in-session, value or to the database.
    //
    protected function saveCustomerAddress($address, $which, $add_address = false)
    {
        global $db;

        $this->debugMessage("saveCustomerAddress($which, $add_address), " . (($this->getShippingBilling()) ? 'shipping=billing' : 'shipping!=billing') . ' ' . json_encode($address));

        // -----
        // If the address is **not** to be added to the customer's address book or if
        // guest-checkout is currently active, the updated address is stored in
        // a temporary address-book record.
        //
        if (!$add_address || $this->isGuestCheckout()) {
            $this->tempAddressValues[$which] = $address;
            if ($which === 'ship') {
                $_SESSION['sendto'] = $this->tempSendtoAddressBookId;
            } else {
                if ($this->isGuestCheckout()) {
                    $this->guestCustomerInfo['firstname'] = $address['firstname'];
                    $this->guestCustomerInfo['lastname'] = $address['lastname'];
                    $this->guestCustomerInfo['gender'] = $address['gender'];

                    $_SESSION['customer_first_name'] = $address['firstname'];
                    $_SESSION['customer_last_name'] = $address['lastname'];
                }
                $_SESSION['billto'] = $this->tempBilltoAddressBookId;
                if ($this->isGuestCheckout() && $this->sendtoTempAddrOk === false) {
                    $this->tempAddressValues['ship'] = $this->tempAddressValues['bill'];
                    $this->sendtoTempAddrOk = true;
                }
                if ($this->getShippingBilling()) {
                    $_SESSION['sendto'] = $this->tempBilltoAddressBookId;
                    $this->tempAddressValues['ship'] = $this->tempAddressValues['bill'];
                } elseif ($this->isGuestCheckout() && $_SESSION['sendto'] === (int)$this->tempBilltoAddressBookId) {
                    $_SESSION['sendto'] = $this->tempSendtoAddressBookId;
                }
            }
            $this->debugMessage("Updated tempAddressValues[$which], billing=shipping(" . $_SESSION['shipping_billing'] . "), sendto(" . $_SESSION['sendto'] . "), billto(" . $_SESSION['billto'] . "):" . json_encode($this->tempAddressValues));
        // -----
        // Otherwise, the address is to be saved in the database ...
        //
        } else {
            // -----
            // Build up the to-be-stored address.
            //
            $sql_data_array = [
                ['fieldName' => 'entry_firstname', 'value' => $address['firstname'], 'type' => $this->dbStringType],
                ['fieldName' => 'entry_lastname', 'value' => $address['lastname'], 'type' => $this->dbStringType],
                ['fieldName' => 'entry_street_address', 'value' => $address['street_address'], 'type' => $this->dbStringType],
                ['fieldName' => 'entry_postcode', 'value' => $address['postcode'], 'type' => $this->dbStringType],
                ['fieldName' => 'entry_city', 'value' => $address['city'], 'type' => $this->dbStringType],
                ['fieldName' => 'entry_country_id', 'value' => $address['country'], 'type' => 'integer']
            ];

            if (ACCOUNT_GENDER === 'true') {
                $sql_data_array[] = ['fieldName' => 'entry_gender', 'value' => $address['gender'], 'type' => 'enum:m|f'];
            }

            if (ACCOUNT_COMPANY === 'true') {
                $sql_data_array[] = ['fieldName' => 'entry_company', 'value' => $address['company'], 'type' => $this->dbStringType];
            }

            if (ACCOUNT_SUBURB === 'true') {
                $sql_data_array[] = ['fieldName' => 'entry_suburb', 'value' => $address['suburb'], 'type' => $this->dbStringType];
            }

            if (ACCOUNT_STATE === 'true') {
                if ($address['zone_id'] > 0) {
                    $sql_data_array[] = ['fieldName' => 'entry_zone_id', 'value' => $address['zone_id'], 'type' => 'integer'];
                    $sql_data_array[] = ['fieldName' => 'entry_state', 'value'=> '', 'type' => $this->dbStringType];
                } else {
                    $sql_data_array[] = ['fieldName' => 'entry_zone_id', 'value' => '0', 'type' => 'integer'];
                    $sql_data_array[] = ['fieldName' => 'entry_state', 'value' => $address['state'], 'type' => $this->dbStringType];
                }
            }

            // -----
            // If a matching address-book entry is found for this logged-in customer (whose account has
            // a primary address), use that pre-saved address entry.  Otherwise, save the new/updated address for the customer.
            //
            $existing_address_book_id = !$this->customerAccountNeedsPrimaryAddress() && $this->findAddressBookEntry($address);
            if ($existing_address_book_id !== false) {
                $address_book_id = $existing_address_book_id;
            } else {
                if (!$this->customerAccountNeedsPrimaryAddress()) {
                    $sql_data_array[] = ['fieldName' => 'customers_id', 'value' => $_SESSION['customer_id'], 'type' => 'integer'];
                    $db->perform(TABLE_ADDRESS_BOOK, $sql_data_array);
                    $address_book_id = $db->Insert_ID();

                    $this->notify('NOTIFY_OPC_ADDED_ADDRESS_BOOK_RECORD', ['address_book_id' => $address_book_id], $sql_data_array);
                } else {
                    $address_book_id = (int)$_SESSION['customer_default_address_id'];
                    $customer_id = (int)$_SESSION['customer_id'];
                    $where_string = "customers_id = $customer_id AND address_book_id = $address_book_id LIMIT 1";
                    $db->perform(TABLE_ADDRESS_BOOK, $sql_data_array, 'update', $where_string);

                    $this->notify('NOTIFY_OPC_ADDED_PRIMARY_ADDRESS', ['address_book_id' => $address_book_id], $sql_data_array);
                }
            }

            // -----
            // Update the session's billto/sendto address based on the previous processing.
            //
            if ($which === 'bill') {
                $_SESSION['billto'] = $address_book_id;
            } else {
                $_SESSION['sendto'] = $address_book_id;
            }
        }
    }

    /* -----
    ** This function, called from the 'checkout_one_confirmation' page to ensure that all temporary
    ** entries (e.g. guest customer information and/or temporary addresses) have been entered and validated.
    **
    ** Returns a boolean value indicating whether or not all entries have been found to be valid.
    **
    ** Note: Under 'normal' circumstances, this function will never return 'false'.  The function's purpose is to thwart
    ** script-kiddies from messing with the CSS overlay and attempting to create an order with invalid entries.
    */
    public function validateTemporaryEntries()
    {
        $validated = ($this->validateCustomerInfo() && $this->validateTempBilltoAddress() && $this->validateTempShiptoAddress());

        $this->debugMessage("validateTemporaryEntries, on exit ({$this->customerInfoOk}, {$this->billtoTempAddrOk}, {$this->sendtoTempAddrOk}), returning ($validated).");
        return $validated;
    }

    /* -----
    ** This series of functions identify whether the various required elements (Guest
    ** Customer Information, Temporary Shipping Address and Temporary Billing Address)
    ** are set and validated.  Used during the 'checkout_one' page's (and associated AJAX
    ** processing) to determine whether or not to display certain blocks within the page's
    ** rendering.
    **
    ** Each returns a boolean value, indicating whether or not the associated entries have been found
    ** to be valid.
    */
    public function validateCustomerInfo()
    {
        return (!$this->isGuestCheckout() || $this->customerInfoOk);
    }
    public function validateTempBilltoAddress()
    {
        if (!$this->isGuestCheckout()) {
            $address_ok = !$this->customerAccountNeedsPrimaryAddress();
        } else {
            $address_ok = (!empty($_SESSION['billto']) && ($_SESSION['billto'] != $this->tempBilltoAddressBookId || $this->billtoTempAddrOk));
        }
        return $address_ok;
    }
    public function validateTempShiptoAddress()
    {
        if ($this->isVirtualOrder) {
            $address_ok = true;
        } elseif (!$this->isGuestCheckout()) {
            $address_ok = !$this->customerAccountNeedsPrimaryAddress();
        } else {
            $address_ok = (!empty($_SESSION['sendto']) && ($_SESSION['sendto'] == $this->tempSendtoAddressBookId || $this->sendtoTempAddrOk));
        }
        return $address_ok;
    }

    /* -----
    ** This function, called from the 'checkout_success' OPC header processing, creates a
    ** customer-account from the information associated with the just-placed order.
    */
    public function createAccountFromGuestInfo($order_id, $password, $newsletter, $email_format)
    {
        global $db;

        $password_error = (strlen((string)$password) < ENTRY_PASSWORD_MIN_LENGTH);
        if (!$this->guestIsActive || !isset($this->guestCustomerInfo) || !isset($this->tempAddressValues) || $password_error) {
            trigger_error("Invalid access ($password_error):" . var_export($this, true), E_USER_ERROR);
            exit();
        }

        $customer_id = $this->createCustomerRecordFromGuestInfo($password, $newsletter, $email_format);
        $_SESSION['customer_id'] = $customer_id;
        $db->Execute(
            "UPDATE " . TABLE_ORDERS . "
                SET customers_id = $customer_id
              WHERE orders_id = " . (int)$order_id . "
              LIMIT 1"
        );

        // -----
        // Issue a notification, indicating that the customer's record has been successfully created
        // based on the guest's just-placed order.
        //
        $this->notify('NOTIFY_OPC_CREATE_ACCOUNT_ORDER_UPDATED', ['customer_id' => $customer_id, 'order_id' => $order_id]);

        $default_address_id = $this->createAddressBookRecord($customer_id, 'bill');
        $db->Execute(
            "UPDATE " . TABLE_CUSTOMERS . "
                SET customers_default_address_id = $default_address_id
              WHERE customers_id = $customer_id
              LIMIT 1"
        );

        if ($this->tempAddressValues['ship']['firstname'] !== '') {
            if ($this->addressArrayToString($this->tempAddressValues['ship']) != $this->addressArrayToString($this->tempAddressValues['bill'])) {
                $this->createAddressBookRecord($customer_id, 'ship');
            }
        }

        unset(
            $_SESSION['sendto'], 
            $_SESSION['billto'],
            $_SESSION['is_guest_checkout'],
            $_SESSION['shipping_billing'], 
            $_SESSION['order_placed_by_guest']
        );

        $_SESSION['customer_first_name'] = $this->guestCustomerInfo['firstname'];
        $_SESSION['customer_last_name'] = $this->guestCustomerInfo['lastname'];
        $_SESSION['customer_default_address_id'] = $default_address_id;
        $_SESSION['customers_email_address'] = $this->guestCustomerInfo['email_address'];
        $_SESSION['customer_country_id'] = $this->tempAddressValues['bill']['country'];
        $_SESSION['customer_zone_id'] = $this->tempAddressValues['bill']['zone_id'];
        $_SESSION['customers_authorization'] = 0;

        $this->reset();
    }

    // -----
    // This internal function creates a customer record in the database for the
    // current guest.
    //
    protected function createCustomerRecordFromGuestInfo($password, $newsletter, $email_format)
    {
        global $db;

        if ($email_format !== 'HTML' && $email_format !== 'TEXT') {
            $email_format = (ACCOUNT_EMAIL_PREFERENCE === '1' ? 'HTML' : 'TEXT');
        }
        $sql_data_array = [
            ['fieldName' => 'customers_firstname', 'value' => $this->guestCustomerInfo['firstname'], 'type' => $this->dbStringType],
            ['fieldName' => 'customers_lastname', 'value' => $this->guestCustomerInfo['lastname'], 'type' => $this->dbStringType],
            ['fieldName' => 'customers_email_address', 'value' => $this->guestCustomerInfo['email_address'], 'type' => $this->dbStringType],
            ['fieldName' => 'customers_telephone', 'value' => $this->guestCustomerInfo['telephone'], 'type' => $this->dbStringType],
            ['fieldName' => 'customers_newsletter', 'value' => $newsletter, 'type' => 'integer'],
            ['fieldName' => 'customers_email_format', 'value' => $email_format, 'type' => $this->dbStringType],
            ['fieldName' => 'customers_default_address_id', 'value' => 0, 'type' => 'integer'],
            ['fieldName' => 'customers_password', 'value' => zen_encrypt_password($password), 'type' => $this->dbStringType],
            ['fieldName' => 'customers_authorization', 'value' => 0, 'type' => 'integer'],
        ];

        if (ACCOUNT_GENDER === 'true') {
            $gender = $this->guestCustomerInfo['gender'];
            $sql_data_array[] = ['fieldName' => 'customers_gender', 'value' => $gender, 'type' => $this->dbStringType];
        }
        if (ACCOUNT_DOB === 'true') {
            $dob = $this->guestCustomerInfo['dob'];
            $dob = (empty($dob) || $dob === '0001-01-01 00:00:00') ? zen_db_prepare_input('0001-01-01 00:00:00') : zen_date_raw($dob);
            $sql_data_array[] = ['fieldName' => 'customers_dob', 'value' => $dob, 'type' => 'date'];
        }

        $db->perform(TABLE_CUSTOMERS, $sql_data_array);
        $customer_id = $db->Insert_ID();

        $db->Execute(
            "INSERT INTO " . TABLE_CUSTOMERS_INFO . "
                    (customers_info_id, customers_info_number_of_logons, customers_info_date_account_created, customers_info_date_of_last_logon)
                VALUES 
                    ($customer_id, 1, now(), now())"
        );

        $this->notify('OPC_ADDED_CUSTOMER_RECORD_FOR_GUEST', $customer_id, $sql_data_array);    //-DEPRECATED for zc158+
        $this->notify('NOTIFY_OPC_ADDED_CUSTOMER_RECORD_FOR_GUEST', $customer_id, $sql_data_array);

        return $customer_id;
    }

    // -----
    // This internal function creates an address-book record in the database using one of the 
    // temporary address-book records.
    //
    protected function createAddressBookRecord($customer_id, $which)
    {
        global $db;

        $sql_data_array = [
            ['fieldName' => 'customers_id', 'value' => $customer_id, 'type' => 'integer'],
            ['fieldName' => 'entry_firstname', 'value' => $this->tempAddressValues[$which]['firstname'], 'type' => $this->dbStringType],
            ['fieldName' => 'entry_lastname', 'value' => $this->tempAddressValues[$which]['lastname'], 'type' => $this->dbStringType],
            ['fieldName' => 'entry_street_address', 'value' => $this->tempAddressValues[$which]['street_address'], 'type' => $this->dbStringType],
            ['fieldName' => 'entry_postcode', 'value' => $this->tempAddressValues[$which]['postcode'], 'type' => $this->dbStringType],
            ['fieldName' => 'entry_city', 'value' => $this->tempAddressValues[$which]['city'], 'type' => $this->dbStringType],
            ['fieldName' => 'entry_country_id', 'value' => $this->tempAddressValues[$which]['country'], 'type' => 'integer'],
        ];

        if (ACCOUNT_GENDER === 'true') {
            $sql_data_array[] = ['fieldName' => 'entry_gender', 'value' => $this->tempAddressValues[$which]['gender'], 'type' => $this->dbStringType];
        }
        if (ACCOUNT_COMPANY === 'true') {
            $sql_data_array[] = ['fieldName' => 'entry_company', 'value' => $this->tempAddressValues[$which]['company'], 'type' => $this->dbStringType];
        }
        if (ACCOUNT_SUBURB === 'true') {
            $sql_data_array[] = ['fieldName' => 'entry_suburb', 'value' => $this->tempAddressValues[$which]['suburb'], 'type' => $this->dbStringType];
        }

        if (ACCOUNT_STATE === 'true') {
            if ($this->tempAddressValues[$which]['zone_id'] > 0) {
                $sql_data_array[] = ['fieldName' => 'entry_zone_id', 'value' => $this->tempAddressValues[$which]['zone_id'], 'type' => 'integer'];
                $sql_data_array[] = ['fieldName' => 'entry_state', 'value' => '', 'type' => $this->dbStringType];
            } else {
                $sql_data_array[] = ['fieldName' => 'entry_zone_id', 'value' => 0, 'type' => 'integer'];
                $sql_data_array[] = ['fieldName' => 'entry_state', 'value' => $this->tempAddressValues[$which]['state'], 'type' => $this->dbStringType];
            }
        }
        $db->perform(TABLE_ADDRESS_BOOK, $sql_data_array);
        $address_book_id = $db->Insert_ID();
        
        $this->notify('NOTIFY_OPC_CREATED_ADDRESS_BOOK_DB_ENTRY', $address_book_id, $sql_data_array);

        return $address_book_id;
    }

    // -----
    // This internal function checks the database to see if the specified address already exists
    // for the customer associated with the current session.
    //
    protected function findAddressBookEntry($address)
    {
        global $db;

        $country_id = $address['country'];
        $country_has_zones = $address['country_has_zones'];

        // do a match on address, street, street2, city
        $sql = 
            "SELECT address_book_id, entry_street_address AS street_address, entry_suburb AS suburb, entry_city AS city, 
                    entry_postcode AS postcode, entry_firstname AS firstname, entry_lastname AS lastname, entry_company AS company,
                    entry_gender AS gender
               FROM " . TABLE_ADDRESS_BOOK . "
              WHERE customers_id = :customerId
                AND entry_country_id = $country_id";
        if (!$country_has_zones) {
            $sql .= " AND entry_state = :stateValue LIMIT 1";
        } else {
            $sql .= " AND entry_zone_id = :zoneId LIMIT 1";
        }
        $sql = $db->bindVars($sql, ':zoneId', $address['zone_id'], 'integer');
        $sql = $db->bindVars($sql, ':stateValue', $address['state'], 'string');
        $sql = $db->bindVars($sql, ':customerId', $_SESSION['customer_id'], 'integer');

        // -----
        // Give a watching observer the opportunity to gather additional address-related fields to be
        // used in the address-matching below, writing a debug-message if the information has
        // been changed.
        //
        $sql_saved = $sql;
        $this->notify('NOTIFY_OPC_ADDRESS_BOOK_SQL', $address, $sql);
        if ($sql_saved !== $sql) {
            $this->debugMessage("findAddressBookEntry, sql changed from\n$sql_saved\nto$sql.");
        }
        $possible_addresses = $db->Execute($sql);

        $address_book_id = false;  //-Identifies that no match was found
        $address_to_match = $this->addressArrayToString($address);
        foreach ($possible_addresses as $next_address) {
            if ($address_to_match === $this->addressArrayToString($next_address)) {
                $address_book_id = $next_address['address_book_id'];
                break;
            }
        }
        $this->debugMessage("findAddressBookEntry, returning ($address_book_id) for '$address_to_match'" . json_encode($address));
        return $address_book_id;
    }

    // -----
    // This internal function creates a string containing the address-related values
    // in the specified address-array.
    //
    protected function addressArrayToString($address_array) 
    {
        $the_address = 
            $address_array['company'] .
            $address_array['gender'] .
            $address_array['firstname'] .
            $address_array['lastname'] .
            $address_array['street_address'] .
            $address_array['suburb'] .
            $address_array['city'] .
            $address_array['postcode'];

        // -----
        // Give an observer the chance to include additional address-related fields for the
        // address-match determination.
        //
        $this->notify('NOTIFY_OPC_ADDRESS_ARRAY_TO_STRING', $address_array, $the_address);
        $the_address = strtolower(str_replace(["\n", "\t", "\r", "\0", ' ', ',', '.'], '', $the_address));

        return $the_address;
    }

    /* -----
    ** This public method is called by the OPC observer-class upon receipt of the indication that
    ** PayPal Express Checkout is preparing to send the order up to PayPal for fulfilment.  If temporary
    ** addresses are currently in use, this processing will return a PayPal-formatted array to be combined
    ** with the order's current PayPal options.
    **
    ** This method also records the order's total value, to be sent to PayPal, for comparison on the PayPal
    ** return to ensure that the order's total remains the same ... just in case the ship-to address returned
    ** from PayPal has a different taxation.
    */
    public function createPayPalTemporaryAddressInfo($paypal_options, $order)
    {
        $which = $this->determineTempShippingAddress();       
        $paypal_temp = [];
        if ($which !== false) {
            $temp_address = $this->createOrderAddressFromTemporary($which);
            $paypal_temp = [
                'PAYMENTREQUEST_0_SHIPTONAME' => $temp_address['firstname'] . ' ' . $temp_address['lastname'],
                'PAYMENTREQUEST_0_SHIPTOSTREET' => $temp_address['street_address'],
                'PAYMENTREQUEST_0_SHIPTOSTREET2' => (!empty($temp_address['suburb'])) ? $temp_address['suburb'] : '',
                'PAYMENTREQUEST_0_SHIPTOCITY' => $temp_address['city'],
                'PAYMENTREQUEST_0_SHIPTOZIP' => $temp_address['postcode'],
                'PAYMENTREQUEST_0_SHIPTOSTATE' => zen_get_zone_code($temp_address['country']['id'], $temp_address['zone_id'], $temp_address['state']),
                'PAYMENTREQUEST_0_SHIPTOCOUNTRYCODE' => $temp_address['country']['iso_code_2']
            ];
 
            if ($this->isGuestCheckout()) {
                $paypal_temp['EMAIL'] = $this->guestCustomerInfo['email_address'];
                if (!empty($this->guestCustomerInfo['telephone'])) {
                    $paypal_temp['PAYMENTREQUEST_0_SHIPTOPHONENUM'] = $this->guestCustomerInfo['telephone'];
                }
            }
            $this->debugMessage("createPayPalTemporaryAddressInfo, returning ($which): " . json_encode($paypal_temp));
        }
        $this->paypalTotalValue = $order->info['total'];
        $this->paypalNoShipping = $paypal_options['NOSHIPPING'];
        return $paypal_temp;
    }

    /* -----
    ** This public method is called by the OPC observer-class upon receipt of the indication that
    ** PayPal Express Checkout is preparing to check/create the address associated with the PayPal-approved
    ** payment. If the current ship-to address is temporary or, for virtual orders, the bill-to address is
    ** temporary, this processing will indicate that PayPal should bypass its automatic address-book creation.
    **
    ** If the order is virtual and the "shipping" address is temporary, a guest has placed a virtual order and
    ** we'll simply return that PayPal should bypass its address-creation process.
    **
    ** Otherwise, the address returned by PayPal **will be used as** the current temporary ship-to address.
    ** Note that the return from determineTempShippingAddress, for guest-checkout, is guaranteed to return either
    ** 'bill' or 'ship' as that method will error-out otherwise!  If the PayPal address returned _is different_ 
    ** from the current temporary shipping address -- for either guests or account-holders:
    **
    ** 1) Record the as-entered (and now overwritten) address within the OPC data; it'll be written
    **    to an order status-history record and "messaged" to the customer to let them know that the
    **    address change occurred.
    ** 2) Set the shipping=billing flag to indicate that the shipping/billing addresses don't match.
    ** 3) The PayPal-returned address is recorded as the current ship-to address.
    **
    ** The method returns a boolean flag, indicating whether or not the paypalwpp payment-method should bypass
    ** its automatic address-record creation.
    */
    public function setPayPalAddressCreationBypass($paypal_ec_payment_info)
    {
        $which = $this->determineTempShippingAddress();
        $bypass_address_creation = false;
        unset($this->paypalAddressOverride);

        // -----
        // If a temporary address is currently in use as the shipping address, it was sent
        // to PayPal via the createPayPalTemporaryAddressInfo method.  Check to see that the
        // shipping address returned by PayPal was changed.
        //
        // Note: $which is returned as (bool)false if the current shipping address is not a temporary one.
        //
        if ($which !== false) {
            // -----
            // If the shipping-address is currently 'temporary', we'll instruct PayPal to bypass its
            // address-creation processing.
            //
            $bypass_address_creation = true;

            // -----
            // Gather the information for the temporary ship-to address that was previously sent
            // to PayPal.
            //
            $temp_address = $this->createOrderAddressFromTemporary($which);
            $temp_address['customer_name'] = $temp_address['firstname'] . ' ' . $temp_address['lastname'];

            // -----
            // If the order was submitted to PayPal with 'NOSHIPPING' required, the order is virtual
            // and the PayPal response contains no shipping-address information.  We'll bypass the
            // shipping-address override processing in that case.
            //
            // Note: Checking *after* creating the temporary address array, since it's output as a debug
            // prior to return and we don't want any PHP notices generated for an undefined variable.
            //
            if (!$this->paypalNoShipping) {
                // -----
                // Loop through some of the more 'static' values in the ship-to address (the country and state
                // will be checked separately).
                //
                $compare_fields = [
                    'customer_name' => 'ship_name',
                    'street_address' => 'ship_street_1',
                    'suburb' => 'ship_street_2',
                    'city' => 'ship_city',
                    'postcode' => 'ship_postal_code',
                ];
                $address_match = true;
                foreach ($compare_fields as $t => $pp) {
                    if (strtoupper(trim($temp_address[$t])) !== strtoupper(trim($paypal_ec_payment_info[$pp]))) {
                        $address_match = false;
                        break;
                    }
                }

                // -----
                // If neither the state "code" (e.g. 'FL') nor the full state name (e.g. 'Florida') of the
                // temporary address matches the state field returned by PayPal, it's not a match.
                //
                $state_code = zen_get_zone_code($temp_address['country']['id'], $temp_address['zone_id'], $temp_address['state']);
                $paypal_state = strtoupper(trim($paypal_ec_payment_info['ship_state']));
                if ($paypal_state !== $state_code && $paypal_state !== strtoupper($temp_address['state'])) {
                    $address_match = false;
                }

                // -----
                // If neither the country "code" (e.g. 'US') nor the full country name (e.g. 'United States') of
                // the temporary address matches the country name returned by PayPal, it's not a match.
                //
                $country_name = strtoupper(trim($paypal_ec_payment_info['ship_country_name']));
                if ($country_name !== $temp_address['country']['iso_code_2'] && $country_name !== strtoupper($temp_address['country']['title'])) {
                    $address_match = false;
                }

                // -----
                // If an address mismatch was detected ...
                //
                if (!$address_match) {
                    // -----
                    // 1) Record the as-entered address into the OPC's session data, for use by the identifyPayPalAddressChange method.
                    //
                    $this->paypalAddressOverride = zen_address_format(zen_get_address_format_id($temp_address['country']['id']), $temp_address, false, '', ', ');

                    // -----
                    // 2) Indicate that the shipping/billing addresses don't match.
                    //
                    $_SESSION['shipping_billing'] = false;

                    // -----
                    // 3) Replace the temporary shipping address with the information supplied by PayPal, taking into account that
                    //    it might need to be created -- if the customer hasn't set shipping != billing.  Also make sure that the
                    //    temporary shipping address' status is set to OK!
                    //
                    $_SESSION['sendto'] = $this->tempSendtoAddressBookId;

                    foreach ($compare_fields as $t => $pp) {
                        if ($t == 'customer_name') {
                            $name_pieces = explode(' ', $paypal_ec_payment_info[$pp]);
                            $this->tempAddressValues['ship']['firstname'] = trim($name_pieces[0]);
                            $this->tempAddressValues['ship']['lastname'] = (isset($name_pieces[1])) ? trim($name_pieces[1]) : '';
                        } else {
                            $this->tempAddressValues['ship'][$t] = $paypal_ec_payment_info[$pp];
                        }
                    }
                    $this->tempAddressValues['ship']['company'] = '';

                    $country_info = $this->getCountryInfoFromIsoCode2($paypal_ec_payment_info['ship_country_code']);
                    $this->tempAddressValues['ship']['country'] = $country_info['id'];
                    $this->tempAddressValues['ship']['country_id'] = $country_info['id'];
                    $this->tempAddressValues['ship']['format_id'] = $country_info['address_format_id'];

                    $zone_info = $this->getZoneInfoFromCode($country_info['id'], $paypal_ec_payment_info['ship_state']);
                    $this->tempAddressValues['ship']['state'] = $zone_info['state'];
                    $this->tempAddressValues['ship']['zone_id'] = $zone_info['zone_id'];

                    $this->sendtoTempAddrOk = true;
                }
            }

            // -----
            // Leave some 'breadcrumbs' in the OPC log to indicate the results of the processing.
            //
            $this->debugMessage("setPayPalAddressCreationBypass, override from comparison: " . json_encode($paypal_ec_payment_info) . PHP_EOL . json_encode($temp_address));
        }
        return $bypass_address_creation;
    }

    // -----
    // This internal function returns 'which' temporary address is currently being used as
    // the order's shipping address; the value returned is (bool)false if a permanent address is in use.
    //
    protected function determineTempShippingAddress()
    {
        $which = false;
        if (!$this->isVirtualOrder()) {
            if (isset($_SESSION['sendto'])) {
                if ($_SESSION['sendto'] == $this->tempSendtoAddressBookId) {
                    $which = 'ship';
                } elseif ($_SESSION['sendto'] == $this->tempBilltoAddressBookId) {
                    $which = 'bill';
                }
            }
        } else {
            if (isset($_SESSION['billto']) && $_SESSION['billto'] == $this->tempBilltoAddressBookId) {
                $which = 'bill';
            }
        }

        // -----
        // If we're currently processing in guest-checkout mode, the shipping address "should"
        // be one of the temporary ship/bill entries -- if not, bail out with an error since
        // someone's mucked with the session-based values and it's not a recoverable case.
        //
        if ($which === false && $this->isGuestCheckout()) {
            trigger_error('Cannot determine guest shipping address, $_SESSION:' . PHP_EOL . var_export($_SESSION, true), E_USER_ERROR);
            exit;
        }
        return $which;
    }

    // -----
    // This internal method returns a country-array containing its id, name, iso-code-2 and iso-code-3
    // values, based on an iso_code_2 value input.
    //
    protected function getCountryInfoFromIsoCode2($iso_code_2)
    {
        global $db;

        $country_info = $db->Execute(
            "SELECT *
               FROM " . TABLE_COUNTRIES . "
              WHERE countries_iso_code_2 = '" . zen_db_input($iso_code_2) . "'
                AND status = 1
              LIMIT 1"
        );
        if ($country_info->EOF) {
            trigger_error("Could not locate the country with the iso-code-2 of $iso_code_2.", E_USER_ERROR);
            exit;
        } else {
            $country = [
                'id' => $country_info->fields['countries_id'],
                'title' => zen_get_country_name($country_info->fields['countries_id']),
                'iso_code_2' => $country_info->fields['countries_iso_code_2'],
                'iso_code_3' => $country_info->fields['countries_iso_code_3'],
                'format_id' => $country_info->fields['address_format_id'],
            ];
        }
        return $country;
    }

    // -----
    // This internal method returns a zone/state array containing the zone_id and name of the
    // state associated with the country/code provided.
    //
    protected function getZoneInfoFromCode($country_id, $zone_code)
    {
        global $db;

        $zone_code = zen_db_input($zone_code);
        $zone_info = $db->Execute(
            "SELECT zone_id
               FROM " . TABLE_ZONES . "
              WHERE zone_country_id = $country_id
                AND zone_code = '$zone_code'
                 OR zone_name = '$zone_code'
             LIMIT 1"
        );
        return [
            'zone_id' => ($zone_info->EOF) ? 0 : $zone_info->fields['zone_id'],
            'state' => $zone_code
        ];
    }

    /* -----
    ** This public method, called from OPC's observer, determines whether the order's total
    ** value (as represented by the order-object supplied) has changed since the order was
    ** originally 'pushed' to PayPal for authorization.  This could happen, for instance, if
    ** the ship-to address originally 'pushed' was for an untaxed location and the customer
    ** changed their address at PayPal to one that is taxed.
    **
    ** Returns (bool)true if the order's total value was changed; (bool)false otherwise.
    */
    public function didPayPalOrderTotalValueChange($order)
    {
        $value_changed = (isset($this->paypalTotalValue) && $this->paypalTotalValue != $order->info['total']);
        unset($this->paypalTotalValue);

        // -----
        // If the value has changed, OPC's observer class will redirect back to OPC's
        // data-gathering page with a message.  Set a flag to let the opc::checkEnabled method
        // to know that it's OK to continue processing.
        //
        if ($value_changed) {
            $this->paypalTotalValueChanged = true;
        }

        return $value_changed;
    }

    /* -----
    ** This public method, called from OPC's observer, updates the order's comments to identify
    ** any change to the order's shipping address based on the PayPal response.
    */
    public function identifyPayPalAddressChange(&$order)
    {
        global $messageStack;

        if (!empty($this->paypalAddressOverride)) {
            $message = sprintf(WARNING_PAYPAL_SENDTO_CHANGED, $this->paypalAddressOverride);
            unset($this->paypalAddressOverride);
            
            $messageStack->add_session('header', $message, 'caution');
            
            if (!empty($order->info['comments'])) {
                $order->info['comments'] .= "\n\n";
            }
            $order->info['comments'] .= $message;
        }
    }

    /* -----
    ** This public method, called from OPC's observer, provides an override to the order's
    ** tax-locations when temporary addresses are in effect.
    **
    ** Noting that this is somewhat complicated, since for the 'Store' tax-basis the order might
    ** have one temporary and one 'permanent' address in use.
    */
    public function getTaxLocations()
    {
        global $db;

        $billing_is_temp = (isset($_SESSION['billto']) && $_SESSION['billto'] == $this->tempBilltoAddressBookId);
        $shipping_is_temp = (isset($_SESSION['sendto']) && $_SESSION['sendto'] == $this->tempSendtoAddressBookId);

        switch (STORE_PRODUCT_TAX_BASIS) {
            case 'Shipping':
                if ($this->isVirtualOrder || $this->getShippingBilling()) {
                    if ($billing_is_temp) {
                        $country_id = $this->tempAddressValues['bill']['country'];
                        $zone_id = $this->tempAddressValues['bill']['zone_id'];
                    }
                } else {
                    if ($shipping_is_temp) {
                        $country_id = $this->tempAddressValues['ship']['country'];
                        $zone_id = $this->tempAddressValues['ship']['zone_id'];
                    }
                }
                break;

            case 'Billing':
               if ($billing_is_temp) {
                    $country_id = $this->tempAddressValues['bill']['country'];
                    $zone_id = $this->tempAddressValues['bill']['zone_id'];
                }
                break;

            case 'Store':
                if ($billing_is_temp) {
                    if ($this->isVirtualOrder || $this->tempAddressValues['bill']['zone_id'] == STORE_ZONE) {
                        $country_id = $this->tempAddressValues['bill']['country'];
                        $zone_id = $this->tempAddressValues['bill']['zone_id'];
                    }
                } else {
                    $country_zone = $db->Execute(
                        "SELECT ab.entry_country_id, ab.entry_zone_id
                           FROM " . TABLE_ADDRESS_BOOK . " ab
                                LEFT JOIN " . TABLE_ZONES . " z 
                                    ON ab.entry_zone_id = z.zone_id
                          WHERE ab.customers_id = " . (int)$_SESSION['customer_id'] . "
                            AND ab.address_book_id = " . (int)$_SESSION['billto'] . "
                          LIMIT 1"
                    );
                    if ($country_zone->EOF) {
                        trigger_error("Unknown/invalid billto address #{$_SESSION['billto']} for customer#{$_SESSION['customer_id']}.", E_USER_ERROR);
                        exit();
                    }
                    if ($this->isVirtualOrder || $country_zone->fields['entry_zone_id'] == STORE_ZONE) {
                        $country_id = $country_zone->fields['entry_country_id'];
                        $zone_id = $country_zone->fields['entry_zone_id'];
                    }
                }

                if (!isset($country_id)) {
                    if ($shipping_is_temp) {
                        $country_id = $this->tempAddressValues['ship']['country'];
                        $zone_id = $this->tempAddressValues['ship']['zone_id'];
                    } else {
                        $country_zone = $db->Execute(
                            "SELECT ab.entry_country_id, ab.entry_zone_id
                               FROM " . TABLE_ADDRESS_BOOK . " ab
                                    LEFT JOIN " . TABLE_ZONES . " z 
                                        ON ab.entry_zone_id = z.zone_id
                              WHERE ab.customers_id = " . (int)$_SESSION['customer_id'] . "
                                AND ab.address_book_id = " . (int)$_SESSION['sendto'] . "
                              LIMIT 1"
                        );
                        if ($country_zone->EOF) {
                            trigger_error("Unknown/invalid sendto address #{$_SESSION['sendto']} for customer#{$_SESSION['customer_id']}.", E_USER_ERROR);
                            exit();
                        }
                        $country_id = $country_zone->fields['entry_country_id'];
                        $zone_id = $country_zone->fields['entry_zone_id'];
                    }
                }
                break;

            default:
                trigger_error('Unknown value (' . STORE_PRODUCT_TAX_BASIS . ') found for \'STORE_PRODUCT_TAX_BASIS\'.', E_USER_ERROR);
                exit();
                break;
        }

        if (!isset($country_id)) {
            $tax_locations = false;
        } else {
            $tax_locations = [
                'country_id' => $country_id,
                'zone_id' => $zone_id
            ];
        }
        $this->debugMessage("getTaxLocations ($billing_is_temp:$shipping_is_temp), " . json_encode($tax_locations));
        return $tax_locations;
    }

    // -----
    // This internal function issues a debug-message using the OPC's observer-class
    // function.  This allows the various messages to be consolidated into a single
    // log-file for easier troubleshooting.
    //
    protected function debugMessage($message, $include_request = false)
    {
        global $checkout_one;

        if (isset($checkout_one)) {
            $checkout_one->debug_message($message, $include_request, 'OnePageCheckout');
        }
    }
}
