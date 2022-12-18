<?php
// -----
// Part of the One-Page Checkout plugin, provided under GPL 2.0 license by lat9 (cindy@vinosdefrutastropicales.com).
// Copyright (C) 2018-2022, Vinos de Frutas Tropicales.  All rights reserved.
//
// Last updated for OPC v2.4.5.
//
/**
 * jscript_form_check
 *
 * @package page
 * @copyright Copyright 2003-2016 Zen Cart Development Team
 * @copyright Portions Copyright 2003 osCommerce
 * @license http://www.zen-cart.com/license/2_0.txt GNU Public License V2.0
 * @version $Id: Author: DrByte  Wed Jan 6 12:47:43 2016 -0500 Modified in v1.5.5 $
 */
// -----
// If the session-based OPC class isn't present or account registration is not enabled, nothing to do here ...
//
if (!(isset($_SESSION['opc']) && $_SESSION['opc']->accountRegistrationEnabled())) {
    return;
}

// -----
// Note that the additional javascript processing allows the create_account_register form to be processed
// successfully with fewer account-creation fields.  The processing still relies on the javascript functions
// and variables defined in the create_account page's jscript_form_check.php module!
//
?>
<script>
function check_register_form()
{
    form = 'create_account';

    if (submitted == true) {
        alert("<?php echo JS_ERROR_SUBMITTED; ?>");
        return false;
    }

    error = false;
    error_message = "<?php echo JS_ERROR; ?>";
<?php
if (ACCOUNT_GENDER === 'true') {
?>
    check_radio("gender", "<?php echo ENTRY_GENDER_ERROR; ?>");
<?php
}

if ((int)ENTRY_FIRST_NAME_MIN_LENGTH > 0) {
?>
    check_input("firstname", <?php echo (int)ENTRY_FIRST_NAME_MIN_LENGTH; ?>, "<?php echo ENTRY_FIRST_NAME_ERROR; ?>");
<?php
}

if ((int)ENTRY_LAST_NAME_MIN_LENGTH > 0) {
?>
    check_input("lastname", <?php echo (int)ENTRY_LAST_NAME_MIN_LENGTH; ?>, "<?php echo ENTRY_LAST_NAME_ERROR; ?>");
<?php
}

if (ACCOUNT_DOB === 'true' && (int)ENTRY_DOB_MIN_LENGTH !== 0) {
?>
    check_input("dob", <?php echo (int)ENTRY_DOB_MIN_LENGTH; ?>, "<?php echo ENTRY_DATE_OF_BIRTH_ERROR; ?>");
<?php
}

if (ACCOUNT_COMPANY === 'true' && (int)ENTRY_COMPANY_MIN_LENGTH !== 0) {
?>
    check_input("company", <?php echo (int)ENTRY_COMPANY_MIN_LENGTH; ?>, "<?php echo ENTRY_COMPANY_ERROR; ?>");
<?php
}

if ((int)ENTRY_EMAIL_ADDRESS_MIN_LENGTH > 0) {
?>
    check_input("email_address", <?php echo (int)ENTRY_EMAIL_ADDRESS_MIN_LENGTH; ?>, "<?php echo ENTRY_EMAIL_ADDRESS_ERROR; ?>");
    
    if (form.elements['email_address'].value != form.elements['email_address_confirmation'].value) {
        error_message = error_message + '<?php echo ENTRY_EMAIL_MISMATCH_ERROR_JS; ?>' + "\n";
        error = true;
    }
<?php
}

if ((int)ENTRY_TELEPHONE_MIN_LENGTH > 0) {
?>
    check_input("telephone", <?php echo ENTRY_TELEPHONE_MIN_LENGTH; ?>, "<?php echo ENTRY_TELEPHONE_NUMBER_ERROR; ?>");
<?php
}

if ((int)ENTRY_PASSWORD_MIN_LENGTH > 0) {
?>
    check_password("password", "confirmation", <?php echo (int)ENTRY_PASSWORD_MIN_LENGTH; ?>, "<?php echo ENTRY_PASSWORD_ERROR; ?>", "<?php echo ENTRY_PASSWORD_ERROR_NOT_MATCHING; ?>");
    check_password_new("password_current", "password_new", "password_confirmation", <?php echo (int)ENTRY_PASSWORD_MIN_LENGTH; ?>, "<?php echo ENTRY_PASSWORD_ERROR; ?>", "<?php echo ENTRY_PASSWORD_NEW_ERROR; ?>", "<?php echo ENTRY_PASSWORD_NEW_ERROR_NOT_MATCHING; ?>");
<?php
}
?>
    if (error == true) {
        alert(error_message);
        return false;
    } else {
        submitted = true;
        return true;
    }
}
</script>
