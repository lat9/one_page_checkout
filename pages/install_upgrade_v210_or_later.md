# Installing or Upgrading _OPC_ v2.1.0 (and later)

v2.3.3 (and later) of ***One-Page Checkout*** supports Zen Cart versions **zc155b** through **zc157**.  For zc157, there are no core-file changes required, so these additional installation steps don't apply!

-------

v2.1.0 (and later) of **_One-Page Checkout_** supports Zen Cart versions **zc155b-f** and **zc156+**, using zc156**b** as its change-basis (unless otherwise noted).   In that transition, the majority of the core-file changes required by _OPC_ are now _built into_ the base Zen Cart file-set and OPC's distribution has changed **_significantly_**.

Use these instructions when you **upgrade** an existing _OPC_ installation from _v2.0.x_ to the currently-released version, or if you are performing an **initial install** of _OPC_.  The goal of these instructions is to lay a path so that you have a smooth transition when you upgrade your store to Zen Cart 1.5.6b or later.

**Notes**: 


1. If you are upgrading an existing _OPC_ installation from a version _prior to v2.0.0_, you'll need to uninstall that previous version and perform a fresh installation of _OPC_ v2.1.0.  Too many changes are now present in the Zen Cart core to properly address any update!
2. There are required edits to the `shipping_estimator` to enable its integration with _OPC_.  See [this](https://github.com/lat9/one_page_checkout/issues/193) GitHub issue for version-specific changes required!


Choose the instructions, below, based on the action to be performed for your _currently-installed_ version of Zen Cart. 

## Initial Installation

### Zen Cart 1.5.6b and later

Perform the following actions on your store's template-override files, then follow the installation instructions provided in the _OPC_ distribution's readme for the _OPC_-specific files.

#### Edit Files

Edit your store's active template's `/includes/templates/YOUR_TEMPLATE/common/tpl_header.php` _using a text-editor_ (like NotePad++), changing all occurrences of

```php
if ($_SESSION['customer_id']) {
```

to

```php
if (zen_is_logged_in() && !zen_in_guest_checkout()) {
```

### Zen Cart 1.5.6, 1.5.6a

Perform the following actions on your store's core-/template-override files, then follow the installation instructions provided in the _OPC_ distribution's readme for the _OPC_-specific files.

#### Copy Files

Copy the files listed below from the _OPC_ distribution's `/zc156b` sub-directory to your store's file-system, bringing these files up to their Zen Cart 1.5.6b version:

1. `/includes/modules/pages/login/header_php.php`
2. `/includes/modules/order_total/ot_coupon.php`

#### Edit Files

##### 1. `/includes/templates/YOUR_TEMPLATE/common/tpl_header.php`

Edit your store's active template's `/includes/templates/YOUR_TEMPLATE/common/tpl_header.php` _using a text-editor_ (like NotePad++), changing all occurrences of

```php
if ($_SESSION['customer_id']) {
```

to

```php
if (zen_is_logged_in() && !zen_in_guest_checkout()) {
```

##### 2. `/includes/modules/payment/paypalwpp.php`

If your store uses the **PayPal Express Checkout** (`paypalwpp`) payment method, you'll need to add a notification to allow _OPC_'s temporary addresses to be used.

In the module's `ec_step2_finish` function, find this section:

```php
    // see if the user is logged in
    if (!empty($_SESSION['customer_first_name']) && !empty($_SESSION['customer_id']) && $_SESSION['customer_id'] > 0) {
      // They're logged in, so forward them straight to checkout stages, depending on address needs etc
      $order->customer['id'] = $_SESSION['customer_id'];

      // set the session value for express checkout temp
      $_SESSION['paypal_ec_temp'] = false;

      // if no address required for shipping, leave shipping portion alone
      if (strtoupper($_SESSION['paypal_ec_payer_info']['ship_address_status']) != 'NONE' && $_SESSION['paypal_ec_payer_info']['ship_street_1'] != '') {
```

and add the notification required for OPC's proper operation.  Note that this was corrected for #221, adding single-quotes so that there are _two_ parameters for the call to `$this->zcLog`:

```php

    // see if the user is logged in
    if (!empty($_SESSION['customer_first_name']) && !empty($_SESSION['customer_id']) && $_SESSION['customer_id'] > 0) {
      // They're logged in, so forward them straight to checkout stages, depending on address needs etc
      $order->customer['id'] = $_SESSION['customer_id'];

      // set the session value for express checkout temp
      $_SESSION['paypal_ec_temp'] = false;
      
      // -----
      // Allow an observer to override the default address-creation processing.
      //
      $bypass_address_creation = false;
      $this->notify('NOTIFY_PAYPALEXPRESS_BYPASS_ADDRESS_CREATION', $paypal_ec_payer_info, $bypass_address_creation);
      if ($bypass_address_creation) {
          $this->zcLog('ec_step2_finish - 2a', 'address-creation bypassed based on observer setting.');
      }

      // if no address required for shipping (or overridden by above), leave shipping portion alone
      if (!$bypass_address_creation && strtoupper($_SESSION['paypal_ec_payer_info']['ship_address_status']) != 'NONE' && $_SESSION['paypal_ec_payer_info']['ship_street_1'] != '') {
```

### Zen Cart 1.5.5b-f

Perform the following actions on your store's core-/template-override files, then follow the installation instructions provided in the _OPC_ distribution's readme for the _OPC_-specific files.

#### Copy Files

Copy the files listed below from the _OPC_ distribution's `/zc156b` sub-directory to your store's file-system, bringing these files up to their Zen Cart 1.5.6b version:

1. `/includes/classes/message_stack.php`
1. `/includes/classes/observers/auto.downloads_via_aws.php`
1. `/includes/classes/observers/auto.downloads_via_redirect.php`
1. `/includes/classes/observers/auto.downloads_via_streaming.php`
1. `/includes/classes/observers/auto.downloads_via_url.php`
2. `/includes/functions/functions_taxes.php`
1. `/includes/modules/pages/download/header_php.php`
2. `/includes/modules/pages/login/header_php.php`
1. `/includes/modules/downloads.php`
1. `/includes/templates/template_default/templates/tpl_modules_downloads.php`
1. `/includes/templates/responsive_classic/templates/tpl_ajax_checkout_confirmation_default.php`
1. `/includes/templates/template_default/templates/tpl_ajax_checkout_confirmation_default.php`

Copy the files listed below from the _OPC_ distribution's `/zc155f` sub-directory to your store's file-system, bringing these files up to a _modified_ Zen Cart 1.5.5f version:

1. `/includes/classes/order.php` (one marked change-section)
2. `/includes/modules/order_total/ot_coupon.php` (one marked change-section)


#### Edit Files

##### 1. `/includes/templates/YOUR_TEMPLATE/common/tpl_header.php`

Edit your store's active template's `/includes/templates/YOUR_TEMPLATE/common/tpl_header.php` _using a text-editor_ (like NotePad++), changing all occurrences of

```php
if ($_SESSION['customer_id']) {
```

to

```php
if (zen_is_logged_in() && !zen_in_guest_checkout()) {
```

##### 2. `/includes/modules/payment/paypalwpp.php`

If your store uses the **PayPal Express Checkout** (`paypalwpp`) payment method, you'll need to add a notification to allow _OPC_'s temporary addresses to be used.

In the module's `ec_step2_finish` function, find this section:

```php
    // see if the user is logged in
    if (!empty($_SESSION['customer_first_name']) && !empty($_SESSION['customer_id']) && $_SESSION['customer_id'] > 0) {
      // They're logged in, so forward them straight to checkout stages, depending on address needs etc
      $order->customer['id'] = $_SESSION['customer_id'];

      // set the session value for express checkout temp
      $_SESSION['paypal_ec_temp'] = false;

      // if no address required for shipping, leave shipping portion alone
      if (strtoupper($_SESSION['paypal_ec_payer_info']['ship_address_status']) != 'NONE' && $_SESSION['paypal_ec_payer_info']['ship_street_1'] != '') {
```

and add the notification required for OPC's proper operation.  Note that this was corrected for #221, adding single-quotes so that there are _two_ parameters for the call to `$this->zcLog`:

```php

    // see if the user is logged in
    if (!empty($_SESSION['customer_first_name']) && !empty($_SESSION['customer_id']) && $_SESSION['customer_id'] > 0) {
      // They're logged in, so forward them straight to checkout stages, depending on address needs etc
      $order->customer['id'] = $_SESSION['customer_id'];

      // set the session value for express checkout temp
      $_SESSION['paypal_ec_temp'] = false;
      
      // -----
      // Allow an observer to override the default address-creation processing.
      //
      $bypass_address_creation = false;
      $this->notify('NOTIFY_PAYPALEXPRESS_BYPASS_ADDRESS_CREATION', $paypal_ec_payer_info, $bypass_address_creation);
      if ($bypass_address_creation) {
          $this->zcLog('ec_step2_finish - 2a', 'address-creation bypassed based on observer setting.');
      }

      // if no address required for shipping (or overridden by above), leave shipping portion alone
      if (!$bypass_address_creation && strtoupper($_SESSION['paypal_ec_payer_info']['ship_address_status']) != 'NONE' && $_SESSION['paypal_ec_payer_info']['ship_street_1'] != '') {
```

### Zen Cart 1.5.4, 1.5.5, 1.5.5a

**Not supported.**

## Upgrading

### Perform Required Edits

If your store uses the **PayPal Express Checkout** (`paypalwpp.php`) payment method, there are edits needed to that module to allow _OPC_'s _temporary_ addresses to be properly transmitted to PayPal and/or recorded in the order.

#### For All Zen Cart Versions _Prior to_ 1.5.6b

Add a notification to allow _OPC_'s temporary addresses to be recorded in the order.  Edit `/includes/modules/payment/paypalwpp.php`; in the module's `ec_step2_finish` function, find this section:

```php
    // see if the user is logged in
    if (!empty($_SESSION['customer_first_name']) && !empty($_SESSION['customer_id']) && $_SESSION['customer_id'] > 0) {
      // They're logged in, so forward them straight to checkout stages, depending on address needs etc
      $order->customer['id'] = $_SESSION['customer_id'];

      // set the session value for express checkout temp
      $_SESSION['paypal_ec_temp'] = false;

      // if no address required for shipping, leave shipping portion alone
      if (strtoupper($_SESSION['paypal_ec_payer_info']['ship_address_status']) != 'NONE' && $_SESSION['paypal_ec_payer_info']['ship_street_1'] != '') {
```

and add the notification required for OPC's proper operation.  Note that this was corrected for #221, adding single-quotes so that there are _two_ parameters for the call to `$this->zcLog`:

```php

    // see if the user is logged in
    if (!empty($_SESSION['customer_first_name']) && !empty($_SESSION['customer_id']) && $_SESSION['customer_id'] > 0) {
      // They're logged in, so forward them straight to checkout stages, depending on address needs etc
      $order->customer['id'] = $_SESSION['customer_id'];

      // set the session value for express checkout temp
      $_SESSION['paypal_ec_temp'] = false;
      
      // -----
      // Allow an observer to override the default address-creation processing.
      //
      $bypass_address_creation = false;
      $this->notify('NOTIFY_PAYPALEXPRESS_BYPASS_ADDRESS_CREATION', $paypal_ec_payer_info, $bypass_address_creation);
      if ($bypass_address_creation) {
          $this->zcLog('ec_step2_finish - 2a', 'address-creation bypassed based on observer setting.');
      }

      // if no address required for shipping (or overridden by above), leave shipping portion alone
      if (!$bypass_address_creation && strtoupper($_SESSION['paypal_ec_payer_info']['ship_address_status']) != 'NONE' && $_SESSION['paypal_ec_payer_info']['ship_street_1'] != '') {
```

#### Zen Cart 1.5.4

For zc154, an additional edit is required to add a notification (already present in zc155+) to allow _OPC_ to send any _temporary_ shipping address to PayPal.  Edit `/includes/modules/payment/paypalwpp.php`; in the module's `ec_step1` function, find:

```php

    $this->zcLog('ec_step1 - 2 -submit', print_r(array_merge($options, array('RETURNURL' => $return_url, 'CANCELURL' => $cancel_url)), true));

    /**
     * Ask PayPal for the token with which to initiate communications
     */
    $response = $doPayPal->SetExpressCheckout($return_url, $cancel_url, $options);
```

and add the required notification:

```php

    $this->zcLog('ec_step1 - 2 -submit', print_r(array_merge($options, array('RETURNURL' => $return_url, 'CANCELURL' => $cancel_url)), true));

    $this->notify('NOTIFY_PAYMENT_PAYPALEC_BEFORE_SETEC', array(), $options, $order, $order_totals);

    /**
     * Ask PayPal for the token with which to initiate communications
     */
    $response = $doPayPal->SetExpressCheckout($return_url, $cancel_url, $options);
```


### Zen Cart 1.5.6, 1.5.6a, 1.5.6b

Perform the following actions on your store's core-/template-override files, then follow the upgrade instructions provided in the _OPC_ distribution's readme for the _OPC_-specific files.

#### Remove Files

The files listed below were previously distributed as _template-overrides_ and are now used from their "base" Zen Cart directories.  If you have not modified theses files from their previously-distributed version, they can be safely removed; otherwise, you'll need to perform a file-merge with the non-template-override versions provided in the _OPC_ distribution's `/zc156b` sub-directory:

1. `/includes/modules/YOUR_TEMPLATE/downloads.php`
1. `/includes/templates/YOUR_TEMPLATE/templates/tpl_modules_downloads.php`

#### Update Template-Overrides

The file previously distributed as `/includes/templates/YOUR_TEMPLATE/templates/tpl_ajax_checkout_confirmation_default.php` should be compared (and possibly merged) with either `/zc156b/includes/responsive_classic/templates/tpl_ajax_checkout_confirmation_default.php` (if you are using a "clone" of that template) or with the like-named file in the distribution's `template_default` sub-directory, otherwise.

#### Copy Files

For Zen Cart versions 1.5.6 and 1.5.6a, copy the files listed below from the _OPC_ distribution's `/zc156b` sub-directory to your store's file-system, bringing these files up to their Zen Cart 1.5.6b version:

1. `/includes/classes/message_stack.php`
1. `/includes/classes/observers/auto.downloads_via_aws.php`
1. `/includes/classes/observers/auto.downloads_via_redirect.php`
1. `/includes/classes/observers/auto.downloads_via_streaming.php`
1. `/includes/classes/observers/auto.downloads_via_url.php`
2. `/includes/modules/order_total/ot_coupon.php`
1. `/includes/modules/pages/download/header_php.php`
1. `/includes/modules/pages/login/header_php.php`
1. `/includes/modules/downloads.php`
1. `/includes/templates/template_default/templates/tpl_modules_downloads.php`
1. `/includes/templates/responsive_classic/templates/tpl_ajax_checkout_confirmation_default.php`
1. `/includes/templates/template_default/templates/tpl_ajax_checkout_confirmation_default.php`

### Zen Cart 1.5.4, 1.5.5, 1.5.5a-f

Perform the following actions on your store's core-/template-override files, then follow the upgrade instructions provided in the _OPC_ distribution's readme for the _OPC_-specific files.

#### Remove Files

The files listed below were previously distributed as _template-overrides_ and are now used from their "base" Zen Cart directories.  If you have not modified theses files from their previously-distributed version, they can be safely removed; otherwise, you'll need to perform a file-merge with the non-template-override versions provided in the _OPC_ distribution's `/zc156b` sub-directory:

1. `/includes/modules/YOUR_TEMPLATE/downloads.php`
1. `/includes/templates/YOUR_TEMPLATE/templates/tpl_modules_downloads.php`

#### Update Template-Overrides

The file previously distributed as `/includes/templates/YOUR_TEMPLATE/templates/tpl_ajax_checkout_confirmation_default.php` should be compared (and possibly merged) with either `/zc156b/includes/responsive_classic/templates/tpl_ajax_checkout_confirmation_default.php` (if you are using a "clone" of that template) or with the like-named file in the distribution's `template_default` sub-directory, otherwise.

#### Copy Files

Copy the files listed below from the _OPC_ distribution's `/zc156b` sub-directory to your store's file-system, bringing these files up to their Zen Cart 1.5.6b version:

1. `/includes/classes/message_stack.php`
1. `/includes/classes/observers/auto.downloads_via_aws.php`
1. `/includes/classes/observers/auto.downloads_via_redirect.php`
1. `/includes/classes/observers/auto.downloads_via_streaming.php`
1. `/includes/classes/observers/auto.downloads_via_url.php`
2. `/includes/functions/functions_taxes.php`
1. `/includes/modules/pages/download/header_php.php`
1. `/includes/modules/pages/login/header_php.php`
1. `/includes/modules/downloads.php`
1. `/includes/templates/template_default/templates/tpl_modules_downloads.php`
1. `/includes/templates/responsive_classic/templates/tpl_ajax_checkout_confirmation_default.php`
1. `/includes/templates/template_default/templates/tpl_ajax_checkout_confirmation_default.php`

Copy the files listed below from the _OPC_ distribution's `/zc155f` sub-directory to your store's file-system, bringing these files up to a _modified_ Zen Cart 1.5.5f version:

1. `/includes/classes/order.php` (one marked change-section)
2. `/includes/modules/order_total/ot_coupon.php` (one marked change-section)
