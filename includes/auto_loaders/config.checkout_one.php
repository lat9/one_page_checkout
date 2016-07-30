<?php
// -----
// Part of the One-Page Checkout plugin, provided under GPL 2.0 license by lat9 (cindy@vinosdefrutastropicales.com).
// Copyright (C) 2013-2016, Vinos de Frutas Tropicales.  All rights reserved.
//
$autoLoadConfig[200][] = array(
    'autoType' => 'class',
    'loadFile' => 'observers/class.checkout_one_observer.php'
);
$autoLoadConfig[200][] = array(
    'autoType'   => 'classInstantiate',
    'className'  => 'checkout_one_observer',
    'objectName' => 'checkout_one'
);