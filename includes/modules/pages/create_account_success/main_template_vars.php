<?php
// -----
// Part of the One-Page Checkout plugin, provided under GPL 2.0 license by lat9 (cindy@vinosdefrutastropicales.com).
// Copyright (C) 2013-2018, Vinos de Frutas Tropicales.  All rights reserved.
//
$zco_notifier->notify('NOTIFY_MAIN_TEMPLATE_VARS_START_CREATE_ACCOUNT_SUCCESS');

// -----
// If the One-Page Checkout's 'account registration' is enabled, display an alternate form of the
// create_account_success page.
//
if ($account_registration_enabled) {
    $page_template = 'tpl_create_account_success_register.php';
} else {
    $page_template = 'tpl_create_account_success_default.php';
}

// -----
// Load the template file.
//
require $template->get_template_dir($page_template, DIR_WS_TEMPLATE, $current_page_base, 'templates') . "/$page_template";