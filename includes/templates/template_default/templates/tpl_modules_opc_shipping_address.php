<?php
// -----
// Part of the One-Page Checkout plugin, provided under GPL 2.0 license by lat9 (cindy@vinosdefrutastropicales.com).
// Copyright (C) 2013-2017, Vinos de Frutas Tropicales.  All rights reserved.
//
?>
<!--bof shipping-address block -->
<?php
// -----
// Display shipping-address information **only if** the order contains at least one physical product (i.e. it's not virtual).
//
if ($is_virtual_order) {
    echo zen_draw_checkbox_field('shipping_billing', '1', false, 'id="shipping_billing" style="display: none;"');
} else {
    if (CHECKOUT_ONE_ENABLE_SHIPPING_BILLING == 'false') {
        echo zen_draw_checkbox_field('shipping_billing', '1', false, 'id="shipping_billing" style="display: none;"');
    } else {
?>
    <div id="checkoutOneShippingFlag" style="display: none;"><?php echo  zen_draw_checkbox_field('shipping_billing', '1', $shipping_billing, 'id="shipping_billing"');?>
      <label class="checkboxLabel" for="shipping_billing"><?php echo TEXT_USE_BILLING_FOR_SHIPPING; ?></label>
    </div>
<?php
    }
?>
    <div id="checkoutOneShipto">
      <fieldset>
        <legend><?php echo TITLE_SHIPPING_ADDRESS; ?></legend>
        <address><?php echo zen_address_format($order->delivery['format_id'], $order->delivery, 1, ' ', '<br />'); ?></address>
        <div class="buttonRow forward"><?php echo '<a href="' . $editShippingButtonLink . '">' . zen_image_button(BUTTON_IMAGE_EDIT_SMALL, BUTTON_EDIT_SMALL_ALT) . '</a>'; ?></div>
      </fieldset>
    </div>
<?php
}
?>
<!--eof shipping-address block -->
