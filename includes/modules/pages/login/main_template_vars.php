<?php
// -----
// Part of the One-Page Checkout plugin, provided under GPL 2.0 license by lat9 (cindy@vinosdefrutastropicales.com).
// Copyright (C) 2013-2020, Vinos de Frutas Tropicales.  All rights reserved.
//
// If the One-Page Checkout's "Guest Checkout" is enabled, instruct the template-formatting to
// load the OPC's modified login template.
//
if (!$block_error && !empty($_SESSION['opc']) && is_object($_SESSION['opc']) && $_SESSION['opc']->temporaryAddressesEnabled()) {
    $login_template = 'tpl_login_guest.php';
} else {
    $login_template = 'tpl_login_default.php';
}
require $template->get_template_dir($login_template, DIR_WS_TEMPLATE, $current_page_base, 'templates') . "/$login_template";
