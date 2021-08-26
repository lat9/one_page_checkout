<?php
/**
 * @package languageDefines
 * @copyright Copyright 2003-2007 Zen Cart Development Team
 * @copyright Portions Copyright 2003 osCommerce
 * @license http://www.zen-cart.com/license/2_0.txt GNU Public License V2.0
 * @version $Id: ot_coupon.php 6099 2007-04-01 10:22:42Z wilt $
 */

  define('MODULE_ORDER_TOTAL_COUPON_TITLE', 'Discount Coupon');
  define('MODULE_ORDER_TOTAL_COUPON_HEADER', TEXT_GV_NAMES . '/Discount Coupon');
  define('MODULE_ORDER_TOTAL_COUPON_DESCRIPTION', 'Discount Coupon');
  define('MODULE_ORDER_TOTAL_COUPON_TEXT_ENTER_CODE', TEXT_GV_REDEEM);
  define('SHIPPING_NOT_INCLUDED', ' [Shipping not included]');
  define('TAX_NOT_INCLUDED', ' [Tax not included]');
  define('IMAGE_REDEEM_VOUCHER', 'Redeem Voucher');
  
//-bof-one_page_checkout-lat9  *** 1 of 1 ***
if (defined ('CHECKOUT_ONE_ENABLED') && CHECKOUT_ONE_ENABLED == 'true') {
  define('MODULE_ORDER_TOTAL_COUPON_REDEEM_INSTRUCTIONS', '<p>Please type your coupon code into the discount code box below. Your coupon will be applied to the total and reflected in your order\'s display after you click the button to the right or submit your order. Please note: you may only use one coupon per order.</p>');
} else {
  define('MODULE_ORDER_TOTAL_COUPON_REDEEM_INSTRUCTIONS', '<p>Please type your coupon code into the discount code box below. Your coupon will be applied to the total and reflected in your cart after you click continue. Please note: you may only use one coupon per order.</p>');
}
//-eof-one_page_checkout-lat9  *** 1 of 1 ***

  define('MODULE_ORDER_TOTAL_COUPON_TEXT_CURRENT_CODE', 'Your Current Redemption Code: ');
  define('MODULE_ORDER_TOTAL_COUPON_REMOVE_INSTRUCTIONS', '<p>To remove a Discount Coupon from this order type REMOVE and press Enter or Return</p>');
  define('TEXT_REMOVE_REDEEM_COUPON', 'Discount Coupon Removed by Request!');
  define('MODULE_ORDER_TOTAL_COUPON_INCLUDE_ERROR', ' Setting Include tax = true, should only happen when recalculate = None');
?>