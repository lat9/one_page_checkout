<?php
// -----
// Part of the One-Page Checkout plugin, provided under GPL 2.0 license by lat9 (cindy@vinosdefrutastropicales.com).
// Copyright (C) 2013-2022, Vinos de Frutas Tropicales.  All rights reserved.
//
// Last updated: OPC v2.4.4
//
if ($shipping_module_available === true && $display_payment_block === true) {
    if ($_SESSION['opc']->isGuestCheckout() && DISPLAY_PRIVACY_CONDITIONS === 'true') {
?>
    <div id="privacy-div">
        <fieldset>
            <legend><?php echo TABLE_HEADING_PRIVACY_CONDITIONS; ?></legend>
            <div class="information"><?php echo TEXT_PRIVACY_CONDITIONS_DESCRIPTION;?></div>
            <div class="custom-control custom-checkbox">
                <?php echo zen_draw_checkbox_field('privacy_conditions', '1', false, 'id="privacy" required'); ?>
                <label class="custom-control-label checkboxLabel" for="privacy"><?php echo TEXT_PRIVACY_CONDITIONS_CONFIRM; ?></label>
            </div>
        </fieldset>
    </div>
<?php
    }

    if (DISPLAY_CONDITIONS_ON_CHECKOUT === 'true') {
?>
<!--bof conditions block -->
  <div id="conditions-div">
    <fieldset>
      <legend><?php echo TABLE_HEADING_CONDITIONS; ?></legend>
      <div><?php echo TEXT_CONDITIONS_DESCRIPTION;?></div>
      <div class="custom-control custom-checkbox">
      <?php echo zen_draw_checkbox_field('conditions', '1', false, 'id="conditions"'); ?><label class="custom-control-label checkboxLabel" for="conditions"><?php echo TEXT_CONDITIONS_CONFIRM; ?></label>
      </div>
    </fieldset>
  </div>
<!--eof conditions block -->
<?php
    }
}
