<?php
// -----
// Part of the One-Page Checkout plugin, provided under GPL 2.0 license by lat9
// Copyright (C) 2013-2019, Vinos de Frutas Tropicales.  All rights reserved.
//
if (!defined('IS_ADMIN_FLAG')) {
    die('Illegal Access');
}

// -----
// Note: The Configuration->One-Page Checkout Settings sort-orders are grouped as follows, enabling the settings to be "grouped":
//
// 1-29 ...... Basic settings
// 30-499 .... Guest-checkout settings
// 500-599 ... Registered-account settings
// 1000+ ..... Debug settings
//
define('CHECKOUT_ONE_CURRENT_VERSION', '2.1.0-beta9');
define('CHECKOUT_ONE_CURRENT_UPDATE_DATE', '2019-04-01');

if (isset($_SESSION['admin_id'])) {
    $version_release_date = CHECKOUT_ONE_CURRENT_VERSION . ' (' . CHECKOUT_ONE_CURRENT_UPDATE_DATE . ')';

    $configurationGroupTitle = 'One-Page Checkout Settings';
    $configuration = $db->Execute("SELECT configuration_group_id FROM " . TABLE_CONFIGURATION_GROUP . " WHERE configuration_group_title = '$configurationGroupTitle' LIMIT 1");
    if ($configuration->EOF) {
        $db->Execute("INSERT INTO " . TABLE_CONFIGURATION_GROUP . " 
                     (configuration_group_title, configuration_group_description, sort_order, visible) 
                     VALUES ('$configurationGroupTitle', '$configurationGroupTitle', '1', '1');");
        $cgi = $db->Insert_ID(); 
        $db->Execute("UPDATE " . TABLE_CONFIGURATION_GROUP . " SET sort_order = $cgi WHERE configuration_group_id = $cgi");
    } else {
        $cgi = $configuration->fields['configuration_group_id'];
    }

    // -----
    // Set the various configuration items, the plugin wasn't previously installed.
    //
    if (!defined('CHECKOUT_ONE_ENABLED')) {
        $db->Execute("INSERT INTO " . TABLE_CONFIGURATION . " ( configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, date_added, sort_order, use_function, set_function ) VALUES ( 'Enable One-Page Checkout?', 'CHECKOUT_ONE_ENABLED', 'false', 'Enable the one-page checkout processing for your store?  Default: <b>false</b>', $cgi, now(), 10, NULL, 'zen_cfg_select_option(array(\'true\', \'false\'),')");
        
        define('CHECKOUT_ONE_ENABLED', 'false');
        
        $db->Execute("INSERT INTO " . TABLE_CONFIGURATION . " ( configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, date_added, sort_order, use_function, set_function ) VALUES ( 'Enable One-Page Checkout Debug?', 'CHECKOUT_ONE_DEBUG', 'false', 'When enabled, debug files named myDEBUG-one_page_checkout-<em>xx</em>.log are created in your /logs folder (<em>xx</em> is the customer_id for the checkout).  Use the <b>true</b> setting in combination with the <em>Debug: Customer List</em> setting to limit the customers for which the debug-action is taken.<br /><br />Default: <b>false</b>', $cgi, now(), 50, NULL, 'zen_cfg_select_option(array(\'true\', \'false\'),')");
        
        $db->Execute("INSERT INTO " . TABLE_CONFIGURATION . " ( configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, date_added, sort_order, use_function, set_function ) VALUES ( 'Debug: Customer List', 'CHECKOUT_ONE_DEBUG_EXTRA', '', 'When you enable the plugin\'s debug, use this setting to limit the customers for which the debug-logs are generated.  Leave the setting blank (the default) to debug <b>all</b> customers or identify a comma-separated list of customer_id values to limit the debug to just those customers.<br />', $cgi, now(), 51, NULL, NULL)");
    } elseif (!defined('CHECKOUT_ONE_MODULE_VERSION')) {
        $db->Execute("UPDATE " . TABLE_CONFIGURATION . " SET configuration_group_id = $cgi WHERE configuration_key LIKE 'CHECKOUT_ONE_%'");
    }

    if (!defined('CHECKOUT_ONE_MODULE_VERSION')) {
        $db->Execute("INSERT INTO " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, date_added, sort_order, set_function) VALUES ('Version/Release Date', 'CHECKOUT_ONE_MODULE_VERSION', '" . $version_release_date . "', 'The One-Page Checkout version number and release date.', $cgi, now(), 1, 'trim(')");
        define('CHECKOUT_ONE_MODULE_VERSION', '0.0.0');
        $messageStack->add(sprintf(TEXT_OPC_INSTALLED, $version_release_date), 'success');
    }

    if (!defined('CHECKOUT_ONE_SHIPPING_TIMEOUT')) {
        $db->Execute("INSERT INTO " . TABLE_CONFIGURATION . " ( configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, date_added, sort_order, use_function, set_function ) VALUES ( 'Update Shipping AJAX Time-out', 'CHECKOUT_ONE_SHIPPING_TIMEOUT', '5000', 'Enter the timeout to use for the plugin\'s request to update the shipping quotes on the &quot;checkout_one&quot; page. The default setting of 5000 (5 seconds) <em>should work</em> for most stores.  If your store has enabled multiple external shipping methods (e.g. USPS, UPS <b>and</b> FedEx), you might need to increase this value.<br />', $cgi, now(), 15, NULL, NULL)");
    }

    // -----
    // If not already updated, update the configuration of the plugin's debug setting.  Starting with v1.0.1, there are now three settings.
    //
    if (defined('CHECKOUT_ONE_DEBUG') && strpos(CHECKOUT_ONE_DEBUG, '<b>full</b>') === false) {
        $db->Execute(
            "UPDATE " . TABLE_CONFIGURATION . " 
                SET configuration_description = 'When enabled, debug files named myDEBUG-one_page_checkout-<em>xx</em>.log are created in your /logs folder (<em>xx</em> is the customer_id for the checkout).  Use the <b>true</b> or <b>full</b> settings in combination with the <em>Debug: Customer List</em> setting to limit the customers for which the debug-action is taken.  Setting the value to <b>full</b> will also set the PHP error-level for the checkout so that <b>all</b> PHP errors are logged.<br /><br />Default: <b>false</b>',
                    set_function = 'zen_cfg_select_option(array(\'true\', \'false\', \'full\'),'
              WHERE configuration_key = 'CHECKOUT_ONE_DEBUG' LIMIT 1"
        );
    }

    // -----
    // Version-specific updates follow ...
    //
    if (version_compare(CHECKOUT_ONE_MODULE_VERSION, '1.1.0', '<')) {
        // -----
        // v1.1.0:  Update the 'Enable' setting to include a value that is conditional on the newly-added customer-id list.
        //
        $db->Execute(
            "UPDATE " . TABLE_CONFIGURATION . "
                SET configuration_description = 'Enable the one-page checkout processing for your store? Choose <em>true</em> to enable for all customers, <em>false</em> to disable or <em>conditional</em> to enable the processing only for customers identified by <b>Enable: Customer List</b>.<br /><br />Default: <b>false</b>',
                    set_function = 'zen_cfg_select_option(array(\'true\', \'conditional\', \'false\'),',
                    last_modified = now()
              WHERE configuration_key = 'CHECKOUT_ONE_ENABLED'
              LIMIT 1"
        );
        $db->Execute(
            "INSERT IGNORE INTO " . TABLE_CONFIGURATION . "
                ( configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, date_added, sort_order, use_function, set_function ) 
                VALUES 
                ( 'Enable: Customer List', 'CHECKOUT_ONE_ENABLE_CUSTOMERS_LIST', '', 'When you <em>conditionally</em> enable the plugin, use this setting to limit the customers for which the plugin is enabled.  Leave the setting blank (the default) to <em>disable</em> the plugin for all customers or identify a comma-separated list of customer_id values for whom the plugin is to be <em>enabled</em>.<br />', $cgi, now(), 11, NULL, NULL)"
        );
    }

    if (version_compare(CHECKOUT_ONE_MODULE_VERSION, '1.3.0', '<')) {
        $db->Execute(
            "INSERT IGNORE INTO " . TABLE_CONFIGURATION . " 
                ( configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, date_added, sort_order, use_function, set_function ) 
                VALUES 
                ( 'Enable Shipping=Billing?', 'CHECKOUT_ONE_ENABLE_SHIPPING_BILLING', 'true', 'Do you want to enable the <em>Shipping Address, same as Billing</em> for your store?<br /><br />Default: <b>true</b>', $cgi, now(), 20, NULL, 'zen_cfg_select_option(array(\'true\', \'false\'),')"
        );
        $db->Execute(
            "INSERT IGNORE INTO " . TABLE_CONFIGURATION . "
                ( configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, date_added, sort_order, use_function, set_function ) 
                VALUES 
                ( 'Payment Methods Requiring Confirmation', 'CHECKOUT_ONE_CONFIRMATION_REQUIRED', 'eway_rapid,stripepay,gps', 'Identify (using a comma-separated list) the payment modules on your store that require confirmation.  If your store requires confirmation on all orders, simply list all payment modules used by your store.<br /><br />Default: <code>eway_rapid,stripepay,gps</code>', $cgi, now(), 21, NULL, NULL)"
        );
    }
    
    if (!defined('CHECKOUT_ONE_MINIFIED_SCRIPT')) {
        $db->Execute(
            "INSERT IGNORE INTO " . TABLE_CONFIGURATION . " 
                ( configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, date_added, sort_order, use_function, set_function ) 
                VALUES 
                ( 'Load Minified Script Files?', 'CHECKOUT_ONE_MINIFIED_SCRIPT', 'true', 'Should the plugin load the minified version of its jQuery scripts, reducing the page-load time for the <code>checkout_one</code> page?<br /><br />Default: <b>true</b>.', $cgi, now(), 25, NULL, 'zen_cfg_select_option(array(\'true\', \'false\'),')"
        );
    }
    
    // -----
    // v2.0.0:
    //
    // - Various guest-checkout options.
    // - Update debug-related sort-orders to "make room" for the guest-checkout options.
    // - Add 'is_guest_order' field to the orders table in the database.
    //
    if (version_compare(CHECKOUT_ONE_MODULE_VERSION, '2.0.0', '<')) {
        $db->Execute(
            "INSERT IGNORE INTO " . TABLE_CONFIGURATION . " 
                ( configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, date_added, sort_order, use_function, set_function ) 
                VALUES 
                ( 'Order Status: Slamming Control', 'CHECKOUT_ONE_ORDER_STATUS_SLAM_COUNT', '3', 'Identify the number of back-to-back errors that your store allows when a customer is checking their order\\'s status via the <code>order_status</code> page (default: <b>3</b>).  When the customer has reached that threshold, they will be redirected to the <code>time_out</code> page.<br /><br />', $cgi, now(), 25, NULL, NULL)"
        );
        $db->Execute(
            "INSERT IGNORE INTO " . TABLE_CONFIGURATION . " 
                ( configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, date_added, sort_order, use_function, set_function ) 
                VALUES 
                ( 'Enable Guest Checkout?', 'CHECKOUT_ONE_ENABLE_GUEST', 'false', 'Do you want to enable <em>Guest Checkout</em> for your store?<br /><br />Default: <b>false</b>', $cgi, now(), 30, NULL, 'zen_cfg_select_option(array(\'true\', \'false\'),')"
        );
        $db->Execute(
            "INSERT IGNORE INTO " . TABLE_CONFIGURATION . " 
                ( configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, date_added, sort_order, use_function, set_function ) 
                VALUES 
                ( 'Guest Checkout: Require Email Confirmation?', 'CHECKOUT_ONE_GUEST_EMAIL_CONFIRMATION', 'true', 'Should a guest-customer be required to confirm their email address when placing an order?<br /><br />Default: <b>true</b>', $cgi, now(), 40, NULL, 'zen_cfg_select_option(array(\'true\', \'false\'),')"
        );
        $db->Execute(
            "INSERT IGNORE INTO " . TABLE_CONFIGURATION . "
                ( configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, date_added, sort_order, use_function, set_function ) 
                VALUES 
                ( 'Guest Checkout: Pages Allowed Post-Checkout', 'CHECKOUT_ONE_GUEST_POST_CHECKOUT_PAGES_ALLOWED', 'download', 'Identify (using a comma-separated list, intervening blanks are OK) the pages that are <em>allowed</em> once a guest has completed their checkout.  When the guest navigates from the <code>checkout_success</code> page to any page <em><b>not in this list</b></em>, their guest-customer session is reset.<br /><br />For example, if your store provides a pop-up print invoice, you would include the name of that page in this list.<br />', $cgi, now(), 50, NULL, NULL)"
        );
        $db->Execute(
            "INSERT IGNORE INTO " . TABLE_CONFIGURATION . "
                ( configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, date_added, sort_order, use_function, set_function ) 
                VALUES 
                ( 'Guest Checkout: Disallowed Pages', 'CHECKOUT_ONE_GUEST_PAGES_DISALLOWED', 'account, account_edit, account_history, account_history_info, account_newsletters, account_notifications, account_password, address_book, address_book_process, create_account_success, gv_redeem, gv_send, password_forgotten, product_reviews_write, unsubscribe', 'Identify (using a comma-separated list, intervening blanks are OK) the pages that are <em>disallowed</em> during guest-checkout.<br /><br />These pages <em>normally</em> require a logged-in customer prior to display, e.g. <code>account</code>.  <b>Do not</b> include the <code>login</code>, <code>create_account</code> or <code>logoff</code> pages in this list!<br />', $cgi, now(), 100, NULL, NULL)"
        );
        $db->Execute(
            "INSERT IGNORE INTO " . TABLE_CONFIGURATION . "
                ( configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, date_added, sort_order, use_function, set_function ) 
                VALUES 
                ( 'Guest Checkout: Disallowed <em>Credit Class</em> Order-Totals', 'CHECKOUT_ONE_ORDER_TOTALS_DISALLOWED_FOR_GUEST', 'ot_gv', 'Identify (using a comma-separated list, intervening blanks are OK) any <em>credit-class</em> order-totals that are <em>disallowed</em> during guest-checkout.<br /><br />These order-totals <em>normally</em> require a customer-account for their processing, e.g. <code>ot_gv</code>.<br />', $cgi, now(), 105, NULL, NULL)"
        );
        $db->Execute(
            "INSERT IGNORE INTO " . TABLE_CONFIGURATION . "
                ( configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, date_added, sort_order, use_function, set_function ) 
                VALUES 
                ( 'Guest Checkout: Disallowed Payment Methods', 'CHECKOUT_ONE_PAYMENTS_DISALLOWED_FOR_GUEST', 'moneyorder, cod', 'Identify (using a comma-separated list, intervening blanks are OK) any payment methods that are <em>disallowed</em> during guest-checkout.<br /><br />These payment methods <em>normally</em> have no validation of purchase &mdash; e.g. <code>moneyorder</code> and <code>cod</code> &mdash; and can, if left enabled, result in unwanted <em>spam purchases</em>.<br />', $cgi, now(), 110, NULL, NULL)"
        );
        $db->Execute(
            "INSERT IGNORE INTO " . TABLE_CONFIGURATION . "
                ( configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, date_added, sort_order, use_function, set_function ) 
                VALUES 
                ( 'Enable Account Registration?', 'CHECKOUT_ONE_ENABLE_REGISTERED_ACCOUNTS', 'false', 'Do you want your store\\'s <code>create_account</code> processing to create a <em>registered</em> rather than a <em>full</em> account?<br /><br />Default: <b>false</b>', $cgi, now(), 500, NULL, 'zen_cfg_select_option(array(\'true\', \'false\'),')"
        );
        $db->Execute(
            "INSERT IGNORE INTO " . TABLE_CONFIGURATION . "
                ( configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, date_added, sort_order, use_function, set_function ) 
                VALUES 
                ( 'Login-Page Layout', 'CHECKOUT_ONE_LOGIN_LAYOUT', 'L;P,G,C;B', 'When you enable the plugin\\'s <em>Guest Checkout</em> and/or <em>Account Registration</em>, an alternate formatting of the storefront <code>login</code> page is displayed.  Use this setting to control the 3-column layout of that modified page.<br /><br />The value is an encoded string, identifying which block should be displayed in which column.  Columns are delimited by a semi-colon (;) and the top-to-bottom column layout is in the order specified by the block-elements\\' left-to-right order.<br /><br />The block elements are:<ul><li><b>L</b> ... (required) The email/password login block.</li><li><b>P</b> ... (optional) The PayPal Express Checkout shortcut-button block.</li><li><b>G</b> ... (required) The guest-checkout block.</li><li><b>C</b> ... (required) The create-account block.</li><li><b>B</b> ... (optional) The \"Account Benefits\" block.</li></ul>Default: <b>L;P,G,C;B</b>', $cgi, now(), 26, NULL, NULL)"
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
    }
    
    // -----
    // v2.0.4:
    //
    // - If the CHECKOUT_ONE_GUEST_POST_CHECKOUT_PAGES_ALLOWED setting does not include the 'download' page, add it!
    //
    if (version_compare(CHECKOUT_ONE_MODULE_VERSION, '2.0.4', '<')) {
        if (defined('CHECKOUT_ONE_GUEST_POST_CHECKOUT_PAGES_ALLOWED') && strpos(CHECKOUT_ONE_GUEST_POST_CHECKOUT_PAGES_ALLOWED, 'download') === false) {
            if (CHECKOUT_ONE_GUEST_POST_CHECKOUT_PAGES_ALLOWED == '') {
                $checkout_pages = array();
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
    if (version_compare(CHECKOUT_ONE_MODULE_VERSION, '2.0.5', '<')) {
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
                ( 'Payment-Block Action on Shipping Change', 'CHECKOUT_ONE_PAYMENT_BLOCK_ACTION', '$opc_pba_default', 'Identify the action to be taken for the order\'s &quot;payment-block&quot; when the order\'s shipping-method changes.  Some payment-methods (e.g. <em>square</em>) require that the block <b>not</b> be updated while others are dependent on the shipping-method selected (e.g. a <em>Cash</em> payment is accepted <em>only</em> if the customer has chosen &quot;Store Pickup&quot;).<br /><br />Choose <b>no-update</b> if at least one of your store\'s payment methods require that no update be performed.<br /><br />Choose <b>update</b>, the default, if <em>none</em> of your store\'s payment methods require no-update.<br /><br />If your store has a combination of payment-method requirements, choose <b>refresh</b> &mdash; but any credit-card information entered in the payment-block will be lost upon a shipping-method change!', $cgi, now(), 16, NULL, 'zen_cfg_select_option(array(\'update\', \'no-update\', \'refresh\'),')"
        );
    }
    
    if (CHECKOUT_ONE_MODULE_VERSION != '0.0.0' && CHECKOUT_ONE_MODULE_VERSION != $version_release_date) {
        $messageStack->add(sprintf(TEXT_OPC_UPDATED, CHECKOUT_ONE_MODULE_VERSION, $version_release_date), 'success');
        $db->Execute("UPDATE " . TABLE_CONFIGURATION . " SET configuration_value = '$version_release_date', last_modified = now() WHERE configuration_key = 'CHECKOUT_ONE_MODULE_VERSION' LIMIT 1");
    }

    // -----
    // Register the plugin's configuration page for display on the menus.
    //
    if (!zen_page_key_exists('configOnePageCheckout')) {
        $next_sort = $db->Execute(
            "SELECT MAX(sort_order) AS max_sort 
               FROM " . TABLE_ADMIN_PAGES . " 
              WHERE menu_key='configuration'", 
              false, 
              false, 
              0, 
              true
        );
        zen_register_admin_page('configOnePageCheckout', 'BOX_TOOLS_CHECKOUT_ONE', 'FILENAME_CONFIGURATION', "gID=$cgi", 'configuration', 'Y', $next_sort->fields['max_sort'] + 1);
    }
        
    // -----
    // Make sure that the guest-/temporary-address indexes have been registered (in case the store-owner
    // somehow removes those settings).
    //
    if (defined('CHECKOUT_ONE_GUEST_CUSTOMER_ID')) {
        $guest_customer_id = CHECKOUT_ONE_GUEST_CUSTOMER_ID;
    } else {
        $sql_data_array = array(
            'customers_firstname' => 'Guest',
            'customers_lastname' => 'Customer, **do not remove**'
        );
        zen_db_perform(TABLE_CUSTOMERS, $sql_data_array);
        $guest_customer_id = zen_db_insert_id();
        $db->Execute(
            "INSERT IGNORE INTO " . TABLE_CONFIGURATION . " 
                ( configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, date_added, sort_order, use_function, set_function ) 
                VALUES 
                ( 'Guest Checkout: Customer ID', 'CHECKOUT_ONE_GUEST_CUSTOMER_ID', '$guest_customer_id', 'This (hidden) value identifies the customers-table entry that is used as the pseudo-customers_id for any guest checkout in your store.', 6, now(), 30, NULL, NULL)"
        );
        $sql_data_array = array(
            'customers_info_id' => $guest_customer_id,
            'customers_info_date_account_created' => 'now()'
        );
        zen_db_perform(TABLE_CUSTOMERS_INFO, $sql_data_array);
    }
    
    if (!defined('CHECKOUT_ONE_GUEST_BILLTO_ADDRESS_BOOK_ID')) {
        $sql_data_array = array(
            'customers_id' => $guest_customer_id,
            'entry_firstname' => 'Guest',
            'entry_lastname' => 'Customer, **do not remove**',
            'entry_street_address' => 'Default billing address',
            'entry_country_id' => (int)STORE_COUNTRY,
            'entry_zone_id' => (int)STORE_ZONE
        );
        zen_db_perform(TABLE_ADDRESS_BOOK, $sql_data_array);
        $address_book_id = zen_db_insert_id();
        $db->Execute(
            "INSERT IGNORE INTO " . TABLE_CONFIGURATION . " 
                ( configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, date_added, sort_order, use_function, set_function ) 
                VALUES 
                ( 'Guest Checkout: Billing-Address ID', 'CHECKOUT_ONE_GUEST_BILLTO_ADDRESS_BOOK_ID', '$address_book_id', 'This (hidden) value identifies the address_book-table entry that is used as the pseudo-billing-address entry for any guest checkout in your store.', 6, now(), 30, NULL, NULL)"
        );
        $db->Execute(
            "UPDATE " . TABLE_CUSTOMERS . "
                SET customers_default_address_id = $address_book_id
              WHERE customers_id = $guest_customer_id
              LIMIT 1"
        );
    }
    
    if (!defined('CHECKOUT_ONE_GUEST_SENDTO_ADDRESS_BOOK_ID')) {
        $sql_data_array = array(
            'customers_id' => $guest_customer_id,
            'entry_firstname' => 'Guest',
            'entry_lastname' => 'Customer, **do not remove**',
            'entry_street_address' => 'Default shipping address',
            'entry_country_id' => (int)STORE_COUNTRY,
            'entry_zone_id' => (int)STORE_ZONE
        );
        zen_db_perform(TABLE_ADDRESS_BOOK, $sql_data_array);
        $address_book_id = zen_db_insert_id();
        $db->Execute(
            "INSERT IGNORE INTO " . TABLE_CONFIGURATION . " 
                ( configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, date_added, sort_order, use_function, set_function ) 
                VALUES 
                ( 'Guest Checkout: Shipping-Address ID', 'CHECKOUT_ONE_GUEST_SENDTO_ADDRESS_BOOK_ID', '$address_book_id', 'This (hidden) value identifies the address_book-table entry that is used as the pseudo-shipping-address entry for any guest checkout in your store, if different from the billing address.', 6, now(), 30, NULL, NULL)"
        );
    }
        
    // -----
    // Now, check to make sure that the currently-active template's folder includes the jscript_framework.php file and disable the One-Page Checkout if
    // that file's not found.
    //
    $template_check = $db->Execute("SELECT DISTINCT template_dir FROM " . TABLE_TEMPLATE_SELECT);
    while (!$template_check->EOF) {
        $jscript_dir = DIR_FS_CATALOG . 'includes/templates/' . $template_check->fields['template_dir'] . '/jscript';
        if (CHECKOUT_ONE_ENABLED !== 'false' && !is_dir($jscript_dir) || !file_exists("$jscript_dir/jscript_framework.php")) {
            $db->Execute("UPDATE " . TABLE_CONFIGURATION . " SET configuration_value = 'false' WHERE configuration_key = 'CHECKOUT_ONE_ENABLED' LIMIT 1");
            $messageStack->add(sprintf(ERROR_STORESIDE_CONFIG, "$jscript_dir/jscript_framework.php"), 'error');
            break;
        }
        $template_check->MoveNext();
    }
}