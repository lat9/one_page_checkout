<?php
// -----
// Part of the One-Page Checkout plugin, provided under GPL 2.0 license by lat9 (cindy@vinosdefrutastropicales.com).
// Copyright (C) 2017-2020, Vinos de Frutas Tropicales.  All rights reserved.
//
$account_registration_enabled = false;
if (!empty($_SESSION['opc']) && is_object($_SESSION['opc']) && $_SESSION['opc']->accountRegistrationEnabled()) {
    $account_registration_enabled = true;
    
    $breadcrumb->reset();
    $breadcrumb->add(NAVBAR_TITLE_1R);
    $breadcrumb->add(NAVBAR_TITLE_2R);
}