<?php
// -----
// Part of the One-Page Checkout plugin, provided under GPL 2.0 license by lat9 (cindy@vinosdefrutastropicales.com).
// Copyright (C) 2013-2018, Vinos de Frutas Tropicales.  All rights reserved.
//
// when free shipping for orders over $XX.00 is active
define('FREE_SHIPPING_TITLE', 'Free Shipping');
define('FREE_SHIPPING_DESCRIPTION', 'Free shipping for orders over %s');

define('ERROR_GUEST_CHECKOUT_PAGE_DISALLOWED', 'Access to that page requires a registered account.  You can create an account using our <a href="' . zen_href_link(FILENAME_LOGIN, '', 'SSL') . '">login</a> page.');
define('WARNING_GUEST_CHECKOUT_NOT_AVAILABLE', 'Sorry, our guest-checkout is temporarily unavailable.  You can continue your checkout by either logging in or creating an account.');