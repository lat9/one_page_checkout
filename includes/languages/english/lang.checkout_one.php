<?php
// -----
// Part of the One-Page Checkout plugin, provided under GPL 2.0 license by lat9.
// Copyright (C) 2013-2022, Vinos de Frutas Tropicales.  All rights reserved.
//
// Last updated for OPC v2.4.2.
//
$define = [
    'NAVBAR_TITLE_1' => 'Checkout',
    'NAVBAR_TITLE_2' => 'Select Shipping/Payment and Confirm Your Order',

    'HEADING_TITLE' => 'Checkout',
    'BUTTON_SAVE_CHANGES_ALT' => 'Save Changes',
    'BUTTON_SAVE_CHANGES_TITLE' => 'Save the changes made to this address',
    'BUTTON_CANCEL_CHANGES_ALT' => 'Cancel',
    'BUTTON_CANCEL_CHANGES_TITLE' => 'Cancel all changes made to this address',

    'TEXT_ADD_TO_ADDRESS_BOOK' => 'Add to Address Book',
    'TITLE_ADD_TO_ADDRESS_BOOK' => 'Tick this box to add this address to your personal address book',

    'TITLE_CONTACT_INFORMATION' => 'Contact Information',
    'ENTRY_EMAIL_ADDRESS_CONF' => 'Confirm Email:',
    'ENTRY_EMAIL_ADDRESS_CONF_TEXT' => '*',
    'ERROR_EMAIL_MUST_MATCH_CONFIRMATION' => 'The <em>Email Address</em> must match the <em>Confirm Email</em> value.',
    'TEXT_CONTACT_INFORMATION' => 'We will use this information <em>only</em> to contact you regarding this order.',

    'TEXT_SELECT_FROM_SAVED_ADDRESSES' => 'Select from saved addresses',

    'TABLE_HEADING_SHIPPING_ADDRESS' => 'Shipping Address',
    'TEXT_CHOOSE_SHIPPING_DESTINATION' => 'Your order will be shipped to the address above or you may change the shipping address by clicking the <em>Change Address</em> button.',
    'TITLE_SHIPPING_ADDRESS' => 'Shipping Address:',

    'TABLE_HEADING_SHIPPING_METHOD' => 'Shipping Method:',
    'TEXT_CHOOSE_SHIPPING_METHOD' => '',
    'TITLE_PLEASE_SELECT' => 'Please Select',
    'TEXT_ENTER_SHIPPING_INFORMATION' => 'This is currently the only shipping method available to use on this order.',
    'TITLE_NO_SHIPPING_AVAILABLE' => 'Not Available At This Time',
    'TEXT_NO_SHIPPING_AVAILABLE' => '<span class="alert">Sorry, we are not shipping to your region at this time.</span><br>Please contact us for alternate arrangements.',

    'TABLE_HEADING_COMMENTS' => 'Special Instructions or Comments',

    'ERROR_PLEASE_RESELECT_SHIPPING_METHOD' => 'Either the available shipping options or your chosen shipping method\'s price has changed. Please re-select/review your desired shipping method.',
    'ERROR_UNKNOWN_SHIPPING_SELECTION' => 'An unknown shipping-method was submitted.  Please contact the store owner.',
    'ERROR_INVALID_REQUEST' => 'An unknown request was received.  Please contact the store owner.',

// -----
// These definitions are prepended to any address-value-related error message as an indication
// of which address-field is being referenced.
//
    'ERROR_IN_BILLING' => '[Billing]: ',
    'ERROR_IN_SHIPPING' => '[Shipping]: ',

// -----
// NOTE: The following constants are used in the page's jscript_main.php file as javascript text literals.  If you want to include single-quotes in a value,
// you'll need to specify them as \\\'; for a new-line, use \n.  Just be sure to keep a constant's string within a set of single-quotes and you should be good-to-go!
//
    'JS_ERROR_SESSION_TIMED_OUT' => 'Sorry, your session has timed out.\n\nThe items in your cart have been saved and will be restored the next time you log in.',
    'JS_ERROR_OPC_NOT_ENABLED' => 'Our expedited checkout process is temporarily unavailable.  You\\\'ll be redirected to our alternate checkout process.',

    'JS_ERROR_AJAX_TIMEOUT' => 'It\\\'s taking a little longer than normal to update your order\\\'s details.',
    'JS_ERROR_AJAX_SHIPPING_TIMEOUT' => 'It\\\'s taking a little longer than normal to update your order\\\'s shipping cost.',
    'JS_ERROR_AJAX_PAYMENT_TIMEOUT' => 'It\\\'s taking a little longer than normal to update your order\\\'s payment method.',
    'JS_ERROR_AJAX_SET_ADDRESS_TIMEOUT' => 'It\\\'s taking a little longer than normal to set your order\\\'s address.',
    'JS_ERROR_AJAX_RESTORE_ADDRESS_TIMEOUT' => 'It\\\'s taking a little longer than normal to restore your order\\\'s address values.',
    'JS_ERROR_AJAX_VALIDATE_ADDRESS_TIMEOUT' => 'It\\\'s taking a little longer than normal to validate your order\\\'s address details.',

    'JS_ERROR_AJAX_RESTORE_CUSTOMER_TIMEOUT' => 'It\\\'s taking a little longer than normal to restore your customer details.',
    'JS_ERROR_AJAX_VALIDATE_CUSTOMER_TIMEOUT' => 'It\\\'s taking a little longer than normal to validate your customer details.',

    'JS_ERROR_CONTACT_US' => '  Please close this message and try again.\n\nIf you continue to receive this message, please contact us.',

    'ERROR_NO_SHIPPING_SELECTED' => 'You must choose a shipping method for your order before the order can be confirmed.',
    'TITLE_BILLING_ADDRESS' => 'Billing Address:',
    'TITLE_BILLING_SHIPPING_ADDRESS' => 'Billing/Shipping Address:',

// -----
// This definition is used on the default page display when there is a javascript/jQuery error (or when javascript is disabled).
// The customer can't checkout via the OPC so we'll give them a link through which they can access the
// "normal" 3-page checkout process.  
//
// NOTE: The %s value in the link is filled in by the checkout_one page's template to contain
// a link back to the checkout_shipping page with OPC disabled.
//
    'TEXT_NOSCRIPT_JS_ERROR' => 'Sorry, but our expedited checkout process cannot be used.  Click <a href="%s">here</a> to use our alternate checkout process.',

// ----- From checkout_payment -----

    'TABLE_HEADING_BILLING_ADDRESS' => 'Billing Address',
    'TEXT_SELECTED_BILLING_DESTINATION' => 'Your billing address is shown above. The billing address should match the address on your credit card statement. You can change the billing address by clicking the <em>Change Address</em> button.',

    'TABLE_HEADING_PAYMENT_METHOD' => 'Payment Method',
    'TEXT_SELECT_PAYMENT_METHOD' => 'Please select a payment method for this order.',
    'TEXT_ENTER_PAYMENT_INFORMATION' => '',

    'TITLE_NO_PAYMENT_OPTIONS_AVAILABLE' => 'Not Available At This Time',
    'TEXT_NO_PAYMENT_OPTIONS_AVAILABLE' => '<span class="alert">Sorry, we are not accepting payments from your region at this time.</span><br />Please contact us for alternate arrangements.',

    'TABLE_HEADING_CONDITIONS' => '<span class="termsconditions">Terms and Conditions</span>',
    'TEXT_CONDITIONS_DESCRIPTION' =>  '<span class="termsdescription">Please acknowledge the terms and conditions bound to this order by ticking the following box. The terms and conditions can be read <a href="' . zen_href_link(FILENAME_CONDITIONS, '', 'SSL') . '"><span class="pseudolink">here</span></a>.</span>',
    'TEXT_CONDITIONS_CONFIRM' => '<span class="termsiagree">I have read and agreed to the terms and conditions bound to this order.</span>',

    'TEXT_CHECKOUT_AMOUNT_DUE' => 'Total Amount Due: ',
    'TEXT_YOUR_TOTAL' => 'Your Total',

// ----- From checkout_confirmation -----
    'HEADING_BILLING_ADDRESS' => 'Billing/Payment Information',
    'HEADING_DELIVERY_ADDRESS' => 'Delivery/Shipping Information',
    'HEADING_SHIPPING_METHOD' => 'Shipping Method:',
    'HEADING_PAYMENT_METHOD' => 'Payment Method:',
    'HEADING_PRODUCTS' => 'Shopping Cart Contents',
    'HEADING_TAX' => 'Tax',
    'HEADING_ORDER_COMMENTS' => 'Special Instructions or Order Comments',
// no comments entered
    'NO_COMMENTS_TEXT' => 'None',

    'TEXT_USE_BILLING_FOR_SHIPPING' =>  'Shipping Address, Same as Billing?',
    'ALT_TEXT_APPLY_DEDUCTION' => 'Apply',

    'TEXT_CONFIRMATION_EMAILS_SENT_TO' => 'A confirmation of this order will be emailed to <b>%s</b>.',  //-The %s is filled in with the customer's email address

// -----
// You can modify this definition to change the name of the image-button/alt-text used to confirm the customer's order.
//
    'BUTTON_IMAGE_CHECKOUT_ONE_CONFIRM' => 'button_confirm_order.gif',
    'BUTTON_CHECKOUT_ONE_CONFIRM_ALT' => 'Confirm Order',

    'BUTTON_IMAGE_CHECKOUT_ONE_REVIEW' => 'button_continue_checkout.gif',
    'BUTTON_CHECKOUT_ONE_REVIEW_ALT' => 'Review Order',

    'CHECKOUT_ONE_LOADING' => 'confirmation_one_loading.gif',
    'CHECKOUT_ONE_LOADING_ALT' => 'Please wait ...',

// -----
// Use these definitions to set any messages you might want to convey to your customers on the checkout-one page.
//
    // -----
    // This constant defines the instructions you want displayed at the very top of the "checkout_one" page, before the form entry.
    //
    'TEXT_CHECKOUT_ONE_TOP_INSTRUCTIONS' => '', //-Displayed within a set of <p>...</p> tags if not empty.
    
    // -----
    // These constants define the instructions that are inserted below the shopping-cart/totals and above the "confirm order" button.
    //
    'TEXT_CHECKOUT_ONE_INSTRUCTION_LABEL' => 'Bottom instructions', //-Displays as the "legend" value for the fieldset that surrounds the message below
    'TEXT_CHECKOUT_ONE_INSTRUCTIONS' => 'Bottom instructions',      //-Displayed within a set of <p>...</p> tags if not empty
];
return $define;
