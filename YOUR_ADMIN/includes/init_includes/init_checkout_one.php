<?php
// -----
// Part of the One-Page Checkout plugin, provided under GPL 2.0 license by lat9
// Copyright (C) 2013-2025, Vinos de Frutas Tropicales.  All rights reserved.
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
define('CHECKOUT_ONE_CURRENT_VERSION', '2.5.5-beta5');
define('CHECKOUT_ONE_CURRENT_UPDATE_DATE', '2025-08-18');

if (isset($_SESSION['admin_id'])) {
    $version_release_date = CHECKOUT_ONE_CURRENT_VERSION . ' (' . CHECKOUT_ONE_CURRENT_UPDATE_DATE . ')';

    $configurationGroupTitle = 'One-Page Checkout Settings';
    $configuration = $db->Execute("SELECT configuration_group_id FROM " . TABLE_CONFIGURATION_GROUP . " WHERE configuration_group_title = '$configurationGroupTitle' LIMIT 1");
    if ($configuration->EOF) {
        $db->Execute("INSERT INTO " . TABLE_CONFIGURATION_GROUP . " 
                     (configuration_group_title, configuration_group_description, sort_order, visible) 
                     VALUES ('$configurationGroupTitle', '$configurationGroupTitle', 1, 1);");
        $cgi = $db->Insert_ID(); 
        $db->Execute("UPDATE " . TABLE_CONFIGURATION_GROUP . " SET sort_order = $cgi WHERE configuration_group_id = $cgi");
    } else {
        $cgi = $configuration->fields['configuration_group_id'];
    }

    // -----
    // If One-Page Checkout is not yet installed, bring in the initial-installation script.
    //
    if (!defined('CHECKOUT_ONE_MODULE_VERSION')) {
        require DIR_WS_INCLUDES . 'init_includes/init_checkout_one_install.php';
    }

    // -----
    // If a new version is present, bring in the plugin's upgrade script.
    //
    if (CHECKOUT_ONE_MODULE_VERSION !== $version_release_date) {
        require DIR_WS_INCLUDES . 'init_includes/init_checkout_one_upgrade.php';
    }

    // -----
    // Make sure that the guest-/temporary-address indexes have been registered (in case the store-owner
    // somehow removes those settings).  If defined, make sure that they reference
    // existing entries in the customers/address_book tables, too!
    //
    $guest_customer_id_ok = false;
    if (defined('CHECKOUT_ONE_GUEST_CUSTOMER_ID')) {
        $guest_customer_id = (int)CHECKOUT_ONE_GUEST_CUSTOMER_ID;
        $opc_check = $db->Execute(
            "SELECT customers_id
               FROM " . TABLE_CUSTOMERS . "
              WHERE customers_id = $guest_customer_id
                AND customers_authorization = 0
              LIMIT 1"
        );
        if (!$opc_check->EOF) {
            $guest_customer_id_ok = true;
        } else {
            $db->Execute(
                "DELETE FROM " . TABLE_CONFIGURATION . "
                  WHERE configuration_key = 'CHECKOUT_ONE_GUEST_CUSTOMER_ID'
                  LIMIT 1"
            );
        }
    }
    if ($guest_customer_id_ok === false) {
        $sql_data_array = [
            'customers_firstname' => 'Guest',
            'customers_lastname' => 'Customer, **do not remove**'
        ];
        zen_db_perform(TABLE_CUSTOMERS, $sql_data_array);
        $guest_customer_id = zen_db_insert_id();
        $db->Execute(
            "INSERT INTO " . TABLE_CONFIGURATION . " 
                (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, date_added, sort_order)
             VALUES
                ('Guest Checkout: Customer ID', 'CHECKOUT_ONE_GUEST_CUSTOMER_ID', '$guest_customer_id', 'This (hidden) value identifies the customers-table entry that is used as the pseudo-customers_id for any guest checkout in your store.', 6, now(), 30)"
        );
        $sql_data_array = [
            'customers_info_id' => $guest_customer_id,
            'customers_info_date_account_created' => 'now()'
        ];
        zen_db_perform(TABLE_CUSTOMERS_INFO, $sql_data_array);
    }

    // -----
    // Ensure that OPC's guest billing address-id is present and actually
    // present in the address_book table.
    //
    $guest_address_id_ok = false;
    if (defined('CHECKOUT_ONE_GUEST_BILLTO_ADDRESS_BOOK_ID')) {
        $opc_check = $db->Execute(
            "SELECT address_book_id
               FROM " . TABLE_ADDRESS_BOOK . "
              WHERE address_book_id = " . (int)CHECKOUT_ONE_GUEST_BILLTO_ADDRESS_BOOK_ID . "
                AND customers_id = $guest_customer_id
              LIMIT 1"
        );
        if (!$opc_check->EOF) {
            $guest_address_id_ok = true;
        } else {
            $db->Execute(
                "DELETE FROM " . TABLE_CONFIGURATION . "
                  WHERE configuration_key = 'CHECKOUT_ONE_GUEST_BILLTO_ADDRESS_BOOK_ID'
                  LIMIT 1"
            );
        }
    }
    if ($guest_address_id_ok === false) {
        $sql_data_array = [
            'customers_id' => $guest_customer_id,
            'entry_firstname' => 'Guest',
            'entry_lastname' => 'Customer, **do not remove**',
            'entry_street_address' => 'Default billing address',
            'entry_country_id' => (int)STORE_COUNTRY,
            'entry_zone_id' => (int)STORE_ZONE
        ];
        zen_db_perform(TABLE_ADDRESS_BOOK, $sql_data_array);
        $address_book_id = zen_db_insert_id();
        $db->Execute(
            "INSERT INTO " . TABLE_CONFIGURATION . " 
                (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, date_added, sort_order)
             VALUES
                ('Guest Checkout: Billing-Address ID', 'CHECKOUT_ONE_GUEST_BILLTO_ADDRESS_BOOK_ID', '$address_book_id', 'This (hidden) value identifies the address_book-table entry that is used as the pseudo-billing-address entry for any guest checkout in your store.', 6, now(), 30)"
        );
        $db->Execute(
            "UPDATE " . TABLE_CUSTOMERS . "
                SET customers_default_address_id = $address_book_id
              WHERE customers_id = $guest_customer_id
              LIMIT 1"
        );
    }

    // -----
    // Ensure that OPC's guest shipping address-id is present and actually
    // present in the address_book table.
    //
    $guest_address_id_ok = false;
    if (defined('CHECKOUT_ONE_GUEST_SENDTO_ADDRESS_BOOK_ID')) {
        $opc_check = $db->Execute(
            "SELECT address_book_id
               FROM " . TABLE_ADDRESS_BOOK . "
              WHERE address_book_id = " . (int)CHECKOUT_ONE_GUEST_SENDTO_ADDRESS_BOOK_ID . "
                AND customers_id = $guest_customer_id
              LIMIT 1"
        );
        if (!$opc_check->EOF) {
            $guest_address_id_ok = true;
        } else {
            $db->Execute(
                "DELETE FROM " . TABLE_CONFIGURATION . "
                  WHERE configuration_key = 'CHECKOUT_ONE_GUEST_SENDTO_ADDRESS_BOOK_ID'
                  LIMIT 1"
            );
        }
    }
    if ($guest_address_id_ok === false) {
        $sql_data_array = [
            'customers_id' => $guest_customer_id,
            'entry_firstname' => 'Guest',
            'entry_lastname' => 'Customer, **do not remove**',
            'entry_street_address' => 'Default shipping address',
            'entry_country_id' => (int)STORE_COUNTRY,
            'entry_zone_id' => (int)STORE_ZONE
        ];
        zen_db_perform(TABLE_ADDRESS_BOOK, $sql_data_array);
        $address_book_id = zen_db_insert_id();
        $db->Execute(
            "INSERT INTO " . TABLE_CONFIGURATION . " 
                (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, date_added, sort_order)
             VALUES
                ('Guest Checkout: Shipping-Address ID', 'CHECKOUT_ONE_GUEST_SENDTO_ADDRESS_BOOK_ID', '$address_book_id', 'This (hidden) value identifies the address_book-table entry that is used as the pseudo-shipping-address entry for any guest checkout in your store.', 6, now(), 30)"
        );
    }

    // -----
    // Now, check to make sure that the currently-active template's folder includes the jscript_framework.php file and disable the One-Page Checkout if
    // that file's not found.
    //
    $template_check = $db->Execute("SELECT DISTINCT template_dir FROM " . TABLE_TEMPLATE_SELECT);
    foreach ($template_check as $next_template) {
        $jscript_dir = DIR_FS_CATALOG . 'includes/templates/' . $next_template['template_dir'] . '/jscript';
        if (CHECKOUT_ONE_ENABLED !== 'false' && !is_dir($jscript_dir) || !file_exists("$jscript_dir/jscript_framework.php")) {
            $db->Execute("UPDATE " . TABLE_CONFIGURATION . " SET configuration_value = 'false' WHERE configuration_key = 'CHECKOUT_ONE_ENABLED' LIMIT 1");
            $messageStack->add(sprintf(ERROR_STORESIDE_CONFIG, "$jscript_dir/jscript_framework.php"), 'error');
            break;
        }
    }
}
