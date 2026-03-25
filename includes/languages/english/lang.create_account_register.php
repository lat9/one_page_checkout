<?php
// -----
// Part of the One-Page Checkout plugin, provided under GPL 2.0 license by lat9
// Copyright (C) 2018-2026, Vinos de Frutas Tropicales.  All rights reserved.
//
// Last updated: OPC v2.6.0
//
$define = [
    'NAVBAR_TITLE_REGISTER' => 'Account Registration',
    'TEXT_INSTRUCTIONS' => '<strong class="note">Note:</strong> If you already have an account with us, please login at our <a href="%s">login</a> page.  We\'ll ask for your address details once you are ready to place an order.',
    'ENTRY_EMAIL_ADDRESS_CONFIRM' => 'Confirm Email:',
    'ENTRY_EMAIL_FORMAT' => 'Email Format:',
    'BUTTON_SUBMIT_REGISTER_ALT' => 'Register',

    'HEADING_CONTACT_DETAILS' => 'Contact Details',

    'ENTRY_EMAIL_MISMATCH_ERROR' => 'The <em>Email</em> and <em>Confirm Email</em> entries do not match.',
    'ENTRY_EMAIL_MISMATCH_ERROR_JS' => '* The "Email" and "Confirm Email" entries do not match.',

    // -----
    // Set the placeholder for the telephone number for registered-account creations.  The value's set
    // to '*' if the configuration setting isn't available or isn't 'empty'; an empty string otherwise.
    //
    'TEXT_TELEPHONE_PLACEHOLDER' => (defined('CHECKOUT_ONE_REGISTERED_ACCT_TELEPHONE_MIN') && empty(CHECKOUT_ONE_REGISTERED_ACCT_TELEPHONE_MIN)) ? '' : '*',
];
return $define;
