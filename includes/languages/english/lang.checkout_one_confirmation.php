<?php
// -----
// Part of the One-Page Checkout plugin, provided under GPL 2.0 license by lat9.
// Copyright (C) 2013-2022, Vinos de Frutas Tropicales.  All rights reserved.
//
$define = [
    'NAVBAR_TITLE_1' => 'Checkout',
    'NAVBAR_TITLE_2' => 'Confirm Your Order',

    'HEADING_TITLE' => 'Finalize and Confirm Your Order',

    'HEADING_BILLING_ADDRESS' => 'Billing/Payment Information',
    'HEADING_DELIVERY_ADDRESS' => 'Delivery/Shipping Information',
    'HEADING_SHIPPING_METHOD' => 'Shipping Method:',
    'HEADING_PAYMENT_METHOD' => 'Payment Method:',
    'HEADING_PRODUCTS' => 'Shopping Cart Contents',
    'HEADING_TAX' => 'Tax',
    'HEADING_ORDER_COMMENTS' => 'Special Instructions or Order Comments',
// no comments entered
    'NO_COMMENTS_TEXT' => 'None',

    'BILLING_ADDRESS' => '(Billing Address) ',
    'SHIPPING_ADDRESS' => '(Shipping Address) ',

    'CAUTION_SHIPPING_CHANGED' => 'Shipping charges were re-calculated, since the shipping address was changed.',
    'ERROR_INVALID_SHIPPING_SELECTION' => 'Invalid shipping selection. Please make another selection.',
    'ERROR_PLEASE_RESELECT_SHIPPING_METHOD' => 'Your available shipping options have changed. Please re-select your desired shipping method.',

    'NO_JAVASCRIPT_MESSAGE' => 'JavaScript is not enabled; please click the confirmation button below to process your order.',
    'CHECKOUT_ONE_CONFIRMATION_LOADING' => 'confirmation_one_loading.gif',
    'CHECKOUT_ONE_CONFIRMATION_LOADING_ALT' => 'Please wait ...',
    'ERROR_NOJS_ORDER_CHANGED' => 'Your order\'s details have changed.  Please review the current values and re-submit.',

    'ERROR_INVALID_TEMPORARY_ENTRIES' => 'Some of the information you entered isn\'t correct, please re-enter.',

// -----
// If your store uses a payment method that needs "additional time" to process (like "Ceon Manual Card"), you can add some instructions
// to your customers on the checkout_one_confirmation page letting them know that the processing might take a while!
//
    'CHECKOUT_ONE_CONFIRMATION_INSTRUCTIONS' => '',
];
return $define;
