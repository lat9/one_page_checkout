<?php
// -----
// Part of the One-Page Checkout plugin, provided under GPL 2.0 license by lat9 (cindy@vinosdefrutastropicales.com).
// Copyright (C) 2017-2018, Vinos de Frutas Tropicales.  All rights reserved.
//
// This module overrides the base Zen Cart processing' determination of the base template-rendering file
// to be loaded.  Since the OPC introduces the concept of a customer account without permanent addresses, the
// template needs to account for that condition.
//
$zco_notifier->notify('NOTIFY_MAIN_TEMPLATE_VARS_START_ADDRESS_BOOK');

// -----
// If the currently-logged-in customer has registered rather than creating a fully-formed address-book
// entry, show the alternate address_book update page.
//
if (!$needs_address_update) {
    $page_template = 'tpl_address_book_default.php';
} else {
    $page_template = 'tpl_address_book_register.php';
}
    
$zco_notifier->notify('NOTIFY_MAIN_TEMPLATE_VARS_END_ADDRESS_BOOK');

// -----
// Load the template file.
//
require $template->get_template_dir($page_template, DIR_WS_TEMPLATE, $current_page_base, 'templates') . "/$page_template";
