<?php
// -----
// Part of the One-Page Checkout plugin, provided under GPL 2.0 license by lat9
// Copyright (C) 2013-2022, Vinos de Frutas Tropicales.  All rights reserved.
//
// Last updated: OPC v2.4.1
//
// -----
// This module is used by both the plugin's tpl_modules_opc_shipping_choices.php template and by the plugin's AJAX handler (zcAjaxOnePageCheckout.php) to render
// the contents of the order's shipping section.
//
            $radio_buttons = 0;
            for ($i = 0, $n = count($quotes); $i < $n; $i++) {
                $shipping_module_id = $quotes[$i]['id'];
                if ($quotes[$i]['module'] != '') { // Standard
?>
        <fieldset>
          <legend><?php echo $quotes[$i]['module']; ?>&nbsp;<?php echo (!empty($quotes[$i]['icon'])) ? $quotes[$i]['icon'] : ''; ?></legend>
<?php
                    if (isset($quotes[$i]['error'])) {
?>
          <div><?php echo $quotes[$i]['error']; ?></div>
<?php
                    } else {
                        $shipping_tax_rate = (empty($quotes[$i]['tax'])) ? 0 : $quotes[$i]['tax'];
                        for ($j = 0, $n2 = count($quotes[$i]['methods']); $j < $n2; $j++) {
                            $shipping_method_id = $quotes[$i]['methods'][$j]['id'];
// set the radio button to be checked if it is the method chosen
                            $shipping_method = $shipping_module_id . '_' . $shipping_method_id;
                            $checked = ($shipping_method == $_SESSION['shipping']['id']);
                            
                            $shipping_method_cost = $quotes[$i]['methods'][$j]['cost'];

                            if ($n > 1 || $n2 > 1) {
?>
          <div class="important forward" id="<?php echo str_replace(' ', '-', $shipping_method) . '-value'; ?>"><?php echo $currencies->format(zen_add_tax($shipping_method_cost, $shipping_tax_rate)); ?></div>
<?php
                            } else {
?>
          <div class="important forward"><?php echo $currencies->format(zen_add_tax($shipping_method_cost, $shipping_tax_rate)) . zen_draw_hidden_field('shipping', $shipping_method); ?></div>
<?php
                            }
?>
          <div class="custom-control custom-radio">
<?php
                            $shipping_module_method_id = 'ship-' . $shipping_module_id . '-' . str_replace(' ', '-', $shipping_method_id);
                            $shipping_method_title = $quotes[$i]['methods'][$j]['title'];
                            $shipping_method_title_id = str_replace(' ', '-', $shipping_method) . '-title';
                            echo zen_draw_radio_field('shipping', $shipping_method, $checked, 'id="' . $shipping_module_method_id . '"'); 
?>
            <label for="<?php echo $shipping_module_method_id; ?>" class="custom-control-label radioButtonLabel" id="<?php echo $shipping_method_title_id; ?>"><?php echo $shipping_method_title; ?></label>
          </div>
          <br class="clearBoth" />
<?php
                            $radio_buttons++;
                        }

                        if (isset($quotes[$i]['html_input'])) {
?>
          <div class="shipping-additional-inputs"><?php echo $quotes[$i]['html_input']; ?></div>
<?php
                        }
                    }
?>
        </fieldset>
<?php
                }
// eof: field set
            }
