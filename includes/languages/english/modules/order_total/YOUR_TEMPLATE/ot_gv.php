<?php
/**
 * @copyright Copyright 2003-2020 Zen Cart Development Team
 * @copyright Portions Copyright 2003 osCommerce
 * @license http://www.zen-cart.com/license/2_0.txt GNU Public License V2.0
 * @version $Id: Scott C Wilson 2019 Jul 20 Modified in v1.5.7 $
 */

  define('MODULE_ORDER_TOTAL_GV_TITLE', TEXT_GV_NAMES);
  define('MODULE_ORDER_TOTAL_GV_HEADER', TEXT_GV_NAMES . '/Discount Coupons');
  define('MODULE_ORDER_TOTAL_GV_DESCRIPTION', TEXT_GV_NAMES);
  define('MODULE_ORDER_TOTAL_GV_USER_PROMPT', 'Apply Amount: ');
  define('MODULE_ORDER_TOTAL_GV_TEXT_ENTER_CODE', TEXT_GV_REDEEM);
  define('TEXT_INVALID_REDEEM_AMOUNT', 'It appears that the amount you have tried to apply and your Gift Certificate balance do not match. Please try again.');
  define('MODULE_ORDER_TOTAL_GV_USER_BALANCE', 'Available balance: ');
  
//-bof-one_page_checkout-lat9  *** 1 of 1 ***
if (defined('CHECKOUT_ONE_ENABLED') && CHECKOUT_ONE_ENABLED == 'true') {
  define('MODULE_ORDER_TOTAL_GV_REDEEM_INSTRUCTIONS', '<p>To use Gift Certificate funds already in your account, type the amount you wish to apply in the box that says \'Apply Amount\'. You will need to choose a payment method,  then click the submit button at the bottom of the page to apply the funds to your order.</p><p>If you are redeeming a <em>new</em> Gift Certificate you should type the number into the box next to &quot;Discount Code&quot;. The amount redeemed will be added to your account when you click the button to the right.</p>');
} else {
  define('MODULE_ORDER_TOTAL_GV_REDEEM_INSTRUCTIONS', '<p>To use Gift Certificate funds already in your account, type the amount you wish to apply in the box that says \'Apply Amount\'. You will need to choose a payment method,  then click the continue button to apply the funds to your shopping cart.</p><p>If you are redeeming a <em>new</em> Gift Certificate you should type the number into the box next to Redemption Code. The amount redeemed will be added to your account when you click the continue button.</p>');
}
//-eof-one_page_checkout-lat9  *** 1 of 1 ***
  define('MODULE_ORDER_TOTAL_GV_INCLUDE_ERROR', ' Setting Include tax = true, should only happen when recalculate = None');
