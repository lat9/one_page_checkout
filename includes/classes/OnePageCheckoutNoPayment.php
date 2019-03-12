<?php
// -----
// Part of the One-Page Checkout plugin, provided under GPL 2.0 license by lat9
// Copyright (C) 2019, Vinos de Frutas Tropicales.  All rights reserved.
//
// This class is instantiated during the 'checkout_one' page's guest-checkout processing (v2.1.0 and later) to
// act as a 'stub' for the payment-class until such time that the guest customer's billing address has
// been supplied.
//
// This will allow interoperation with some payment modules (e.g. payeezyjszc and square).
//
class OnePageCheckoutNoPayment extends base
{
    /* -----
    ** This function emulates the like-named method for some payment methods, indicating that
    ** the checkout is currently "special" so that any payment modules' jscript files won't mistakenly
    ** load.
    */
    public function in_special_checkout()
    {
        return true;
    }
}