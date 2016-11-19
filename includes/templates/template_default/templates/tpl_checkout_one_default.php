<?php
// -----
// Part of the One-Page Checkout plugin, provided under GPL 2.0 license by lat9 (cindy@vinosdefrutastropicales.com).
// Copyright (C) 2013-2016, Vinos de Frutas Tropicales.  All rights reserved.
//
?>
<?php 
echo $payment_modules->javascript_validation();

// -----
// Gather the count of enabled shipping- and payment-methods, so that only applicable sections are displayed.
//
$shipping_module_available = (zen_count_shipping_modules () > 0);
$enabled_payment_modules = $payment_modules->selection();
$payment_module_available = ($payment_modules->in_special_checkout() || count ($enabled_payment_modules > 0));
?>
<div class="centerColumn" id="checkoutPayment">
<?php
  echo zen_draw_form ('checkout_payment', zen_href_link (FILENAME_CHECKOUT_ONE_CONFIRMATION, '', 'SSL'), 'post', 'id="checkout_payment"') . zen_draw_hidden_field ('action', 'process') . zen_draw_hidden_field ('javascript_enabled', '0', 'id="javascript-enabled"'); 
?>
  <h1 id="checkoutOneHeading"><?php echo HEADING_TITLE; ?></h1>
<?php
if (TEXT_CHECKOUT_ONE_TOP_INSTRUCTIONS != '') {
?>
  <div id="co1-top-message"><p><?php echo TEXT_CHECKOUT_ONE_TOP_INSTRUCTIONS; ?></p></div>
<?php
}
$messages_to_check = array ( 'checkout_shipping', 'checkout_payment', 'redemptions' );
foreach ($messages_to_check as $page_check) {
    if ($messageStack->size ($page_check) > 0) {
        echo $messageStack->output ($page_check);
    }
}
?>
  <div id="checkoutOneLeft" class="floatingBox back">
    <div id="checkoutOneBillto">
      <fieldset>
        <legend><?php echo TITLE_BILLING_ADDRESS; ?></legend>
        <address><?php echo zen_address_format ($order->billing['format_id'], $order->billing, 1, ' ', '<br />'); ?></address>
<?php 
if (!$flagDisablePaymentAddressChange) { 
?>
        <div class="buttonRow forward"><?php echo '<a href="' . zen_href_link (FILENAME_CHECKOUT_PAYMENT_ADDRESS, '', 'SSL') . '">' . zen_image_button (BUTTON_IMAGE_EDIT_SMALL, BUTTON_EDIT_SMALL_ALT) . '</a>'; ?></div>
<?php 
} 
?>
      </fieldset> 
    </div>
<?php
// -----
// Display shipping-address information **only if** the order contains at least one physical product (i.e. it's not virtual).
//
if ($is_virtual_order) {
    echo zen_draw_checkbox_field ('shipping_billing', '1', false, 'id="shipping_billing" style="display: none;"');
} else {
?>
    <div id="checkoutOneShippingFlag" style="display: none;"><?php echo  zen_draw_checkbox_field ('shipping_billing', '1', $shipping_billing, 'id="shipping_billing" onchange="shippingIsBilling ();"');?>
      <label class="checkboxLabel" for="shipping_billing"><?php echo TEXT_USE_BILLING_FOR_SHIPPING; ?></label>
    </div>
 
    <div id="checkoutOneShipto">
      <fieldset>
        <legend><?php echo TITLE_SHIPPING_ADDRESS; ?></legend>
        <address><?php echo zen_address_format($order->delivery['format_id'], $order->delivery, 1, ' ', '<br />'); ?></address>
        <div class="buttonRow forward"><?php echo '<a href="' . $editShippingButtonLink . '">' . zen_image_button (BUTTON_IMAGE_EDIT_SMALL, BUTTON_EDIT_SMALL_ALT) . '</a>'; ?></div>
      </fieldset>
    </div>
<?php
}
?>    
    <div id="checkoutComments">
      <fieldset class="shipping" id="comments"><legend><?php echo TABLE_HEADING_COMMENTS; ?></legend><?php echo zen_draw_textarea_field('comments', '45', '3'); ?></fieldset>
    </div>
 
