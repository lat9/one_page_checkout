<?php
// -----
// Part of the One-Page Checkout plugin, provided under GPL 2.0 license by lat9
// Copyright (C) 2017-2020, Vinos de Frutas Tropicales.  All rights reserved.
//
?>
<div class="centerColumn" id="registerDefault">

    <h1 id="createAcctDefaultHeading"><?php echo HEADING_TITLE; ?></h1>
<?php
echo zen_draw_form('create_account', zen_href_link(FILENAME_CREATE_ACCOUNT, '', 'SSL'), 'post', 'onsubmit="return check_register_form();"') . zen_draw_hidden_field('action', 'register') . zen_draw_hidden_field('email_pref_html', 'email_format'); 
?>
    <div id="registerDefaultLoginLink"><?php echo sprintf(TEXT_INSTRUCTIONS, zen_href_link(FILENAME_LOGIN, '', 'SSL')); ?></div>
<?php
if ($messageStack->size('create_account') > 0) {
    echo $messageStack->output('create_account');
}
?>
    <div class="alert forward"><?php echo FORM_REQUIRED_INFORMATION; ?></div>
    <br class="clearBoth" />

<?php
if (DISPLAY_PRIVACY_CONDITIONS == 'true') {
?>
    <fieldset>
        <legend><?php echo TABLE_HEADING_PRIVACY_CONDITIONS; ?></legend>
        <div class="information"><?php echo TEXT_PRIVACY_CONDITIONS_DESCRIPTION;?></div>
<?php 
    echo zen_draw_checkbox_field('privacy_conditions', '1', false, 'id="privacy"');
?>
        <label class="checkboxLabel" for="privacy"><?php echo TEXT_PRIVACY_CONDITIONS_CONFIRM;?></label>
        <br class="clearBoth" />
    </fieldset>
<?php
}

if (ACCOUNT_COMPANY == 'true') {
?>
    <fieldset>
        <legend><?php echo CATEGORY_COMPANY; ?></legend>
        <label class="inputLabel" for="company"><?php echo ENTRY_COMPANY; ?></label>
        <?php echo zen_draw_input_field('company', '', zen_set_field_length(TABLE_ADDRESS_BOOK, 'entry_company', '40') . ' id="company" placeholder="' . ENTRY_COMPANY_TEXT . '"'. ((int)ENTRY_COMPANY_MIN_LENGTH != 0 ? ' required' : '')); ?>
    </fieldset>
<?php
  }
?>
    <fieldset>
        <legend><?php echo HEADING_CONTACT_DETAILS; ?></legend>
<?php
if (ACCOUNT_GENDER == 'true') {
?>
        <label class="inputLabel"><?php echo ENTRY_GENDER; ?></label>
<?php
    echo zen_draw_radio_field('gender', 'm', '', 'id="gender-male"') . 
        '<label class="radioButtonLabel" for="gender-male">' . MALE . '</label>' . 
        zen_draw_radio_field('gender', 'f', '', 'id="gender-female"') . 
        '<label class="radioButtonLabel" for="gender-female">' . FEMALE . '</label>' . 
        (zen_not_null(ENTRY_GENDER_TEXT) ? '<span class="alert">' . ENTRY_GENDER_TEXT . '</span>': ''); 
?>
        <br class="clearBoth" />
<?php
}
?>
        <label class="inputLabel" for="firstname"><?php echo ENTRY_FIRST_NAME; ?></label>
<?php 
echo zen_draw_input_field('firstname', '', zen_set_field_length(TABLE_CUSTOMERS, 'customers_firstname', '40') . ' id="firstname" placeholder="' . ENTRY_FIRST_NAME_TEXT . '"' . ((int)ENTRY_FIRST_NAME_MIN_LENGTH > 0 ? ' required' : '')); 
?>
        <br class="clearBoth" />

        <label class="inputLabel" for="lastname"><?php echo ENTRY_LAST_NAME; ?></label>
<?php 
echo zen_draw_input_field('lastname', '', zen_set_field_length(TABLE_CUSTOMERS, 'customers_lastname', '40') . ' id="lastname" placeholder="' . ENTRY_LAST_NAME_TEXT . '"'. ((int)ENTRY_LAST_NAME_MIN_LENGTH > 0 ? ' required' : '')); 
?>
        <br class="clearBoth" />

        <label class="inputLabel" for="telephone"><?php echo ENTRY_TELEPHONE_NUMBER; ?></label>
<?php 
echo zen_draw_input_field('telephone', '', zen_set_field_length(TABLE_CUSTOMERS, 'customers_telephone', '40') . ' id="telephone" placeholder="' . ENTRY_TELEPHONE_NUMBER_TEXT . '"' . ((int)ENTRY_TELEPHONE_MIN_LENGTH > 0 ? ' required' : ''), 'tel');
?>
        <br class="clearBoth" />
