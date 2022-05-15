<?php
// -----
// Part of the One-Page Checkout plugin, provided under GPL 2.0 license by lat9 (cindy@vinosdefrutastropicales.com).
// Copyright (C) 2013-2022, Vinos de Frutas Tropicales.  All rights reserved.
//
// If the One-Page Checkout's "Guest Checkout" or account-registration are enabled, instruct the template-formatting 
// to disable the right and left sideboxes.
//
// Last updated: OPC v2.4.1
//
$block_error = false;
if (!empty($_SESSION['opc']) && is_object($_SESSION['opc']) && $_SESSION['opc']->temporaryAddressesEnabled()) {
    $flag_disable_right = $flag_disable_left = true;

    // -----
    // The CHECKOUT_ONE_LOGIN_LAYOUT setting controls the formatting used in the display of the
    // guest-checkout enabled login screen.  The value is an encoded string, identifying which block should be
    // displayed in which column.  Columns are delimited by a semi-colon (;) and the top-to-bottom column
    // layout is in the order specified by the block-elements' left-to-right order.
    //
    // The block elements are:
    //
    // L ... (required) The email/password login block.
    // P ... (optional) The PayPal Express Checkout shortcut-button block.
    // G ... (required) The guest-checkout block.
    // C ... (required) The create-account block.
    // B ... (optional) The "Account Benefits" block.
    //
    $required_blocks = [
        'L' => true,
        'G' => true,
        'C' => true,
    ];

    // -----
    // Set the variable that lets tpl_login_guest.php 'know' whether to display the
    // TEXT_NEW_CUSTOMER_POST_INTRODUCTION_DIVIDER language constant and, if so, whether it should
    // be displayed before or after the PayPal Express Checkout button.
    //
    // Possible values:
    //
    // 'prev' ... Display the constant before the PPEC button; that button is displayed in a column
    //            after another block.
    // 'next' ... Display the constant after the PPEC button; that button is displayed as the first
    //            block in the configured column.
    //
    $ppec_divider_location = 'prev';

    $column_blocks = [];
    $display_elements = explode(';', CHECKOUT_ONE_LOGIN_LAYOUT);
    $valid_blocks = explode(',', 'L,P,G,C,B');
    $num_columns = 0;
    foreach ($display_elements as $current_element) {
        $current_block = [];
        $column_elements = explode(',', $current_element);
        $column_elements_count = count($column_elements);
        $found_nonppec_block = false;
        foreach ($column_elements as $block) {
            if (!in_array($block, $valid_blocks)) {
                $block_error = true;
            } else {
                switch ($block) {
                    case 'G':
                        if ($_SESSION['cart']->count_contents() > 0 && $_SESSION['opc']->guestCheckoutEnabled()) {
                            $current_block[] = $block;
                            $found_nonppec_block = true;
                        }
                        break;
                    case 'P':
                        if ($ec_button_enabled) {
                            $current_block[] = $block;
                            if ($found_nonppec_block === false && $column_elements_count !== 1) {
                                $ppec_divider_location = 'next';
                            }
                        }
                        break;
                    default:
                        $current_block[] = $block;
                        $found_nonppec_block = true;
                        break;
                }
                unset($required_blocks[$block]);
            }
        }
        $column_blocks[] = $current_block;
        if (count($current_block) !== 0) {
            $num_columns++;
        }
    }
    if ($block_error === true || $num_columns === 0 || count($required_blocks) !== 0) {
        $block_error = true;
        trigger_error('Invalid value(s) found in CHECKOUT_ONE_LOGIN_LAYOUT (' . CHECKOUT_ONE_LOGIN_LAYOUT . ').  Guest-checkout is disabled.', E_USER_WARNING);
    }
    unset($display_elements, $valid_blocks, $current_element, $block, $current_block, $column_elements, $required_blocks);

    $guest_active = zen_in_guest_checkout();
}
