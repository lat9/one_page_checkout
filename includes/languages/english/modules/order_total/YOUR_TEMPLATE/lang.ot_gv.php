<?php
// -----
// Part of the One-Page Checkout plugin, provided under GPL 2.0 license by lat9 (cindy@vinosdefrutastropicales.com).
// Copyright (C) 2022, Vinos de Frutas Tropicales.  All rights reserved.
//
// Last updated for OPC v2.4.2.
//
$define = [];
if (defined('CHECKOUT_ONE_ENABLED') && CHECKOUT_ONE_ENABLED == 'true') {
    $define['MODULE_ORDER_TOTAL_GV_REDEEM_INSTRUCTIONS'] = '<p>To use ' . TEXT_GV_NAME . ' funds already in your account, type the amount you wish to apply in the box that says \'Apply Amount\'. You will need to choose a payment method,  then click the submit button at the bottom of the page to apply the funds to your order.</p><p>If you are redeeming a <em>new</em> ' . TEXT_GV_NAME . ' you should type the number into the box next to &quot;Discount Code&quot;. The amount redeemed will be added to your account when you click the button to the right.</p>';
}
return $define;
