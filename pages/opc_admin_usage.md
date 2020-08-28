# Using the `opc_admin.php` script #

v2.3.4 of *One-Page Checkout* includes an admin-level script (`/YOUR_ADMIN/opc_admin.php`) that validates a store's guest-customer's pseudo-customer-id and the default guest billing and shipping addresses.  That script is normally run once to provide any fix-ups required, but can be run multiple times without adverse affect.

Once you've copied that file to your store's admin-root, a ***SuperUser*** admin can run the script by entering that script-name directly in the browser's address bar, e.g. `www.example.com/admin/opc_admin.php`.  The script provides a screen output to identify whether any issues are found, also writing a log of those issues to `/logs/opc_admin_messages.log`.

## What it does

The `opc_admin.php` script validates the database settings associated with OPC's guest-checkout, specifically the guest-customer ID and its two guest address-book entries.

1. If a guest-customer id is not configured, there should not be guest billing- or shipping-address entries, either.

   - The address-book entries, if present, are removed.

2. If a guest-customer id is configured

   a. If there's no `customers` table record for that `customers_id`.

   	-  Any `customers_info` or `address_book` records associated with that `customers_id` are removed.
   	-  The `configuration` table entries for the guest's `customers_id` and the two address-book-id's are removed (they'll be restored on an admin-page refresh by the base OPC initialization script).

   b. Otherwise, the guest-customer's base record is OK.  Check the address-book entries.

   	-  For the guest's default billing-address, make sure that an address-book record is present and that the record contains the OPC default information.  If so, also make sure that the guest-customer's default address refers to this address-book record, updating if needed.  Otherwise, create a new address-book record, saving that value as the guest-customer's default address and in the OPC configuration.
   	-  For the guest's default shipping-address, make sure that an address-book record is present and that the record contains the OPC default information.  If not, create a new address-book record, saving that value in the OPC configuration.

   



