<?php
// -----
// Part of the One-Page Checkout plugin, provided under GPL 2.0 license by lat9
// Copyright (C) 2013-2019, Vinos de Frutas Tropicales.  All rights reserved.
//
?>
<?php 
if ($payment_module_available) {
    echo $payment_modules->javascript_validation();
}

// -----
// The following content is initially visible and then hidden if the page's javascript/jQuery processing loads
// without error.
//
$nojs_link = zen_href_link(FILENAME_CHECKOUT_SHIPPING, 'opctype=jserr', 'SSL');
?>
<div class="centerColumn" id="checkoutPaymentNoJs"><?php echo sprintf(TEXT_NOSCRIPT_JS_ERROR, $nojs_link); ?></div>

<?php
// -----
// Start main form ...
//
?>
<div class="centerColumn opc-base" id="checkoutPayment" style="display:none;">
<?php
  echo zen_draw_form('checkout_payment', zen_href_link(FILENAME_CHECKOUT_ONE_CONFIRMATION, '', 'SSL'), 'post', 'id="checkout_payment"') . zen_draw_hidden_field('action', 'process') . zen_draw_hidden_field('javascript_enabled', '0', 'id="javascript-enabled"'); 
?>
  <h1 id="checkoutOneHeading"><?php echo HEADING_TITLE; ?></h1>
<?php
if (TEXT_CHECKOUT_ONE_TOP_INSTRUCTIONS != '') {
?>
  <div id="co1-top-message"><p><?php echo TEXT_CHECKOUT_ONE_TOP_INSTRUCTIONS; ?></p></div>
<?php
}
$messages_to_check = array('checkout_shipping', 'checkout_payment', 'redemptions', 'checkout');
foreach ($messages_to_check as $page_check) {
    if ($messageStack->size($page_check) > 0) {
        echo $messageStack->output($page_check);
    }
}
?>
<!--bof left-hand floating box -->
  <div id="checkoutOneLeft" class="floatingBox back">
<?php
// -----
// Insert the (conditional) guest-login block.  That block's formatting will determine whether/not to render.
//
require $template->get_template_dir('tpl_modules_opc_customer_info.php', DIR_WS_TEMPLATE, $current_page_base, 'templates') . '/tpl_modules_opc_customer_info.php';

// -----
// Insert the billing-address block.
//
require $template->get_template_dir('tpl_modules_opc_billing_address.php', DIR_WS_TEMPLATE, $current_page_base, 'templates') . '/tpl_modules_opc_billing_address.php';

// -----
// Insert the shipping-address block.
//
require $template->get_template_dir('tpl_modules_opc_shipping_address.php', DIR_WS_TEMPLATE, $current_page_base, 'templates') . '/tpl_modules_opc_shipping_address.php';

// -----
// Insert the customer-comments block.
//
require $template->get_template_dir('tpl_modules_opc_comments.php', DIR_WS_TEMPLATE, $current_page_base, 'templates') . '/tpl_modules_opc_comments.php';

// -----
// Insert the credit-selection block (for coupons, gift certificates, etc.)
//
require $template->get_template_dir('tpl_modules_opc_credit_selections.php', DIR_WS_TEMPLATE, $current_page_base, 'templates') . '/tpl_modules_opc_credit_selections.php';
?>

  </div>
<!--eof left-hand floating box -->

<?php
// -----
// Insert the shipping-method choices block.
//
require $template->get_template_dir('tpl_modules_opc_shipping_choices.php', DIR_WS_TEMPLATE, $current_page_base, 'templates') . '/tpl_modules_opc_shipping_choices.php';

// -----
// Insert the payment-method choices block.
//
require $template->get_template_dir('tpl_modules_opc_payment_choices.php', DIR_WS_TEMPLATE, $current_page_base, 'templates') . '/tpl_modules_opc_payment_choices.php';

// -----
// Insert the shopping-cart/totals block.
//
require $template->get_template_dir('tpl_modules_opc_shopping_cart.php', DIR_WS_TEMPLATE, $current_page_base, 'templates') . '/tpl_modules_opc_shopping_cart.php';

// -----
// Insert the instructions, conditions and submit-button block.
//
require $template->get_template_dir('tpl_modules_opc_instructions.php', DIR_WS_TEMPLATE, $current_page_base, 'templates') . '/tpl_modules_opc_instructions.php';
require $template->get_template_dir('tpl_modules_opc_conditions.php', DIR_WS_TEMPLATE, $current_page_base, 'templates') . '/tpl_modules_opc_conditions.php';
require $template->get_template_dir('tpl_modules_opc_submit_block.php', DIR_WS_TEMPLATE, $current_page_base, 'templates') . '/tpl_modules_opc_submit_block.php';

echo '</form>';
?>
    <div class="opc-overlay"></div>
</div>
  
<div id="checkoutOneLoading" style="display: none;"><?php echo zen_image($template->get_template_dir(CHECKOUT_ONE_LOADING, DIR_WS_TEMPLATE, $current_page_base ,'images') . '/' . CHECKOUT_ONE_LOADING, CHECKOUT_ONE_LOADING_ALT); ?></div>