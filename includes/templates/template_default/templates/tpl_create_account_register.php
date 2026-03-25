<?php
// -----
// Part of the One-Page Checkout plugin, provided under GPL 2.0 license by lat9
// Copyright (C) 2017-2026, Vinos de Frutas Tropicales.  All rights reserved.
//
// Last updated: OPC v2.6.0
//
?>
<div class="centerColumn" id="registerDefault">
    <h1 id="createAcctDefaultHeading"><?= HEADING_TITLE ?></h1>
    <?= zen_draw_form('create_account', zen_href_link(FILENAME_CREATE_ACCOUNT, '', 'SSL'), 'post', 'onsubmit="return check_register_form();"') ?>
    <?= zen_draw_hidden_field('action', 'register') ?>
    <?= zen_draw_hidden_field('email_pref_html', 'email_format') ?>
    <div id="registerDefaultLoginLink"><?= sprintf(TEXT_INSTRUCTIONS, zen_href_link(FILENAME_LOGIN, '', 'SSL')) ?></div>
<?php
if ($messageStack->size('create_account') > 0) {
    echo $messageStack->output('create_account');
}
?>
    <div class="alert forward"><?= FORM_REQUIRED_INFORMATION ?></div>
    <br class="clearBoth">

<?php
if (DISPLAY_PRIVACY_CONDITIONS === 'true') {
?>
    <fieldset>
        <legend><?= TABLE_HEADING_PRIVACY_CONDITIONS ?></legend>
        <div class="information"><?= TEXT_PRIVACY_CONDITIONS_DESCRIPTION ?></div>
        <?= zen_draw_checkbox_field('privacy_conditions', '1', false, 'id="privacy"') ?>
        <label class="checkboxLabel" for="privacy"><?= TEXT_PRIVACY_CONDITIONS_CONFIRM ?></label>
        <br class="clearBoth">
    </fieldset>
<?php
}

if (ACCOUNT_COMPANY === 'true') {
    $company_field_length = zen_set_field_length(TABLE_ADDRESS_BOOK, 'entry_company', '40');
?>
    <fieldset>
        <legend><?= CATEGORY_COMPANY ?></legend>
        <label class="inputLabel" for="company"><?= ENTRY_COMPANY ?></label>
        <?= zen_draw_input_field('company', '', $company_field_length . ' id="company" placeholder="' . ENTRY_COMPANY_TEXT . '"' . ((int)ENTRY_COMPANY_MIN_LENGTH > 0 ? ' required' : '')) ?>
    </fieldset>
<?php
}
?>
    <fieldset>
        <legend><?= HEADING_CONTACT_DETAILS ?></legend>
<?php
if (ACCOUNT_GENDER === 'true') {
?>
        <label class="inputLabel"><?= ENTRY_GENDER ?></label>
<?php
    echo zen_draw_radio_field('gender', 'm', '', 'id="gender-male"') . 
        '<label class="radioButtonLabel" for="gender-male">' . MALE . '</label>' . 
        zen_draw_radio_field('gender', 'f', '', 'id="gender-female"') . 
        '<label class="radioButtonLabel" for="gender-female">' . FEMALE . '</label>' . 
        (!empty(ENTRY_GENDER_TEXT) ? '<span class="alert">' . ENTRY_GENDER_TEXT . '</span>': ''); 
?>
        <br class="clearBoth">
<?php
}

$firstname_field_length = zen_set_field_length(TABLE_CUSTOMERS, 'customers_firstname', '40');
$lastname_field_length = zen_set_field_length(TABLE_CUSTOMERS, 'customers_lastname', '40');
$telephone_field_length = zen_set_field_length(TABLE_CUSTOMERS, 'customers_telephone', '40');
$telephone_required = ($telephone_min_length > 0) ? ' required' : '';
?>
        <label class="inputLabel" for="firstname"><?= ENTRY_FIRST_NAME ?></label>
        <?= zen_draw_input_field('firstname', '', $firstname_field_length . ' id="firstname" placeholder="' . ENTRY_FIRST_NAME_TEXT . '"' . ((int)ENTRY_FIRST_NAME_MIN_LENGTH > 0 ? ' required' : '')) ?>
        <br class="clearBoth">

        <label class="inputLabel" for="lastname"><?= ENTRY_LAST_NAME ?></label>
        <?= zen_draw_input_field('lastname', '', $lastname_field_length . ' id="lastname" placeholder="' . ENTRY_LAST_NAME_TEXT . '"' . ((int)ENTRY_LAST_NAME_MIN_LENGTH > 0 ? ' required' : '')) ?>
        <br class="clearBoth">

        <label class="inputLabel phone" for="telephone"><?= ENTRY_TELEPHONE_NUMBER ?></label>
        <?= zen_draw_input_field('telephone', '', $telephone_field_length . ' id="telephone" class="phone" placeholder="' . $telephone_placeholder . '"' . $telephone_required, 'tel') ?>
        <br class="clearBoth phone">
<?php
unset($company_field_length, $firstname_field_length, $lastname_field_length, $telephone_field_length);

