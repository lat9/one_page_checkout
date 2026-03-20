<?php
// -----
// Part of the One-Page Checkout plugin, provided under GPL 2.0 license by lat9 (cindy@vinosdefrutastropicales.com).
// Copyright (C) 2013-2026, Vinos de Frutas Tropicales.  All rights reserved.
//
// Last updated: OPC v2.6.0
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
        $entries_to_remove = [];
        $log_file_name = DIR_FS_LOGS . '/opc_address_book_cleanup.log';
        error_log(date('Y-m-d H:i:s') . ": Removing $entry_count guest address-book entries." . PHP_EOL, 3, $log_file_name);
        foreach ($entries as $next_entry) {
            error_log(str_replace(',"', ', "', json_encode($next_entry)) . PHP_EOL, 3, $log_file_name);
            $entries_to_remove[] = $next_entry['address_book_id'];
        }
        $db->Execute(
            "DELETE FROM " . TABLE_ADDRESS_BOOK . "
              WHERE address_book_id IN (" . implode(', ', $entries_to_remove) . ")"
        );
    }
}
