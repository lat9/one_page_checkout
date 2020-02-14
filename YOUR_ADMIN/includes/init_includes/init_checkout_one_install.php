<?php
// -----
// Part of the One-Page Checkout plugin, provided under GPL 2.0 license by lat9
// Copyright (C) 2013-2020, Vinos de Frutas Tropicales.  All rights reserved.
//
if (!defined('IS_ADMIN_FLAG')) {
    die('Illegal Access');
}

// -----
// This module is required by the base 'init_checkout_one.php' installation script when it finds
// that One-Page Checkout is not yet installed.
//
if (!defined('CHECKOUT_ONE_ENABLED')) {
    $db->Execute("INSERT INTO " . TABLE_CONFIGURATION . " ( configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, date_added, sort_order, use_function, set_function ) VALUES ( 'Enable One-Page Checkout?', 'CHECKOUT_ONE_ENABLED', 'false', 'Enable the one-page checkout processing for your store?  Default: <b>false</b>', $cgi, now(), 10, NULL, 'zen_cfg_select_option(array(\'true\', \'false\'),')");
    
    define('CHECKOUT_ONE_ENABLED', 'false');
    
    $db->Execute("INSERT INTO " . TABLE_CONFIGURATION . " ( configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, date_added, sort_order, use_function, set_function ) VALUES ( 'Enable One-Page Checkout Debug?', 'CHECKOUT_ONE_DEBUG', 'false', 'When enabled, debug files named <code>one_page_checkout-<em>xx</em>.log</code> are created in your /logs folder (<em>xx</em> is the customer_id for the checkout).  Use the <b>true</b> setting in combination with the <em>Debug: Customer List</em> setting to limit the customers for which the debug-action is taken.<br /><br />Default: <b>false</b>', $cgi, now(), 50, NULL, 'zen_cfg_select_option(array(\'true\', \'false\'),')");
    
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
            SET configuration_description = 'When enabled, debug files named <code>one_page_checkout-<em>xx</em>.log</code> are created in your /logs folder (<em>xx</em> is the customer_id for the checkout).  Use the <b>true</b> or <b>full</b> settings in combination with the <em>Debug: Customer List</em> setting to limit the customers for which the debug-action is taken.  Setting the value to <b>full</b> will also set the PHP error-level for the checkout so that <b>all</b> PHP errors are logged.<br /><br />Default: <b>false</b>',
                set_function = 'zen_cfg_select_option(array(\'true\', \'false\', \'full\'),'
          WHERE configuration_key = 'CHECKOUT_ONE_DEBUG' LIMIT 1"
    );
}
    
// -----
// Register the plugin's configuration page for display on the menus.
//
if (!zen_page_key_exists('configOnePageCheckout')) {
    zen_register_admin_page('configOnePageCheckout', 'BOX_TOOLS_CHECKOUT_ONE', 'FILENAME_CONFIGURATION', "gID=$cgi", 'configuration', 'Y');
}