<?php
// -----
// Process the "credit-selection", e.g. coupon-code entry, gift-voucher redeem-code, block(s) for the active
// order-totals -- so long as there is a shipping method available.
//
if ($shipping_module_available) {
    $credit_selection =  $order_total_modules->credit_selection();
    for ($i = 0, $n = count ($credit_selection); $i < $n; $i++) {
        if (isset ($_GET['credit_class_error_code']) && $_GET['credit_class_error_code'] == $credit_selection[$i]['id']) {
?>
    <div class="messageStackError"><?php echo zen_output_string_protected ($_GET['credit_class_error']); ?></div>

<?php
        }
        $ot_class = str_replace ('ot_', '', $credit_selection[$i]['id']);
        for ($j = 0, $n2 = count ($credit_selection[$i]['fields']); $j < $n2; $j++) {
?>
    <div class="checkoutOne<?php echo ucfirst ($ot_class); ?>">
      <fieldset>
        <legend><?php echo $credit_selection[$i]['module']; ?></legend><?php echo $credit_selection[$i]['redeem_instructions']; ?>
        <div class="gvBal larger"><?php echo $credit_selection[$i]['checkbox']; ?></div>
        <label class="inputLabel"<?php echo ($credit_selection[$i]['fields'][$j]['tag']) ? ' for="'.$credit_selection[$i]['fields'][$j]['tag'].'"': ''; ?>><?php echo $credit_selection[$i]['fields'][$j]['title']; ?></label><?php echo $credit_selection[$i]['fields'][$j]['field']; ?>
        <div class="buttonRow forward"><?php echo zen_image_submit (BUTTON_IMAGE_SUBMIT, ALT_TEXT_APPLY_DEDUCTION, 'name="apply_' . $ot_class . '" onclick="setOrderConfirmed (0);"'); ?></div>
        <div class="clearBoth"></div>
      </fieldset>
    </div>
<?php
        }
    }
}
?>   
  </div>
  
  <div id="checkoutShippingMethod" class="floatingBox forward">   
<?php
// -----
// If the order contains only virtual products, the shipping block contains only a hidden field that
// identifies the "free" shipping method; otherwise, display the full shipping block.
//
if ($is_virtual_order) {
    echo zen_draw_hidden_field ('shipping', $_SESSION['shipping']['id']) . PHP_EOL;
} else {
    if (zen_count_shipping_modules() > 0) {
?>
    <fieldset>
      <legend><?php echo TABLE_HEADING_SHIPPING_METHOD; ?></legend>
<?php
        if (count ($quotes) > 1 && count ($quotes[0]) > 1) {
            $checkout_one->debug_message ("CHECKOUT_ONE_TEMPLATE_SHIPPING_QUOTES:\n" . var_export ($_SESSION['shipping'], true) . "\n" . var_export ($quotes, true));
?>

        <div id="checkoutShippingContentChoose" class="important"><?php echo TEXT_CHOOSE_SHIPPING_METHOD; ?></div>

<?php
        } elseif ($free_shipping == false) {
?>
        <div id="checkoutShippingContentChoose" class="important"><?php echo TEXT_ENTER_SHIPPING_INFORMATION; ?></div>

<?php
        }
?>
<?php
        if ($free_shipping == true) {
?>
        <div id="freeShip" class="important" ><?php echo FREE_SHIPPING_TITLE; ?></div>
        <div id="defaultSelected"><?php echo sprintf (FREE_SHIPPING_DESCRIPTION, $currencies->format (MODULE_ORDER_TOTAL_SHIPPING_FREE_SHIPPING_OVER)) . zen_draw_hidden_field ('shipping', 'free_free'); ?></div>

<?php
        } else {
?>
        <div id="checkoutShippingChoices">
<?php
            require ($template->get_template_dir ('tpl_modules_checkout_one_shipping.php', DIR_WS_TEMPLATE, $current_page_base, 'templates'). '/tpl_modules_checkout_one_shipping.php');
?>
        </div>
<?php
        }
?>
    </fieldset>
<?php
    } else {
?>
    <h2 id="checkoutShippingHeadingMethod"><?php echo TITLE_NO_SHIPPING_AVAILABLE; ?></h2>
<?php
//-bof-product_delivery_by_postcode (PDP) integration
        $chk_local_delivery_only = $_SESSION['cart']->in_cart_check ('product_is_local_delivery', '1');
        if ($chk_local_delivery_only) {
?>
    <div id="cartLocalText"><?php echo TEXT_PRODUCT_LOCAL_DELIVERY_ONLY; ?></div>
<?php
        }
//-eof-product_delivery_by_postcode (PDP) integration
?>
    <div id="checkoutShippingContentChoose" class="important"><?php echo TEXT_NO_SHIPPING_AVAILABLE; ?></div>
<?php
    }
}  //-Order is not "virtual", display full shipping-method block
?>
  </div>