<?php
if (ACCOUNT_DOB == 'true') {
?>
        <label class="inputLabel" for="dob"><?php echo ENTRY_DATE_OF_BIRTH; ?></label>
<?php 
    echo zen_draw_input_field('dob','', 'id="dob" placeholder="' . ENTRY_DATE_OF_BIRTH_TEXT . '"' . (ACCOUNT_DOB == 'true' && (int)ENTRY_DOB_MIN_LENGTH != 0 ? ' required' : '')); 
?>
        <br class="clearBoth" />
<?php
}

if ($display_nick_field == true) {
?>
        <label class="inputLabel" for="nickname"><?php echo ENTRY_NICK; ?></label>
<?php 
    echo zen_draw_input_field('nick','','id="nickname" placeholder="' . ENTRY_NICK_TEXT . '"'); 
?>
        <br class="clearBoth" />
<?php
}
?>
    </fieldset>
        
    <fieldset>
        <legend><?php echo TABLE_HEADING_LOGIN_DETAILS; ?></legend>

        <label class="inputLabel" for="email-address"><?php echo ENTRY_EMAIL_ADDRESS; ?></label>
<?php
$email_field_length = zen_set_field_length(TABLE_CUSTOMERS, 'customers_email_address', '40');
$email_required = ((int)ENTRY_EMAIL_ADDRESS_MIN_LENGTH > 0 ? ' required' : '');
echo zen_draw_input_field('email_address', '', $email_field_length . ' id="email-address" placeholder="' . ENTRY_EMAIL_ADDRESS_TEXT . '"' . $email_required, 'email'); 
?>
        <br class="clearBoth" />

        <label class="inputLabel" for="email-address-confirm"><?php echo ENTRY_EMAIL_ADDRESS_CONFIRM; ?></label>
<?php 
echo zen_draw_input_field('email_address_confirm', '', $email_field_length . ' id="email-address-confirm" placeholder="' . ENTRY_EMAIL_ADDRESS_TEXT . '"' . $email_required, 'email'); 
?>
        <br class="clearBoth" />

        <label class="inputLabel"><?php echo ENTRY_EMAIL_FORMAT; ?></label>
<?php
echo 
    zen_draw_radio_field('email_format', 'HTML', ($email_format == 'HTML' ? true : false),'id="email-format-html"') . 
    '<label class="radioButtonLabel" for="email-format-html">' . ENTRY_EMAIL_HTML_DISPLAY . '</label>' .  
    zen_draw_radio_field('email_format', 'TEXT', ($email_format == 'TEXT' ? true : false), 'id="email-format-text"') . 
    '<label class="radioButtonLabel" for="email-format-text">' . ENTRY_EMAIL_TEXT_DISPLAY . '</label>'; 
?>
        <br class="clearBoth" />

        <label class="inputLabel" for="password-new"><?php echo ENTRY_PASSWORD; ?></label>
<?php
$password_field_length = zen_set_field_length(TABLE_CUSTOMERS, 'customers_password', '20');
$password_required = ((int)ENTRY_PASSWORD_MIN_LENGTH > 0 ? ' required' : '');
echo zen_draw_password_field('password', '', $password_field_length . ' id="password-new" autocomplete="off" placeholder="' . ENTRY_PASSWORD_TEXT . '"'. $password_required); 
?>
        <br class="clearBoth" />

        <label class="inputLabel" for="password-confirm"><?php echo ENTRY_PASSWORD_CONFIRMATION; ?></label>
<?php 
echo zen_draw_password_field('confirmation', '', $password_field_length . ' id="password-confirm" autocomplete="off" placeholder="' . ENTRY_PASSWORD_CONFIRMATION_TEXT . '"'. $password_required); 
?>
        <br class="clearBoth" />
    </fieldset>
    
<?php
if (ACCOUNT_NEWSLETTER_STATUS != 0) {
?>
    <fieldset>
        <legend><?php echo ENTRY_EMAIL_PREFERENCE; ?></legend>
<?php
    echo zen_draw_checkbox_field('newsletter', '1', $newsletter, 'id="newsletter-checkbox"') . 
    '<label class="checkboxLabel" for="newsletter-checkbox">' . ENTRY_NEWSLETTER . '</label>' . 
    (zen_not_null(ENTRY_NEWSLETTER_TEXT) ? '<span class="alert">' . ENTRY_NEWSLETTER_TEXT . '</span>': ''); 
?>
        <br class="clearBoth" />
    </fieldset>
<?php 
} 

if (CUSTOMERS_REFERRAL_STATUS == 2) {
?>
    <fieldset>
        <legend><?php echo TABLE_HEADING_REFERRAL_DETAILS; ?></legend>
        <label class="inputLabel" for="customers_referral"><?php echo ENTRY_CUSTOMERS_REFERRAL; ?></label>
<?php echo zen_draw_input_field('customers_referral', '', zen_set_field_length(TABLE_CUSTOMERS, 'customers_referral', '15') . ' id="customers_referral"'); ?>
        <br class="clearBoth" />
    </fieldset>
<?php 
} 
?>
    <div class="buttonRow forward"><?php echo zen_image_submit(BUTTON_IMAGE_SUBMIT, BUTTON_SUBMIT_REGISTER_ALT); ?></div>
<?php
echo '</form>';
?>
</div>
