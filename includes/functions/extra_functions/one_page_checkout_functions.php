<?php
// -----
// Part of the One-Page Checkout plugin, provided under GPL 2.0 license by lat9 (cindy@vinosdefrutastropicales.com).
// Copyright (C) 2013-2021, Vinos de Frutas Tropicales.  All rights reserved.
//

// -----
// For versions of OPC prior to v2.1.0, it was possible that additional address-book entries were recorded
// for the temporary (guest) account.  We'll clean those up, if present, on each page-load, recording any
// addresses found for the store-owner's inspection.
//
if (defined('CHECKOUT_ONE_ENABLED') && defined('CHECKOUT_ONE_GUEST_CUSTOMER_ID')) {
    $check = $db->Execute(
        "SELECT COUNT(*) AS count
           FROM " . TABLE_ADDRESS_BOOK . "
          WHERE customers_id = " . (int)CHECKOUT_ONE_GUEST_CUSTOMER_ID,
          false,
          false,
          0,
          true
    );
    if ($check->fields['count'] > 2) {
        $entry_count = $check->fields['count'] - 2;
        $entries = $db->Execute(
            "SELECT *
               FROM " . TABLE_ADDRESS_BOOK . "
              WHERE customers_id = " . (int)CHECKOUT_ONE_GUEST_CUSTOMER_ID . "
              ORDER BY address_book_id DESC
              LIMIT $entry_count",
              false,
              false,
              0,
              true
        );
        $entries_to_remove = array();
        $log_file_name = DIR_FS_LOGS . '/opc_address_book_cleanup.log';
        error_log(date('Y-m-d H:i:s') . ": Removing $entry_count guest address-book entries." . PHP_EOL, 3, $log_file_name);
        while (!$entries->EOF) {
            error_log(str_replace(',"', ', "', json_encode($entries->fields)) . PHP_EOL, 3, $log_file_name);
            $entries_to_remove[] = $entries->fields['address_book_id'];
            $entries->MoveNext();
        }
        $db->Execute(
            "DELETE FROM " . TABLE_ADDRESS_BOOK . "
              WHERE address_book_id IN (" . implode(', ', $entries_to_remove) . ")"
        );
    }
}

// -----
// This function identifies whether (true) or not (false) the current customer session is
// associated with a guest-checkout process.
//
if (!function_exists('zen_in_guest_checkout')) {
    function zen_in_guest_checkout()
    {
        global $zco_notifier;

        $in_guest_checkout = false;
        $zco_notifier->notify('NOTIFY_ZEN_IN_GUEST_CHECKOUT', '', $in_guest_checkout);
        return (bool)$in_guest_checkout;
    }
}

// -----
// This function identifies whether (true) or not (false) a customer is currently logged into the site.
//
if (!function_exists('zen_is_logged_in')) {
    function zen_is_logged_in()
    {
        global $zco_notifier;

        $is_logged_in = (!empty($_SESSION['customer_id']));
        $zco_notifier->notify('NOTIFY_ZEN_IS_LOGGED_IN', '', $is_logged_in);
        return (bool)$is_logged_in;
    }
}

// -----
// This function identifies whether (true) or not (false) the current page is being accessed
// by a spider.
//
if (!function_exists('zen_is_spider_session')) {
    function zen_is_spider_session()
    {
        $user_agent = strtolower($_SERVER['HTTP_USER_AGENT']);
        $spider_flag = false;
        if (zen_not_null($user_agent)) {
            $spiders = file(DIR_WS_INCLUDES . 'spiders.txt');
            for ($i=0, $n=count($spiders); $i<$n; $i++) {
                if (zen_not_null($spiders[$i]) && strpos($spiders[$i], '$Id:') !== 0) {
                    if (strpos($user_agent, trim($spiders[$i])) !== false) {
                        $spider_flag = true;
                        break;
                    }
                }
            }
        }
        return $spider_flag;
    }
}