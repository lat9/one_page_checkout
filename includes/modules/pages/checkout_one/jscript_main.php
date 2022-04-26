<?php
// -----
// Part of the One-Page Checkout plugin, provided under GPL 2.0 license by lat9
// Copyright (C) 2013-2022, Vinos de Frutas Tropicales.  All rights reserved.
//
?>
<script>
<?php
// -----
// Introduced in OPC v2.3.0 to identify the template-specific selector for the
// order's current total.  If not defined, default to the selector associated with
// the 'responsive_classic' template.
//
if (!defined('CHECKOUT_ONE_OTTOTAL_SELECTOR')) {
    define('CHECKOUT_ONE_OTTOTAL_SELECTOR', '#ottotal > div:first-child');
//    define('CHECKOUT_ONE_OTTOTAL_SELECTOR', '#ottotal div span');   //-Value for YourStore template
}

// -----
// Introduced in OPC v2.4.0 to identify payment methods that handle the form's submittal directly.
//
if (!defined('CHECKOUT_ONE_PAYMENT_METHODS_THAT_SUBMIT')) {
    define('CHECKOUT_ONE_PAYMENT_METHODS_THAT_SUBMIT', 'square_webPay');
}
$payments_that_submit = '';
if (CHECKOUT_ONE_PAYMENT_METHODS_THAT_SUBMIT !== '') {
    $payments_that_submit = explode(',', str_replace(' ', '', CHECKOUT_ONE_PAYMENT_METHODS_THAT_SUBMIT));
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
var confirmation_required = [<?php echo $required_list; ?>];
var paymentsThatSubmit = [<?php echo $payments_that_submit; ?>];

var virtual_order = <?php echo ($is_virtual_order) ? 'true' : 'false'; ?>;
var timeoutUrl = '<?php echo zen_href_link(FILENAME_LOGIN, '', 'SSL'); ?>';
var sessionTimeoutErrorMessage = '<?php echo JS_ERROR_SESSION_TIMED_OUT; ?>';
var ajaxTimeoutErrorMessage = '<?php echo JS_ERROR_AJAX_TIMEOUT . JS_ERROR_CONTACT_US; ?>';
var ajaxTimeoutShippingErrorMessage = '<?php echo JS_ERROR_AJAX_SHIPPING_TIMEOUT . JS_ERROR_CONTACT_US; ?>';
var ajaxTimeoutPaymentErrorMessage = '<?php echo JS_ERROR_AJAX_PAYMENT_TIMEOUT . JS_ERROR_CONTACT_US; ?>';
var ajaxTimeoutSetAddressErrorMessage = '<?php echo JS_ERROR_AJAX_SET_ADDRESS_TIMEOUT . JS_ERROR_CONTACT_US; ?>';
var ajaxTimeoutRestoreAddressErrorMessage = '<?php echo JS_ERROR_AJAX_RESTORE_ADDRESS_TIMEOUT . JS_ERROR_CONTACT_US; ?>';
var ajaxTimeoutValidateAddressErrorMessage = '<?php echo JS_ERROR_AJAX_VALIDATE_ADDRESS_TIMEOUT . JS_ERROR_CONTACT_US; ?>';
var ajaxTimeoutRestoreCustomerErrorMessage = '<?php echo JS_ERROR_AJAX_RESTORE_CUSTOMER_TIMEOUT . JS_ERROR_CONTACT_US; ?>';
var ajaxTimeoutValidateCustomerErrorMessage = '<?php echo JS_ERROR_AJAX_VALIDATE_CUSTOMER_TIMEOUT . JS_ERROR_CONTACT_US; ?>';
var ajaxNotAvailableMessage = '<?php echo JS_ERROR_OPC_NOT_ENABLED; ?>';
var checkoutShippingUrl = '<?php echo zen_href_link(FILENAME_CHECKOUT_SHIPPING, '', 'SSL'); ?>';
var noShippingSelectedError = '<?php echo ERROR_NO_SHIPPING_SELECTED; ?>';
var flagOnSubmit = <?php echo ($flagOnSubmit) ? 'true' : 'false'; ?>;
var shippingTimeout = <?php echo (int)((defined('CHECKOUT_ONE_SHIPPING_TIMEOUT')) ? CHECKOUT_ONE_SHIPPING_TIMEOUT : 5000); ?>;
var textPleaseSelect = '<?php echo PLEASE_SELECT; ?>';
var displayShippingBlock = <?php echo ($display_shipping_block) ? 'true' : 'false'; ?>;
var displayPaymentBlock = <?php echo ($display_payment_block) ? 'true' : 'false'; ?>;
var ottotalSelector = '<?php echo CHECKOUT_ONE_OTTOTAL_SELECTOR; ?>';
var billingTitle = '<?php echo TITLE_BILLING_ADDRESS; ?>';
var billingShippingTitle = '<?php echo TITLE_BILLING_SHIPPING_ADDRESS; ?>';
var shippingChoiceAvailable = <?php echo (is_array($quotes) && count($quotes) > 0) ? 'true' : 'false'; ?>;
var paymentChoiceAvailable = <?php echo (is_array($enabled_payment_modules) && count($enabled_payment_modules) > 0) ? 'true' : 'false'; ?>;
<?php
// -----
// If dropdown states are to be displayed, include that json-formatted array of countries/zones.
//
if ($show_state_dropdowns) {
    echo $_SESSION['opc']->getCountriesZonesJavascript();
}
?>
var additionalShippingInputs = {
<?php
// -----
// If the current order has generated shipping quotes (i.e. it's got at least one physical product), check to see if a 
// shipping-module has required inputs that should accompany the post, format the necessary jQuery to gather those inputs.
//
$input_array = 'var shippingInputs = {';
if (isset($quotes) && is_array($quotes)) {
    $additional_shipping_inputs = [];
    foreach ($quotes as $current_quote) {
        if (isset($current_quote['required_input_names']) && is_array($current_quote['required_input_names'])) {
            foreach ($current_quote['required_input_names'] as $current_input_name => $selection_required) {
                $variable_name = base::camelize($current_input_name);
                $input_array .= "$variable_name: '', ";
?>
    <?php echo $variable_name; ?>: { input_name: '<?php echo $current_input_name; ?>', parms: '<?php echo ($selection_required) ? ':checked' : ''; ?>' },
<?php
            }
        }
    }
}
?>
};
<?php
echo $input_array . '};'.PHP_EOL;
?>
</script>
<?php
if (defined('CHECKOUT_ONE_MINIFIED_SCRIPT') && CHECKOUT_ONE_MINIFIED_SCRIPT === 'true') {
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
<script src="<?php echo $main_script_filepath; ?>" defer></script>
<?php
// -----
// Check to see if dropdown states are to be displayed, including that processing only
// if enabled.
//
if ($show_state_dropdowns) {
    $addr_script_filepath = DIR_WS_MODULES . "pages/checkout_one/$addr_script_filename";
    $addr_script_mtime = filemtime($addr_script_filepath);
    $addr_script_filepath .= "?$addr_script_mtime";
?>
<script src="<?php echo $addr_script_filepath; ?>" defer></script>
<?php
}
