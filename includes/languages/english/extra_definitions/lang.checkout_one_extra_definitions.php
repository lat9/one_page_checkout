<?php
// -----
// Part of the One-Page Checkout plugin, provided under GPL 2.0 license by lat9
// Copyright (C) 2013-2022, Vinos de Frutas Tropicales.  All rights reserved.
//
// Last updated for OPC v2.4.2.
//
$define = [
    // when free shipping for orders over $XX.00 is active
    'FREE_SHIPPING_TITLE' => 'Free Shipping',
    'FREE_SHIPPING_DESCRIPTION' => 'Free shipping for orders over %s',

    'ERROR_GUEST_CHECKOUT_PAGE_DISALLOWED' => 'Access to that page requires a registered account.  You can create an account using our <a href="' . zen_href_link(FILENAME_LOGIN, '', 'SSL') . '">login</a> page.',
    'WARNING_GUEST_CHECKOUT_NOT_AVAILABLE' => 'Sorry, our guest-checkout is temporarily unavailable.  You can continue your checkout by either logging in or creating an account.',

    'WARNING_GUEST_NO_GCS' => '<b>Note</b>: You must have (or create) an account with our store to purchase Gift Certificates.',
    'WARNING_GUEST_GCS_RESET' => 'If you continue to <em>Checkout</em>, any information that you have entered during &quot;Guest Checkout&quot; will be lost.',
    'WARNING_GUEST_REMOVE_GC' => 'To continue with &quot;Guest Checkout&quot;, remove the Gift Certificate(s) from your shopping-cart <em>before</em> clicking a &quot;Checkout&quot; button or link.',

// -----
// This constant is used when an order's temporary shipping address has been overridden by paypalwpp's
// processing and identifies the address that was overridden by paypalwpp.  The message is both
// displayed to the customer and recorded as a customer-visible orders-status-history record.
//
    'WARNING_PAYPAL_SENDTO_CHANGED' => 'The delivery address that you entered (%s) was replaced by the address you selected at PayPal.  Please review your order and contact us if an update is needed.',
    'WARNING_PAYPALWPP_TOTAL_CHANGED' => 'Your order\'s total has changed, based on the delivery address you selected at PayPal.  Please review your order and re-submit.',

// -----
// This language-constant can be used in the store's update to /includes/modules/[YOUR_TEMPLATE/]information.php
// to point the customer to the order_status page link.
//
    'BOX_INFORMATION_ORDER_STATUS' => 'Order Status',
];
return $define;
