<?php
// -----
// Part of the One-Page Checkout plugin, provided under GPL 2.0 license by lat9 (cindy@vinosdefrutastropicales.com).
// Copyright (C) 2013-2017, Vinos de Frutas Tropicales.  All rights reserved.
//
?>
<!--bof credit-selection block -->
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
    <div class="messageStackError"><?php echo zen_output_string_protected($_GET['credit_class_error']); ?></div>

<?php
        }
        // -----
        // Determine which parameter needs to be submitted on the button-formatting to include a common class to which
        // a jQuery event handler binds.  When CSS buttons are used, the "secondary class" input must be used; otherwise,
        // the class is submitted to the function as part of the to-be-created parameter list.
        //
        if (strtolower(IMAGE_USE_CSS_BUTTONS) == 'yes') {
            $secondary_class = 'opc-cc-submit';
            $additional_parms = '';
        } else {
            $secondary_class = '';
            $additional_parms = 'name="apply_' . $ot_class . '"' . ' class="opc-cc-submit"';
        }
        
        $ot_class = str_replace('ot_', '', $credit_selection[$i]['id']);
        for ($j = 0, $n2 = count($credit_selection[$i]['fields']); $j < $n2; $j++) {
?>
    <div class="checkoutOne<?php echo ucfirst($ot_class); ?>">
      <fieldset>
        <legend><?php echo $credit_selection[$i]['module']; ?></legend><?php echo $credit_selection[$i]['redeem_instructions']; ?>
        <div class="gvBal larger"><?php echo $credit_selection[$i]['checkbox']; ?></div>
        <label class="inputLabel"<?php echo ($credit_selection[$i]['fields'][$j]['tag']) ? ' for="'.$credit_selection[$i]['fields'][$j]['tag'].'"': ''; ?>><?php echo $credit_selection[$i]['fields'][$j]['title']; ?></label><?php echo $credit_selection[$i]['fields'][$j]['field']; ?>
        <div class="buttonRow forward"><?php echo zen_image_button(BUTTON_IMAGE_SUBMIT, ALT_TEXT_APPLY_DEDUCTION, $additional_parms, $secondary_class); ?></div>
        <div class="clearBoth"></div>
      </fieldset>
    </div>
<?php
        }
    }
}
?>
<!--eof credit-selection block -->
