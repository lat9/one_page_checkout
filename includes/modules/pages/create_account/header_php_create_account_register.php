<?php
// -----
// Part of the One-Page Checkout plugin, provided under GPL 2.0 license by lat9 (cindy@vinosdefrutastropicales.com).
// Copyright (C) 2017-2022, Vinos de Frutas Tropicales.  All rights reserved.
//
// Last updated: OPC v2.4.5
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
    $customers_referral = isset($_POST['customers_referral']) ? zen_db_prepare_input($_POST['customers_referral']) : '';

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
    if (strlen($firstname) < ENTRY_FIRST_NAME_MIN_LENGTH) {
        $error = true;
        $messageStack->add('create_account', ENTRY_FIRST_NAME_ERROR);
    }

    $lastname = zen_db_prepare_input(zen_sanitize_string($_POST['lastname']));
    if (strlen($lastname) < ENTRY_LAST_NAME_MIN_LENGTH) {
        $error = true;
        $messageStack->add('create_account', ENTRY_LAST_NAME_ERROR);
    }

    if (ACCOUNT_DOB === 'true') {
        $dob = zen_db_prepare_input($_POST['dob']);
        if (ENTRY_DOB_MIN_LENGTH > 0 or !empty($_POST['dob'])) {
            // Support ISO-8601 style date
            if (preg_match('/^([0-9]{4})(|-|\/)([0-9]{2})\2([0-9]{2})$/', $dob)) {
                // Account for incorrect date format provided to strtotime such as swapping day and month instead of the expected yyyymmdd, yyyy-mm-dd, or yyyy/mm/dd format
                if (strtotime($dob) !== false) {
                    $_POST['dob'] = $dob = date(DATE_FORMAT, strtotime($dob));
                }
            }
            if (substr_count($dob, '/') > 2 || checkdate((int)substr(zen_date_raw($dob), 4, 2), (int)substr(zen_date_raw($dob), 6, 2), (int)substr(zen_date_raw($dob), 0, 4)) === false) {
                $error = true;
                $messageStack->add('create_account', ENTRY_DATE_OF_BIRTH_ERROR);
            }
        }
    }

    if (ACCOUNT_COMPANY === 'true') {
        $company = zen_db_prepare_input($_POST['company']);
        if ((int)ENTRY_COMPANY_MIN_LENGTH > 0 && strlen($company) < ENTRY_COMPANY_MIN_LENGTH) {
            $error = true;
            $messageStack->add('create_account', ENTRY_COMPANY_ERROR);
        }
    }

    $nick_error = false;
    $email_address = zen_db_prepare_input($_POST['email_address']);
    $email_address_confirm = zen_db_prepare_input($_POST['email_address_confirm']);
    if (strlen($email_address) < ENTRY_EMAIL_ADDRESS_MIN_LENGTH) {
        $error = true;
        $messageStack->add('create_account', ENTRY_EMAIL_ADDRESS_ERROR);
    } elseif (zen_validate_email($email_address) === false) {
        $error = true;
        $messageStack->add('create_account', ENTRY_EMAIL_ADDRESS_CHECK_ERROR);
    } else {
        $check_email_query = 
            "SELECT COUNT(*) as total
               FROM " . TABLE_CUSTOMERS . "
              WHERE customers_email_address = '" . zen_db_input($email_address) . "'";
        $zco_notifier->notify('NOTIFY_CREATE_ACCOUNT_LOOKUP_BY_EMAIL', $email_address, $check_email_query, $send_welcome_email);
        $check_email = $db->Execute($check_email_query);

        if ($check_email->fields['total'] != 0) {
            $error = true;
            $messageStack->add('create_account', ENTRY_EMAIL_ADDRESS_ERROR_EXISTS);
        } else {
            $zco_notifier->notify('NOTIFY_NICK_CHECK_FOR_EXISTING_EMAIL', $email_address, $nick_error, $nick);
            if ($nick_error === true) {
                $error = true;
            }
            if ($email_address !== $email_address_confirm) {
                $error = true;
                $messageStack->add('create_account', ENTRY_EMAIL_MISMATCH_ERROR, 'error');
            }
        }
    }
    
    $nick = (isset($_POST['nick'])) ? zen_db_prepare_input($_POST['nick']) : '';
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

    $telephone = zen_db_prepare_input($_POST['telephone']);
    if (strlen($telephone) < ENTRY_TELEPHONE_MIN_LENGTH) {
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
        $db_string_type = $_SESSION['opc']->getDbStringType();
        $sql_data_array = [
            [
                'fieldName' => 'customers_firstname', 
                'value' => $firstname, 
                'type' => $db_string_type
            ],
            [
                'fieldName' => 'customers_lastname', 
                'value' => $lastname, 
                'type' => $db_string_type
            ],
            [
                'fieldName' => 'customers_email_address', 
                'value' => $email_address, 
                'type' => $db_string_type
            ],
            [
                'fieldName' => 'customers_nick', 
                'value' => $nick, 
                'type' => $db_string_type
            ],
            [
                'fieldName' => 'customers_telephone', 
                'value' => $telephone, 
                'type' => $db_string_type
            ],
            [
                'fieldName' => 'customers_newsletter', 
                'value' => $newsletter, 
                'type' => 'integer'
            ],
            [
                'fieldName' => 'customers_email_format', 
                'value' => $email_format, 
                'type' => $db_string_type
            ],
            [
                'fieldName' => 'customers_default_address_id', 
                'value' => 0, 
                'type' => 'integer'
            ],
            [
                'fieldName' => 'customers_password', 
                'value' => zen_encrypt_password($password), 
                'type' => $db_string_type
            ],
            [
                'fieldName' => 'customers_authorization', 
                'value' => $customers_authorization, 
                'type' => 'integer'
            ],
        ];

        if ((CUSTOMERS_REFERRAL_STATUS === '2' && $customers_referral !== '')) {
            $sql_data_array[] = [
                'fieldName' => 'customers_referral', 
                'value' => $customers_referral, 
                'type' => $db_string_type
            ];
        }
        if (ACCOUNT_GENDER === 'true') {
            $sql_data_array[] = [
                'fieldName' => 'customers_gender', 
                'value' => $gender, 
                'type' => $db_string_type
            ];
        }
        if (ACCOUNT_DOB === 'true') {
            $sql_data_array[] = [
                'fieldName' => 'customers_dob', 
                'value' => empty($_POST['dob']) ? zen_db_prepare_input('0001-01-01 00:00:00') : zen_date_raw($_POST['dob']), 
                'type' => 'date'
            ];
        }
        $db->perform(TABLE_CUSTOMERS, $sql_data_array);

        $customers_id = $_SESSION['customer_id'] = $db->Insert_ID();

        $zco_notifier->notify('NOTIFY_MODULE_CREATE_ACCOUNT_ADDED_CUSTOMER_RECORD', array_merge(['customer_id' => $_SESSION['customer_id']], $sql_data_array));
        $zco_notifier->notify('NOTIFY_HEADER_REGISTER_ADDED_CUSTOMER_RECORD', array_merge(['customer_id' => $_SESSION['customer_id']], $sql_data_array));

        $db->Execute(
            "INSERT INTO " . TABLE_CUSTOMERS_INFO . "
                (customers_info_id, customers_info_number_of_logons, customers_info_date_account_created, customers_info_date_of_last_logon)
             VALUES 
                ($customers_id, 1, now(), now())"
        );

        // -----
        // Create a default, but not fully-populated, address-book record for this newly-registered
        // customer.
        //
        $sql_data_array = [
            [
                'fieldName' => 'customers_id',
                'value' => $customers_id,
                'type' => 'integer'
            ],
            [
                'fieldName' => 'entry_firstname',
                'value' => $firstname,
                'type' => $db_string_type
            ],
            [
                'fieldName' => 'entry_lastname',
                'value' => $lastname,
                'type' => $db_string_type
            ],
            [
                'fieldName' => 'entry_street_address',
                'value' => '',
                'type' => $db_string_type
            ],
            [
                'fieldName' => 'entry_postcode',
                'value' => '',
                'type' => $db_string_type
            ],
            [
                'fieldName' => 'entry_city',
                'value' => '',
                'type' => $db_string_type
            ],
            [
                'fieldName' => 'entry_country_id',
                'value' => (int)STORE_COUNTRY,
                'type' => 'integer'
            ],
            [
                'fieldName' => 'entry_zone_id',
                'value' => (int)STORE_ZONE,
                'type' => 'integer'
            ],
        ];
        if (ACCOUNT_GENDER === 'true') {
            $sql_data_array[] = [
                'fieldName' => 'entry_gender', 
                'value' => $gender, 
                'type' => $db_string_type
            ];
        }
        if (ACCOUNT_COMPANY === 'true') {
            $sql_data_array[] = [
                'fieldName' => 'entry_company', 
                'value' => $company, 
                'type' => $db_string_type
            ];
        }
        $db->perform(TABLE_ADDRESS_BOOK, $sql_data_array);
        $address_id = $db->Insert_ID();

        $zco_notifier->notify('NOTIFY_HEADER_REGISTER_ADDED_ADDRESS_BOOK_RECORD', array_merge(['address_id' => $address_id], $sql_data_array));

        $db->Execute(
            "UPDATE " . TABLE_CUSTOMERS . "
                SET customers_default_address_id = $address_id
              WHERE customers_id = $customers_id
              LIMIT 1"
        ); 

        // do any 3rd-party nick creation
        $nick_email = $email_address;
        $zco_notifier->notify('NOTIFY_NICK_CREATE_NEW', $nick, $password, $nick_email, $extra_welcome_text);

        if (SESSION_RECREATE === 'True') {
            zen_session_recreate();
        }

        $_SESSION['customers_email_address'] = $email_address;
        $_SESSION['customer_first_name'] = $firstname;
        $_SESSION['customer_last_name'] = $lastname;
        $_SESSION['customer_default_address_id'] = $address_id;
        $_SESSION['customers_authorization'] = $customers_authorization;
        $_SESSION['customer_country_id'] = (int)STORE_COUNTRY;
        $_SESSION['customer_zone_id'] = (int)STORE_ZONE;

        // restore cart contents
        $_SESSION['cart']->restore_contents();

        // hook notifier class
        $zco_notifier->notify('NOTIFY_LOGIN_SUCCESS_VIA_CREATE_ACCOUNT', $email_address, $extra_welcome_text, $send_welcome_email);

        if ($send_welcome_email) {
            // build the message content
            $name = $firstname . ' ' . $lastname;

            if (ACCOUNT_GENDER === 'true') {
                if ($gender === 'm') {
                    $email_text = sprintf(EMAIL_GREET_MR, $lastname);
                } else {
                    $email_text = sprintf(EMAIL_GREET_MS, $lastname);
                }
            } else {
                $email_text = sprintf(EMAIL_GREET_NONE, $firstname);
            }
            $html_msg['EMAIL_GREETING'] = str_replace('\n', '', $email_text);
            $html_msg['EMAIL_FIRST_NAME'] = $firstname;
            $html_msg['EMAIL_LAST_NAME']  = $lastname;

            // initial welcome
            $email_text .=  EMAIL_WELCOME . $extra_welcome_text;
            $html_msg['EMAIL_WELCOME'] = str_replace('\n', '', EMAIL_WELCOME . $extra_welcome_text);

            if (NEW_SIGNUP_DISCOUNT_COUPON !== '' && NEW_SIGNUP_DISCOUNT_COUPON !== '0') {
                $coupon_id = (int)NEW_SIGNUP_DISCOUNT_COUPON;
                if ($coupon_id < 1) {
                    trigger_error('Invalid integer value detected for \'NEW_SIGNUP_DISCOUNT_COUPON\' (' . NEW_SIGNUP_DISCOUNT_COUPON . ').  The coupon was not sent.', E_USER_WARNING);
                } else {
                    $coupon = $db->Execute(
                        "SELECT c.*, cd.coupon_description
                           FROM " . TABLE_COUPONS . " c
                                INNER JOIN " . TABLE_COUPONS_DESCRIPTION . " cd
                                    ON cd.coupon_id = c.coupon_id
                                   AND cd.language_id = " . (int)$_SESSION['languages_id'] . "
                          WHERE c.coupon_id = $coupon_id
                          LIMIT 1"
                    );
                    if ($coupon->EOF) {
                        trigger_error('Unknown coupon_id (' . NEW_SIGNUP_DISCOUNT_COUPON . ') during account creation.  The coupon was not sent.', E_USER_WARNING);
                    } else {
                        $db->Execute(
                            "INSERT INTO " . TABLE_COUPON_EMAIL_TRACK . " 
                                (coupon_id, customer_id_sent, sent_firstname, emailed_to, date_sent) 
                             VALUES 
                                (" . $coupon_id . ", '0', 'Admin', '" . $email_address . "', now() )"
                        );

                        $text_coupon_help = sprintf(TEXT_COUPON_HELP_DATE, zen_date_short($coupon->fields['coupon_start_date']), zen_date_short($coupon->fields['coupon_expire_date']));

                        // if on, add in Discount Coupon explanation
                        //        $email_text .= EMAIL_COUPON_INCENTIVE_HEADER .
                        $email_text .= 
                            PHP_EOL . 
                            EMAIL_COUPON_INCENTIVE_HEADER .
                            (!empty($coupon->fields['coupon_description']) ? $coupon->fields['coupon_description'] . PHP_EOL . PHP_EOL : '') . 
                            $text_coupon_help  . PHP_EOL . PHP_EOL .
                            strip_tags(sprintf(EMAIL_COUPON_REDEEM, ' ' . $coupon->fields['coupon_code'])) . 
                            EMAIL_SEPARATOR;

                        $html_msg['COUPON_TEXT_VOUCHER_IS'] = EMAIL_COUPON_INCENTIVE_HEADER ;
                        $html_msg['COUPON_DESCRIPTION']     = (!empty($coupon->fields['coupon_description']) ? '<strong>' . $coupon->fields['coupon_description'] . '</strong>' : '');
                        $html_msg['COUPON_TEXT_TO_REDEEM']  = str_replace("\n", '', sprintf(EMAIL_COUPON_REDEEM, ''));
                        $html_msg['COUPON_CODE']  = $coupon->fields['coupon_code'] . $text_coupon_help;
                    }
                }
            } //endif coupon

            if (NEW_SIGNUP_GIFT_VOUCHER_AMOUNT > 0) {
                $coupon_code = zen_create_coupon_code();
                $insert_query = $db->Execute(
                    "INSERT INTO " . TABLE_COUPONS . " 
                        (coupon_code, coupon_type, coupon_amount, date_created) 
                     VALUES ('" . $coupon_code . "', 'G', '" . NEW_SIGNUP_GIFT_VOUCHER_AMOUNT . "', now())"
                );
                $insert_id = $db->Insert_ID();
                $db->Execute("insert into " . TABLE_COUPON_EMAIL_TRACK . " (coupon_id, customer_id_sent, sent_firstname, emailed_to, date_sent) values ('" . $insert_id ."', '0', 'Admin', '" . $email_address . "', now() )");

                // if on, add in GV explanation
                $email_text .= 
                    PHP_EOL . PHP_EOL . 
                    sprintf(EMAIL_GV_INCENTIVE_HEADER, $currencies->format(NEW_SIGNUP_GIFT_VOUCHER_AMOUNT)) . 
                    sprintf(EMAIL_GV_REDEEM, $coupon_code) .
                    EMAIL_GV_LINK . zen_href_link(FILENAME_GV_REDEEM, 'gv_no=' . $coupon_code, 'NONSSL', false) . PHP_EOL . PHP_EOL .
                    EMAIL_GV_LINK_OTHER . 
                    EMAIL_SEPARATOR;
                $html_msg['GV_WORTH'] = str_replace('\n', '', sprintf(EMAIL_GV_INCENTIVE_HEADER, $currencies->format(NEW_SIGNUP_GIFT_VOUCHER_AMOUNT)) );
                $html_msg['GV_REDEEM'] = str_replace('\n', '', str_replace('\n\n','<br />',sprintf(EMAIL_GV_REDEEM, '<strong>' . $coupon_code . '</strong>')));
                $html_msg['GV_CODE_NUM'] = $coupon_code;
                $html_msg['GV_CODE_URL'] = str_replace('\n', '', EMAIL_GV_LINK . '<a href="' . zen_href_link(FILENAME_GV_REDEEM, 'gv_no=' . $coupon_code, 'NONSSL', false) . '">' . TEXT_GV_NAME . ': ' . $coupon_code . '</a>');
                $html_msg['GV_LINK_OTHER'] = EMAIL_GV_LINK_OTHER;
            } // endif voucher

            // add in regular email welcome text
            $email_text .= "\n\n" . EMAIL_TEXT . EMAIL_CONTACT . EMAIL_GV_CLOSURE;

            $html_msg['EMAIL_MESSAGE_HTML']  = str_replace('\n', '', EMAIL_TEXT);
            $html_msg['EMAIL_CONTACT_OWNER'] = str_replace('\n', '', EMAIL_CONTACT);
            $html_msg['EMAIL_CLOSURE']       = nl2br(EMAIL_GV_CLOSURE);

            // include create-account-specific disclaimer
            $email_text .= "\n\n" . sprintf(EMAIL_DISCLAIMER_NEW_CUSTOMER, STORE_OWNER_EMAIL_ADDRESS). "\n\n";
            $html_msg['EMAIL_DISCLAIMER'] = sprintf(EMAIL_DISCLAIMER_NEW_CUSTOMER, '<a href="mailto:' . STORE_OWNER_EMAIL_ADDRESS . '">'. STORE_OWNER_EMAIL_ADDRESS .' </a>');

            // send welcome email
            if (trim(EMAIL_SUBJECT) != 'n/a') {
                zen_mail($name, $email_address, EMAIL_SUBJECT, $email_text, STORE_NAME, EMAIL_FROM, $html_msg, 'welcome');
            }

            // send additional emails
            if (SEND_EXTRA_CREATE_ACCOUNT_EMAILS_TO_STATUS === '1' && SEND_EXTRA_CREATE_ACCOUNT_EMAILS_TO !== '') {
                $extra_info = email_collect_extra_info($name, $email_address, $name, $email_address, $telephone, $fax);
                $html_msg['EXTRA_INFO'] = $extra_info['HTML'];
                if (trim(SEND_EXTRA_CREATE_ACCOUNT_EMAILS_TO_SUBJECT) !== 'n/a') {
                    zen_mail('', SEND_EXTRA_CREATE_ACCOUNT_EMAILS_TO, SEND_EXTRA_CREATE_ACCOUNT_EMAILS_TO_SUBJECT . ' ' . EMAIL_SUBJECT, $email_text . $extra_info['TEXT'], STORE_NAME, EMAIL_FROM, $html_msg, 'welcome_extra');
                }
            } //endif send extra emails
        }
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
