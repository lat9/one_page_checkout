<?php
// -----
// Part of the One-Page Checkout plugin, provided under GPL 2.0 license by lat9 (cindy@vinosdefrutastropicales.com).
// Copyright (C) 2017-2019, Vinos de Frutas Tropicales.  All rights reserved.
//
// This class, instantiated in the current customer session, keeps track of a customer's login and checkout
// progression with the aid of the OPC's observer- and AJAX-classes.
//
class OnePageCheckout extends base
{
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
    // tempBilltoAddressBookId .. Contains a sanitized/int version of the configured "temporary" bill-to address-book ID.
    // tempSendtoAddressBookId .. Contains a sanitized/int version of the configured "temporary" ship-to address-book ID.
    // dbStringType ............. Identifies the form of string data "binding" to use on $db requests; 'string' for ZC < 1.5.5b, 'stringIgnoreNull', otherwise.
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
    protected $isGuestCheckoutEnabled,
              $registeredAccounts,
              $guestIsActive,
              $isEnabled,
              $tempAddressValues,
              $guestCustomerInfo,
              $guestCustomerId,
              $tempBilltoAddressBookId,
              $tempSendtoAddressBookId,
              $dbStringType,
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
    }
    
    /* -----
    ** This function, called by the OPC's observer-class, provides the common-use debug filename.
    */
    public function getDebugLogFileName()
    {
        $customer_id = (isset($_SESSION['customer_id'])) ? $_SESSION['customer_id'] : 'na';
        return DIR_FS_LOGS . "/myDEBUG-one_page_checkout-$customer_id.log";
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
        // - No previous jQuery error on the checkout_one page has been detected.
        // - The plugin's database configuration is available and set for either
        //   - Full enablement
        //   - Conditional enablement and the current customer is in the conditional-customers list
        //
        $this->isEnabled = false;
        if (defined('CHECKOUT_ONE_ENABLED') && !isset($_SESSION['opc_error'])) {
            if (CHECKOUT_ONE_ENABLED == 'true') {
                $this->isEnabled = true;
            } elseif (CHECKOUT_ONE_ENABLED == 'conditional' && isset($_SESSION['customer_id'])) {
                if (in_array($_SESSION['customer_id'], explode(',', str_replace(' ', '', CHECKOUT_ONE_ENABLE_CUSTOMERS_LIST)))) {
                    $this->isEnabled = true;
                }
            }
        }
        return $this->isEnabled;
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
        $_SESSION['shipping_billing'] = (isset($_SESSION['shipping_billing'])) ? $_SESSION['shipping_billing'] : true;
        return $_SESSION['shipping_billing'];
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

        $addresses_query = $GLOBALS['db']->bindVars($addresses_query, ':customersID', $_SESSION['customer_id'], 'integer');
        $addresses_query = $GLOBALS['db']->bindVars($addresses_query, ':addressBookID', $_SESSION['customer_default_address_id'], 'integer');
        $default_address = $GLOBALS['db']->Execute($addresses_query);
        
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
        $this->checkEnabled();
        $this->isGuestCheckoutEnabled = !zen_is_spider_session() && (defined('CHECKOUT_ONE_ENABLE_GUEST') && CHECKOUT_ONE_ENABLE_GUEST == 'true');
        $this->guestCustomerId = (defined('CHECKOUT_ONE_GUEST_CUSTOMER_ID')) ? (int)CHECKOUT_ONE_GUEST_CUSTOMER_ID : 0;
        $this->tempBilltoAddressBookId = (defined('CHECKOUT_ONE_GUEST_BILLTO_ADDRESS_BOOK_ID')) ? (int)CHECKOUT_ONE_GUEST_BILLTO_ADDRESS_BOOK_ID : 0;
        $this->tempSendtoAddressBookId = (defined('CHECKOUT_ONE_GUEST_SENDTO_ADDRESS_BOOK_ID')) ? (int)CHECKOUT_ONE_GUEST_SENDTO_ADDRESS_BOOK_ID : 0;
        $this->registeredAccounts = (CHECKOUT_ONE_ENABLE_REGISTERED_ACCOUNTS === 'true');
        
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
        if (zen_in_guest_checkout() || $_SESSION['customer_id'] == $this->guestCustomerId) {
            unset(
                $_SESSION['customer_id'], 
                $_SESSION['customers_email_address'],
                $_SESSION['customers_authorization'],
                $_SESSION['sendto'], 
                $_SESSION['billto'],
                $_SESSION['customer_default_address_id'],
                $_SESSION['customer_country_id'],
                $_SESSION['customer_zone_id']
            );
        }
        unset(
            $_SESSION['is_guest_checkout']
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
            $_SESSION['shipping_billing'], 
            $_SESSION['opc_sendto_saved']
        );        
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
        unset($this->tempAddressValues, $this->guestCustomerInfo); 
        
        $this->initializeGuestCheckout();
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
        $this->guestIsActive = false;
        $redirect_required = false;
        if ($this->guestCheckoutEnabled()) {
            $redirect_required = ($GLOBALS['current_page_base'] == FILENAME_CHECKOUT_ONE && isset($_POST['guest_checkout']));
            if ($this->isGuestCheckout() || $redirect_required) {
                $this->guestIsActive = true;
                if (!isset($this->guestCustomerInfo)) {
                    $this->customerInfoOk = false;
                    $this->billtoTempAddrOk = false;
                    $this->sendtoTempAddrOk = false;
                    $this->guestCustomerInfo = array(
                        'firstname' => '',
                        'lastname' => '',
                        'email_address' => '',
                        'telephone' => '',
                        'dob' => ''
                    );
                    
                    // -----
                    // Allow an observer to add fields to the guest-customer's record.
                    //
                    $additional_guest_fields = array();
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

        $this->debugMessage('startGuestOnePageCheckout, exit: sendto: ' . ((isset($_SESSION['sendto'])) ? $_SESSION['sendto'] : 'not set') . ', billto: ' . ((isset($_SESSION['billto'])) ? $_SESSION['billto'] : 'not set') . var_export($this, true));
        
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
                $_SESSION['opc_sendto_saved'],
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
        }
        return (isset($this->guestCustomerInfo['dob'])) ? $this->guestCustomerInfo['dob'] : '';
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
                $check = $GLOBALS['db']->Execute(
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
            $enabled_payment_modules = array();
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
        $show_add_address = false;
        if (!zen_in_guest_checkout() && !empty($_SESSION['customer_id'])) {
            $check = $GLOBALS['db']->Execute(
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
    ** update when an order includes one or more temporary addresses (a superset of guest
    ** checkout).
    */
    public function updateOrderAddresses($order, &$taxCountryId, &$taxZoneId)
    {
        $this->debugMessage("updateOrderAddresses, on entry:" . var_export($order, true) . var_export($this, true));
        if (zen_in_guest_checkout()) {
            $address = (array)$order->customer;
            $order->customer = array_merge($address, $this->createOrderAddressFromTemporary('bill'), $this->getGuestCustomerInfo());
        }
        
        $temp_billing_address = $temp_shipping_address = false;
        if (isset($_SESSION['sendto']) && ($_SESSION['sendto'] == $this->tempSendtoAddressBookId || $_SESSION['sendto'] == $this->tempBilltoAddressBookId)) {
            $temp_shipping_address = true;
            $address = (array)$order->delivery;
            $order->delivery = array_merge($address, $this->createOrderAddressFromTemporary('ship'));
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
        $this->debugMessage("updateOrderAddresses, $temp_billing_address, $temp_shipping_address, $taxCountryId, $taxZoneId" . var_export($order->customer, true) . var_export($order->billing, true) . var_export($order->delivery, true));
    }
    
    // -----
    // This internal function returns the guest-customer information currently gathered.
    //
    protected function getGuestCustomerInfo()
    {
        if (!isset($this->guestCustomerInfo)) {
            trigger_error("Guest customer-info not set during guest checkout.", E_USER_ERROR);
        }
        return $this->guestCustomerInfo;
    }
    
    // -----
    // This internal function creates an address-array in the format used by the built-in Zen Cart
    // order-class from the selected temporary address.
    //
    protected function createOrderAddressFromTemporary($which)
    {
        $country_id = $this->tempAddressValues[$which]['country'];
        $country_info = $GLOBALS['db']->Execute(
            "SELECT *
               FROM " . TABLE_COUNTRIES . "
              WHERE countries_id = $country_id
                AND status = 1
              LIMIT 1"
        );
        if ($country_info->EOF) {
            trigger_error("Unknown or disabled country present for '$which' address ($country_id).", E_USER_ERROR);
        }
        
        $address = array(
            'firstname' => $this->tempAddressValues[$which]['firstname'],
            'lastname' => $this->tempAddressValues[$which]['lastname'],
            'company' => $this->tempAddressValues[$which]['company'],
            'street_address' => $this->tempAddressValues[$which]['street_address'],
            'suburb' => $this->tempAddressValues[$which]['suburb'],
            'city' => $this->tempAddressValues[$which]['city'],
            'postcode' => $this->tempAddressValues[$which]['postcode'],
            'state' => ((zen_not_null($this->tempAddressValues[$which]['state'])) ? $this->tempAddressValues[$which]['state'] : $this->tempAddressValues[$which]['zone_name']),
            'zone_id' => $this->tempAddressValues[$which]['zone_id'],
            'country' => array(
                'id' => $country_id, 
                'title' => $country_info->fields['countries_name'], 
                'iso_code_2' => $country_info->fields['countries_iso_code_2'], 
                'iso_code_3' => $country_info->fields['countries_iso_code_3']
            ),
            'country_id' => $country_id,
            'format_id' => (int)$country_info->fields['address_format_id']
        );
        $this->debugMessage("createOrderAddressFromTemporary($which), returning:" . var_export($address, true));
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
                } elseif ((!$use_temp_billing && $order->billing['zone_id'] == STORE_ZONE) || $order->content_type == 'virtual') {
                    $tax_country_id = $order->billing['country_id'];
                    $tax_zone_id = $order->billing['zone_id'];
                } else {
                    $tax_country_id = $order->delivery['country_id'];
                    $tax_zone_id = $order->delivery['zone_id'];
                }
                break;
        }
        
        $this->debugMessage("recalculateTaxBasis, temp_billing($use_temp_billing), temp_shipping($use_temp_shipping), returning country_id = $tax_country_id, zone_id = $tax_zone_id.");
        return array(
            'tax_country_id' => $tax_country_id,
            'tax_zone_id' => $tax_zone_id
        );
    }
    
    /* -----
    ** This function returns the Zen-Cart formatted address for the specified temporary address.
    */
    public function formatAddress($which)
    {
        $this->inputPreCheck($which);
        
        $address = $this->createOrderAddressFromTemporary($which);
        
        return zen_address_format($address['format_id'], $address, 0, '', "\n");
    }
    
    /* -----
    ** This function validates (true) or not (false) the specified order-related
    ** address ('bill' or 'ship').
    */
    public function validateBilltoSendto($which)
    {
        $this->inputPreCheck($which);
        
        // -----
        // First, determine whether the specified address is/isn't temporary.
        //
        if ($which == 'bill') {
            $address_book_id = $_SESSION['billto'];
            $is_temp_address = ($address_book_id == $this->tempBilltoAddressBookId);
        } else {
            $address_book_id = $_SESSION['sendto'];
            $is_temp_address = ($address_book_id == $this->tempSendtoAddressBookId);
            if (isset($_SESSION['shipping_billing'])) {
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
                $check_query = $GLOBALS['db']->bindVars($check_query, ':customersID', $_SESSION['customer_id'], 'integer');
                $check_query = $GLOBALS['db']->bindVars($check_query, ':addressBookID', $address_book_id, 'integer');
                $check = $GLOBALS['db']->Execute($check_query);
                $is_valid = !$check->EOF;
            }
        }
        
        // -----
        // If the address isn't valid for the current usage, reset the session's address to
        // the customer's default and kill the session-variable previously set for that
        // invalid address.
        //
        if (!$is_valid) {
            if ($which == 'bill') {
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
    ** This function resets the current session's address to the specified address-book entry.
    */
    public function setAddressFromSavedSelections($which, $address_book_id)
    {
        $this->inputPreCheck($which);
        
        if ($which == 'bill') {
            $_SESSION['billto'] = $address_book_id;
            if (isset($_SESSION['shipping_billing']) && $_SESSION['shipping_billing']) {
                $_SESSION['sendto'] = $address_book_id;
            }
        } else {
            $_SESSION['sendto'] = $address_book_id;
        }
    }
    
    public function getAddressValues($which)
    {
        $this->inputPreCheck($which);
        
        $address_book_id = (int)($which == 'bill') ? $_SESSION['billto'] : $_SESSION['sendto'];
        
        if ($address_book_id == $this->tempBilltoAddressBookId || $address_book_id == $this->tempSendtoAddressBookId) {
            $address_values = $this->tempAddressValues[$which];
        } else {
            $address_values = $this->getAddressValuesFromDb($address_book_id);
        }
        
        return $this->updateStateDropdownSettings($address_values);
    }
    
    protected function getAddressValuesFromDb($address_book_id)
    {
        $address_info_query = 
            "SELECT ab.entry_gender AS gender, ab.entry_company AS company, ab.entry_firstname AS firstname, ab.entry_lastname AS lastname, 
                    ab.entry_street_address AS street_address, ab.entry_suburb AS suburb, ab.entry_city AS city, ab.entry_postcode AS postcode, 
                    ab.entry_state AS state, ab.entry_country_id AS country, ab.entry_zone_id AS zone_id, z.zone_name, ab.address_book_id,
                    ab.address_book_id
               FROM " . TABLE_ADDRESS_BOOK . "  ab
                    LEFT JOIN " . TABLE_ZONES . " z
                        ON z.zone_id = ab.entry_zone_id
                       AND z.zone_country_id = ab.entry_country_id
              WHERE ab.customers_id = :customersID 
                AND ab.address_book_id = :addressBookID 
              LIMIT 1";
        $address_info_query = $GLOBALS['db']->bindVars($address_info_query, ':customersID', $_SESSION['customer_id'], 'integer');
        $address_info_query = $GLOBALS['db']->bindVars($address_info_query, ':addressBookID', $address_book_id, 'integer');

        $address_info = $GLOBALS['db']->Execute($address_info_query);
        if ($address_info->EOF) {
            trigger_error("unknown $which/$session_var_name address_book_id (" . $address_book_id . ') for customer_id (' . $_SESSION['customer_id'] . ')', E_USER_ERROR);
        }

        $address_info->fields['error_state_input'] = $address_info->fields['error'] = false;
        $address_info->fields['country_has_zones'] = $this->countryHasZones($address_info->fields['country']);
        
        $this->notify('NOTIFY_OPC_INIT_ADDRESS_FROM_DB', $address_book_id, $address_info->fields);
        
        $this->debugMessage("getAddressValuesFromDb($address_book_id), returning: " . var_export($address_info->fields, true)); 
        
        return $address_info->fields;
    }
    
    protected function initAddressValuesForGuest()
    {
        $address_values = array(
            'gender' => '',
            'company' => '',
            'firstname' => '',
            'lastname' => '',
            'street_address' => '',
            'suburb' => '',
            'city' => '',
            'postcode' => SHIPPING_ORIGIN_ZIP,
            'state' => '',
            'country' => (int)STORE_COUNTRY,
            'zone_id' => (int)STORE_ZONE,
            'zone_name' => zen_get_zone_name(STORE_COUNTRY, STORE_ZONE, ''),
            'address_book_id' => 0,
            'selected_country' => (int)STORE_COUNTRY,
            'country_has_zones' => $this->countryHasZones((int)STORE_COUNTRY),
            'state_field_label' => '',
            'show_pulldown_states' => false,
            'error' => false,
            'error_state_input' => false,
            'validated' => false,
        );
        $address_values = $this->updateStateDropdownSettings($address_values);
        
        $this->notify('NOTIFY_OPC_INIT_ADDRESS_FOR_GUEST', '', $address_values);
        
        return $address_values;
    }
    
    public function formatAddressBookDropdown()
    {
        $select_array = array();
        if (isset($_SESSION['customer_id']) && !$this->isGuestCheckout() && !$this->customerAccountNeedsPrimaryAddress()) {
            // -----
            // Build up address list input to create a customer-specific selection list of 
            // pre-existing addresses from which to choose.
            //
            $addresses = $GLOBALS['db']->Execute(
                "SELECT address_book_id 
                   FROM " . TABLE_ADDRESS_BOOK . " 
                  WHERE customers_id = " . (int)$_SESSION['customer_id'] . "
               ORDER BY address_book_id"
            );
            if (!$addresses->EOF) {
                $select_array[] = array(
                    'id' => 0,
                    'text' => TEXT_SELECT_FROM_SAVED_ADDRESSES
                );
            }
            while (!$addresses->EOF) {
                $select_array[] = array( 
                    'id' => $addresses->fields['address_book_id'],
                    'text' => str_replace("\n", ', ', zen_address_label($_SESSION['customer_id'], $addresses->fields['address_book_id']))
                );
                $addresses->MoveNext();
            }
        }
        return $select_array;
    }
    
    public function getAddressDropDownSelection($which)
    {
        $this->inputPreCheck($which);
        
        if ($which == 'bill') {
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
        $countries = $GLOBALS['db']->Execute(
            "SELECT DISTINCT zone_country_id
               FROM " . TABLE_ZONES . "
                    INNER JOIN " . TABLE_COUNTRIES . "
                        ON countries_id = zone_country_id
                       AND status = 1
           ORDER BY zone_country_id"
        );
        
        $c2z = array();
        while (!$countries->EOF) {
            $current_country_id = $countries->fields['zone_country_id'];
            $c2z[$current_country_id] = array();

            $states = $GLOBALS['db']->Execute(
                "SELECT zone_name, zone_id
                   FROM " . TABLE_ZONES . "
                  WHERE zone_country_id = $current_country_id
               ORDER BY zone_name"
            );
            while (!$states->EOF) {
                $c2z[$current_country_id][$states->fields['zone_id']] = $states->fields['zone_name'];
                $states->MoveNext();
            }
            $countries->MoveNext();
        }
        
        if (count($c2z) == 0) {
            $output_string = '';
        } else {
            $output_string = 'var c2z = \'' . json_encode($c2z) . '\';' . PHP_EOL;
        }
        return $output_string;
    }
    
    protected function initializeTempAddressValues()
    {
        if (!isset($this->tempAddressValues)) {
            $this->tempAddressValues = array(
                'ship' => $this->initAddressValuesForGuest(),
                'bill' => $this->initAddressValuesForGuest()
            );
        }
    }
    
    protected function countryHasZones($country_id)
    {
        $check = $GLOBALS['db']->Execute(
            "SELECT zone_id
               FROM " . TABLE_ZONES . "
              WHERE zone_country_id = $country_id
              LIMIT 1"
        );
        return !$check->EOF;
    }
    
    protected function updateStateDropdownSettings($address_values)
    {
        $show_pulldown_states = ($address_values['zone_name'] == '' && $address_values['country_has_zones']) || ACCOUNT_STATE_DRAW_INITIAL_DROPDOWN == 'true' || $address_values['error_state_input'];
        $address_values['selected_country'] = $address_values['country'];
        $address_values['state'] = ($show_pulldown_states) ? $address_values['state'] : $address_values['zone_name'];
        $address_values['state_field_label'] = ($show_pulldown_states) ? '' : ENTRY_STATE;
        $address_values['show_pulldown_states'] = $show_pulldown_states;
        
        return $address_values;
    }
    
    public function validatePostedAddress($which)
    {
        $this->inputPreCheck($which);
        
        $messages = $this->validateUpdatedAddress($_POST[$which], $which);
        if (!$_POST[$which]['validated']) {
            foreach ($messages as $field_name => $message) {
                $GLOBALS['messageStack']->add_session('addressbook', $message, 'error');
            }
        }
        return !$_POST[$which]['validated'];
    }
    
    public function formatAddressElement($which, $field_name, $field_value, $field_text, $db_table, $db_fieldname, $min_length, $placeholder)
    {
        $this->inputPreCheck($which);
        
        $field_id = str_replace('_', '-', $field_name) . "-$which";
        $field_name .= "[$which]";
        $field_len = zen_set_field_length($db_table, $db_fieldname, '40');
        $field_required = (((int)$min_length) > 0) ? ' required' : '';
        
        return
            '<label class="inputLabel" for="' . $field_id . '">' . $field_text . '</label>' . PHP_EOL .
            zen_draw_input_field($field_name, $field_value, "$field_len id=\"$field_id\" placeholder=\"$placeholder\" $field_required") . PHP_EOL .
            '<br class="clearBoth" />';
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
            $this->saveCustomerAddress($address_info, $which, (isset($_POST['add_address']) && $_POST['add_address'] === 'true'));
        }
        
        return !$address_info['validated'];
    }
    
    public function validateAndSaveAjaxCustomerInfo()
    {
        if (!isset($_POST['email_address'])) {
            trigger_error('validateAndSaveAjaxCustomerInfo, invalid POST: ' . var_export($_POST, true), E_USER_ERROR);
        }
        
        $messages = array();
        $this->customerInfoOk = false;
        
        $email_address = zen_db_prepare_input(zen_sanitize_string($_POST['email_address']));
        if (strlen($email_address) < ENTRY_EMAIL_ADDRESS_MIN_LENGTH) {
            $messages['email_address'] = ENTRY_EMAIL_ADDRESS_ERROR;
        } elseif (!zen_validate_email($email_address) || $this->isEmailBanned($email_address)) {
            $messages['email_address'] = ENTRY_EMAIL_ADDRESS_CHECK_ERROR;
        } elseif (CHECKOUT_ONE_GUEST_EMAIL_CONFIRMATION == 'true') {
            $email_confirm = zen_db_prepare_input(zen_sanitize_string($_POST['email_address_conf']));
            if ($email_confirm != $email_address) {
                $messages['email_address_conf'] = ERROR_EMAIL_MUST_MATCH_CONFIRMATION;
            }
        }
        
        $telephone = zen_db_prepare_input(zen_sanitize_string($_POST['telephone']));
        if (strlen($telephone) < ENTRY_TELEPHONE_MIN_LENGTH) {
            $messages['telephone'] = ENTRY_TELEPHONE_NUMBER_ERROR;
        }
        
        $dob = '';
        if (ACCOUNT_DOB == 'true') {
            $dob = zen_db_prepare_input($_POST['dob']);
            if (ENTRY_DOB_MIN_LENGTH > 0 or !empty($_POST['dob'])) {
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
        $additional_messages = array();
        $additional_fields = array();
        $this->notify('NOTIFY_OPC_VALIDATE_SAVE_GUEST_INFO', '', $additional_messages, $additional_fields);
        if (is_array($additional_messages) && is_array($additional_fields) && (count($additional_messages) != 0 || count($additional_fields) != 0)) {
            $this->debugMessage('validateAndSaveAjaxCustomerInfo, additional messages (' . json_encode($additional_messages) . '), additional fields (' . json_encode($additional_fields) . ')');
            $messages = array_merge($messages, $additional_messages);
        }
        
        if (count($messages) == 0) {
            $this->customerInfoOk = true;
            $this->guestCustomerInfo['email_address'] = $email_address;
            $this->guestCustomerInfo['telephone'] = $telephone;
            $this->guestCustomerInfo['dob'] = $dob;
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
        $email_address = $GLOBALS['db']->prepare_input($email_address);
        $check = $GLOBALS['db']->Execute(
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
        if ($which != 'bill' && $which != 'ship') {
            trigger_error("Unknown address selection ($which) received.", E_USER_ERROR);
        }
        if (!isset($this->tempAddressValues)) {
            trigger_error("Invalid request, tempAddressValues not set.", E_USER_ERROR);
        }
    }
    
    // -----
    // This internal function validates (and potentially updates) the supplied address-values information,
    // returning an array of messages identifying "issues" found.  If the returned array is
    // empty, no "issues" were found.
    //
    protected function validateUpdatedAddress(&$address_values, $which, $prepend_which = true)
    {
        $error = false;
        $zone_id = 0;
        $zone_name = '';
        $error_state_input = false;
        $entry_state_has_zones = false;
        $messages = array();
        
        $message_prefix = ($prepend_which) ? (($which == 'bill') ? ERROR_IN_BILLING : ERROR_IN_SHIPPING) : '';
        
        $this->debugMessage("Start validateUpdatedAddress, which = $which:" . var_export($address_values, true));
        
        if ($which == 'bill') {
            $this->billtoTempAddrOk = false;
        } else {
            $this->sendtoTempAddrOk = false;
        }
        
        $gender = false;
        $company = '';
        $suburb = '';
        
        if (ACCOUNT_COMPANY == 'true') {
            $company = zen_db_prepare_input($_POST['company']);
            if (((int)ENTRY_COMPANY_MIN_LENGTH > 0) && strlen($company) < ((int)ENTRY_COMPANY_MIN_LENGTH)) {
                $error = true;
                $messages['company'] = $message_prefix . ENTRY_COMPANY_ERROR;
            }
        }

        if (ACCOUNT_GENDER == 'true') {
          $gender = zen_db_prepare_input($address_values['gender']);
            if ($gender != 'm' && $gender != 'f') {
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
        
        if (ACCOUNT_SUBURB == 'true') {
            $suburb = zen_db_prepare_input($_POST['suburb']);
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
        if (!is_numeric($country)) {
            $error = true;
            $messages['zone_country_id'] = $message_prefix . ENTRY_COUNTRY_ERROR;
        } elseif (ACCOUNT_STATE == 'true') {
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

                $zone_query = $GLOBALS['db']->bindVars($zone_query, ':zoneCountryID', $country, 'integer');
                $zone_query = $GLOBALS['db']->bindVars($zone_query, ':zoneState', strtoupper($state), 'noquotestring');
                $zone_query = $GLOBALS['db']->bindVars($zone_query, ':zoneID', $zone_id, 'integer');
                $zone = $GLOBALS['db']->Execute($zone_query);

                //look for an exact match on zone ISO code
                $found_exact_iso_match = ($zone->RecordCount() == 1);
                if ($zone->RecordCount() > 1) {
                    while (!$zone->EOF) {
                        if (strtoupper($zone->fields['zone_code']) == strtoupper($state) ) {
                            $found_exact_iso_match = true;
                            break;
                        }
                        $zone->MoveNext();
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

        if ($error) {
            $address_values['validated'] = false;
        } else {
            $address_values = array_merge(
                $address_values,
                array(
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
                    'show_pulldown_states' => false,
                    'error' => false,
                    'validated' => true
                )
            );
            $address_values = $this->updateStateDropdownSettings($address_values);
            if ($which == 'bill') {
                $this->billtoTempAddrOk = true;
            } else {
                $this->sendtoTempAddrOk = true;
            }
        }
        
        $this->debugMessage('Exiting validateUpdatedAddress.' . var_export($messages, true) . var_export($address_values, true));
        return $messages;
    }
    
    // -----
    // This internal function saves the requested (and previously validated!) address,
    // either to a temporary, in-session, value or to the database.
    //
    protected function saveCustomerAddress($address, $which, $add_address = false)
    {
        $this->debugMessage("saveCustomerAddress($which, $add_address), " . ((isset($_SESSION['shipping_billing']) && $_SESSION['shipping_billing']) ? 'shipping=billing' : 'shipping!=billing') . ' ' . var_export($address, true));
        
        // -----
        // If the address is **not** to be added to the customer's address book or if
        // guest-checkout is currently active, the updated address is stored in
        // a temporary address-book record.
        //
        if (!$add_address || $this->isGuestCheckout()) {
            $this->tempAddressValues[$which] = $address;
            if ($which == 'ship') {
                $_SESSION['sendto'] = $this->tempSendtoAddressBookId;
            } else {
                if ($this->isGuestCheckout()) {
                    $this->guestCustomerInfo['firstname'] = $address['firstname'];
                    $this->guestCustomerInfo['lastname'] = $address['lastname'];
                }
                $_SESSION['billto'] = $this->tempBilltoAddressBookId;
                if (isset($_SESSION['shipping_billing']) && $_SESSION['shipping_billing']) {
                    $_SESSION['sendto'] = $this->tempSendtoAddressBookId;
                    $this->tempAddressValues['ship'] = $this->tempAddressValues['bill'];
                }
            }
            $this->debugMessage("Updated tempAddressValues[$which], billing=shipping(" . $_SESSION['shipping_billing'] . "), sendto(" . $_SESSION['sendto'] . "), billto(" . $_SESSION['billto'] . "):" . var_export($this->tempAddressValues, true));
        // -----
        // Otherwise, the address is to be saved in the database ...
        //
        } else {
            // -----
            // Build up the to-be-stored address.
            //
            $sql_data_array = array(
                array('fieldName' => 'entry_firstname', 'value' => $address['firstname'], 'type' => $this->dbStringType),
                array('fieldName' => 'entry_lastname', 'value' => $address['lastname'], 'type' => $this->dbStringType),
                array('fieldName' => 'entry_street_address', 'value' => $address['street_address'], 'type' => $this->dbStringType),
                array('fieldName' => 'entry_postcode', 'value' => $address['postcode'], 'type' => $this->dbStringType),
                array('fieldName' => 'entry_city', 'value' => $address['city'], 'type' => $this->dbStringType),
                array('fieldName' => 'entry_country_id', 'value' => $address['country'], 'type' => 'integer')
            );

            if (ACCOUNT_GENDER == 'true') {
                $sql_data_array[] = array('fieldName' => 'entry_gender', 'value' => $address['gender'], 'type' => 'enum:m|f');
            }
            
            if (ACCOUNT_COMPANY == 'true') {
                $sql_data_array[] = array('fieldName' => 'entry_company', 'value' => $address['company'], 'type' => $this->dbStringType);
            }
            
            if (ACCOUNT_SUBURB == 'true') {
                $sql_data_array[] = array('fieldName' => 'entry_suburb', 'value' => $address['suburb'], 'type' => $this->dbStringType);
            }
            
            if (ACCOUNT_STATE == 'true') {
                if ($address['zone_id'] > 0) {
                    $sql_data_array[] = array('fieldName' => 'entry_zone_id', 'value' => $address['zone_id'], 'type' => 'integer');
                    $sql_data_array[] = array('fieldName' => 'entry_state', 'value'=> '', 'type' => $this->dbStringType);
                } else {
                    $sql_data_array[] = array('fieldName' => 'entry_zone_id', 'value' => '0', 'type' => 'integer');
                    $sql_data_array[] = array('fieldName' => 'entry_state', 'value' => $address['state'], 'type' => $this->dbStringType);
                }
            }
            
            // -----
            // If a matching address-book entry is found for this logged-in customer, use that pre-saved
            // address entry.  Otherwise, save the new address for the customer.
            //
            $existing_address_book_id = $this->findAddressBookEntry($address);
            if ($existing_address_book_id !== false) {
                $address_book_id = $existing_address_book_id;
            } else {
                $sql_data_array[] = array('fieldName' => 'customers_id', 'value' => $_SESSION['customer_id'], 'type'=>'integer');
                $GLOBALS['db']->perform(TABLE_ADDRESS_BOOK, $sql_data_array);
                $address_book_id = $GLOBALS['db']->Insert_ID();
                
                $this->notify('NOTIFY_OPC_ADDED_ADDRESS_BOOK_RECORD', array('address_book_id' => $address_book_id), $sql_data_array);
            }
            
            // -----
            // Update the session's billto/sendto address based on the previous processing.
            //
            if ($which == 'bill') {
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
        $validated = true;
        if ($this->isGuestCheckout() && !$this->customerInfoOk) {
            $validated = false;
        }
        
        if (!empty($_SESSION['billto']) && $_SESSION['billto'] == $this->tempBilltoAddressBookId && !$this->billtoTempAddrOk) {
            $validated = false;
        }
        
        if (!empty($_SESSION['sendto']) && $_SESSION['sendto'] == $this->tempSendtoAddressBookId && !$this->sendtoTempAddrOk) {
            $validated = false;
        }
        $this->debugMessage("validateTemporaryEntries, on entry ({$this->customerInfoOk}, {$this->billtoTempAddrOk}, {$this->sendtoTempAddrOk}), returning ($validated).");
        return $validated;
    }
    
    /* -----
    ** This function, called from the 'checkout_success' OPC header processing, creates a
    ** customer-account from the information associated with the just-placed order.
    */
    public function createAccountFromGuestInfo($order_id, $password, $newsletter)
    {
        $password_error = (strlen((string)$password) < ENTRY_PASSWORD_MIN_LENGTH);
        if (!$this->guestIsActive || !isset($this->guestCustomerInfo) || !isset($this->tempAddressValues) || $password_error) {
            trigger_error("Invalid access ($password_error):" . var_export($this, true), E_USER_ERROR);
        }
        
        $customer_id = $this->createCustomerRecordFromGuestInfo($password, $newsletter);
        $_SESSION['customer_id'] = $customer_id;
        $GLOBALS['db']->Execute(
            "UPDATE " . TABLE_ORDERS . "
                SET customers_id = $customer_id
              WHERE orders_id = " . (int)$order_id . "
              LIMIT 1"
        );
        
        $default_address_id = $this->createAddressBookRecord($customer_id, 'bill');
        $GLOBALS['db']->Execute(
            "UPDATE " . TABLE_CUSTOMERS . "
                SET customers_default_address_id = $default_address_id
              WHERE customers_id = $customer_id
              LIMIT 1"
        );
        
        if ($this->tempAddressValues['ship']['firstname'] != '') {
            if ($this->addressArrayToString($this->tempAddressValues['ship']) != $this->addressArrayToString($this->tempAddressValues['bill'])) {
                $this->createAddressBookRecord($customer_id, 'ship');
            }
        }
        
        unset(
            $_SESSION['sendto'], 
            $_SESSION['billto'],
            $_SESSION['is_guest_checkout'],
            $_SESSION['shipping_billing'], 
            $_SESSION['opc_sendto_saved'],
            $_SESSION['order_placed_by_guest']
        );
        
        $_SESSION['customer_first_name'] = $this->guestCustomerInfo['firstname'];
        $_SESSION['customer_last_name'] = $this->guestCustomerInfo['lastname'];
        $_SESSION['customer_default_address_id'] = $default_address_id;
        $_SESSION['customer_country_id'] = $this->tempAddressValues['bill']['country'];
        $_SESSION['customer_zone_id'] = $this->tempAddressValues['bill']['zone_id'];
        $_SESSION['customers_authorization'] = 0;
        
        $this->reset();
    }
    
    // -----
    // This internal function creates a customer record in the database for the
    // current guest.
    //
    protected function createCustomerRecordFromGuestInfo($password, $newsletter)
    {
        $sql_data_array = array(
            array('fieldName' => 'customers_firstname', 'value' => $this->guestCustomerInfo['firstname'], 'type' => $this->dbStringType),
            array('fieldName' => 'customers_lastname', 'value' => $this->guestCustomerInfo['lastname'], 'type' => $this->dbStringType),
            array('fieldName' => 'customers_email_address', 'value' => $this->guestCustomerInfo['email_address'], 'type' => $this->dbStringType),
            array('fieldName' => 'customers_telephone', 'value' => $this->guestCustomerInfo['telephone'], 'type' => $this->dbStringType),
            array('fieldName' => 'customers_newsletter', 'value' => $newsletter, 'type' => 'integer'),
            array('fieldName' => 'customers_email_format', 'value' => 'TEXT', 'type' => $this->dbStringType),
            array('fieldName' => 'customers_default_address_id', 'value' => 0, 'type' => 'integer'),
            array('fieldName' => 'customers_password', 'value' => zen_encrypt_password($password), 'type' => $this->dbStringType),
            array('fieldName' => 'customers_authorization', 'value' => 0, 'type' => 'integer'),
        );

        if (ACCOUNT_GENDER == 'true') {
            $sql_data_array[] = array('fieldName' => 'customers_gender', 'value' => $gender, 'type' => $this->dbStringType);
        }
        if (ACCOUNT_DOB == 'true') {
            $dob = $this->guestCustomerInfo['dob'];
            $dob = (empty($dob) || $dob == '0001-01-01 00:00:00') ? zen_db_prepare_input('0001-01-01 00:00:00') : zen_date_raw($dob);
            $sql_data_array[] = array('fieldName' => 'customers_dob', 'value' => $dob, 'type' => 'date');
        }

        $GLOBALS['db']->perform(TABLE_CUSTOMERS, $sql_data_array);
        $customer_id = $GLOBALS['db']->Insert_ID();
        
        $GLOBALS['db']->Execute(
            "INSERT INTO " . TABLE_CUSTOMERS_INFO . "
                    (customers_info_id, customers_info_number_of_logons, customers_info_date_account_created, customers_info_date_of_last_logon)
                VALUES 
                    ($customer_id, 1, now(), now())"
        );
        
        $this->notify('OPC_ADDED_CUSTOMER_RECORD_FOR_GUEST', $customer_id, $sql_data_array);

        return $customer_id;
    }
    
    // -----
    // This internal function creates an address-book record in the database using one of the 
    // temporary address-book records.
    //
    protected function createAddressBookRecord($customer_id, $which)
    {
        $sql_data_array = array(
            array('fieldName' => 'customers_id', 'value' => $customer_id, 'type' => 'integer'),
            array('fieldName' => 'entry_firstname', 'value' => $this->tempAddressValues[$which]['firstname'], 'type' => $this->dbStringType),
            array('fieldName' => 'entry_lastname', 'value' => $this->tempAddressValues[$which]['lastname'], 'type' => $this->dbStringType),
            array('fieldName' => 'entry_street_address', 'value' => $this->tempAddressValues[$which]['street_address'], 'type' => $this->dbStringType),
            array('fieldName' => 'entry_postcode', 'value' => $this->tempAddressValues[$which]['postcode'], 'type' => $this->dbStringType),
            array('fieldName' => 'entry_city', 'value' => $this->tempAddressValues[$which]['city'], 'type' => $this->dbStringType),
            array('fieldName' => 'entry_country_id', 'value' => $this->tempAddressValues[$which]['country'], 'type' => 'integer'),
        );

        if (ACCOUNT_GENDER == 'true') {
            $sql_data_array[] = array('fieldName' => 'entry_gender', 'value' => $this->tempAddressValues[$which]['gender'], 'type' => $this->dbStringType);
        }
        if (ACCOUNT_COMPANY == 'true') {
            $sql_data_array[] = array('fieldName' => 'entry_company', 'value' => $this->tempAddressValues[$which]['company'], 'type' => $this->dbStringType);
        }
        if (ACCOUNT_SUBURB == 'true') {
            $sql_data_array[] = array('fieldName' => 'entry_suburb', 'value' => $this->tempAddressValues[$which]['suburb'], 'type' => $this->dbStringType);
        }

        if (ACCOUNT_STATE == 'true') {
            if ($this->tempAddressValues[$which]['zone_id'] > 0) {
                $sql_data_array[] = array('fieldName' => 'entry_zone_id', 'value' => $this->tempAddressValues[$which]['zone_id'], 'type' => 'integer');
                $sql_data_array[] = array('fieldName' => 'entry_state', 'value' => '', 'type' => $this->dbStringType);
            } else {
                $sql_data_array[] = array('fieldName' => 'entry_zone_id', 'value' => 0, 'type' => 'integer');
                $sql_data_array[] = array('fieldName' => 'entry_state', 'value' => $this->tempAddressValues[$which]['state'], 'type' => $this->dbStringType);
            }
        }
        $GLOBALS['db']->perform(TABLE_ADDRESS_BOOK, $sql_data_array);
        $address_book_id = $GLOBALS['db']->Insert_ID();
        
        $this->notify('NOTIFY_OPC_CREATED_ADDRESS_BOOK_DB_ENTRY', $address_book_id, $sql_data_array);

        return $address_book_id;
    }
    
    // -----
    // This internal function checks the database to see if the specified address already exists
    // for the customer associated with the current session.
    //
    protected function findAddressBookEntry($address)
    {
        $country_id = $address['country'];
        $country_has_zones = $address['country_has_zones'];

        // do a match on address, street, street2, city
        $sql = 
            "SELECT address_book_id, entry_street_address AS street_address, entry_suburb AS suburb, entry_city AS city, 
                    entry_postcode AS postcode, entry_firstname AS firstname, entry_lastname AS lastname
               FROM " . TABLE_ADDRESS_BOOK . "
              WHERE customers_id = :customerId
                AND entry_country_id = $country_id";
        if (!$country_has_zones) {
            $sql .= " AND entry_state = :stateValue LIMIT 1";
        } else {
            $sql .= " AND entry_zone_id = :zoneId LIMIT 1";
        }
        $sql = $GLOBALS['db']->bindVars($sql, ':zoneId', $address['zone_id'], 'integer');
        $sql = $GLOBALS['db']->bindVars($sql, ':stateValue', $address['state'], 'string');
        $sql = $GLOBALS['db']->bindVars($sql, ':customerId', $_SESSION['customer_id'], 'integer');
        $possible_addresses = $GLOBALS['db']->Execute($sql);
        
        $address_book_id = false;  //-Identifies that no match was found
        $address_to_match = $this->addressArrayToString($address);
        while (!$possible_addresses->EOF) {
            if ($address_to_match == $this->addressArrayToString($possible_addresses->fields)) {
                $address_book_id = $possible_addresses->fields['address_book_id'];
                break;
            }
            $possible_addresses->MoveNext();
        }
        $this->debugMessage("findAddressBookEntry, returning ($address_book_id) for '$address_to_match'" . var_export($address, true));
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
            $address_array['firstname'] . 
            $address_array['lastname'] . 
            $address_array['street_address'] . 
            $address_array['suburb'] . 
            $address_array['city'] . 
            $address_array['postcode'];
        $the_address = strtolower(str_replace(array("\n", "\t", "\r", "\0", ' ', ',', '.'), '', $the_address));
        
        return $the_address;
    }

    // -----
    // This internal function issues a debug-message using the OPC's observer-class
    // function.  This allows the various messages to be consolidated into a single
    // log-file for easier troubleshooting.
    //
    protected function debugMessage($message, $include_request = false)
    {
        if (isset($GLOBALS['checkout_one'])) {
            $GLOBALS['checkout_one']->debug_message($message, $include_request, 'OnePageCheckout');
        }
    }
}
