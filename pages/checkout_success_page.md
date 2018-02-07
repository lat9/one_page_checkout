# *Checkout Success* Page Modifications #

When a store has enabled the *OPC*'s *guest-checkout* (setting ***Configuration->One-Page Checkout Settings->
Enable Guest Checkout?*** to *true*) and a *guest-customer* completes an order, the customer is shown a modified version of the `checkout_success` page's content.

This page's layout differs slightly from the standard `checkout_success` page:

1. The **Checkout Success Sample Text** is present in `includes/languages/english/html_includes/define_checkout_success_guest.php`, allowing you to create specific messaging for any orders placed by guests.
2. The language-text guides the customer to the [`order_status`](order_status_page.md) page instead of their `account`.
3. *If the email-address used for the guest-checkout is not associated with a customer account*, then the guest-customer is given the opportunity to create an account using the contact- and address-information supplied for the just-placed order.  


----------

![](images/checkout_success_guest.jpg)

----------

The language-text associated with the changes is found in `includes/languages/english/checkout_success_login.php` and `includes/languages/english/html_includes/define_checkout_success_guest.php`; the page-template is found in `includes/templates/template_default/templates/tpl_checkout_success_guest.php`.