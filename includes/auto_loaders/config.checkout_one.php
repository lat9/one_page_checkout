<?php
// -----
// Part of the One-Page Checkout plugin, provided under GPL 2.0 license by lat9 (cindy@vinosdefrutastropicales.com).
// Copyright (C) 2013-2022, Vinos de Frutas Tropicales.  All rights reserved.
//
$autoLoadConfig[0][] = array(
    'autoType' => 'class',
    'loadFile' => 'OnePageCheckout.php'
);

// -----
// Loaded at CP-75, needs init_sessions.php to have been run since the OPC base class is session-instantiated.
//
$autoLoadConfig[75][] = array(
    'autoType' => 'classInstantiate',
    'className' => 'OnePageCheckout',
    'objectName' => 'opc',
    'checkInstantiated' => true,
    'classSession' => true
);

// -----
// Load the base observer at CP-85, since it needs the cart to be instantiated as well as the base OPC class.
//
$autoLoadConfig[85][] = array(
    'autoType' => 'class',
    'loadFile' => 'observers/class.checkout_one_observer.php'
);
$autoLoadConfig[85][] = array(
    'autoType'   => 'classInstantiate',
    'className'  => 'checkout_one_observer',
    'objectName' => 'checkout_one'
);

// -----
// Remaining items observe page-specific notifications and are loaded 'last'.
//
$autoLoadConfig[200][] = array(
    'autoType' => 'class',
    'loadFile' => 'observers/CheckoutOneEmailObserver.php',
    'classPath' => DIR_WS_CLASSES
);

$autoLoadConfig[200][] = array(
    'autoType' => 'classInstantiate',
    'className' => 'CheckoutOneEmailObserver',
    'objectName' => 'opcEmail'
);
