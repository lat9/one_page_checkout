<?php
// -----
// Part of the One-Page Checkout plugin, provided under GPL 2.0 license by lat9 (cindy@vinosdefrutastropicales.com).
// Copyright (C) 2013-2017, Vinos de Frutas Tropicales.  All rights reserved.
//
?>
<!--bof payment-method choices -->
<?php
// -----
// Don't display the payment-method block if there is no shipping method available.
//
if ($shipping_module_available) {
?>
  <div id="checkoutPaymentMethod" class="floatingBox forward clearRight">
    <fieldset>
      <legend><?php echo TABLE_HEADING_PAYMENT_METHOD; ?></legend>
<?php 
    // ** BEGIN PAYPAL EXPRESS CHECKOUT **
    if (!$payment_modules->in_special_checkout()) {
    // ** END PAYPAL EXPRESS CHECKOUT ** 
        if (SHOW_ACCEPTED_CREDIT_CARDS != '0') {
            if (SHOW_ACCEPTED_CREDIT_CARDS == '1') {
                echo TEXT_ACCEPTED_CREDIT_CARDS . zen_get_cc_enabled();
          
            } elseif (SHOW_ACCEPTED_CREDIT_CARDS == '2') {
                echo TEXT_ACCEPTED_CREDIT_CARDS . zen_get_cc_enabled('IMAGE_');
          
            }
?>
      <br class="clearBoth" />
<?php 
    } 

        $selection = $enabled_payment_modules;

        if (sizeof($selection) > 1) {
?>
      <p class="important"><?php echo TEXT_SELECT_PAYMENT_METHOD; ?></p>
<?php
        } elseif (sizeof($selection) == 0) {
?>
      <p class="important"><?php echo TEXT_NO_PAYMENT_OPTIONS_AVAILABLE; ?></p>

<?php
        }

        $radio_buttons = 0;
        for ($i=0, $n=sizeof($selection); $i<$n; $i++) {
            if (sizeof($selection) > 1) {
                if (empty($selection[$i]['noradio'])) {
                    echo zen_draw_radio_field('payment', $selection[$i]['id'], ($selection[$i]['id'] == $_SESSION['payment'] ? true : false), 'id="pmt-'.$selection[$i]['id'].'"');
                }
            } else {
                echo zen_draw_hidden_field('payment', $selection[$i]['id'], 'id="pmt-'.$selection[$i]['id'].'"');
            }
?>
      <label for="pmt-<?php echo $selection[$i]['id']; ?>" class="radioButtonLabel"><?php echo $selection[$i]['module']; ?></label>

<?php
            if (defined ('MODULE_ORDER_TOTAL_COD_STATUS') && MODULE_ORDER_TOTAL_COD_STATUS == 'true' and $selection[$i]['id'] == 'cod') {
?>
      <div class="alert"><?php echo TEXT_INFO_COD_FEES; ?></div>
<?php
            }
?>
      <br class="clearBoth" />

<?php
            if (isset($selection[$i]['error'])) {
?>
      <div><?php echo $selection[$i]['error']; ?></div>

<?php
            } elseif (isset($selection[$i]['fields']) && is_array($selection[$i]['fields'])) {
?>

      <div class="ccinfo">
<?php
                for ($j=0, $n2=sizeof($selection[$i]['fields']); $j<$n2; $j++) {
?>
        <label <?php echo (isset($selection[$i]['fields'][$j]['tag']) ? 'for="'.$selection[$i]['fields'][$j]['tag'] . '" ' : ''); ?>class="inputLabelPayment"><?php echo $selection[$i]['fields'][$j]['title']; ?></label><?php echo $selection[$i]['fields'][$j]['field']; ?>
        <br class="clearBoth" />
<?php
                }
?>
      </div>
      <br class="clearBoth" />
<?php
            }
            $radio_buttons++;

        }
    // ** BEGIN PAYPAL EXPRESS CHECKOUT **
    } else {
?>
    <p><?php echo ${$_SESSION['payment']}->title; ?></p>
    <input type="hidden" name="payment" value="<?php echo $_SESSION['payment']; ?>" />
<?php
    }
    // ** END PAYPAL EXPRESS CHECKOUT **
?>
    </fieldset>
  </div>
<?php
}  //-Shipping-method available, display payment block.
?>
  <div class="clearBoth"></div>
<!--eof payment-method choices -->