# Debugging "Your order's details have changed" #

On entry to the `checkout_one_confirmation` page, _OPC_ checks that the customer's session-data hasn't changed after the final run of the order's shipping, payment and order-total processing.  This is done to ensure that &mdash; especially for payments that are confirmed &mdash; the order's information still reflects what the customer last saw prior to the order's transition to the `checkout_process` phase.

If a discrepancy is detected, that processing causes a redirect back to the `checkout_one` page for the customer's review with the message:

```
Your order's details have changed.  Please review the current values and re-submit.
```

Starting with _OPC_ v2.3.5, a diagnostic tool is provided to review an _OPC_ log and identify the discrepancies.  This tool is intended ***for developers*** and, as such, doesn't provide any hook into your site's admin menus or language translations.

To use the tool, follow these instructions:

1. In the site's admin, set ***Configuration :: One-Page Checkout Settings ::  Enable One-Page Checkout Debug?*** to *true*. 
2. On the storefront, re-run the failing order.  A log-file will be generated in the site's `/logs` directory named `one_page_checkout-ccc-yyyy-mm-dd.log`.  The `ccc` value is either the characters *na* or the value associated with the `customers_id` being checked-out.
3. Login or return to the site's admin and change the address in your browser's address-bar to `yoursite.com/your_admin/opc_debug_redirection_error.php?id=ccc-yyyy-mm-dd`, where the `ccc-yyyy-mm-dd` are those from the log in step 2.
4. Press `Enter` and the report will run, outputting its results to the screen.  Use the differences it reports to determine what session values are either added or removed after the shipping, payment and order-totals have been run.