if (ACCOUNT_DOB === 'true') {
?>
        <label class="inputLabel" for="dob"><?= ENTRY_DATE_OF_BIRTH ?></label>
        <?= zen_draw_input_field('dob', '', 'id="dob" placeholder="' . ENTRY_DATE_OF_BIRTH_TEXT . '"' . ((int)ENTRY_DOB_MIN_LENGTH > 0 ? ' required' : '')) ?>
        <br class="clearBoth">
<?php
}

if ($display_nick_field == true) {
?>
        <label class="inputLabel" for="nickname"><?= ENTRY_NICK ?></label>
        <?= zen_draw_input_field('nick', '', 'id="nickname" placeholder="' . ENTRY_NICK_TEXT . '"') ?>
        <br class="clearBoth">
<?php
}
?>
    </fieldset>

    <fieldset>
        <legend><?= TABLE_HEADING_LOGIN_DETAILS ?></legend>

        <label class="inputLabel" for="email-address"><?= ENTRY_EMAIL_ADDRESS ?></label>
<?php
$email_field_length = zen_set_field_length(TABLE_CUSTOMERS, 'customers_email_address', '40');
$email_required = ((int)ENTRY_EMAIL_ADDRESS_MIN_LENGTH > 0 ? ' required' : '');
echo zen_draw_input_field('email_address', '', $email_field_length . ' id="email-address" placeholder="' . ENTRY_EMAIL_ADDRESS_TEXT . '"' . $email_required, 'email'); 
?>
        <br class="clearBoth">

        <label class="inputLabel" for="email-address-confirm"><?= ENTRY_EMAIL_ADDRESS_CONFIRM ?></label>
        <?= zen_draw_input_field('email_address_confirm', '', $email_field_length . ' id="email-address-confirm" placeholder="' . ENTRY_EMAIL_ADDRESS_TEXT . '"' . $email_required, 'email') ?>
        <br class="clearBoth">

        <label class="inputLabel"><?= ENTRY_EMAIL_FORMAT ?></label>
<?php
echo 
    zen_draw_radio_field('email_format', 'HTML', ($email_format == 'HTML' ? true : false),'id="email-format-html"') . 
    '<label class="radioButtonLabel" for="email-format-html">' . ENTRY_EMAIL_HTML_DISPLAY . '</label>' .  
    zen_draw_radio_field('email_format', 'TEXT', ($email_format == 'TEXT' ? true : false), 'id="email-format-text"') . 
    '<label class="radioButtonLabel" for="email-format-text">' . ENTRY_EMAIL_TEXT_DISPLAY . '</label>'; 
?>
        <br class="clearBoth">

        <label class="inputLabel" for="password-new"><?= ENTRY_PASSWORD ?></label>
<?php
$password_field_length = zen_set_field_length(TABLE_CUSTOMERS, 'customers_password', '20');
$password_required = ((int)ENTRY_PASSWORD_MIN_LENGTH > 0 ? ' required' : '');
echo zen_draw_password_field('password', '', $password_field_length . ' id="password-new" autocomplete="off" placeholder="' . ENTRY_PASSWORD_TEXT . '"'. $password_required); 
?>
        <br class="clearBoth">

        <label class="inputLabel" for="password-confirm"><?= ENTRY_PASSWORD_CONFIRMATION ?></label>
        <?= zen_draw_password_field('confirmation', '', $password_field_length . ' id="password-confirm" autocomplete="off" placeholder="' . ENTRY_PASSWORD_CONFIRMATION_TEXT . '"' . $password_required)?>
        <br class="clearBoth">
    </fieldset>
    
<?php
if (ACCOUNT_NEWSLETTER_STATUS !== '0') {
?>
    <fieldset>
        <legend><?= ENTRY_EMAIL_PREFERENCE ?></legend>
<?php
    echo zen_draw_checkbox_field('newsletter', '1', $newsletter, 'id="newsletter-checkbox"') .
    '<label class="checkboxLabel" for="newsletter-checkbox">' . ENTRY_NEWSLETTER . '</label>' .
    (!empty(ENTRY_NEWSLETTER_TEXT) ? '<span class="alert">' . ENTRY_NEWSLETTER_TEXT . '</span>': '');
?>
        <br class="clearBoth">
    </fieldset>
<?php
} 

if (CUSTOMERS_REFERRAL_STATUS === '2') {
?>
    <fieldset>
        <legend><?= TABLE_HEADING_REFERRAL_DETAILS ?></legend>
        <label class="inputLabel" for="customers_referral"><?= ENTRY_CUSTOMERS_REFERRAL ?></label>
        <?= zen_draw_input_field('customers_referral', '', zen_set_field_length(TABLE_CUSTOMERS, 'customers_referral', '15') . ' id="customers_referral"') ?>
        <br class="clearBoth">
    </fieldset>
<?php 
} 
?>
    <div class="buttonRow forward"><?= zen_image_submit(BUTTON_IMAGE_SUBMIT, BUTTON_SUBMIT_REGISTER_ALT) ?></div>
    <?= '</form>' ?>
</div>
