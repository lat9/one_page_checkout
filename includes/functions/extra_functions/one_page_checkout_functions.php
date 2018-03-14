<?php
// -----
// Part of the One-Page Checkout plugin, provided under GPL 2.0 license by lat9 (cindy@vinosdefrutastropicales.com).
// Copyright (C) 2013-2018, Vinos de Frutas Tropicales.  All rights reserved.
//

// -----
// This function identifies whether (true) or not (false) the current customer session is
// associated with a guest-checkout process.
//
if (!function_exists('zen_in_guest_checkout')) {
    function zen_in_guest_checkout()
    {
        $in_guest_checkout = false;
        $GLOBALS['zco_notifier']->notify('NOTIFY_ZEN_IN_GUEST_CHECKOUT', '', $in_guest_checkout);
        return (bool)$in_guest_checkout;
    }
}

// -----
// This function identifies whether (true) or not (false) a customer is currently logged into the site.
//
if (!function_exists('zen_is_logged_in')) {
    function zen_is_logged_in()
    {
        $is_logged_in = (!empty($_SESSION['customer_id']));
        $GLOBALS['zco_notifier']->notify('NOTIFY_ZEN_IS_LOGGED_IN', '', $is_logged_in);
        return (bool)$is_logged_in;
    }
}

// -----
// This function identifies whether (true) or not (false) the current page is being accessed
// by a spider.
//
if (!function_exists('zen_is_spider_session')) {
    function zen_is_spider_session()
    {
        $user_agent = strtolower($_SERVER['HTTP_USER_AGENT']);
        $spider_flag = false;
        if (zen_not_null($user_agent)) {
            $spiders = file(DIR_WS_INCLUDES . 'spiders.txt');
            for ($i=0, $n=count($spiders); $i<$n; $i++) {
                if (zen_not_null($spiders[$i]) && strpos($spiders[$i], '$Id:') !== 0) {
                    if (strpos($user_agent, trim($spiders[$i])) !== false) {
                        $spider_flag = true;
                        break;
                    }
                }
            }
        }
        return $spider_flag;
    }
}