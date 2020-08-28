<?php
// -----
// A "quick" script to provide address-book table fix-ups for invalid
// guest billing/shipping addresses.  The processing is built into OPC 2.3.4,
// but this enables any installation to perform the fixup without upgrading.
//
require 'includes/application_top.php';

$fixup_logname = DIR_FS_LOGS . '/opc_admin_messages.log';
$log_message = '';

// -----
// If there's no guest customer-id defined, make sure that neither the guest
// bill-to or send-to address-book entries are defined.  If they are, remove
// those entries from the address_book table.
//
if (!defined('CHECKOUT_ONE_GUEST_CUSTOMER_ID')) {
    if (defined('CHECKOUT_ONE_GUEST_BILLTO_ADDRESS_BOOK_ID')) {
        $log_message .= 'Removing billto address-book entry (' . CHECKOUT_ONE_GUEST_BILLTO_ADDRESS_BOOK_ID . ') for undefined guest customer_id.' . PHP_EOL;
        $db->Execute(
            "DELETE FROM " . TABLE_ADDRESS_BOOK . "
              WHERE address_book_id = " . (int)CHECKOUT_ONE_GUEST_BILLTO_ADDRESS_BOOK_ID . "
              LIMIT 1"
        );
        $db->Execute(
            "DELETE FROM " . TABLE_CONFIGURATION . "
              WHERE configuration_key = 'CHECKOUT_ONE_GUEST_BILLTO_ADDRESS_BOOK_ID'
              LIMIT 1"
        );
    }
    if (defined('CHECKOUT_ONE_GUEST_SENDTO_ADDRESS_BOOK_ID')) {
        $log_message .= 'Removing send-to address-book entry (' . CHECKOUT_ONE_GUEST_SENDTO_ADDRESS_BOOK_ID . ') for undefined guest customer_id.' . PHP_EOL;
        $db->Execute(
            "DELETE FROM " . TABLE_ADDRESS_BOOK . "
              WHERE address_book_id = " . (int)CHECKOUT_ONE_GUEST_SENDTO_ADDRESS_BOOK_ID . "
              LIMIT 1"
        );
        $db->Execute(
            "DELETE FROM " . TABLE_CONFIGURATION . "
              WHERE configuration_key = 'CHECKOUT_ONE_GUEST_SENDTO_ADDRESS_BOOK_ID'
              LIMIT 1"
        );
    }
// -----
// Otherwise the guest customer-id *is* present in the database.  Make sure that it's found in
// the customers table and that each of the billto/shipto address-book entries are valid.
//
} else {
    $guest_customer_id = (int)CHECKOUT_ONE_GUEST_CUSTOMER_ID;
    $guest_id_check = $db->Execute(
        "SELECT *
           FROM " . TABLE_CUSTOMERS . "
          WHERE customers_id = $guest_customer_id
          LIMIT 1"
    );
    
    // -----
    // If there's no customer-record for the guest customer-id, ensure that any customer-info
    // and address-book entries for that customer-id are removed and further remove the guest
    // customer-id and address-book-id values from the configuration table.  They'll be re-generated
    // by OPC's admin-level initialization script.
    //
    if ($guest_id_check->EOF) {
        $log_message .= "Invalid guest customer_id value (" . CHECKOUT_ONE_GUEST_CUSTOMER_ID . ") found.  Removing configuration settings to enable re-generation." . PHP_EOL;
        $db->Execute(
            "DELETE FROM " . TABLE_CUSTOMERS_INFO . "
              WHERE customers_id = $guest_customer_id
              LIMIT 1"
        );
        $db->Execute(
            "DELETE FROM " . TABLE_ADDRESS_BOOK . "
              WHERE customers_id = $guest_customer_id"
        );
        $db->Execute(
            "DELETE FROM " . TABLE_CONFIGURATION . "
              WHERE configuration_key IN ('CHECKOUT_ONE_GUEST_CUSTOMER_ID', 'CHECKOUT_ONE_GUEST_BILLTO_ADDRESS_BOOK_ID', 'CHECKOUT_ONE_GUEST_SENDTO_ADDRESS_BOOK_ID')"
        );
    // -----
    // Otherwise, a valid guest customer-id and associated customers-table record has been located.
    // Make sure that the address-book entries are 'solid', too.
    //
    } else {
        // -----
        // Create a data-array containing the values set for the guest-customer's addresses.  They'll
        // be used to check against any in-database content.
        //
        $sql_data_array = array(
            'customers_id' => $guest_customer_id,
            'entry_firstname' => 'Guest',
            'entry_lastname' => 'Customer, **do not remove**',
            'entry_street_address' => 'Default billing address',
            'entry_country_id' => (int)STORE_COUNTRY,
            'entry_zone_id' => (int)STORE_ZONE
        );
        
        // -----
        // Initialize the guest shipto/billto address-book id's, used towards the bottom of this
        // script to ensure that a guest-customer has only those (at most) addresses.
        //
        $guest_billto_id = 0;
        $guest_sendto_id = 0;

        // -----
        // If the guest billto address book id configuration value is present, make sure that
        // it's a valid address-book entry.  That is, it's present and contains the default information.
        //
        // If the information's invalid, remove the entry, note the change in a log file and recreate the
        // default entry.
        //
        if (defined('CHECKOUT_ONE_GUEST_BILLTO_ADDRESS_BOOK_ID')) {
            $guest_billto_id = (int)CHECKOUT_ONE_GUEST_BILLTO_ADDRESS_BOOK_ID;
            $opc_ab_info_ok = false;
            $opc_ab_info = $db->Execute(
                "SELECT *
                   FROM " . TABLE_ADDRESS_BOOK . "
                  WHERE address_book_id = $guest_billto_id
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
            
            // -----
            // If the address-book record is present and valid, make sure that that record is
            // noted as the guest-customer's default address, setting that if not.
            //
            if ($opc_ab_info_ok) {
                if ($guest_id_check->fields['customers_default_address_id'] != $guest_billto_id) {
                    $log_message = "Resetting guest-customer's default address, was '" . $guest_id_check->fields['customers_default_address_id'] . "'." . PHP_EOL;
                    $db->Execute(
                        "UPDATE " . TABLE_CUSTOMERS . "
                            SET customers_default_address_id = $guest_billto_id
                          LIMIT 1"
                    );
                }
            // -----
            // Otherwise, there's an issue with the guest customer's billto address definition.
            // Recreate that base address-book record and 'tie' it to the guest-customer's default
            // address-book id.
            //
            } else {
                zen_db_perform(TABLE_ADDRESS_BOOK, $sql_data_array);
                $address_book_id = zen_db_insert_id();
                $db->Execute(
                    "UPDATE " . TABLE_CONFIGURATION . "
                        SET configuration_value = '$address_book_id'
                      WHERE configuration_key = 'CHECKOUT_ONE_GUEST_BILLTO_ADDRESS_BOOK_ID'
                      LIMIT 1"
                );
                $db->Execute(
                   "UPDATE " . TABLE_CUSTOMERS . "
                        SET customers_default_address_id = $address_book_id
                      WHERE customers_id = $guest_customer_id
                      LIMIT 1"
                );
                $db->Execute(
                    "DELETE FROM " . TABLE_ADDRESS_BOOK . "
                      WHERE address_book_id = $guest_billto_id
                      LIMIT 1"
                );
                $guest_billto_id = $address_book_id;
                $log_message .= "Removed invalid guest billing-address information (" . CHECKOUT_ONE_GUEST_BILLTO_ADDRESS_BOOK_ID . "): $opc_ab_msg." . PHP_EOL;
            }
        }
        
        // -----
        // Perform the same checks on the guest-customer's send-to address-book record.
        //
        if (defined('CHECKOUT_ONE_GUEST_SENDTO_ADDRESS_BOOK_ID')) {
            $guest_sendto_id = (int)CHECKOUT_ONE_GUEST_SENDTO_ADDRESS_BOOK_ID;
            $sql_data_array['entry_street_address'] = 'Default shipping address';
            
            $opc_ab_info_ok = false;
            $opc_ab_info = $db->Execute(
                "SELECT *
                   FROM " . TABLE_ADDRESS_BOOK . "
                  WHERE address_book_id = $guest_sendto_id
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
                zen_db_perform(TABLE_ADDRESS_BOOK, $sql_data_array);
                $address_book_id = zen_db_insert_id();
                $db->Execute(
                    "UPDATE " . TABLE_CONFIGURATION . "
                        SET configuration_value = '$address_book_id'
                      WHERE configuration_key = 'CHECKOUT_ONE_GUEST_SENDTO_ADDRESS_BOOK_ID'
                      LIMIT 1"
                );
                $db->Execute(
                    "DELETE FROM " . TABLE_ADDRESS_BOOK . "
                      WHERE address_book_id = $guest_sendto_id
                      LIMIT 1"
                );
                $guest_sendto_id = $address_book_id;
                $log_message .= "Removed invalid guest shipping-address information (" . CHECKOUT_ONE_GUEST_SENDTO_ADDRESS_BOOK_ID . "): $opc_ab_msg." . PHP_EOL;
            }
        }
        
        // -----
        // Finally, for completeness, make sure that there are no guest-customer address-book records
        // other than the configured billto/sendto ones.
        //
        if ($guest_billto_id != 0 || $guest_sendto_id != 0) {
            $valid_ids = ($guest_billto_id == 0) ? '' : $guest_billto_id;
            if ($guest_sendto_id != 0) {
                if (!empty($valid_ids)) {
                    $valid_ids .= ', ';
                }
                $valid_ids .= $guest_sendto_id;
            }
            $db->Execute(
                "DELETE FROM " . TABLE_ADDRESS_BOOK . "
                  WHERE customers_id = $guest_customer_id
                    AND address_book_id NOT IN ($valid_ids)"
            );
            $addresses_removed = (int)$db->link->affected_rows;
            if ($addresses_removed != 0) {
                $log_message .= "Removed $addresses_removed invalid, i.e. neither billto nor shipto, guest addresses." . PHP_EOL;
            }
        }
    }
}

// -----
// Let the admin know that the update's complete, saving any issues found in the log.
//
if (empty($log_message)) {
    echo 'No fix-ups required!';
} else {
    error_log(date('Y-m-d H:i:s: ') . $log_message . PHP_EOL, 3, $fixup_logname);
    echo "Fix-ups required; original content saved in $fixup_logname<br><br>";
    echo nl2br($log_message);
}

require DIR_WS_INCLUDES . 'application_bottom.php';
