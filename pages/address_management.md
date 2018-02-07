# Address Management #
Starting with v2.0.0, *One-Page Checkout* can be entered in one of three operational modes:
1. Full-Account Checkout
2. Registered-Account Checkout
2. Guest Checkout

Each mode requires a slightly different method for handling the addresses that the customer supplies for the checkout process.

## Full-Account Checkout ##
This mode starts when a fully-registered, logged-in customer enters the checkout process.  The customer has created a primary address, either during the `create_account` page's base processing or by supplying that address during the checkout process.

On entry, the `login` page's processing has recorded the customer's primary `address_book_id` into the session; that address is the default for both the billing and shipping.  A dropdown selection (***Select from saved addresses***) is displayed, allowing the customer to choose a previously saved address from their address-book.

When the customer changes an address (either billing or shipping), the address they enter is either *permanent* (i.e. recorded for their future use in the database) or *temporary* (i.e. used for this order only), as controlled by the ***Add to Address Book*** checkbox. That checkbox is displayed ***only if*** the customer has an available address-book entry into which a *permanent* address can be saved.

If the address-change is *temporary* (i.e. the checkbox is not ticked), the updated address is temporarily stored in the customer's session.  Otherwise, the  OPC processing will attempt to find a pre-existing address-book record that matches the requested change, using that `address_book_id` if found; if not, a new address-book entry is created to contain the requested address-change.

In either case, the address-related session-variable (either `$_SESSION['billto']` or `$_SESSION['sendto']`) is updated to reflect the change.  Any *temporary* address-book information is stored for the current order, but is removed once the checkout process is completed.

## Registered-Account Checkout ##
This mode starts when a partially-registered, logged-in customer enters the checkout process.  The customer previously created their account going through the *modified* `login` page's processing and does not (yet) have a defined primary address-book entry.

On entry, the Billing Address block is given focus, requiring the customer to supply that information; unlike the ***Guest Checkout*** mode, the customer is given the opportunity to ***Add to Address Book***.  The *First Name* and *Last Name* fields of that block are populated with the information the customer supplied at account-registration.

Other than the address-entry requirement, the address-information gathering is similar to the full-account handling.

## Guest Checkout ##
This mode starts when a not-logged-in customer with an item in their cart enters the checkout process via click of a guest-checkout link in the store (normally from the store's `login` page).  The customer *might* have a pre-existing account with the store. On entry, the customer's ***Email Address*** and ***Telephone Number*** are gathered to allow the store's admin to contact the customer with any questions and/or notifications regarding the current order.

Once supplied, the ***Billing Address*** block is given focus, requiring the customer to supply that information.  Upon validation, the information is stored in the customer's session and is used as the order's current billing- and shipping-address.

If the customer chooses to have a different ship-to address, they un-tick the ***Billing, same as Shipping*** checkbox and supply that address' information.  Upon validation, the information is stored in the customer's session and is used as the order's current shipping address.

All address-book entries are removed once the checkout process is completed.

If the guest's supplied email-address is not currently associated with a customer account, the guest is given the opportunity to create a permanent account using the customer- and address-values from the just-placed order on the display of the `checkout_success` page.  At that time, they can supply a password (and confirmation) and (optionally) sign up for any newsletters.