<?php
// -----
// Don't display the payment-method block if there is no shipping method available.
//
if ($shipping_method_available) {
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
                echo TEXT_ACCEPTED_CREDIT_CARDS . zen_get_cc_enabled ('IMAGE_');
          
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
  <div id="checkoutOneShoppingCart">
    <fieldset id="checkoutOneCartGroup">
      <legend><?php echo HEADING_PRODUCTS; ?></legend>
      <table border="0" width="100%" cellspacing="0" cellpadding="0" id="cartContentsDisplay">
        <tr class="cartTableHeading">
          <th scope="col" id="ccQuantityHeading"><?php echo TABLE_HEADING_QUANTITY; ?></th>
          <th scope="col" id="ccProductsHeading"><?php echo TABLE_HEADING_PRODUCTS; ?></th>
<?php
// If there are tax groups, display the tax columns for price breakdown
if (sizeof ($order->info['tax_groups']) > 1) {
?>
          <th scope="col" id="ccTaxHeading"><?php echo HEADING_TAX; ?></th>
<?php
}
?>
          <th scope="col" id="ccTotalHeading"><?php echo TABLE_HEADING_TOTAL; ?></th>
        </tr>
<?php 
// now loop thru all products to display quantity and price
for ($i = 0, $n = count ($order->products); $i < $n; $i++) {
    $last_row_class = $order->products[$i]['rowClass'];
?>
        <tr class="<?php echo $order->products[$i]['rowClass']; ?>">
          <td class="cartQuantity"><?php echo $order->products[$i]['qty']; ?>&nbsp;x</td>
          <td class="cartProductDisplay"><?php echo $order->products[$i]['name'] . $stock_check[$i]; ?>
<?php 
// if there are attributes, loop thru them and display one per line
    if (isset ($order->products[$i]['attributes']) && count ($order->products[$i]['attributes']) > 0 ) {
?>
            <ul class="cartAttribsList">
<?php
        for ($j = 0, $n2 = count ($order->products[$i]['attributes']); $j<$n2; $j++) {
?>
              <li><?php echo $order->products[$i]['attributes'][$j]['option'] . ': ' . nl2br(zen_output_string_protected($order->products[$i]['attributes'][$j]['value'])); ?></li>
<?php
        } // end loop
?>
            </ul>
<?php
    } // endif attribute-info
    
    if (isset ($posStockMessage)) {
        echo '<br />' . $posStockMessage[$i];
    }
?>
          </td>
<?php 
  // display tax info if exists
    if (sizeof ($order->info['tax_groups']) > 1)  { 
?>
          <td class="cartTotalDisplay"><?php echo zen_display_tax_value($order->products[$i]['tax']); ?>%</td>
<?php
    }  // endif tax info display  
?>
          <td class="cartTotalDisplay">
<?php 
    echo $currencies->display_price($order->products[$i]['final_price'], $order->products[$i]['tax'], $order->products[$i]['qty']);
    if ($order->products[$i]['onetime_charges'] != 0 ) {
        echo '<br /> ' . $currencies->display_price($order->products[$i]['onetime_charges'], $order->products[$i]['tax'], 1);
    }
?>
          </td>
        </tr>
<?php  
}  
// end for loopthru all products 

if (MODULE_ORDER_TOTAL_INSTALLED) {
    $row_class = ($last_row_class == 'rowEven') ? 'rowOdd' : 'rowEven';
?>     
        <tr class="<?php echo $row_class; ?>" id="cartOrderTotals">
            <td colspan="<?php echo (count ($order->info['tax_groups']) > 1) ? 4 : 3; ?>" id="orderTotalDivs"><?php $order_total_modules->process (); $order_total_modules->output (); ?></td>
        </tr>
<?php
}
?>
      </table>
    </fieldset>
  </div>
  <div class="clearBoth"></div>
<?php
// -----
// Check to see that at least one shipping-method and one payment-method is enabled; if not, don't render the instructions, conditions or submit-button.
//
if ($shipping_module_available && $payment_module_available) {
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

    if (DISPLAY_CONDITIONS_ON_CHECKOUT == 'true') {
?>
  <div id="conditions-div">
    <fieldset>
      <legend><?php echo TABLE_HEADING_CONDITIONS; ?></legend>
      <div><?php echo TEXT_CONDITIONS_DESCRIPTION;?></div>
      <?php echo zen_draw_checkbox_field ('conditions', '1', false, 'id="conditions"'); ?><label class="checkboxLabel" for="conditions"><?php echo TEXT_CONDITIONS_CONFIRM; ?></label>
    </fieldset>
  </div>
<?php
    }
?>
  <div id="checkoutOneSubmit" class="buttonRow forward"><?php echo zen_image_submit (BUTTON_IMAGE_CHECKOUT_ONE_CONFIRM, BUTTON_CHECKOUT_ONE_CONFIRM_ALT, 'id="confirm-order" name="confirm_order" onclick="submitFunction(' .zen_user_has_gv_account($_SESSION['customer_id']).','.$order->info['total'] . '); setOrderConfirmed (1);"') . zen_draw_hidden_field ('order_confirmed', '1', 'id="confirm-the-order"'); ?></div>
  <div id="checkoutOneEmail" class="forward clearRight"><?php echo sprintf (TEXT_CONFIRMATION_EMAILS_SENT_TO, $order->customer['email_address']); ?></div>
<?php
}
?>
  <div class="clearBoth"></div>

</form>
</div>