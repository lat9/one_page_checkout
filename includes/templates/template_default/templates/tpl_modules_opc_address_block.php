<?php
// -----
// Part of the One-Page Checkout plugin, provided under GPL 2.0 license by lat9 (cindy@vinosdefrutastropicales.com).
// Copyright (C) 2017, Vinos de Frutas Tropicales.  All rights reserved.
//
// This module is included by tpl_modules_opc_billing_address.php and tpl_modules_opc_shipping_address.php and
// provides a common-formatting for those two address-blocks.
//
?>
<!--bof address block -->
<?php
// -----
// Sanitize module input values.
//
if (!isset($opc_address_type) || !in_array($opc_address_type, array('bill', 'ship'))) {
    trigger_error("Unknown value for opc_address_type ($opc_address_type).", E_USER_ERROR);
}

// -----
// If the address can be changed and an account-bearing customer has previously-defined addresses, create a dropdown list
// from which they can select.
//
if (!$opc_disable_address_change) {
    $address_selections = $_SESSION['opc']->formatAddressBookDropdown();
    if (count($address_selections) != 0) {
        $selected = $_SESSION['opc']->getAddressDropDownSelection($opc_address_type);
?>
    <div id="choices-<?php echo $opc_address_type; ?>"><?php echo zen_draw_pull_down_menu("address-$opc_address_type", $address_selections, $selected); ?></div>
<?php
    }
}

// -----
// Start address formatting ...
//
$which = $opc_address_type;
$address = $_SESSION['opc']->getAddressValues($which);
if (ACCOUNT_GENDER == 'true') {
    $field_name = "gender[$which]";
    $male_id = "gender-male-$which";
    $female_id = "gender-female-$which";
    echo '<div class="custom-control custom-radio custom-control-inline">' . zen_draw_radio_field ($field_name, 'm', ($address['gender'] == 'm'), "id=\"$male_id\"") . 
    "<label class=\"custom-control-label radioButtonLabel\" for=\"$male_id\">" . MALE . '</label></div><div class="custom-control custom-radio custom-control-inline">' . 
    zen_draw_radio_field ($field_name, 'f', ($address['gender'] == 'f'), "id=\"$female_id\"") . 
    "<label class=\"custom-control-label radioButtonLabel\" for=\"$female_id\">" . FEMALE . '</label></div>' . 
    (zen_not_null(ENTRY_GENDER_TEXT) ? '<span class="alert">' . ENTRY_GENDER_TEXT . '</span>': ''); 
?>
      <br class="clearBoth" />
<?php
}

echo $_SESSION['opc']->formatAddressElement($which, 'firstname', $address['firstname'], ENTRY_FIRST_NAME, TABLE_CUSTOMERS, 'customers_firstname', ENTRY_FIRST_NAME_MIN_LENGTH, ENTRY_FIRST_NAME_TEXT);
    
echo $_SESSION['opc']->formatAddressElement($which, 'lastname', $address['lastname'], ENTRY_LAST_NAME, TABLE_CUSTOMERS, 'customers_lastname', ENTRY_LAST_NAME_MIN_LENGTH, ENTRY_LAST_NAME_TEXT);

if (ACCOUNT_COMPANY == 'true') {
    echo $_SESSION['opc']->formatAddressElement($which, 'company', $address['company'], ENTRY_COMPANY, TABLE_ADDRESS_BOOK, 'entry_company', ENTRY_COMPANY_MIN_LENGTH, ENTRY_COMPANY_TEXT);
}

echo $_SESSION['opc']->formatAddressElement($which, 'street_address', $address['street_address'], ENTRY_STREET_ADDRESS, TABLE_ADDRESS_BOOK, 'entry_street_address', ENTRY_STREET_ADDRESS_MIN_LENGTH, ENTRY_STREET_ADDRESS_TEXT);

if (ACCOUNT_SUBURB == 'true') {
    echo $_SESSION['opc']->formatAddressElement($which, 'suburb', $address['suburb'], ENTRY_SUBURB, TABLE_ADDRESS_BOOK, 'entry_suburb', 0, ENTRY_SUBURB_TEXT);
}

echo $_SESSION['opc']->formatAddressElement($which, 'city', $address['city'], ENTRY_CITY, TABLE_ADDRESS_BOOK, 'entry_city', ENTRY_CITY_MIN_LENGTH, ENTRY_CITY_TEXT);

if (ACCOUNT_STATE == 'true') {
    $state_zone_id = "stateZone-$which";
    $zone_field_name = "zone_id[$which]";
    $state_field_name = "state[$which]";
    $state_field_id = "state-$which";
?>
      <label class="inputLabel"><?php echo ENTRY_STATE; ?></label>
<?php    
    if ($address['show_pulldown_states']) {
        echo zen_draw_pull_down_menu($zone_field_name, zen_prepare_country_zones_pull_down($address['country'], $address['zone_id']), $address['zone_id'], "id=\"$state_zone_id\"");
        if (zen_not_null(ENTRY_STATE_TEXT)) {
            echo '<span class="alert">' . ENTRY_STATE_TEXT . '</span>';
        }
        echo '<br />';
    } else {
        echo zen_draw_hidden_field($zone_field_name, $address['zone_name']);
    }
    
    echo zen_draw_input_field($state_field_name, $address['state'], zen_set_field_length(TABLE_ADDRESS_BOOK, 'entry_state', '40') . " id=\"$state_field_id\"");
    if (zen_not_null(ENTRY_STATE_TEXT)) {
        echo '<span class="alert">' . ENTRY_STATE_TEXT . '</span>';
    }
?>
      <br class="clearBoth" />
<?php
}

echo $_SESSION['opc']->formatAddressElement($which, 'postcode', $address['postcode'], ENTRY_POST_CODE, TABLE_ADDRESS_BOOK, 'entry_postcode', ENTRY_POSTCODE_MIN_LENGTH, ENTRY_POST_CODE_TEXT);

$field_name = "zone_country_id[$which]";
$field_id = "country-$which";
?>
      <label class="inputLabel" for="country-bill"><?php echo ENTRY_COUNTRY; ?></label>
      <?php echo zen_get_country_list($field_name, $address['country'], "id=\"$field_id\"") . 
      (zen_not_null(ENTRY_COUNTRY_TEXT) ? '<span class="alert">' . ENTRY_COUNTRY_TEXT . '</span>' : ''); ?>
      <div class="clearBoth"></div>
      
      <div id="messages-<?php echo $which; ?>"></div>
<!--eof address block -->
