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

Copy the files listed below from the _OPC_ distribution to your store's file-system, bringing these files up to their Zen Cart 1.5.6b version:

1. `/zc156b/includes/modules/pages/login/header_php.php`
2. `/zc156b/includes/modules/order_total/ot_coupon.php`

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

Copy the files listed below from the _OPC_ distribution to your store's file-system, bringing these files up to their Zen Cart 1.5.6b version:

1. `/zc156b/includes/modules/pages/login/header_php.php`
1. `/zc156b/includes/classes/observers/auto.downloads_via_aws.php`
1. `/zc156b/includes/classes/observers/auto.downloads_via_redirect.php`
1. `/zc156b/includes/classes/observers/auto.downloads_via_streaming.php`
1. `/zc156b/includes/classes/observers/auto.downloads_via_url.php`
1. `/zc156b/includes/modules/pages/download/header_php.php`
1. `/zc156b/includes/modules/downloads.php`
1. `/zc156b/includes/templates/template_default/templates/tpl_modules_downloads.php`
1. `/zc156b/includes/templates/responsive_classic/templates/tpl_ajax_checkout_confirmation_default.php`
1. `/zc156b/includes/templates/template_default/templates/tpl_ajax_checkout_confirmation_default.php`

Copy the files listed below from the _OPC_ distribution to your store's file-system, bringing these files up to a _modified_ Zen Cart 1.5.5f version:

1. `/zc155f/includes/modules/order_total/ot_coupon.php` (one marked change-section)

### Edit Files

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

Copy the files listed below from the _OPC_ distribution to your store's file-system, bringing these files up to their Zen Cart 1.5.6b version:

1. `/zc156b/includes/modules/pages/login/header_php.php`
2. `/zc156b/includes/modules/order_total/ot_coupon.php`
1. `/zc156b/includes/classes/observers/auto.downloads_via_aws.php`
1. `/zc156b/includes/classes/observers/auto.downloads_via_redirect.php`
1. `/zc156b/includes/classes/observers/auto.downloads_via_streaming.php`
1. `/zc156b/includes/classes/observers/auto.downloads_via_url.php`
1. `/zc156b/includes/modules/pages/download/header_php.php`
1. `/zc156b/includes/modules/downloads.php`
1. `/zc156b/includes/templates/template_default/templates/tpl_modules_downloads.php`
1. `/zc156b/includes/templates/responsive_classic/templates/tpl_ajax_checkout_confirmation_default.php`
1. `/zc156b/includes/templates/template_default/templates/tpl_ajax_checkout_confirmation_default.php`

### Zen Cart 1.5.4, 1.5.5, 1.5.5a-f

Perform the following actions on your store's core-/template-override files, then follow the upgrade instructions provided in the _OPC_ distribution's readme for the _OPC_-specific files.

#### Remove Files

The files listed below were previously distributed as _template-overrides_ and are now used from their "base" Zen Cart directories.  If you have not modified theses files from their previously-distributed version, they can be safely removed; otherwise, you'll need to perform a file-merge with the non-template-override versions provided in the _OPC_ distribution's `/zc156b` sub-directory:

1. `/includes/modules/YOUR_TEMPLATE/downloads.php`
1. `/includes/templates/YOUR_TEMPLATE/templates/tpl_modules_downloads.php`

#### Update Template-Overrides

The file previously distributed as `/includes/templates/YOUR_TEMPLATE/templates/tpl_ajax_checkout_confirmation_default.php` should be compared (and possibly merged) with either `/zc156b/includes/responsive_classic/templates/tpl_ajax_checkout_confirmation_default.php` (if you are using a "clone" of that template) or with the like-named file in the distribution's `template_default` sub-directory, otherwise.

#### Copy Files

Copy the files listed below from the _OPC_ distribution to your store's file-system, bringing these files up to their Zen Cart 1.5.6b version:

1. `/zc156b/includes/modules/pages/login/header_php.php`
1. `/zc156b/includes/classes/observers/auto.downloads_via_aws.php`
1. `/zc156b/includes/classes/observers/auto.downloads_via_redirect.php`
1. `/zc156b/includes/classes/observers/auto.downloads_via_streaming.php`
1. `/zc156b/includes/classes/observers/auto.downloads_via_url.php`
1. `/zc156b/includes/modules/pages/download/header_php.php`
1. `/zc156b/includes/modules/downloads.php`
1. `/zc156b/includes/templates/template_default/templates/tpl_modules_downloads.php`
1. `/zc156b/includes/templates/responsive_classic/templates/tpl_ajax_checkout_confirmation_default.php`
1. `/zc156b/includes/templates/template_default/templates/tpl_ajax_checkout_confirmation_default.php`

