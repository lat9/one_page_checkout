<?php
// -----
// Part of the One-Page Checkout plugin, provided under GPL 2.0 license by lat9 (cindy@vinosdefrutastropicales.com).
// Copyright (C) 2017-2026, Vinos de Frutas Tropicales.  All rights reserved.
//
// Last updated: OPC v2.6.0
//
// This should be first line of the script:
$zco_notifier->notify('NOTIFY_HEADER_START_CREATE_ACCOUNT_REGISTER');

if (!defined('IS_ADMIN_FLAG')) {
    die('Illegal Access');
}

// -----
// If OPC's not installed or not properly set in the session, nothing to do here.
//
if (empty($_SESSION['opc']) || !is_object($_SESSION['opc'])) {
    return;
}

// -----
// Provide zc200+ function if not currently defined.
//
/**
 * Validate a date in the selected locale date format
 *
 * @param string $date
 * @param string $format (optional) needs to be a valid short date format for DateTimeImmutableObject using / or - or nothing as separators
 * @return bool
 * @since ZC v2.0.0
 */
if (!function_exists('zen_valid_date')) {
    function zen_valid_date(string $date, string $format = DATE_FORMAT): bool
    {
        // Build 3 formats from 1 with 3 possible separators
        $format0 = str_replace('-', '/', $format);
        $format1 = str_replace('/', '-', $format);
        $format2 = str_replace(['/','-'], '', $format);
        $d0 = DateTime::createFromFormat('!' . $format0, $date);
        $d1 = DateTime::createFromFormat('!' . $format1, $date);
        $d2 = DateTime::createFromFormat('!' . $format2, $date);
        return ($d0 && $d0->format($format0) == $date) || ($d1 && $d1->format($format1) == $date) || ($d2 && $d2->format($format2) == $date);
    }
}

// -----
// Determine the minimum length of an entered telephone number. It's set
// to the OPC-specific value (added in v2.6.0), if present, otherwise it'll
// default to the base's minimum value setting.
//
$telephone_min_length = (int)((defined('CHECKOUT_ONE_REGISTERED_ACCT_TELEPHONE_MIN')) ? CHECKOUT_ONE_REGISTERED_ACCT_TELEPHONE_MIN : ENTRY_TELEPHONE_MIN_LENGTH);

// -----
// Ditto for the telephone number's placeholder text, using the registered-account version, if
// the constant's available or falling back to the base value otherwise.
//
$telephone_placeholder = (defined('TEXT_TELEPHONE_PLACEHOLDER')) ? TEXT_TELEPHONE_PLACEHOLDER : ENTRY_TELEPHONE_NUMBER_TEXT;

