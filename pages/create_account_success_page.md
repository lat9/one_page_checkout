# *Create Account Success* Page Modifications #

When a store has enabled the *OPC*'s registered-account handling (setting ***Configuration->One-Page Checkout Settings->
Enable Account Registration?*** to *true*) and a customer completes their account-registration, the customer is shown a modified version of the `create_account_success` page's content.  That modification removes the display of the customer's primary address information &hellip; since it's not yet been entered.

----------

![](images/create_account_success_register.jpg)

----------

The language-text associated with the changes is found in `includes/languages/english/create_account_success_register.php` and the page-template is found in `includes/templates/template_default/templates/tpl_create_account_success_register.php`.