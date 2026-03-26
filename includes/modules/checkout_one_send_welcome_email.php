<?php
// -----
// Part of the One-Page Checkout plugin, provided under GPL 2.0 license by lat9 (cindy@vinosdefrutastropicales.com).
// Copyright (C) 2017-2026, Vinos de Frutas Tropicales.  All rights reserved.
//
// Last updated: OPC v2.6.0
//
if (defined('FILENAME_CREATE_ACCOUNT_SEND_EMAIL')) {
    require DIR_WS_MODULES . zen_get_module_directory(FILENAME_CREATE_ACCOUNT_SEND_EMAIL);
    Customer::setWelcomeEmailSent((int)$result['customers_id']);
} elseif ($send_welcome_email) {
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
    $html_msg['EMAIL_LAST_NAME'] = $lastname;

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
        $html_msg['GV_REDEEM'] = str_replace('\n', '', str_replace('\n\n', '<br>',sprintf(EMAIL_GV_REDEEM, '<strong>' . $coupon_code . '</strong>')));
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
        $extra_info = email_collect_extra_info($name, $email_address, $name, $email_address, $telephone, $fax ?? '');
        $html_msg['EXTRA_INFO'] = $extra_info['HTML'];
        if (trim(SEND_EXTRA_CREATE_ACCOUNT_EMAILS_TO_SUBJECT) !== 'n/a') {
            zen_mail('', SEND_EXTRA_CREATE_ACCOUNT_EMAILS_TO, SEND_EXTRA_CREATE_ACCOUNT_EMAILS_TO_SUBJECT . ' ' . EMAIL_SUBJECT, $email_text . $extra_info['TEXT'], STORE_NAME, EMAIL_FROM, $html_msg, 'welcome_extra');
        }
    } //endif send extra emails
}