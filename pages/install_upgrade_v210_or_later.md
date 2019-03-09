# Installing or Upgrading _OPC_ v2.1.0 (and later)

v2.1.0 (and later) of **_One-Page Checkout_** supports Zen Cart versions **zc155b-f** and **zc156+**, using zc156**b** as its change-basis (unless otherwise noted).   In that transition, the majority of the core-file changes required by _OPC_ are now _built into_ the base Zen Cart file-set and OPC's distribution has changed **_significantly_**.

Use these instructions when you **upgrade** an existing _OPC_ installation from _v2.0.x_ to the currently-released version, or if you are performing an **initial install** of _OPC_.  The goal of these instructions is to lay a path so that you have a smooth transition when you upgrade your store to Zen Cart 1.5.6b or later.

**Note**: If you are upgrading an existing _OPC_ installation from a version _prior to v2.0.0_, you'll need to uninstall that previous version and perform a fresh installation of _OPC_ v2.1.0.  Too many changes are now present in the Zen Cart core to properly address any update!

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

Edit your store's active template's `/includes/templates/YOUR_TEMPLATE/common/tpl_header.php` _using a text-editor_ (like NotePad++), changing all occurrences of

```php
if ($_SESSION['customer_id']) {
```

to

```php
if (zen_is_logged_in() && !zen_in_guest_checkout()) {
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

Edit your store's active template's `/includes/templates/YOUR_TEMPLATE/common/tpl_header.php` _using a text-editor_ (like NotePad++), changing all occurrences of

```php
if ($_SESSION['customer_id']) {
```

to

```php
if (zen_is_logged_in() && !zen_in_guest_checkout()) {
```

### Zen Cart 1.5.4, 1.5.5, 1.5.5a

**Not supported.**

## Upgrading

### Zen Cart 1.5.6, 1.5.6a, 1.5.6b

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
1. `/includes/modules/pages/download/header_php.php`
1. `/includes/modules/pages/login/header_php.php`
1. `/includes/modules/downloads.php`
1. `/includes/templates/template_default/templates/tpl_modules_downloads.php`
1. `/includes/templates/responsive_classic/templates/tpl_ajax_checkout_confirmation_default.php`
1. `/includes/templates/template_default/templates/tpl_ajax_checkout_confirmation_default.php`

Copy the files listed below from the _OPC_ distribution's `/zc155f` sub-directory to your store's file-system, bringing these files up to a _modified_ Zen Cart 1.5.5f version:

1. `/includes/classes/order.php` (one marked change-section)
2. `/includes/modules/order_total/ot_coupon.php` (one marked change-section)
