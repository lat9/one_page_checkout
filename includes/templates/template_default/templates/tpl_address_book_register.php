<?php
// -----
// Part of the One-Page Checkout plugin, provided under GPL 2.0 license by lat9 (cindy@vinosdefrutastropicales.com).
// Copyright (C) 2017, Vinos de Frutas Tropicales.  All rights reserved.
//
// This template is loaded for the address_book page by /includes/modules/pages/address_book/main_template_vars.php
// when the One-Page Checkout plugin is installed, its "account registration" processing is enabled and the currently
// logged-in customer has completed account "registration" but has not (yet) provided their default address information.
//
?>
<div class="centerColumn" id="addressBookDefault">
    <h1 id="addressBookDefaultHeading"><?php echo HEADING_TITLE; ?></h1>

    <div id="addressBookNoPrimary"><?php echo TEXT_NO_ADDRESSES; ?></div>
    
    <div class="buttonRow forward">
        <a href="<?php echo zen_href_link(FILENAME_ADDRESS_BOOK_PROCESS, 'edit=' . $_SESSION['customer_default_address_id'], 'SSL'); ?>"><?php echo zen_image_button(BUTTON_IMAGE_ADD_ADDRESS, BUTTON_ADD_ADDRESS_ALT); ?></a>
    </div>
    <div class="buttonRow back">
        <a href="<?php echo zen_href_link(FILENAME_ACCOUNT, '', 'SSL'); ?>"><?php echo zen_image_button(BUTTON_IMAGE_BACK, BUTTON_BACK_ALT); ?></a>
    </div>
    <br class="clearBoth" />
</div>
