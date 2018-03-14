<?php
// -----
// Part of the One-Page Checkout plugin, provided under GPL 2.0 license by lat9 (cindy@vinosdefrutastropicales.com).
// Copyright (C) 2013-2017, Vinos de Frutas Tropicales.  All rights reserved.
//
// Check to see that at least one shipping-method and one payment-method is enabled; if not, don't render the submit-button.
//
if ($shipping_module_available && $payment_module_available) {
    // -----
    // Set up two form-submittal buttons, one for payment methods that require confirmation and one for those that don't.
    // This page's header_php.php has created an array of payment modules that require confirmation, which is pulled into the
    // page's jscript_main.php.
    //
?>
<!--bof submit block -->
  <div id="checkoutOneSubmit" class="buttonRow forward">
    <span id="opc-order-confirm"><?php echo zen_image_button(BUTTON_IMAGE_CHECKOUT_ONE_CONFIRM, BUTTON_CHECKOUT_ONE_CONFIRM_ALT); ?></span>
    <span id="opc-order-review"><?php echo zen_image_button(BUTTON_IMAGE_CHECKOUT_ONE_REVIEW, BUTTON_CHECKOUT_ONE_REVIEW_ALT); ?></span>
    <?php echo zen_draw_hidden_field('order_confirmed', '1', 'id="confirm-the-order"') . zen_draw_hidden_field ('current_order_total', '0', 'id="current-order-total"'); ?>
  </div>
<?php
    if (!empty($order->customer['email_address'])) {
?>
  <div id="checkoutOneEmail" class="forward clearRight"><?php echo sprintf(TEXT_CONFIRMATION_EMAILS_SENT_TO, $order->customer['email_address']); ?></div>
<?php
    }
?>
  <div class="clearBoth"></div>
<!--eof submit block -->
<?php
}
