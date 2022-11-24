<?php
// -----
// Part of the One-Page Checkout plugin, provided under GPL 2.0 license by lat9
// Copyright (C) 2020-2022, Vinos de Frutas Tropicales.  All rights reserved.
//
// Last updated: OPC v2.4.4
//
if (!defined('IS_ADMIN_FLAG')) {
    die('Illegal Access');
}

// -----
// This observer class, used both in the admin and storefront, waits for the notification
// from zen_update_orders_history and checks to see if the order was placed by a guest.  If
// so, the next email sent is checked to change the status-update page-link from the
// 'account_history_info' page to the 'order_status' one.
//
// Consideration is given to store customizations that "batch" process order-updates,
// recognizing that multiple status-updates might occur in a single page-load.
//
class CheckoutOneEmailObserver extends base
{
    protected
        $history_info_link,
        $status_page_link;

    public function __construct()
    {
        // -----
        // Watch for any order-status-update processing to start.
        //
        $this->attach(
            $this,
            [
                'ZEN_UPDATE_ORDERS_HISTORY_STATUS_VALUES',
            ]
        );

        // -----
        // If not already defined (like in the zc156 admin), set the definition for the
        // storefront order_status page.
        //
        if (!defined('FILENAME_ORDER_STATUS')) {
            define('FILENAME_ORDER_STATUS', 'order_status');
        }
    }

    public function update(&$class, $eventID, $p1, &$p2, &$p3, &$p4, &$p5, &$p6, &$p7)
    {
        switch ($eventID) {
            // -----
            // An order-status-update is beginning, on entry:
            //
            // $p1 ... (r/o) An associative array containing the 'orders_id'.
            //
            case 'ZEN_UPDATE_ORDERS_HISTORY_STATUS_VALUES':
                global $db;

                $oID = (int)$p1['orders_id'];

                // -----
                // Check to see if the order was placed by a guest-customer.  If not,
                // nothing more to do here.
                //
                $check = $db->Execute(
                    "SELECT is_guest_order
                       FROM " . TABLE_ORDERS . "
                      WHERE orders_id = $oID
                      LIMIT 1"
                );
                if ($check->EOF || $check->fields['is_guest_order'] != 1) {
                    $this->detach(
                        $this,
                        [
                            'NOTIFY_EMAIL_BEFORE_PROCESS_ATTACHMENTS',
                            'NOTIFY_EMAIL_READY_TO_SEND',
                        ]
                    );
                    break;
                }

                // -----
                // Capture (for follow-on email updates) the link to the storefront 'order_status' page and that
                // for the account-history-info page.
                //
                if (IS_ADMIN_FLAG === true) {
                    $this->history_info_link = zen_catalog_href_link(FILENAME_ACCOUNT_HISTORY_INFO, "order_id=$oID", 'SSL');
                    $this->status_page_link = zen_catalog_href_link(FILENAME_ORDER_STATUS, '', 'SSL');
                } else {
                    $this->history_info_link = zen_href_link(FILENAME_ACCOUNT_HISTORY_INFO, "order_id=$oID", 'SSL');
                    $this->status_page_link = zen_href_link(FILENAME_ORDER_STATUS, '', 'SSL');
                }

                // -----
                // The order-update *is* for a guest-placed order.  Register to receive
                // notification when the (presumed) email is sent.
                //
                $this->attach(
                    $this,
                    [
                        'NOTIFY_EMAIL_BEFORE_PROCESS_ATTACHMENTS',
                    ]
                );
                break;

            // -----
            // Issued by the zen_mail function, supplying us with the email-template to be used.
            //
            // On entry:
            //
            // $p1 ... (r/o) An associative array containing 'attachments' and the 'module'.
            //
            case 'NOTIFY_EMAIL_BEFORE_PROCESS_ATTACHMENTS':
                // -----
                // If the email is to be sent using the 'order_status' template, it's going to the customer, the
                // 'order_status_extra' is going to the configured admins.  Register to receive
                // notification just prior to the email's transmission.
                //
                if ($p1['module'] == 'order_status' || $p1['module'] == 'order_status_extra') {
                    $this->attach(
                        $this,
                        [
                            'NOTIFY_EMAIL_READY_TO_SEND',
                        ]
                    );
                }
                break;

            // -----
            // Issued by the zen_mail function, just prior to sending any email.  Gives us the opportunity
            // to replace any reference to the account-history-info page with one to the order_status page.
            //
            // On entry:
            //
            // $p1 ... Uninteresting.
            // $p2 ... (r/w) A reference to the 'mail' object that contains the text/HTML email information.
            //
            case 'NOTIFY_EMAIL_READY_TO_SEND':
                $p2->AltBody = str_replace($this->history_info_link, $this->status_page_link, $p2->AltBody);
                $p2->Body = str_replace($this->history_info_link, $this->status_page_link, $p2->Body);
                break;

            default:
                break;
        }
    }
}
