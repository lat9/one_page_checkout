<?php
// -----
// Part of the One-Page Checkout plugin, provided under GPL 2.0 license by lat9
// Copyright (C) 2013-2026, Vinos de Frutas Tropicales.  All rights reserved.
//
// Last updated: OPC v2.7.0
//
?>
<script id="opc-js-main">
<?php
// -----
// Introduced in OPC v2.3.0 to identify the template-specific selector for the
// order's current total.  If not defined, default to the selector associated with
// the 'responsive_classic' template.
//
zen_define_default('CHECKOUT_ONE_OTTOTAL_SELECTOR', '#ottotal > div:first-child');
//    zen_define_default('CHECKOUT_ONE_OTTOTAL_SELECTOR', '#ottotal div span');   //-Value for YourStore template

$payments_that_submit = '';
if (zen_config('CHECKOUT_ONE_PAYMENT_METHODS_THAT_SUBMIT', 'square_webPay') !== '') {
    $payments_that_submit = explode(',', str_replace(' ', '', zen_config('CHECKOUT_ONE_PAYMENT_METHODS_THAT_SUBMIT', 'square_webPay')));
    $payments_that_submit = (count($payments_that_submit) === 1 && $payments_that_submit[0] === '') ? [] : $payments_that_submit;
    $payments_that_submit = '"' . implode('", "', $payments_that_submit) . '"';
}

// -----
// The "confirmation_required" array contains a list of payment modules for which, er, confirmation
// is required.  This is used to determine whether the "confirm-order" or "review-order" button is displayed.
// The $required_list value is created by the page's header_php.php processing.
//
// Note: Starting with v2.3.2, the state dropdowns are **always** rendered.
//
$show_state_dropdowns = true;
?>
    const confirmation_required = [<?= $required_list ?>];
    const paymentsThatSubmit = [<?= $payments_that_submit ?>];

    const virtual_order = <?= ($is_virtual_order) ? 'true' : 'false' ?>;
    const timeoutUrl = '<?= zen_href_link(FILENAME_LOGIN, '', 'SSL') ?>';
    const sessionTimeoutErrorMessage = '<?= JS_ERROR_SESSION_TIMED_OUT ?>';
    const ajaxTimeoutErrorMessage = '<?= JS_ERROR_AJAX_TIMEOUT . JS_ERROR_CONTACT_US ?>';
    const ajaxTimeoutShippingErrorMessage = '<?= JS_ERROR_AJAX_SHIPPING_TIMEOUT . JS_ERROR_CONTACT_US ?>';
    const ajaxTimeoutPaymentErrorMessage = '<?= JS_ERROR_AJAX_PAYMENT_TIMEOUT . JS_ERROR_CONTACT_US ?>';
    const ajaxTimeoutSetAddressErrorMessage = '<?= JS_ERROR_AJAX_SET_ADDRESS_TIMEOUT . JS_ERROR_CONTACT_US ?>';
    const ajaxTimeoutRestoreAddressErrorMessage = '<?= JS_ERROR_AJAX_RESTORE_ADDRESS_TIMEOUT . JS_ERROR_CONTACT_US ?>';
    const ajaxTimeoutValidateAddressErrorMessage = '<?= JS_ERROR_AJAX_VALIDATE_ADDRESS_TIMEOUT . JS_ERROR_CONTACT_US ?>';
    const ajaxTimeoutRestoreCustomerErrorMessage = '<?= JS_ERROR_AJAX_RESTORE_CUSTOMER_TIMEOUT . JS_ERROR_CONTACT_US ?>';
    const ajaxTimeoutValidateCustomerErrorMessage = '<?= JS_ERROR_AJAX_VALIDATE_CUSTOMER_TIMEOUT . JS_ERROR_CONTACT_US ?>';
    const ajaxNotAvailableMessage = '<?= JS_ERROR_OPC_NOT_ENABLED ?>';
    const checkoutShippingUrl = '<?= zen_href_link(FILENAME_CHECKOUT_SHIPPING, '', 'SSL') ?>';
    const noShippingSelectedError = '<?= ERROR_NO_SHIPPING_SELECTED ?>';
    const flagOnSubmit = <?= ($flagOnSubmit) ? 'true' : 'false' ?>;
    const shippingTimeout = <?= (int)zen_config('CHECKOUT_ONE_SHIPPING_TIMEOUT', 5000) ?>;
    const textPleaseSelect = '<?= PLEASE_SELECT ?>';
    const displayShippingBlock = <?= ($display_shipping_block) ? 'true' : 'false' ?>;
    const displayPaymentBlock = <?= ($display_payment_block) ? 'true' : 'false' ?>;
    const billingTitle = '<?= TITLE_BILLING_ADDRESS ?>';
    const billingShippingTitle = '<?= TITLE_BILLING_SHIPPING_ADDRESS ?>';
    const shippingChoiceAvailable = <?= (is_array($quotes) && count($quotes) > 0) ? 'true' : 'false' ?>;
    const paymentChoiceAvailable = <?= (is_array($enabled_payment_modules) && count($enabled_payment_modules) > 0) ? 'true' : 'false' ?>;
