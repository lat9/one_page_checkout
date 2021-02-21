<?php
// -----
// Part of the One-Page Checkout plugin, provided under GPL 2.0 license by lat9 (cindy@vinosdefrutastropicales.com).
// Copyright (C) 2018-2021, Vinos de Frutas Tropicales.  All rights reserved.
//
// If the previous order was placed via the One-Page Checkout's "Guest Checkout", set a flag for the
// template processing (to load the alternate template) and reset the session-related information
// associated with that guest-checkout (handled by OPC's session-based class).
//
$order_placed_by_guest = false;
if (isset($_SESSION['order_placed_by_guest'])) {
    $order_placed_by_guest = true;
    $email_format = (ACCOUNT_EMAIL_PREFERENCE == '1') ? 'HTML' : 'TEXT';
    $check = $db->Execute(
        "SELECT customers_id
           FROM " . TABLE_CUSTOMERS . "
          WHERE customers_email_address = '" . zen_db_input($order->customer['email_address']) . "'
          LIMIT 1"
    );
    $offer_account_creation = $check->EOF;
    
    if (isset($_GET['action']) && $_GET['action'] == 'create_account') {
        $password = zen_db_prepare_input($_POST['password']);
        $confirmation = zen_db_prepare_input($_POST['confirmation']);
        $newsletter = (isset($_POST['newsletter']));
        $email_format = $_POST['email_format'];
        if (strlen($password) < ENTRY_PASSWORD_MIN_LENGTH) {
            $messageStack->add('checkout_success', ENTRY_PASSWORD_ERROR);
        } elseif ($password != $confirmation) {
            $messageStack->add('checkout_success', ENTRY_PASSWORD_ERROR_NOT_MATCHING);
        } else {
            $_SESSION['opc']->createAccountFromGuestInfo($_SESSION['order_placed_by_guest'], $password, $newsletter, $email_format);
            if (SESSION_RECREATE == 'True') {
                zen_session_recreate();
            }
            zen_redirect(zen_href_link(FILENAME_CREATE_ACCOUNT_SUCCESS, '', 'SSL')); 
        }
    }
}
