<?php
// -----
// Part of the One-Page Checkout plugin, provided under GPL 2.0 license by lat9 (cindy@vinosdefrutastropicales.com).
// Copyright (C) 2013-2020, Vinos de Frutas Tropicales.  All rights reserved.
//
if (!defined('IS_ADMIN_FLAG')) {
    die('Illegal Access');
} 

$autoLoadConfig[200][] = array(
    'autoType'  => 'init_script',
    'loadFile'  => 'init_checkout_one.php'
);

$autoLoadConfig[200][] = array(
    'autoType' => 'class',
    'loadFile' => 'observers/OnePageCheckoutAdminObserver.php',
    'classPath' => DIR_WS_CLASSES
);
$autoLoadConfig[200][] = array(
    'autoType' => 'classInstantiate',
    'className' => 'OnePageCheckoutAdminObserver',
    'objectName' => 'opcAdmin'
);

$autoLoadConfig[200][] = array(
    'autoType' => 'class',
    'loadFile' => 'observers/CheckoutOneEmailObserver.php',
    'classPath' => DIR_FS_CATALOG . DIR_WS_CLASSES
);

$autoLoadConfig[200][] = array(
    'autoType' => 'classInstantiate',
    'className' => 'CheckoutOneEmailObserver',
    'objectName' => 'opcEmail'
);
