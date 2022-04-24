<?php
// -----
// Part of the One-Page Checkout plugin, provided under GPL 2.0 license by lat9 (cindy@vinosdefrutastropicales.com).
// Copyright (C) 2018-2022, Vinos de Frutas Tropicales.  All rights reserved.
//
if (!defined('IS_ADMIN_FLAG') || IS_ADMIN_FLAG !== true) {
    die('Illegal Access');
}

class OnePageCheckoutAdminObserver extends base
{
    public function __construct()
    {
        $this->attach(
            $this,
            [ 
                /* Issued by /orders.php */
                'NOTIFY_ADMIN_ORDERS_MENU_LEGEND',
                'NOTIFY_ADMIN_ORDERS_SHOW_ORDER_DIFFERENCE',
            ]
        );
    }

    public function update(&$class, $eventID, $p1, &$p2, &$p3, &$p4) 
    {
        switch ($eventID) {
            // -----
            // Issued by Customers->Orders during the order-listing phase, allows us to identify
            // the icon used to identify any orders placed by guests.
            //
            // On entry:
            //
            // $p2 ... (r/w) Contains a string, to which additional order "legend" icons can be appended.
            //
            case 'NOTIFY_ADMIN_ORDERS_MENU_LEGEND':
                $p2 .= '&nbsp;' . ICON_GUEST_CHECKOUT . '&nbsp;' . TEXT_GUEST_CHECKOUT;
                break;

            // -----
            // Issued by Customers->Orders, for each listed order, allows us to identify whether the
            // order was placed by a guest.
            //
            // On entry:
            //
            // $p2 ... (r/w) A copy of the order's database fields.
            // $p3 ... (r/w) A reference to the "show_difference" string
            // $p4 ... (r/w) A reference to the "extra action icons" string (not used by this processing).
            //
            case 'NOTIFY_ADMIN_ORDERS_SHOW_ORDER_DIFFERENCE':
                global $db;

                if (isset($p2['is_guest_order'])) {
                    $is_guest_order = $p2['is_guest_order'];
                } else {
                    $check = $db->Execute(
                        "SELECT is_guest_order
                           FROM " . TABLE_ORDERS . "
                          WHERE orders_id = " . $p2['orders_id'] . "
                          LIMIT 1"
                    );
                    $is_guest_order = (!$check->EOF) ? $check->fields['is_guest_order'] : 0;
                }
                if ($is_guest_order) {
                    $p3 .= '&nbsp;' . ICON_GUEST_CHECKOUT;
                }
                break;

            default:
                break;
        }
    }
}
