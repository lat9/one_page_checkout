<?php
// -----
// Part of the One-Page Checkout plugin, provided under GPL 2.0 license by lat9 (cindy@vinosdefrutastropicales.com).
// Copyright (C) 2013-2016, Vinos de Frutas Tropicales.  All rights reserved.
//

define('NAVBAR_TITLE_1', 'Checkout');
define('NAVBAR_TITLE_2', 'Select Shipping/Payment and Confirm Your Order');

define('HEADING_TITLE', 'Checkout');

define('TABLE_HEADING_SHIPPING_ADDRESS', 'Shipping Address');
define('TEXT_CHOOSE_SHIPPING_DESTINATION', 'Your order will be shipped to the address above or you may change the shipping address by clicking the <em>Change Address</em> button.');  //-20130916-lat9
define('TITLE_SHIPPING_ADDRESS', 'Shipping Address:');

define('TABLE_HEADING_SHIPPING_METHOD', 'Shipping Method:');
define('TEXT_CHOOSE_SHIPPING_METHOD', '');
define('TITLE_PLEASE_SELECT', 'Please Select');
define('TEXT_ENTER_SHIPPING_INFORMATION', 'This is currently the only shipping method available to use on this order.');
define('TITLE_NO_SHIPPING_AVAILABLE', 'Not Available At This Time');
define('TEXT_NO_SHIPPING_AVAILABLE','<span class="alert">Sorry, we are not shipping to your region at this time.</span><br />Please contact us for alternate arrangements.');

define('TABLE_HEADING_COMMENTS', 'Special Instructions or Comments');

define('TITLE_CONTINUE_CHECKOUT_PROCEDURE', 'Continue to Step 2');
define('TEXT_CONTINUE_CHECKOUT_PROCEDURE', '- choose your payment method.');

define('ERROR_PLEASE_RESELECT_SHIPPING_METHOD', 'Your available shipping options have changed. Please re-select your desired shipping method.');
define('ERROR_UNKNOWN_SHIPPING_SELECTION', 'An unknown shipping-method was submitted.  Please contact the store owner.');
define('ERROR_NO_SHIPPING_SELECTED', 'You must choose a shipping method for your order before the order can be confirmed.');

// -----
// NOTE: The following constants are used in the page's jscript_main.php file as javascript text literals.  If you want to include single-quotes in a value,
// you'll need to specify them as \\\'; for a new-line, use \n.  Just be sure to keep a constant's string within a set of single-quots and you should be good-to-go!
//
define('JS_ERROR_SESSION_TIMED_OUT', 'Sorry, your session has timed out.\n\nThe items in your cart have been saved and will be restored the next time you log in.');
define('JS_ERROR_AJAX_TIMEOUT', 'It\\\'s taking a little longer than normal to update your order\\\'s shipping cost.  Please close this message and try again.\n\nIf you continue to receive this message, please contact us.');

// ----- From checkout_payment -----

define('TABLE_HEADING_BILLING_ADDRESS', 'Billing Address');
define('TEXT_SELECTED_BILLING_DESTINATION', 'Your billing address is shown above. The billing address should match the address on your credit card statement. You can change the billing address by clicking the <em>Change Address</em> button.');  //-20130916-lat9
define('TITLE_BILLING_ADDRESS', 'Billing Address:');

define('TABLE_HEADING_PAYMENT_METHOD', 'Payment Method');
define('TEXT_SELECT_PAYMENT_METHOD', 'Please select a payment method for this order.');
define('TITLE_PLEASE_SELECT', 'Please Select');
define('TEXT_ENTER_PAYMENT_INFORMATION', '');
define('TABLE_HEADING_COMMENTS', 'Special Instructions or Order Comments');

define('TITLE_NO_PAYMENT_OPTIONS_AVAILABLE', 'Not Available At This Time');
define('TEXT_NO_PAYMENT_OPTIONS_AVAILABLE','<span class="alert">Sorry, we are not accepting payments from your region at this time.</span><br />Please contact us for alternate arrangements.');

define('TITLE_CONTINUE_CHECKOUT_PROCEDURE', '<strong>Continue to Step 3</strong>');
define('TEXT_CONTINUE_CHECKOUT_PROCEDURE', '- to confirm your order.');

define('TABLE_HEADING_CONDITIONS', '<span class="termsconditions">Terms and Conditions</span>');
define('TEXT_CONDITIONS_DESCRIPTION', '<span class="termsdescription">Please acknowledge the terms and conditions bound to this order by ticking the following box. The terms and conditions can be read <a href="' . zen_href_link(FILENAME_CONDITIONS, '', 'SSL') . '"><span class="pseudolink">here</span></a>.');
define('TEXT_CONDITIONS_CONFIRM', '<span class="termsiagree">I have read and agreed to the terms and conditions bound to this order.</span>');

define('TEXT_CHECKOUT_AMOUNT_DUE', 'Total Amount Due: ');
define('TEXT_YOUR_TOTAL','Your Total');

// ----- From checkout_confirmation -----
define('HEADING_BILLING_ADDRESS', 'Billing/Payment Information');
define('HEADING_DELIVERY_ADDRESS', 'Delivery/Shipping Information');
define('HEADING_SHIPPING_METHOD', 'Shipping Method:');
define('HEADING_PAYMENT_METHOD', 'Payment Method:');
define('HEADING_PRODUCTS', 'Shopping Cart Contents');
define('HEADING_TAX', 'Tax');
define('HEADING_ORDER_COMMENTS', 'Special Instructions or Order Comments');
// no comments entered
define('NO_COMMENTS_TEXT', 'None');
define('TITLE_CONTINUE_CHECKOUT_PROCEDURE', '<strong>Continue to Step 4</strong>');
define('TEXT_CONTINUE_CHECKOUT_PROCEDURE', '- to complete your order. Thank you!');

define('OUT_OF_STOCK_CAN_CHECKOUT', 'Products marked with ' . STOCK_MARK_PRODUCT_OUT_OF_STOCK . ' are out of stock.<br />Items not in stock will be placed on backorder.');

define ('TEXT_USE_BILLING_FOR_SHIPPING', 'Shipping Address, Same as Billing?');
define ('ALT_TEXT_APPLY_DEDUCTION', 'Apply');

define ('TEXT_CONFIRMATION_EMAILS_SENT_TO', 'Upon order-submittal, a confirmation email will be sent to <b>%s</b>.');  //-The %s is filled in with the customer's email address

// -----
// You can modify this definition to change the name of the image-button/alt-text used to confirm the customer's order.
//
define ('BUTTON_IMAGE_CHECKOUT_ONE_CONFIRM', 'button_confirm_order.gif');
define ('BUTTON_CHECKOUT_ONE_CONFIRM_ALT', 'Confirm Order');

// -----
// Use these definitions to set any messages you might want to convey to your customers on the checkout-one page.
//
    // -----
    // This constant defines the instructions you want displayed at the very top of the "checkout_one" page, before the form entry.
    //
    define ('TEXT_CHECKOUT_ONE_TOP_INSTRUCTIONS', '');  //-Displayed within a set of <p>...</p> tags if not empty.
    
    // -----
    // These constants define the instructions that are inserted below the shopping-cart/totals and above the "confirm order" button.
    //
    define ('TEXT_CHECKOUT_ONE_INSTRUCTION_LABEL', ''); //-Displays as the "legend" value for the fieldset that surrounds the message below
    define ('TEXT_CHECKOUT_ONE_INSTRUCTIONS', '');      //-Displayed within a set of <p>...</p> tags if not empty