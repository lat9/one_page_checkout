<?php
// -----
// Part of the One-Page Checkout plugin, provided under GPL 2.0 license by lat9 (cindy@vinosdefrutastropicales.com).
// Copyright (C) 2018, Vinos de Frutas Tropicales.  All rights reserved.
//
// If the previous order was placed via the One-Page Checkout's "Guest Checkout", instruct the template-formatting to
// load the OPC's modified checkout_success template.
//
if ($order_placed_by_guest) {
    $checkout_success_template = 'tpl_checkout_success_guest.php';
    $define_page = zen_get_file_directory(DIR_WS_LANGUAGES . $_SESSION['language'] . '/html_includes/', FILENAME_DEFINE_CHECKOUT_SUCCESS_GUEST, 'false');
} else {
    $checkout_success_template = 'tpl_checkout_success_default.php';
}
require $template->get_template_dir($checkout_success_template, DIR_WS_TEMPLATE, $current_page_base, 'templates') . "/$checkout_success_template";
