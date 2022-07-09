<?php
// -----
// Part of the One-Page Checkout plugin, provided under GPL 2.0 license by lat9
// Copyright (C) 2013-2022, Vinos de Frutas Tropicales.  All rights reserved.
//
if (!defined('IS_ADMIN_FLAG')) {
    die('Illegal Access');
}

// -----
// This module is required by the base 'init_checkout_one.php' installation script when it finds
// that the One-Page Checkout plugin has been upgraded.
//
switch (true) {
    case version_compare(CHECKOUT_ONE_MODULE_VERSION, '1.1.0', '<'):
        // -----
        // v1.1.0:  Update the 'Enable' setting to include a value that is conditional on the newly-added customer-id list.
        //
        $db->Execute(
            "UPDATE " . TABLE_CONFIGURATION . "
                SET configuration_description = 'Enable the one-page checkout processing for your store? Choose <em>true</em> to enable for all customers, <em>false</em> to disable or <em>conditional</em> to enable the processing only for customers identified by <b>Enable: Customer List</b>.<br><br>Default: <b>false</b>',
                    set_function = 'zen_cfg_select_option(array(\'true\', \'conditional\', \'false\'),',
                    last_modified = now()
              WHERE configuration_key = 'CHECKOUT_ONE_ENABLED'
              LIMIT 1"
        );
        $db->Execute(
            "INSERT IGNORE INTO " . TABLE_CONFIGURATION . "
                ( configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, date_added, sort_order, use_function, set_function ) 
                VALUES 
                ( 'Enable: Customer List', 'CHECKOUT_ONE_ENABLE_CUSTOMERS_LIST', '', 'When you <em>conditionally</em> enable the plugin, use this setting to limit the customers for which the plugin is enabled.  Leave the setting blank (the default) to <em>disable</em> the plugin for all customers or identify a comma-separated list of customer_id values for whom the plugin is to be <em>enabled</em>.<br>', $cgi, now(), 11, NULL, NULL)"
        );

    case version_compare(CHECKOUT_ONE_MODULE_VERSION, '1.3.0', '<'):    //-Fall-through processing from above
        $db->Execute(
            "INSERT IGNORE INTO " . TABLE_CONFIGURATION . " 
                ( configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, date_added, sort_order, use_function, set_function ) 
                VALUES 
                ( 'Enable Shipping=Billing?', 'CHECKOUT_ONE_ENABLE_SHIPPING_BILLING', 'true', 'Do you want to enable the <em>Shipping Address, same as Billing</em> for your store?<br><br>Default: <b>true</b>', $cgi, now(), 20, NULL, 'zen_cfg_select_option(array(\'true\', \'false\'),')"
        );
        $db->Execute(
            "INSERT IGNORE INTO " . TABLE_CONFIGURATION . "
                ( configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, date_added, sort_order, use_function, set_function ) 
                VALUES 
                ( 'Payment Methods Requiring Confirmation', 'CHECKOUT_ONE_CONFIRMATION_REQUIRED', 'eway_rapid,stripepay,gps', 'Identify (using a comma-separated list) the payment modules on your store that require confirmation.  If your store requires confirmation on all orders, simply list all payment modules used by your store.<br><br>Default: <code>eway_rapid,stripepay,gps</code>', $cgi, now(), 21, NULL, NULL)"
        );

    case version_compare(CHECKOUT_ONE_MODULE_VERSION, '1.5.0', '<'):    //-Fall-through processing from above
        $db->Execute(
            "INSERT IGNORE INTO " . TABLE_CONFIGURATION . " 
                ( configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, date_added, sort_order, use_function, set_function ) 
                VALUES 
                ( 'Load Minified Script Files?', 'CHECKOUT_ONE_MINIFIED_SCRIPT', 'true', 'Should the plugin load the minified version of its jQuery scripts, reducing the page-load time for the <code>checkout_one</code> page?<br><br>Default: <b>true</b>.', $cgi, now(), 25, NULL, 'zen_cfg_select_option(array(\'true\', \'false\'),')"
        );

    // -----
    // v2.0.0:
    //
    // - Various guest-checkout options.
    // - Update debug-related sort-orders to "make room" for the guest-checkout options.
    // - Add 'is_guest_order' field to the orders table in the database.
    //
    case version_compare(CHECKOUT_ONE_MODULE_VERSION, '2.0.0', '<'):    //-Fall-through processing from above
        $db->Execute(
            "INSERT IGNORE INTO " . TABLE_CONFIGURATION . " 
                ( configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, date_added, sort_order, use_function, set_function ) 
                VALUES 
                ( 'Order Status: Slamming Control', 'CHECKOUT_ONE_ORDER_STATUS_SLAM_COUNT', '3', 'Identify the number of back-to-back errors that your store allows when a customer is checking their order\\'s status via the <code>order_status</code> page (default: <b>3</b>).  When the customer has reached that threshold, they will be redirected to the <code>time_out</code> page.<br><br>', $cgi, now(), 25, NULL, NULL)"
        );
        $db->Execute(
            "INSERT IGNORE INTO " . TABLE_CONFIGURATION . " 
                ( configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, date_added, sort_order, use_function, set_function ) 
                VALUES 
                ( 'Enable Guest Checkout?', 'CHECKOUT_ONE_ENABLE_GUEST', 'false', 'Do you want to enable <em>Guest Checkout</em> for your store?<br><br>Default: <b>false</b>', $cgi, now(), 30, NULL, 'zen_cfg_select_option(array(\'true\', \'false\'),')"
        );
        $db->Execute(
            "INSERT IGNORE INTO " . TABLE_CONFIGURATION . " 
                ( configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, date_added, sort_order, use_function, set_function ) 
                VALUES 
                ( 'Guest Checkout: Require Email Confirmation?', 'CHECKOUT_ONE_GUEST_EMAIL_CONFIRMATION', 'true', 'Should a guest-customer be required to confirm their email address when placing an order?<br><br>Default: <b>true</b>', $cgi, now(), 40, NULL, 'zen_cfg_select_option(array(\'true\', \'false\'),')"
        );
        $db->Execute(
            "INSERT IGNORE INTO " . TABLE_CONFIGURATION . "
                ( configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, date_added, sort_order, use_function, set_function ) 
                VALUES 
                ( 'Guest Checkout: Pages Allowed Post-Checkout', 'CHECKOUT_ONE_GUEST_POST_CHECKOUT_PAGES_ALLOWED', 'download', 'Identify (using a comma-separated list, intervening blanks are OK) the pages that are <em>allowed</em> once a guest has completed their checkout.  When the guest navigates from the <code>checkout_success</code> page to any page <em><b>not in this list</b></em>, their guest-customer session is reset.<br><br>For example, if your store provides a pop-up print invoice, you would include the name of that page in this list.<br>', $cgi, now(), 50, NULL, NULL)"
        );
        $db->Execute(
            "INSERT IGNORE INTO " . TABLE_CONFIGURATION . "
                ( configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, date_added, sort_order, use_function, set_function ) 
                VALUES 
                ( 'Guest Checkout: Disallowed Pages', 'CHECKOUT_ONE_GUEST_PAGES_DISALLOWED', 'account, account_edit, account_history, account_history_info, account_newsletters, account_notifications, account_password, address_book, address_book_process, create_account_success, gv_redeem, gv_send, product_reviews_write, unsubscribe', 'Identify (using a comma-separated list, intervening blanks are OK) the pages that are <em>disallowed</em> during guest-checkout.<br><br>These pages <em>normally</em> require a logged-in customer prior to display, e.g. <code>account</code>.  <b>Do not</b> include the <code>login</code>, <code>create_account</code>, <code>password_forgotten</code> or <code>logoff</code> pages in this list!<br>', $cgi, now(), 100, NULL, NULL)"
        );
        $db->Execute(
            "INSERT IGNORE INTO " . TABLE_CONFIGURATION . "
                ( configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, date_added, sort_order, use_function, set_function ) 
                VALUES 
                ( 'Guest Checkout: Disallowed <em>Credit Class</em> Order-Totals', 'CHECKOUT_ONE_ORDER_TOTALS_DISALLOWED_FOR_GUEST', 'ot_gv', 'Identify (using a comma-separated list, intervening blanks are OK) any <em>credit-class</em> order-totals that are <em>disallowed</em> during guest-checkout.<br><br>These order-totals <em>normally</em> require a customer-account for their processing, e.g. <code>ot_gv</code>.<br>', $cgi, now(), 105, NULL, NULL)"
        );
        $db->Execute(
            "INSERT IGNORE INTO " . TABLE_CONFIGURATION . "
                ( configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, date_added, sort_order, use_function, set_function ) 
                VALUES 
                ( 'Guest Checkout: Disallowed Payment Methods', 'CHECKOUT_ONE_PAYMENTS_DISALLOWED_FOR_GUEST', 'moneyorder, cod', 'Identify (using a comma-separated list, intervening blanks are OK) any payment methods that are <em>disallowed</em> during guest-checkout.<br><br>These payment methods <em>normally</em> have no validation of purchase &mdash; e.g. <code>moneyorder</code> and <code>cod</code> &mdash; and can, if left enabled, result in unwanted <em>spam purchases</em>.<br>', $cgi, now(), 110, NULL, NULL)"
        );
        $db->Execute(
            "INSERT IGNORE INTO " . TABLE_CONFIGURATION . "
                ( configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, date_added, sort_order, use_function, set_function ) 
                VALUES 
                ( 'Enable Account Registration?', 'CHECKOUT_ONE_ENABLE_REGISTERED_ACCOUNTS', 'false', 'Do you want your store\\'s <code>create_account</code> processing to create a <em>registered</em> rather than a <em>full</em> account?<br><br>Default: <b>false</b>', $cgi, now(), 500, NULL, 'zen_cfg_select_option(array(\'true\', \'false\'),')"
        );
        $db->Execute(
            "INSERT IGNORE INTO " . TABLE_CONFIGURATION . "
                ( configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, date_added, sort_order, use_function, set_function ) 
                VALUES 
                ( 'Login-Page Layout', 'CHECKOUT_ONE_LOGIN_LAYOUT', 'L;P,G,C;B', 'When you enable the plugin\\'s <em>Guest Checkout</em> and/or <em>Account Registration</em>, an alternate formatting of the storefront <code>login</code> page is displayed.  Use this setting to control the 3-column layout of that modified page.<br><br>The value is an encoded string, identifying which block should be displayed in which column.  Columns are delimited by a semi-colon (;) and the top-to-bottom column layout is in the order specified by the block-elements\\' left-to-right order.<br><br>The block elements are:<ul><li><b>L</b> ... (required) The email/password login block.</li><li><b>P</b> ... (optional) The PayPal Express Checkout shortcut-button block.</li><li><b>G</b> ... (required) The guest-checkout block.</li><li><b>C</b> ... (required) The create-account block.</li><li><b>B</b> ... (optional) The \"Account Benefits\" block.</li></ul>Default: <b>L;P,G,C;B</b>', $cgi, now(), 26, NULL, NULL)"
        );
        $db->Execute(
            "UPDATE " . TABLE_CONFIGURATION . "
                SET sort_order = 1000
              WHERE configuration_key = 'CHECKOUT_ONE_DEBUG'
              LIMIT 1"
        );
        $db->Execute(
            "UPDATE " . TABLE_CONFIGURATION . "
                SET sort_order = 1001
              WHERE configuration_key = 'CHECKOUT_ONE_DEBUG_EXTRA'
              LIMIT 1"
        );

        if (!$sniffer->field_exists(TABLE_ORDERS, 'is_guest_order')) {
            $db->Execute("ALTER TABLE " . TABLE_ORDERS . " ADD COLUMN is_guest_order tinyint(1) NOT NULL default 0");
        }

    // -----
    // v2.0.4:
    //
    // - If the CHECKOUT_ONE_GUEST_POST_CHECKOUT_PAGES_ALLOWED setting does not include the 'download' page, add it!
    //
    case version_compare(CHECKOUT_ONE_MODULE_VERSION, '2.0.4', '<'):    //-Fall-through processing from above
        if (defined('CHECKOUT_ONE_GUEST_POST_CHECKOUT_PAGES_ALLOWED') && strpos(CHECKOUT_ONE_GUEST_POST_CHECKOUT_PAGES_ALLOWED, 'download') === false) {
            if (CHECKOUT_ONE_GUEST_POST_CHECKOUT_PAGES_ALLOWED === '') {
                $checkout_pages = [];
            } else {
                $checkout_pages = explode(',', str_replace(' ', '', CHECKOUT_ONE_GUEST_POST_CHECKOUT_PAGES_ALLOWED));
            }
            $checkout_pages[] = 'download';
            $checkout_pages = implode(', ', $checkout_pages);
            $db->Execute(
                "UPDATE " . TABLE_CONFIGURATION . "
                    SET configuration_value = '$checkout_pages'
                  WHERE configuration_key = 'CHECKOUT_ONE_GUEST_POST_CHECKOUT_PAGES_ALLOWED'
                  LIMIT 1"
            );
        }

    // -----
    // v2.0.5:
    //
    // - Add the setting CHECKOUT_ONE_PAYMENT_BLOCK_ACTION, since there are some payment methods, e.g. square,
    //   that require that the payment-block **not** be reloaded on a shipping-module change while there are others
    //   that require the payment-block to be reloaded on that change (e.g. a Cash payment is accepted only when
    //   shipping is store-pickup.
    //
    //   Note: Will default to 'no-update' if the store currently uses the Square payment-method or to 'update' otherwise.
    //
    case version_compare(CHECKOUT_ONE_MODULE_VERSION, '2.0.5', '<'):    //-Fall-through processing from above
        $opc_pba_default = 'update';
        if (defined('MODULE_PAYMENT_INSTALLED')) {
            $opc_payment_modules = explode(';', MODULE_PAYMENT_INSTALLED);
            if (in_array('square.php', $opc_payment_modules)) {
                $opc_pba_default = 'no-update';
            }
        }
        $db->Execute(
            "INSERT IGNORE INTO " . TABLE_CONFIGURATION . "
                ( configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, date_added, sort_order, use_function, set_function ) 
                VALUES 
                ( 'Payment-Block Action on Shipping Change', 'CHECKOUT_ONE_PAYMENT_BLOCK_ACTION', '$opc_pba_default', 'Identify the action to be taken for the order\'s &quot;payment-block&quot; when the order\'s shipping-method changes.  Some payment-methods (e.g. <em>square</em>) require that the block <b>not</b> be updated while others are dependent on the shipping-method selected (e.g. a <em>Cash</em> payment is accepted <em>only</em> if the customer has chosen &quot;Store Pickup&quot;).<br><br>Choose <b>no-update</b> if at least one of your store\'s payment methods require that no update be performed.<br><br>Choose <b>update</b>, the default, if <em>none</em> of your store\'s payment methods require no-update.<br><br>If your store has a combination of payment-method requirements, choose <b>refresh</b> &mdash; but any credit-card information entered in the payment-block will be lost upon a shipping-method change!', $cgi, now(), 16, NULL, 'zen_cfg_select_option(array(\'update\', \'no-update\', \'refresh\'),')"
        );

    // -----
    // v2.2.0:
    //
    // - Remove 'myDEBUG-' prefix from OPC's debug file names.
    //
    case version_compare(CHECKOUT_ONE_MODULE_VERSION, '2.2.0', '<'):    //-Fall-through processing from above
        $db->Execute(
            "UPDATE " . TABLE_CONFIGURATION . "
                SET configuration_description = 'When enabled, debug files named <code>one_page_checkout-<em>xx</em>.log</code> are created in your /logs folder (<em>xx</em> is the customer_id for the checkout).  Use the <b>true</b> setting in combination with the <em>Debug: Customer List</em> setting to limit the customers for which the debug-action is taken.<br><br>Default: <b>false</b>'
              WHERE configuration_key = 'CHECKOUT_ONE_DEBUG'
              LIMIT 1"
        );

    // -----
    // v2.3.0:
    //
    // - Add configuration setting, enabling different 'selectors' to locate an order's total
    //   value for their customized template.
    //
    case version_compare(CHECKOUT_ONE_MODULE_VERSION, '2.3.0', '<'):    //-Fall-through processing from above
        $db->Execute(
            "INSERT IGNORE INTO " . TABLE_CONFIGURATION . "
                (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, date_added, sort_order, use_function, set_function) 
                VALUES 
                ('Order Total, jQuery Selector', 'CHECKOUT_ONE_OTTOTAL_SELECTOR', '#ottotal > div:first-child', 'Identify the CSS/jQuery <code>selector</code> used to locate an order\'s current total value.  The default value, <code>#ottotal > div:first-child</code> applies to the Zen Cart <em>responsive_classic</em> template\'s format.<br>', $cgi, now(), 18, NULL, NULL)"
        );

    // -----
    // v2.3.1:
    //
    // - Remove 'password_forgotten' from the guest-checkout disallowed pages' setting.
    //
    case version_compare(CHECKOUT_ONE_MODULE_VERSION, '2.3.1', '<'):    //-Fall-through processing from above
        if (defined('CHECKOUT_ONE_GUEST_PAGES_DISALLOWED')) {
            $guest_pages_disallowed = CHECKOUT_ONE_GUEST_PAGES_DISALLOWED;
            if (strpos(CHECKOUT_ONE_GUEST_PAGES_DISALLOWED, 'password_forgotten') !== false) {
                $disallowed_pages = explode(',', str_replace(' ', '', $guest_pages_disallowed));
                $guest_pages_disallowed = array_diff($disallowed_pages, array('password_forgotten'));
                $guest_pages_disallowed = implode(', ', $guest_pages_disallowed);
            }
            $db->Execute(
                "UPDATE " . TABLE_CONFIGURATION . "
                    SET configuration_value = '$guest_pages_disallowed',
                        configuration_description = 'Identify (using a comma-separated list, intervening blanks are OK) the pages that are <em>disallowed</em> during guest-checkout.<br><br>These pages <em>normally</em> require a logged-in customer prior to display, e.g. <code>account</code>.  <b>Do not</b> include the <code>login</code>, <code>create_account</code>, <code>password_forgotten</code> or <code>logoff</code> pages in this list!<br>'
                  WHERE configuration_key = 'CHECKOUT_ONE_GUEST_PAGES_DISALLOWED'
                  LIMIT 1"
            );
        }

    // -----
    // v2.3.4:
    //
    // - Ensure no overwrite of guest-customer's billing/shipping address.
    //
    case version_compare(CHECKOUT_ONE_MODULE_VERSION, '2.3.4', '<'):    //-Fall-through processing from above
        $guest_customer_id = (defined('CHECKOUT_ONE_GUEST_CUSTOMER_ID')) ? (int)CHECKOUT_ONE_GUEST_CUSTOMER_ID : 0;
        $sql_data_array = [
            'customers_id' => $guest_customer_id,
            'entry_firstname' => 'Guest',
            'entry_lastname' => 'Customer, **do not remove**',
            'entry_street_address' => 'Default billing address',
            'entry_country_id' => (int)STORE_COUNTRY,
            'entry_zone_id' => (int)STORE_ZONE
        ];

        // -----
        // If the guest billto address book id configuration value is present, make sure that
        // it's a valid address-book entry.  That is, it's present and contains the default information.
        //
        // If the information's invalid, remove the entry, note the change in a log file and set a flag so that
        // the 'base' OPC initialization script will recreate that entry.
        //
        if (defined('CHECKOUT_ONE_GUEST_BILLTO_ADDRESS_BOOK_ID')) {
            $opc_ab_info_ok = false;
            $opc_ab_info = $db->Execute(
                "SELECT *
                   FROM " . TABLE_ADDRESS_BOOK . "
                  WHERE address_book_id = " . (int)CHECKOUT_ONE_GUEST_BILLTO_ADDRESS_BOOK_ID . "
                    AND customers_id = $guest_customer_id
                  LIMIT 1"
            );
            if ($opc_ab_info->EOF) {
                $opc_ab_info_ok = false;
                $opc_ab_msg = '[Empty]';
            } else {
                $opc_ab_info_ok = true;
                $opc_ab_msg = json_encode($opc_ab_info->fields);
                foreach ($sql_data_array as $key => $val) {
                    if ($opc_ab_info->fields[$key] != $val) {
                        $opc_ab_info_ok = false;
                        break;
                    }
                }
            }
            if (!$opc_ab_info_ok) {
                $opc_recreate_billto = true;
                $db->Execute(
                    "DELETE FROM " . TABLE_CONFIGURATION . "
                      WHERE configuration_key = 'CHECKOUT_ONE_GUEST_BILLTO_ADDRESS_BOOK_ID'
                      LIMIT 1"
                );
                $db->Execute(
                    "DELETE FROM " . TABLE_ADDRESS_BOOK . "
                      WHERE address_book_id = " . (int)CHECKOUT_ONE_GUEST_BILLTO_ADDRESS_BOOK_ID . "
                      LIMIT 1"
                );
                error_log(date('Y-m-d H:i:s') . ": Removed invalid guest billing-address information (" . CHECKOUT_ONE_GUEST_BILLTO_ADDRESS_BOOK_ID . "': $opc_ab_msg.\n", 3, DIR_FS_LOGS . '/opc_admin_messages.log');
            }
        }
        if (defined('CHECKOUT_ONE_GUEST_SENDTO_ADDRESS_BOOK_ID')) {
            $opc_ab_info_ok = false;
            $sql_data_array['entry_street_address'] = 'Default shipping address';

            $opc_ab_info = $db->Execute(
                "SELECT *
                   FROM " . TABLE_ADDRESS_BOOK . "
                  WHERE address_book_id = " . (int)CHECKOUT_ONE_GUEST_SENDTO_ADDRESS_BOOK_ID . "
                    AND customers_id = $guest_customer_id
                  LIMIT 1"
            );
            if ($opc_ab_info->EOF) {
                $opc_ab_info_ok = false;
                $opc_ab_msg = '[Empty]';
            } else {
                $opc_ab_info_ok = true;

                $opc_ab_msg = json_encode($opc_ab_info->fields);
                foreach ($sql_data_array as $key => $val) {
                    if ($opc_ab_info->fields[$key] != $val) {
                        $opc_ab_info_ok = false;
                        break;
                    }
                }
            }
            if (!$opc_ab_info_ok) {
                $opc_recreate_sendto = true;
                $db->Execute(
                    "DELETE FROM " . TABLE_CONFIGURATION . "
                      WHERE configuration_key = 'CHECKOUT_ONE_GUEST_SENDTO_ADDRESS_BOOK_ID'
                      LIMIT 1"
                );
                $db->Execute(
                    "DELETE FROM " . TABLE_ADDRESS_BOOK . "
                      WHERE address_book_id = " . (int)CHECKOUT_ONE_GUEST_SENDTO_ADDRESS_BOOK_ID . "
                      LIMIT 1"
                );
                error_log(date('Y-m-d H:i:s') . ": Removed invalid guest shipping-address information (" . CHECKOUT_ONE_GUEST_SENDTO_ADDRESS_BOOK_ID . "): $opc_ab_msg.\n", 3, DIR_FS_LOGS . '/opc_admin_messages.log');
            }
        }

    // -----
    // v2.4.0:
    //
    // - Adding a configuration setting to identify payment methods that auto-submit the form, e.g. square_webPay
    // - Modify module-version setting to use 'zen_cfg_read_only` as its set_function so that the configuration value doesn't get wiped.
    //
    case version_compare(CHECKOUT_ONE_MODULE_VERSION, '2.4.0', '<'):    //-Fall-through processing from above
        $db->Execute(
            "INSERT IGNORE INTO " . TABLE_CONFIGURATION . "
                (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, date_added, sort_order, use_function, set_function) 
                VALUES 
                ('Payment Methods Handling Form Submittal', 'CHECKOUT_ONE_PAYMENT_METHODS_THAT_SUBMIT', 'square_webPay', 'Use a comma-separated list (intervening blanks are OK) to identify any payment methods that handle the checkout form\'s submittal themselves, e.g. <code>square_WebPay</code>, the default.<br>', $cgi, now(), 21, NULL, NULL)"
        );
        $db->Execute(
            "UPDATE " . TABLE_CONFIGURATION . "
                SET set_function = 'zen_cfg_read_only(',
                    last_modified = now()
              WHERE configuration_key = 'CHECKOUT_ONE_MODULE_VERSION'
              LIMIT 1"
        );

    // -----
    // v2.4.2:
    //
    // - Modify the description of "Payment Methods Requiring Confirmation" to indicate that a
    //   'credit_covers' method indicates that the confirmation page is required for orders where
    //   a Gift Certificate or coupon that 'covers' the charge for the order is present.
    //
    case version_compare(CHECKOUT_ONE_MODULE_VERSION, '2.4.2', '<'):    //-Fall-through processing from above
        $db->Execute(
            "UPDATE " . TABLE_CONFIGURATION . "
                SET configuration_description = 'Identify (using a comma-separated list) the payment modules on your store that require confirmation.  If your store requires confirmation on all orders, simply list all payment modules used by your store.<br><br>Use the <code>credit_covers</code> &quot;method&quot; if orders that are fully paid using a Gift Certificate or coupon should also require confirmation.<br><br>Default: <code>eway_rapid,stripepay,gps</code><br>',
                    last_modified = now()
              WHERE configuration_key = 'CHECKOUT_ONE_CONFIRMATION_REQUIRED'
              LIMIT 1"
        );
    default:                                                            //-Fall-through processing from above
        break;
}

// -----
// If this isn't an initial install, set the message to let the currently-signed-in admin
// know that the upgrade has occurred.
//
if (CHECKOUT_ONE_MODULE_VERSION !== '0.0.0' && CHECKOUT_ONE_MODULE_VERSION !== $version_release_date) {
    $messageStack->add(sprintf(TEXT_OPC_UPDATED, CHECKOUT_ONE_MODULE_VERSION, $version_release_date), 'success');
    $db->Execute("UPDATE " . TABLE_CONFIGURATION . " SET configuration_value = '$version_release_date', last_modified = now() WHERE configuration_key = 'CHECKOUT_ONE_MODULE_VERSION' LIMIT 1");
}