Copy the files listed below from the _OPC_ distribution to your store's file-system, bringing these files up to a _modified_ Zen Cart 1.5.5f version:

1. `/zc155f/includes/modules/order_total/ot_coupon.php` (one marked change-section)


## Core/Template Files Distributed in Previous _OPC_ Releases

This section identifies the core- and template-override files (and their locations in your store's file-system) that were distributed by previous versions of _OPC_.  In all cases, the sub-directory named `YOUR_TEMPLATE` refers to your store's active, in-use template.

Each number in the **Action** column refers to one of the **Upgrade Actions/Notes**, see the section after this table for additional information.

| File Name |  Action |
| ---- |  :----: |
| `/ajax.php` | 6 |
| `/includes/classes/message_stack.php` | 1 | 
| `/includes/classes/order.php` | 2 |
| `/includes/classes/payment.php` | 7 |
| `/includes/classes/observers/auto.downloads_via_aws.php` | 1 |
| `/includes/classes/observers/auto.downloads_via_redirect.php` | 1 |
| `/includes/classes/observers/auto.downloads_via_streaming.php` | 1 |
| `/includes/classes/observers/auto.downloads_via_url.php` | 1 |
| `/includes/modules/order_total/ot_coupon.php` | 1 |
| `/includes/modules/pages/download/header_php.php` | 1 |
| `/includes/modules/pages/login/header_php.php` | 1, 7 |
| `/includes/modules/YOUR_TEMPLATE/downloads.php` | 1, **4** |
| `/includes/templates/template_default/templates/tpl_modules_downloads.php` | 3 |
| `/includes/templates/YOUR_TEMPLATE/jscript/jscript_framework.php` | 3 |
| `/includes/templates/YOUR_TEMPLATE/templates/tpl_ajax_checkout_confirmation_default.php` | 3 |


### Upgrade Actions/Notes


1. File is distributed in the `zc155` sub-directory, for use on initial installs and/or upgrades when a store's base Zen Cart version is between 1.5.5b and 1.5.5f.
2. File is distributed in the `zc156` sub-directory, for use on initial installs and/or upgrades when a store's base Zen Cart version is 1.5.6 or 1.5.6a.
2. Too many changes to the file in the `zc155` to `zc156` transition.  Distributed in the `zc155` directory for Zen Cart versions 1.5.5b-f and `zc156` for Zen Cart versions 1.5.6 and 1.5.6a.
3. The file has been updated in the _OPC_ v2.1.0 distribution.  Be sure to compare to the version currently used for your store!
4. The file is no longer distributed by _OPC_; use the file provided by your Zen Cart version's `/includes/templates/template_default/` sub-directory.
5. The file was previously distributed _only for_ Zen Cart 1.5.4, 1.5.5 and 1.5.5a; use the file provided by your current Zen Cart version.
5. The file was previously distributed _only for_ Zen Cart 1.5.5 and 1.5.5a; use the file provided by your current Zen Cart version.

## For Stores Running on Zen Cart 1.5.5 variants

_OPC_'s distribution, as of v2.1.0, now includes a `zc155` sub-directory that contains Zen Cart **core-file** changes required for Zen Cart versions 1.5.5b-f, as identified below:

| File Name | Description |
| ---- | ---- |
| `/includes/classes/message_stack.php` | Two marked change-sections (comments-only).  Otherwise, this is the `zc156a` version of the file. | 
| `/includes/classes/order.php` | One marked change-section, using the `zc155f` version as the change-basis. |
| `/includes/classes/observers/auto.downloads_via_aws.php` | This is the `zc156a` version of the file, unchanged. |
| `/includes/classes/observers/auto.downloads_via_redirect.php` | This is the `zc156a` version of the file, unchanged. |
| `/includes/classes/observers/auto.downloads_via_streaming.php` | This is the `zc156a` version of the file, unchanged. |
| `/includes/classes/observers/auto.downloads_via_url.php` | This is the `zc156a` version of the file, unchanged. |
| `/includes/modules/pages/download/header_php.php` | Other than the header-timestamp comments, this is the `zc156a` version of the file. |
| `/includes/modules/YOUR_TEMPLATE/downloads.php` | This is (now) the `zc156a` version of the file, updated.  **Note that the file has been updated in this distribution!** |
| `/includes/templates/template_default/templates/tpl_modules_downloads.php` | 