<?php
// -----
// If dropdown states are to be displayed, include that json-formatted array of countries/zones.
//
if ($show_state_dropdowns === true) {
    echo $_SESSION['opc']->getCountriesZonesJavascript();
}

// -----
// Save the shipping quotes' information into the session, for use by the AJAX
// processing's 'updateShippingSelection' method.
//
// Keeping the values in the session so that the checkout_one page's
// jquery.checkout_one.js can simply pass the updated shipping-method "name". Otherwise,
// characters like &nbsp; and &reg; get decoded to their utf8 representation and
// make it "difficult" to record those values in the order.
//
$_SESSION['opc_shipping_quotes'] = [];
$opc_additional_shipping_inputs = [];
if (isset($quotes) && is_array($quotes)) {
    foreach ($quotes as $current_quote) {
        if (empty($current_quote['methods'])) {
            continue;
        }

        $module_id = $current_quote['id'];
        $_SESSION['opc_shipping_quotes'][$module_id] = [
            'title' => $current_quote['module'],
        ];
        foreach ($current_quote['methods'] as $next_method) {
            $_SESSION['opc_shipping_quotes'][$module_id][$next_method['id']] = [
                'title' => $next_method['title'],
                'cost' => $next_method['cost'],
            ];
        }

        if (isset($current_quote['required_input_names']) && is_array($current_quote['required_input_names'])) {
            foreach ($current_quote['required_input_names'] as $current_input_name => $selection_required) {
                $opc_additional_shipping_inputs[$current_input_name] = [
                    'parms' => ($selection_required) ? ':checked' : '',
                ];
            }
        }
    }
}
?>
    const additionalShippingInputs = <?= json_encode($opc_additional_shipping_inputs) ?>;
</script>
<?php
if (zen_config('CHECKOUT_ONE_MINIFIED_SCRIPT') === 'true') {
    $main_script_filename = 'jquery.checkout_one.min.js';
    $addr_script_filename = 'jquery.checkout_one_addr.min.js';
} else {
    $main_script_filename = 'jquery.checkout_one.js';
    $addr_script_filename = 'jquery.checkout_one_addr.js';
}
$main_script_filepath = DIR_WS_MODULES . "pages/checkout_one/$main_script_filename";
$main_script_mtime = filemtime($main_script_filepath);
$main_script_filepath .= "?$main_script_mtime";
?>
<script src="<?= $main_script_filepath ?>" defer></script>
<?php
// -----
// Check to see if dropdown states are to be displayed, including that processing only
// if enabled.
//
if ($show_state_dropdowns === true) {
    $addr_script_filepath = DIR_WS_MODULES . "pages/checkout_one/$addr_script_filename";
    $addr_script_mtime = filemtime($addr_script_filepath);
    $addr_script_filepath .= "?$addr_script_mtime";
?>
<script src="<?= $addr_script_filepath ?>" defer></script>
<?php
}
