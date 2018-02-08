# Storefront Considerations #

*OPC-GC/RA* ***does not*** create `customers` database records for its processing; address- and contact-information for a guest-placed order is recorded *only in that order*.  Instead, its admin-initialization script creates "dummy" records that identify a guest-customer (in the `customers` and `customers_info` tables) as well as "dummy" records that identify a temporary billing- and shipping-address (in the `address_book` table).

Unlike its predecessors, there is no `no_account` page provided for *OPC-GC*.  Instead, an alternate template-display for the store's `login` page begins the guest's checkout and the guest-customer's contact information is gathered as a first step in the *OPC* checkout process. *OPC-GC/RA* modifies the flow of that and other pages within the store when its features are enabled:

Page Name | Modifications
-------------  | -------------
[address_book](address_book_page.md) | Recognizes when a customer-account does not yet have a defined primary address (i.e. the customer has registered for an account).
[checkout_one](checkout_one_page.md) | Updated to gather guest-customer contact information and provide full support for the plugin's temporary billing- and shipping-addresses.
[checkout_success](checkout_success_page.md) | Recognizes when an order has been placed by a guest.  The guest has the opportunity to "convert" to a fully-registered account by supplying an account password.
[create_account](create_account_page.md) | Displays a modified form for entry, requiring only the non-address-related elements for the customer.
[create_account_success](create_account_success_page.md) | Displays a modified version of the page when the just-created account is for a registered-account only.
[download](download_page.md) | Provides the order-lookup by order-id and email-address, enabling guest customers to download their purchases.
[login](login_page.md) | Displays an alternate form of the page when either the OPC's guest-checkout or registered-accounts processing is enabled.

Like its predecessors, OPC-GC/RA provides an [order_status](order_status_page.md) page that enables a not-logged-in customer to check the status of their order.

## Additional Topics ##

[Customer-Address Management](address_management.md)

## Implementation Notes ##

*OPC*'s storefront guest-checkout and registered-accounts handling is controlled by two class modules:

1. `/includes/classes/OnePageCheckout.php`
2. `/includes/classes/observers/class.checkout_one_observer.php`

The main class-file is session-based &mdash; instantiated as `$_SESSION['opc']` &mdash; so that it can remember a customer from `login` through `checkout`; its observer-class loads fresh on each page-load. This allows the observer-class to act as a *conductor*, instructing the session-based processing what to do next based on current customer action; it also acts to "refresh" the session-based settings, just in case an admin configuration occurred during the customer's session.
