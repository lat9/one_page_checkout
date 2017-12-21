<?php
// -----
// Part of the One-Page Checkout plugin, provided under GPL 2.0 license by lat9 (cindy@vinosdefrutastropicales.com).
// Copyright (C) 2013-2017, Vinos de Frutas Tropicales.  All rights reserved.
//
if (TEXT_CHECKOUT_ONE_INSTRUCTIONS != '') {
?>
  <div id="instructions">
    <fieldset>
<?php
    if (TEXT_CHECKOUT_ONE_INSTRUCTION_LABEL != '') {
?>
      <legend><?php echo TEXT_CHECKOUT_ONE_INSTRUCTION_LABEL; ?></legend>
<?php
    }
?>
      <p><?php echo TEXT_CHECKOUT_ONE_INSTRUCTIONS; ?></p>
    </fieldset>
  </div>
<?php
}
