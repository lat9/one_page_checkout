<?php
// -----
// Part of the One-Page Checkout plugin, provided under GPL 2.0 license by lat9 (cindy@vinosdefrutastropicales.com).
// Copyright (C) 2018-2024, Vinos de Frutas Tropicales.  All rights reserved.
//
// Last updated: OPC v2.5.3
//
if (!defined('IS_ADMIN_FLAG') || IS_ADMIN_FLAG !== true) {
    die('Illegal Access');
}

class OnePageCheckoutAdminObserver extends base
{
    public function __construct()
    {
        // -----
        // Check to see if this is an unwanted action on the 'customers' page for OPC's
        // 'guest' customer id. If so, redirect back to that page (with message)
        // indicating that the action can't be performed for that 'guest' customer.
        //
        global $current_page, $messageStack;
        if ($current_page === FILENAME_CUSTOMERS . '.php' && isset($_GET['action']) && defined('CHECKOUT_ONE_GUEST_CUSTOMER_ID')) {
            $opc_cid = (int)($_POST['cID'] ?? $_GET['cID'] ?? 0);
            if ($opc_cid === (int)CHECKOUT_ONE_GUEST_CUSTOMER_ID && in_array($_GET['action'], ['status', 'update', 'deleteconfirm'])) {
                $messageStack->add_session(sprintf(ERROR_ACTION_INVALID_FOR_GUEST_CUSTOMER, $_GET['action']), 'error');
                zen_redirect(zen_href_link(FILENAME_CUSTOMERS, zen_get_all_get_params(['cID', 'action']) . 'cID=' . $opc_cid));
            }
        }

        $this->attach(
            $this,
            [ 
                /* Issued by /orders.php */
                'NOTIFY_ADMIN_ORDERS_SEARCH_PARMS',
                'NOTIFY_ADMIN_ORDERS_MENU_LEGEND', 
                'NOTIFY_ADMIN_ORDERS_SHOW_ORDER_DIFFERENCE',
            ]
        );
    }

    public function update(&$class, $eventID, $p1, &$p2, &$p3, &$p4, &$p5, &$p6)
    {
        switch ($eventID) {
            // -----
            // Issued by Customers->Orders during the order-listing phase, allows us to identify additional
            // database fields to pull in for the display.
            //
            // On entry (fields of interest only):
            //
            // $p4 ... (r/w) A reference to the (string)$new_fields, to which the order's is_guest_order field is added
            //
            case 'NOTIFY_ADMIN_ORDERS_SEARCH_PARMS':
                $p4 .= ', o.is_guest_order';
                break;

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
                if (!empty($p2['is_guest_order'])) {
                    $p3 .= '&nbsp;' . ICON_GUEST_CHECKOUT;
                }
                break;

            default:
                break;
        }
    }
}
