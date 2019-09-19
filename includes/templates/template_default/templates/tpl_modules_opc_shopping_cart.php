<?php
// -----
// Part of the One-Page Checkout plugin, provided under GPL 2.0 license by lat9 (cindy@vinosdefrutastropicales.com).
// Copyright (C) 2013-2019, Vinos de Frutas Tropicales.  All rights reserved.
//
// The following definition is used in multiple pages and will in the main language file, e.g. english.php, for
// Zen Cart versions 1.5.7 and later.
//
// Provide an in-script override for the case it's not defined.
//
if (!defined('TEXT_OPTION_DIVIDER')) define('TEXT_OPTION_DIVIDER', '&nbsp;-&nbsp;');
?>
<!--bof shopping-cart block -->
  <div id="checkoutOneShoppingCart">
    <fieldset id="checkoutOneCartGroup">
      <legend><?php echo HEADING_PRODUCTS; ?></legend>
      <table border="0" width="100%" cellspacing="0" cellpadding="0" id="cartContentsDisplay">
        <tr>
            <td class="edit-button" colspan="<?php echo (count($order->info['tax_groups']) > 1) ? 3 : 2; ?>">&nbsp;</td>
            <td class="edit-button"><?php echo '<a href="' . zen_href_link(FILENAME_SHOPPING_CART) . '">' . zen_image_button(BUTTON_IMAGE_EDIT_SMALL, BUTTON_EDIT_SMALL_ALT) . '</a>'; ?></td>
        </tr>
        
        <tr class="cartTableHeading">
          <th scope="col" id="ccQuantityHeading"><?php echo TABLE_HEADING_QUANTITY; ?></th>
          <th scope="col" id="ccProductsHeading"><?php echo TABLE_HEADING_PRODUCTS; ?></th>
<?php
// If there are tax groups, display the tax columns for price breakdown
if (count($order->info['tax_groups']) > 1) {
?>
          <th scope="col" id="ccTaxHeading"><?php echo HEADING_TAX; ?></th>
<?php
}
?>
          <th scope="col" id="ccTotalHeading"><?php echo TABLE_HEADING_TOTAL; ?></th>
        </tr>
<?php 
// now loop thru all products to display quantity and price
for ($i = 0, $n = count($order->products); $i < $n; $i++) {
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
        for ($j = 0, $n2 = count($order->products[$i]['attributes']); $j < $n2; $j++) {
?>
              <li><?php echo $order->products[$i]['attributes'][$j]['option'] . TEXT_OPTION_DIVIDER . nl2br(zen_output_string_protected($order->products[$i]['attributes'][$j]['value'])); ?></li>
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
            <td colspan="<?php echo (count($order->info['tax_groups']) > 1) ? 4 : 3; ?>" id="orderTotalDivs"><?php $order_total_modules->process (); $order_total_modules->output(); ?></td>
        </tr>
<?php
}
?>
      </table>
    </fieldset>
  </div>
  <div class="clearBoth"></div>
<!--eof shopping-cart block -->
