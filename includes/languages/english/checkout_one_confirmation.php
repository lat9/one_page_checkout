<?php
// -----
// Part of the One-Page Checkout plugin, provided under GPL 2.0 license by lat9 (cindy@vinosdefrutastropicales.com).
// Copyright (C) 2013-2016, Vinos de Frutas Tropicales.  All rights reserved.
//
define('NAVBAR_TITLE_1', 'Checkout');
define('NAVBAR_TITLE_2', 'Confirm Your Order');

define ('HEADING_TITLE', 'Finalize and Confirm Your Order');

define('HEADING_BILLING_ADDRESS', 'Billing/Payment Information');
define('HEADING_DELIVERY_ADDRESS', 'Delivery/Shipping Information');
define('HEADING_SHIPPING_METHOD', 'Shipping Method:');
define('HEADING_PAYMENT_METHOD', 'Payment Method:');
define('HEADING_PRODUCTS', 'Shopping Cart Contents');
define('HEADING_TAX', 'Tax');
define('HEADING_ORDER_COMMENTS', 'Special Instructions or Order Comments');
// no comments entered
define('NO_COMMENTS_TEXT', 'None');

define('OUT_OF_STOCK_CAN_CHECKOUT', 'Products marked with ' . STOCK_MARK_PRODUCT_OUT_OF_STOCK . ' are out of stock.<br />Items not in stock will be placed on backorder.');
define ('BILLING_ADDRESS', '(Billing Address) ');
define ('SHIPPING_ADDRESS', '(Shipping Address) ');

define ('CAUTION_SHIPPING_CHANGED', 'Shipping charges were re-calculated, since the shipping address was changed.');
define ('ERROR_INVALID_SHIPPING_SELECTION', 'Invalid shipping selection. Please make another selection.');
define ('ERROR_PLEASE_RESELECT_SHIPPING_METHOD', 'Your available shipping options have changed. Please re-select your desired shipping method.');

define ('NO_JAVASCRIPT_MESSAGE', 'JavaScript is not enabled; please click the confirmation button below to process your order.');
define ('CHECKOUT_ONE_CONFIRMATION_LOADING', 'confirmation_one_loading.gif');
define ('CHECKOUT_ONE_CONFIRMATION_LOADING_ALT', 'Please wait ...');
define ('ERROR_NOJS_ORDER_CHANGED', 'Your order\'s details have changed.  Please review the current values and re-submit.');

// -----
// If your store uses a payment method that needs "additional time" to process (like "Ceon Manual Card"), you can add some instructions
// to your customers on the checkout_one_confirmation page letting them know that the processing might take a while!
//
define ('CHECKOUT_ONE_CONFIRMATION_INSTRUCTIONS', '');