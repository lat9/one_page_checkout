<?php
// -----
// Part of the One-Page Checkout plugin, provided under GPL 2.0 license by lat9 (cindy@vinosdefrutastropicales.com).
// Copyright (C) 2013-2022, Vinos de Frutas Tropicales.  All rights reserved.
//
if (!defined('IS_ADMIN_FLAG')) {
    die('Illegal Access');
}

define('FILENAME_CHECKOUT_ONE', 'checkout_one');
define('FILENAME_CHECKOUT_ONE_CONFIRMATION', 'checkout_one_confirmation');

// -----
// Conditional definition; it's already defined for zc158.
//
if (!defined('FILENAME_ORDER_STATUS')) {
    define('FILENAME_ORDER_STATUS', 'order_status');
}

define('FILENAME_DEFINE_CHECKOUT_SUCCESS_GUEST', 'define_checkout_success_guest');
