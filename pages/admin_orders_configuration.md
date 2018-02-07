# Admin: Identifying Orders Placed by Guests #

For full integration with a pre-Zen Cart 1.5.6 store's admin, there are two (2) edits that you'll need to make to the store's `/YOUR_ADMIN/orders.php` if you want to have indicators on the orders' listing to identify orders placed by guests.  That module is very "volatile" (i.e. tends to change with each Zen Cart version), and the *OPC* plugin *will not distribute* version(s) of that module for that reason.

***Note***: The additional code-fragments are already included in the Zen Cart 1.5.6 base!

Find:
```php
           <tr>
             <td class="smallText"><?php echo TEXT_LEGEND . ' ' . zen_image(DIR_WS_IMAGES . 'icon_status_red.gif', TEXT_BILLING_SHIPPING_MISMATCH, 10, 10) . ' ' . TEXT_BILLING_SHIPPING_MISMATCH; ?>
           </td>
```

... and make the changes below:

```php
<?php
//-bof-one_page_checkout-lat9-Additional notifiers to enable additional order status-icons.  *** 1 of 2 ***
    $extra_legends = '';
    $zco_notifier->notify('NOTIFY_ADMIN_ORDERS_MENU_LEGEND', array(), $extra_legends);
?>
          <tr>
            <td class="smallText"><?php echo TEXT_LEGEND . ' ' . zen_image(DIR_WS_IMAGES . 'icon_status_red.gif', TEXT_BILLING_SHIPPING_MISMATCH, 10, 10) . ' ' . TEXT_BILLING_SHIPPING_MISMATCH . $extra_legends; ?>
          </tr>
<?php
//-eof-one_page_checkout-lat9-Additional notifiers to enable additional order status-icons.  *** 1 of 2 ***
?>
```
Next, find:
```php

      $show_difference = '';
      if ((strtoupper($orders->fields['delivery_name']) != strtoupper($orders->fields['billing_name']) and trim($orders->fields['delivery_name']) != '')) {
        $show_difference = zen_image(DIR_WS_IMAGES . 'icon_status_red.gif', TEXT_BILLING_SHIPPING_MISMATCH, 10, 10) . '&nbsp;';
      }
      if ((strtoupper($orders->fields['delivery_street_address']) != strtoupper($orders->fields['billing_street_address']) and trim($orders->fields['delivery_street_address']) != '')) {
        $show_difference = zen_image(DIR_WS_IMAGES . 'icon_status_red.gif', TEXT_BILLING_SHIPPING_MISMATCH, 10, 10) . '&nbsp;';
      }
```
... and make the change below:
```php

      $show_difference = '';
      if ((strtoupper($orders->fields['delivery_name']) != strtoupper($orders->fields['billing_name']) and trim($orders->fields['delivery_name']) != '')) {
        $show_difference = zen_image(DIR_WS_IMAGES . 'icon_status_red.gif', TEXT_BILLING_SHIPPING_MISMATCH, 10, 10) . '&nbsp;';
      }
      if ((strtoupper($orders->fields['delivery_street_address']) != strtoupper($orders->fields['billing_street_address']) and trim($orders->fields['delivery_street_address']) != '')) {
        $show_difference = zen_image(DIR_WS_IMAGES . 'icon_status_red.gif', TEXT_BILLING_SHIPPING_MISMATCH, 10, 10) . '&nbsp;';
      }
      
//-bof-one_page_checkout-lat9-Additional "difference" icons added on a per-order basis.  *** 2 of 2 ***
      $extra_action_icons = '';
      $zco_notifier->notify('NOTIFY_ADMIN_ORDERS_SHOW_ORDER_DIFFERENCE', array(), $orders->fields, $show_difference, $extra_action_icons);
//-eof-one_page_checkout-lat9-Additional "difference" icons added on a per-order basis.  *** 2 of 2 ***
```
Those edits, adding two notifiers to the store's `/YOUR_ADMIN/orders.php` will enable the store to include icons that identify that an order was placed via guest-checkout.