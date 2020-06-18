<?php
// -----
// Part of the One-Page Checkout plugin, provided under GPL 2.0 license by lat9 (cindy@vinosdefrutastropicales.com).
// Copyright (C) 2013-2020, Vinos de Frutas Tropicales.  All rights reserved.
//
// If the One-Page Checkout's "Registered Accounts" is enabled, instruct the template-formatting to
// load the OPC's modified create_account template.
//
if (!empty($_SESSION['opc']) && is_object($_SESSION['opc']) && $_SESSION['opc']->accountRegistrationEnabled()) {
    $login_template = 'tpl_create_account_register.php';
} else {
    $login_template = 'tpl_create_account_default.php';
}
require $template->get_template_dir($login_template, DIR_WS_TEMPLATE, $current_page_base, 'templates') . "/$login_template";
