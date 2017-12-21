<?php
// -----
// Part of the One-Page Checkout plugin, provided under GPL 2.0 license by lat9 (cindy@vinosdefrutastropicales.com).
// Copyright (C) 2013-2017, Vinos de Frutas Tropicales.  All rights reserved.
//
if (DISPLAY_CONDITIONS_ON_CHECKOUT == 'true') {
?>
<!--bof conditions block -->
  <div id="conditions-div">
    <fieldset>
      <legend><?php echo TABLE_HEADING_CONDITIONS; ?></legend>
      <div><?php echo TEXT_CONDITIONS_DESCRIPTION;?></div>
      <?php echo zen_draw_checkbox_field('conditions', '1', false, 'id="conditions"'); ?><label class="checkboxLabel" for="conditions"><?php echo TEXT_CONDITIONS_CONFIRM; ?></label>
    </fieldset>
  </div>
<!--eof conditions block -->
<?php
}