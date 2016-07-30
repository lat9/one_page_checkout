<?php
// -----
// Part of the One-Page Checkout plugin, provided under GPL 2.0 license by lat9 (cindy@vinosdefrutastropicales.com).
// Copyright (C) 2013-2016, Vinos de Frutas Tropicales.  All rights reserved.
//

// -----
// The "display: none;" on the loading icon enables that to "not display" if javascript is disabled in the customer's browser.  The
// page's jscript_main.php handling will "show" that when javascript is enabled.
//
?>
<div id="checkoutOneConfirmation">
<?php
echo zen_draw_form ('confirmation_one', $form_action_url, 'post', 'id="checkout_confirmation""');

if (is_array ($payment_modules->modules)) {
    echo $payment_modules->process_button();
}
?>
    <noscript>
        <p><?php echo NO_JAVASCRIPT_MESSAGE; ?></p>
        <div class="buttonRow"><?php echo zen_image_submit (BUTTON_IMAGE_CONFIRM_ORDER, BUTTON_CONFIRM_ORDER_ALT, 'name="btn_submit" id="btn_submit"'); ?></div>
    </noscript>
  </form>
  <div id="checkoutOneConfirmationLoading" style="display: none;"><?php echo zen_image ($template->get_template_dir (CHECKOUT_ONE_CONFIRMATION_LOADING, DIR_WS_TEMPLATE, $current_page_base ,'images') . '/' . CHECKOUT_ONE_CONFIRMATION_LOADING, CHECKOUT_ONE_CONFIRMATION_LOADING_ALT); ?></div>
</div>