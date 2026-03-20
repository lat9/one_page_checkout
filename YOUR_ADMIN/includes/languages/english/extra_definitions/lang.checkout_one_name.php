<?php
// -----
// Part of the One-Page Checkout plugin, provided under GPL 2.0 license by lat9 (cindy@vinosdefrutastropicales.com).
// Copyright (C) 2013-2026, Vinos de Frutas Tropicales.  All rights reserved.
//
// Last updated: OPC v2.6.0
//
$defines = [
    'BOX_TOOLS_CHECKOUT_ONE' => 'One-Page Checkout Settings',

    'ERROR_ACTION_INVALID_FOR_GUEST_CUSTOMER' => 'The action requested (%s) cannot be performed on the <em>One-Page Checkout</em> guest-customer.',
    'ERROR_STORESIDE_CONFIG' => 'The <em>One-Page Checkout</em> plugin has been disabled.  The file &quot;%s&quot; is required for the plugin\'s proper operation.',

    'ICON_GUEST_ALT' => 'Guest Checkout',

    'TEXT_GUEST_CHECKOUT' => 'Order placed via guest-checkout',
    'TEXT_OPC_INSTALLED' => 'The <em>One-Page Checkout</em> plugin [%s] has been successfully installed.',
    'TEXT_OPC_UPDATED' => 'The <em>One-Page Checkout</em> plugin was successfully upgraded from [%1$s] to [%2$s].',
];

$defines['ICON_GUEST_CHECKOUT'] = '<i class="fa fa-user-secret" aria-hidden="true" title="' . ICON_GUEST_ALT . '"></i>';

return $defines;
