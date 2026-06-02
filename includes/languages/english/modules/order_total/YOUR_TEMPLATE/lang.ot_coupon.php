<?php
// -----
// Part of the One-Page Checkout plugin, provided under GPL 2.0 license by lat9 (cindy@vinosdefrutastropicales.com).
// Copyright (C) 2022-2026, Vinos de Frutas Tropicales.  All rights reserved.
//
// Last updated for OPC v2.6.2
//
$define = [];
if (zen_config('CHECKOUT_ONE_ENABLED') === 'true') {
    $define['MODULE_ORDER_TOTAL_COUPON_REDEEM_INSTRUCTIONS'] = '<p>Please type your coupon code into the discount code box below. Your coupon will be applied to the total and reflected in your order\'s display after you click the button to the right or submit your order. Please note: you may only use one coupon per order.</p>';
}
return $define;
