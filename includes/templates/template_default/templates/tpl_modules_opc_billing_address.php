<?php
// -----
// Part of the One-Page Checkout plugin, provided under GPL 2.0 license by lat9 (cindy@vinosdefrutastropicales.com).
// Copyright (C) 2013-2017, Vinos de Frutas Tropicales.  All rights reserved.
//
?>
<!--bof billing-address block -->
    <div id="checkoutOneBillto">
      <fieldset>
        <legend><?php echo TITLE_BILLING_ADDRESS; ?></legend>
        <address><?php echo zen_address_format($order->billing['format_id'], $order->billing, 1, ' ', '<br />'); ?></address>
<?php 
if (!$flagDisablePaymentAddressChange) { 
?>
        <div class="buttonRow forward"><?php echo '<a href="' . zen_href_link(FILENAME_CHECKOUT_PAYMENT_ADDRESS, '', 'SSL') . '">' . zen_image_button(BUTTON_IMAGE_EDIT_SMALL, BUTTON_EDIT_SMALL_ALT) . '</a>'; ?></div>
<?php 
} 
?>
      </fieldset> 
    </div>
<!--eof billing-address block -->
