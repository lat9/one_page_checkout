# One-Page Checkout v2.1.2

This documentation contains implementation details associated with the *guest-checkout* and *registered-account* features introduced by v2.0.0 of [***One-Page Checkout***](https://github.com/lat9/one_page_checkout) (*OPC*) for Zen Cart and augments the information already present in the plugin's readme. The roots of OPC's *guest-checkout* and *registered-account* processing (*OPC-GC/RA*) are found in the [COWOA](https://www.zen-cart.com/showthread.php?196995-COWOA-%28for-ZC-v1-5-x%29) (by @DivaVocals, @countrycharm and others) and [COWAA](https://www.zen-cart.com/downloads.php?do=file&id=2131) (by @davewest) plugins &hellip; but the implementation is quite different.

**Note:** v2.1.0 drops support for Zen Cart versions _prior to_ `1.5.5b`!

## Overview ##

v1.0.0 of *One-Page Checkout* enables a Zen Cart store &mdash; without additional fees &mdash; to present an up-to-date checkout experience to its customers. 

v2.0.0+ continues that fee-less tradition, with the introduction of the following features:

- ***Guest checkout***. Enables a store to provide a fast-path through its checkout process.  An *OPC-GC* customer's information is gathered and saved for *only the current order*.  In this checkout path, the `create_account`, `checkout_shipping`, `checkout_payment` and `checkout_confirmation` steps are combined in a single, AJAX-aided `checkout_one` page.

- ***Registered accounts***.  Enables a store to gather *minimal* information from a customer in its account-creation processing.  An *OPC-RA* customer has all the benefits of a regular store customer, but they have not yet provided any address-related information.  They can sign up for newsletters, product notifications and wishlists &hellip; just like full-account holders.  When an *OPC-RA* customer checks out, the `checkout_one` page's processing requires that they supply that address information.

- ***Temporary addresses***.  A non-guest-customer has the option of using an address *only for the current order*.  

Refer to the links below for additional information.

## Additional Information ###

[Storefront Considerations](pages/storefront.md)

[HTML/jQuery Selectors](pages/jquery_selectors.md)

[Admin Considerations](pages/admin.md)
