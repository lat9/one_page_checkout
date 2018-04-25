<?php
// -----
// Part of the One-Page Checkout plugin, provided under GPL 2.0 license by lat9 (cindy@vinosdefrutastropicales.com).
// Copyright (C) 2013-2016, Vinos de Frutas Tropicales.  All rights reserved.
//
// -----
// This module is used by both the plugin's tpl_modules_opc_shipping_choices.php template and by the plugin's AJAX handler (zcAjaxOnePageCheckout.php) to render
// the contents of the order's shipping section.
//
            $radio_buttons = 0;
            for ($i = 0, $n = count ($quotes); $i < $n; $i++) {
                if ($quotes[$i]['module'] != '') { // Standard
?>
        <fieldset>
          <legend id="<?php echo $quotes[$i]['id']; ?>"><?php echo $quotes[$i]['module']; ?>&nbsp;<?php if (isset($quotes[$i]['icon']) && zen_not_null($quotes[$i]['icon'])) { echo $quotes[$i]['icon']; } ?></legend>

<?php
                    if (isset($quotes[$i]['error'])) {
?>
          <div><?php echo $quotes[$i]['error']; ?></div>
<?php
                    } else {
                        for ($j = 0, $n2 = count ($quotes[$i]['methods']); $j < $n2; $j++) {
// set the radio button to be checked if it is the method chosen
                            $shipping_method = $quotes[$i]['id'] . '_' . $quotes[$i]['methods'][$j]['id'];
                            $checked = ($shipping_method == $_SESSION['shipping']['id']);

                            if ($n > 1 || $n2 > 1) {
?>
          <div class="important forward" id="<?php echo str_replace (' ', '-', $shipping_method) . '-value'; ?>"><?php echo $currencies->format (zen_add_tax ($quotes[$i]['methods'][$j]['cost'], (isset ($quotes[$i]['tax']) ? $quotes[$i]['tax'] : 0))); ?></div>
<?php
                            } else {
?>
          <div class="important forward"><?php echo $currencies->format (zen_add_tax ($quotes[$i]['methods'][$j]['cost'], $quotes[$i]['tax'])) . zen_draw_hidden_field ('shipping', $shipping_method); ?></div>
<?php
                            }
                             echo '<div class="custom-control custom-radio">';        
                            echo zen_draw_radio_field('shipping', $shipping_method, $checked, 'id="ship-'.$quotes[$i]['id'] . '-' . str_replace(' ', '-', $quotes[$i]['methods'][$j]['id']) .'"'); 
?>
          <label for="ship-<?php echo $quotes[$i]['id'] . '-' . str_replace(' ', '-', $quotes[$i]['methods'][$j]['id']); ?>" class="custom-control-label radioButtonLabel" id="<?php echo str_replace (' ', '-', $shipping_method); ?>-title"><?php echo $quotes[$i]['methods'][$j]['title']; ?></label>
                    </div>
                    <br class="clearBoth" />
<?php
                            $radio_buttons++;
                        }

                        if (isset ($quotes[$i]['html_input'])) {
                            echo '<div class="shipping-additional-inputs">' . $quotes[$i]['html_input'] . '</div>';
                        }
                    }
?>
        </fieldset>
<?php
                }
// eof: field set
            }
