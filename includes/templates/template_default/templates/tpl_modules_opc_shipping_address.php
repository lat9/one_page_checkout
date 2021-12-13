<?php
// -----
// Part of the One-Page Checkout plugin, provided under GPL 2.0 license by lat9 (cindy@vinosdefrutastropicales.com).
// Copyright (C) 2013-2021, Vinos de Frutas Tropicales.  All rights reserved.
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
    <div id="checkoutOneShippingFlag" class="custom-control custom-checkbox" style="display: none;"><?php echo  zen_draw_checkbox_field('shipping_billing', '1', $shipping_billing, 'id="shipping_billing"');?>
      <label class="custom-control-label checkboxLabel" for="shipping_billing"><?php echo TEXT_USE_BILLING_FOR_SHIPPING; ?></label>
    </div>
<?php
    }
?>
    <div id="checkoutOneShipto">
      <fieldset>
        <legend><?php echo TITLE_SHIPPING_ADDRESS; ?></legend>
<?php
$opc_address_type = 'ship';
    $opc_disable_address_change = !$editShippingButtonLink;
    require $template->get_template_dir('tpl_modules_opc_address_block.php', DIR_WS_TEMPLATE, $current_page_base, 'templates'). '/tpl_modules_opc_address_block.php';

    if ($editShippingButtonLink) {
        $cancel_title = 'title="' . BUTTON_CANCEL_CHANGES_TITLE . '"';
        $save_title = 'title="' . BUTTON_SAVE_CHANGES_TITLE . '"';
        $show_add_address = $_SESSION['opc']->showAddAddressField();
        $parameters = ($show_add_address) ? '' : ' class="hiddenField"';
?>
        <div class="buttonRow opc-buttons">
            <div class="custom-control custom-checkbox">
<?php
        if ($show_add_address) {
?>
                <?php echo zen_draw_checkbox_field("add_address['ship']", '1', false, 'id="opc-add-ship"' . $parameters); ?>
                <label class="checkboxLabel custom-control-label" for="opc-add-ship" title="<?php echo TITLE_ADD_TO_ADDRESS_BOOK; ?>"><?php echo TEXT_ADD_TO_ADDRESS_BOOK; ?></label>
<?php
        }
?>
            </div>
            <div class="opc-right">
                <span id="opc-ship-cancel"><?php echo zen_image_button(BUTTON_IMAGE_CANCEL, BUTTON_CANCEL_CHANGES_ALT, $cancel_title); ?></span>
                <span id="opc-ship-save"><?php echo zen_image_button(BUTTON_IMAGE_UPDATE, BUTTON_SAVE_CHANGES_ALT, $save_title); ?></span>
            </div>
        </div>
<?php 
    } 
?>
      </fieldset>
    </div>
<?php
}
?>
<!--eof shipping-address block -->