// -----
// Process the form-submittal, if indicated.
//
if (isset($_POST['action']) && $_POST['action'] === 'register') {
    $process = true;
    $company = '';
    
    $antiSpamFieldName = isset($_SESSION['antispam_fieldname']) ? $_SESSION['antispam_fieldname'] : 'should_be_empty';
    $antiSpam = !empty($_POST[$antiSpamFieldName]) ? 'spam' : '';
    if (!empty($_POST['firstname']) && preg_match('~https?://?~', $_POST['firstname'])) {
        $antiSpam = 'spam';
    }
    if (!empty($_POST['lastname']) && preg_match('~https?://?~', $_POST['lastname'])) {
        $antiSpam = 'spam';
    }

    $zco_notifier->notify('NOTIFY_CREATE_ACCOUNT_CAPTCHA_CHECK');

    if (isset($_POST['email_format'])) {
        $email_format = in_array($_POST['email_format'], ['HTML', 'TEXT', 'NONE', 'OUT'], true) ? $_POST['email_format'] : 'TEXT';
    }

    $customers_authorization = (int)CUSTOMERS_APPROVAL_AUTHORIZATION;
    $customers_referral = isset($_POST['customers_referral']) ? zen_db_prepare_input(zen_sanitize_string($_POST['customers_referral'])) : '';

    if (ACCOUNT_NEWSLETTER_STATUS === '1' || ACCOUNT_NEWSLETTER_STATUS === '2') {
        $newsletter = 0;
        if (isset($_POST['newsletter'])) {
            $newsletter = zen_db_prepare_input($_POST['newsletter']);
        }
    }

    if (DISPLAY_PRIVACY_CONDITIONS === 'true') {
        if (!(isset($_POST['privacy_conditions']) && $_POST['privacy_conditions'] === '1')) {
            $error = true;
            $messageStack->add('create_account', ERROR_PRIVACY_STATEMENT_NOT_ACCEPTED, 'error');
        }
    }

    if (ACCOUNT_GENDER === 'true') {
        $gender = (isset($_POST['gender'])) ? zen_db_prepare_input($_POST['gender']) : false;
        if ($gender !== 'm' && $gender !== 'f') {
            $error = true;
            $messageStack->add('create_account', ENTRY_GENDER_ERROR);
        }
    }

    $firstname = zen_db_prepare_input(zen_sanitize_string($_POST['firstname']));
    if (mb_strlen($firstname) < ENTRY_FIRST_NAME_MIN_LENGTH) {
        $error = true;
        $messageStack->add('create_account', ENTRY_FIRST_NAME_ERROR);
    }

    $lastname = zen_db_prepare_input(zen_sanitize_string($_POST['lastname']));
    if (mb_strlen($lastname) < ENTRY_LAST_NAME_MIN_LENGTH) {
        $error = true;
        $messageStack->add('create_account', ENTRY_LAST_NAME_ERROR);
    }

    if (ACCOUNT_DOB === 'true') {
        $dob = zen_db_prepare_input($_POST['dob']);
        if ((int)ENTRY_DOB_MIN_LENGTH > 0 || !empty($_POST['dob'])) {
            if (strlen($dob) > 10 || zen_valid_date($dob) === false) {
                $error = true;
                $messageStack->add('create_account', ENTRY_DATE_OF_BIRTH_ERROR);
            }
        }
    }

    if (ACCOUNT_COMPANY === 'true') {
        $company = zen_db_prepare_input(zen_sanitize_string($_POST['company']));
        if ((int)ENTRY_COMPANY_MIN_LENGTH > 0 && mb_strlen($company) < ENTRY_COMPANY_MIN_LENGTH) {
            $error = true;
            $messageStack->add('create_account', ENTRY_COMPANY_ERROR);
        }
    }

    $nick_error = false;
    $email_address = zen_db_prepare_input($_POST['email_address']);
    $email_address_confirm = zen_db_prepare_input($_POST['email_address_confirm']);
    if (mb_strlen($email_address) < ENTRY_EMAIL_ADDRESS_MIN_LENGTH) {
        $error = true;
        $messageStack->add('create_account', ENTRY_EMAIL_ADDRESS_ERROR);
    } elseif (zen_validate_email($email_address) === false) {
        $error = true;
        $messageStack->add('create_account', ENTRY_EMAIL_ADDRESS_CHECK_ERROR);
    } else {
        $already_exists = !zen_check_email_address_not_already_used($email_address);
        $zco_notifier->notify('NOTIFY_CREATE_ACCOUNT_LOOKUP_BY_EMAIL', $email_address, $already_exists, $send_welcome_email);

        if ($already_exists) {
            $error = true;
            $messageStack->add('create_account', ENTRY_EMAIL_ADDRESS_ERROR_EXISTS);
        } else {
            $zco_notifier->notify('NOTIFY_NICK_CHECK_FOR_EXISTING_EMAIL', $email_address, $nick_error, $nick);
            if ($nick_error) {
                $error = true;
            }

            if ($email_address !== $email_address_confirm) {
                $error = true;
                $messageStack->add('create_account', ENTRY_EMAIL_MISMATCH_ERROR, 'error');
            }
        }
    }

    $nick = (isset($_POST['nick'])) ? zen_db_prepare_input(zen_sanitize_string($_POST['nick'])) : '';
    $nick_length_min = ENTRY_NICK_MIN_LENGTH;
    $zco_notifier->notify('NOTIFY_NICK_CHECK_FOR_MIN_LENGTH', $nick, $nick_error, $nick_length_min);
    if ($nick_error === true) {
        $error = true;
    }
    $zco_notifier->notify('NOTIFY_NICK_CHECK_FOR_DUPLICATE', $nick, $nick_error);
    if ($nick_error === true) {
        $error = true;
    }
    if ($error === false && !empty($nick)) {
        $sql = "SELECT * FROM " . TABLE_CUSTOMERS  . " WHERE customers_nick = :nick: LIMIT 1";
        $check_nick_query = $db->bindVars($sql, ':nick:', $nick, 'string');
        $check_nick = $db->Execute($check_nick_query);
        if (!$check_nick->EOF) {
            $error = true;
            $messageStack->add('create_account', ENTRY_NICK_DUPLICATE_ERROR);
        }
    }

    $telephone = zen_db_prepare_input(zen_sanitize_string($_POST['telephone']));
    if (strlen($telephone) < $telephone_min_length) {
        $error = true;
        $messageStack->add('create_account', ENTRY_TELEPHONE_NUMBER_ERROR);
    }

    $zco_notifier->notify('NOTIFY_CREATE_ACCOUNT_VALIDATION_CHECK', [], $error, $send_welcome_email);

    $password = zen_db_prepare_input($_POST['password']);
    $confirmation = zen_db_prepare_input($_POST['confirmation']);
    if (strlen($password) < ENTRY_PASSWORD_MIN_LENGTH) {
        $error = true;
        $messageStack->add('create_account', ENTRY_PASSWORD_ERROR);
    } elseif ($password !== $confirmation) {
        $error = true;
        $messageStack->add('create_account', ENTRY_PASSWORD_ERROR_NOT_MATCHING);
    }

    if ($error === true) {
        $zco_notifier->notify('NOTIFY_FAILURE_DURING_CREATE_ACCOUNT');
    } elseif ($antiSpam !== '') {
        $zco_notifier->notify('NOTIFY_SPAM_DETECTED_DURING_CREATE_ACCOUNT');
        $messageStack->add_session('header', (defined('ERROR_CREATE_ACCOUNT_SPAM_DETECTED') ? ERROR_CREATE_ACCOUNT_SPAM_DETECTED : 'Thank you, your account request has been submitted for review.'), 'success');
        zen_redirect(zen_href_link(FILENAME_SHOPPING_CART));
    } else {
        $ip_address = zen_get_ip_address();

        $customer = new Customer();

        // -----
        // A "registered" customer doesn't supply their address information, set some defaults
        // for the customer's address.
        //
        $dob ??= '';
        $fax ??= '';
        $street_address = '';
        $suburb = '';
        $city = '';
        $zone_id = (int)STORE_ZONE;
        $postcode = '';
        $country = (int)STORE_COUNTRY;
        $state = zen_get_zone_name($country, $zone_id);
        $data = compact(
            'firstname', 'lastname', 'email_address', 'nick', 'email_format', 'telephone', 'fax',
            'newsletter', 'password', 'customers_authorization', 'customers_referral',
            'gender', 'dob', 'company', 'street_address',
            'suburb', 'city', 'zone_id', 'state', 'postcode', 'country', 'ip_address'
        );

        $result = $customer->create($data);
        if (!empty($result)) {
            $zco_notifier->notify('NOTIFY_HEADER_REGISTER_ADDED_CUSTOMER_RESULT', $result);

            $customer->login($result['customers_id'], $restore_cart = true);
            if (SESSION_RECREATE === 'True') {
                zen_session_recreate();
            }
        }

        // do any 3rd-party nick creation
        $nick_email = $email_address;
        $zco_notifier->notify('NOTIFY_NICK_CREATE_NEW', $nick, $password, $nick_email, $extra_welcome_text);

        if (!empty($result['activation_required'])) {
            require DIR_WS_MODULES . zen_get_module_directory(FILENAME_SEND_AUTH_TOKEN_EMAIL);
            zen_redirect(zen_href_link(CUSTOMERS_AUTHORIZATION_FILENAME, '', 'SSL'));
        }

        require DIR_WS_MODULES . zen_get_module_directory(FILENAME_CHECKOUT_ONE_SEND_WELCOME_EMAIL);
        zen_redirect(zen_href_link(FILENAME_CREATE_ACCOUNT_SUCCESS, '', 'SSL'));
    } //endif !error
}

// -----
// If the OPC's registered accounts' processing is enabled, set some flags for the alternate
// create-account page's processing.
//
if ($_SESSION['opc']->accountRegistrationEnabled()) {
    $display_nick_field = false;
    $zco_notifier->notify('NOTIFY_NICK_SET_TEMPLATE_FLAG', 0, $display_nick_field);

    $breadcrumb->add(NAVBAR_TITLE_REGISTER);
}

// This should be last line of the script:
$zco_notifier->notify('NOTIFY_HEADER_END_CREATE_ACCOUNT_REGISTER');
