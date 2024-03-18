<?php
// -----
// Part of the One-Page Checkout plugin, provided under GPL 2.0 license by lat9
// Copyright (C) 2013-2024, Vinos de Frutas Tropicales.  All rights reserved.
//
// Last updated: OPC v2.5.0
//
?>
<!--bof shipping-method choices -->
<div id="checkoutShippingMethod" class="floatingBox forward">
<?php
// -----
// If the order contains only virtual products, the shipping block contains only a hidden field that
// identifies the "free" shipping method; otherwise, display the full shipping block.
//
if ($is_virtual_order) {
    echo zen_draw_hidden_field('shipping', $_SESSION['shipping']['id']) . PHP_EOL;
} else {
?>
    <fieldset>
        <legend><?= TABLE_HEADING_SHIPPING_METHOD ?></legend>
<?php
    if (zen_count_shipping_modules() > 0) {
        if (count($quotes) > 1 && count($quotes[0]) > 1) {
            $checkout_one->debug_message("CHECKOUT_ONE_TEMPLATE_SHIPPING_QUOTES:\n" . json_encode($_SESSION['shipping'], JSON_PRETTY_PRINT) . "\n" . json_encode($quotes, JSON_PRETTY_PRINT));
?>
        <div id="checkoutShippingContentChoose" class="important">
            <?= TEXT_CHOOSE_SHIPPING_METHOD ?>
        </div>

<?php
        } elseif ($free_shipping == false) {
?>
        <div id="checkoutShippingContentChoose" class="important">
            <?= TEXT_ENTER_SHIPPING_INFORMATION ?>
        </div>
<?php
        }
?>
<?php
        if ($free_shipping == true) {
?>
        <div id="freeShip" class="important">
            <?= FREE_SHIPPING_TITLE ?>
        </div>
        <div id="defaultSelected">
            <?= (!empty(MODULE_ORDER_TOTAL_SHIPPING_FREE_SHIPPING_OVER)) ? sprintf(FREE_SHIPPING_DESCRIPTION, $currencies->format(MODULE_ORDER_TOTAL_SHIPPING_FREE_SHIPPING_OVER)) : '' ?>
        </div>
        <?= zen_draw_hidden_field('shipping', 'free_free') ?>
<?php
        } else {
?>
        <div id="checkoutShippingChoices">
<?php
            require $template->get_template_dir('tpl_modules_checkout_one_shipping.php', DIR_WS_TEMPLATE, $current_page_base, 'templates') . '/tpl_modules_checkout_one_shipping.php';
?>
        </div>
<?php
        }
    } else {
?>
        <h2 id="checkoutShippingHeadingMethod"><?= TITLE_NO_SHIPPING_AVAILABLE ?></h2>
<?php
//-bof-product_delivery_by_postcode (PDP) integration
        if ($sniffer->field_exists(TABLE_PRODUCTS, 'product_is_local_delivery')) {
            $chk_local_delivery_only = $_SESSION['cart']->in_cart_check('product_is_local_delivery', '1');
            if ($chk_local_delivery_only !== 0) {
?>
        <div id="cartLocalText">
            <?= TEXT_PRODUCT_LOCAL_DELIVERY_ONLY ?>
        </div>
<?php
            }
        }
//-eof-product_delivery_by_postcode (PDP) integration
?>
        <div id="checkoutShippingContentChoose" class="important">
            <?= TEXT_NO_SHIPPING_AVAILABLE ?>
        </div>
<?php
    }
?>
    </fieldset>
<?php
}  //-Order is not "virtual", display full shipping-method block
?>
</div>
<!--eof shipping-method choices -->
