# HTML Selectors used by OPC's jQuery

Much of _One-Page Checkout_'s processing is performed by its jQuery script (`jquery.checkout_one.js`).  This document identifies the HTML selectors used by that jQuery handler.

**Note**: The *Source* column contains the name of the template file that creates the HTML containing the associated *Selector*.  For brevity, if the source's name begins with `opc_*`, the associated source-file name is actually `tpl_modules_opc_*.php`.

| Selector | Usage | Source |
| ----- | ----- | ----- |
| `#checkoutPaymentNoJs` | Identifies the container &mdash; outside of the `#checkoutPayment` block &mdash; that is displayed if a jQuery-related error is detected, overriding the main data-gathering display. | tpl_checkout_one_default.php |
| `#checkoutPayment` | Identifies the overall container block for the `checkout_one` page's form. | tpl_checkout_one_default.php |
| `form[name="checkout_payment"]` | The name of the primary checkout form on the `checkout_one` page. | tpl_checkout_one_default.php |
| `.opc-overlay` | Identifies the container, present as a _direct_ child of the `#checkoutPayment` block, that is used to provide an overlay of input areas not used for the current action. For example, when the _Customer Information_ block is active, the other areas of the form are faded. | tpl_checkout_one_default.php |
| &nbsp; | &nbsp; | &nbsp; |
| `#checkoutOneGuestInfo` | Identifies the block that contains the form elements associated with the guest-related information. | opc_customer_info.php |
| `#opc-guest-cancel` | Identifies the _Cancel_ button associated with a guest-information change. | opc_customer_info.php |
| `#opc-guest-save` | Identifies the _Save_ button associated with a guest-information change. | opc_customer_info.php |
| &nbsp; | &nbsp; | &nbsp; |
| `#checkoutOneBillto` | Identifies the container block that holds the order's billing address. | opc_billing_address.php |
| `#opc-bill-cancel` | Identifies the _Cancel_ button associated with a billing-address change. | opc_billing_address.php |
| `#opc-bill-save` | Identifies the _Save_ button associated with a billing-address change. | opc_billing_address.php |
| `#opc-add-bill` | Identifies the _Add to Address Book_ checkbox within the billing-address block. | opc_billing_address.php |
| `#select-address-bill` | Identifies the existing-address drop-down, present in the billing-address block for logged-in customers. | opc_billing_address.php, opc_address_block.php |
| &nbsp; | &nbsp; | &nbsp; |
| `#shipping_billing` | Identifies the checkbox field that indicates whether or not the order is shipping to the specified billing address. | opc_shipping_address.php |
| `#checkoutOneShipto` | Identifies the container block that holds the order's shipping address.  Required if the order is not _virtual_. | opc_shipping_address.php |
| `#opc-ship-cancel` | Identifies the _Cancel_ button associated with a shipping-address change. | opc_shipping_address.php |
| `#opc-ship-save` | Identifies the _Save_ button associated with a shipping-address change. | opc_shipping_address.php |
| `#opc-add-ship` | Identifies the _Add to Address Book_ checkbox within the shipping-address block. | opc_shipping_address.php |
| `#select-address-ship` | Identifies the existing-address drop-down, present in the shipping-address block for logged-in customers. | opc_shipping_address.php, opc_address_block.php |
| &nbsp; | &nbsp; | &nbsp; |
| `#checkoutShippingMethod` | Identifies the overall container that holds the order's shipping selections. | opc_shipping_choices.php |
| `#checkoutShippingChoices` | Identifies the container that holds the order's current shipping choices. | opc_shipping_choices.php |
| `#checkoutShippingContentChoose` | Identifies the container that holds the instructions associated with the order's current shipping choices. | opc_shipping_choices.php |
| `input[name=shipping]` | Identifies the collection of radio-button inputs used to choose the order's shipping method. | checkout_one_shipping.php |
| &nbsp; | &nbsp; | &nbsp; |
| `#orderTotalDivs` | Identifies the wrapper element for the order-totals' section. | opc_shopping_cart.php |
| `#ottotal`<sup>1</sup> | Identifies the order's current total value on entry to the page. | tpl_modules_order_totals.php |
| `#otshipping` | Identifies the order's shipping cost element; required if the order is not _virtual_. | tpl_modules_order_totals.php |
| &nbsp; | &nbsp; | &nbsp; |
| `#current-order-total` | Set to contain the order's _current_ total value prior to submit, possibly updated from the initial value (`#ottotal`) based on a change in shipping-method selection. | opc_submit_block.php |
| `#opc-order-review` | Identifies the _Review_ button on the `checkout_one` page. | opc_submit_block.php |
| `#opc-order-confirm` | Identifies the _Confirm_ button on the `checkout_one` page. | opc_submit_block.php |
| `#confirm-the-order` | Identifies the hidden field that is set to indicate whether (1) or not (0) the order is being confirmed, as opposed to being submitted to record a credit-class order-total's (e.g. `ot_coupon`) variables. | opc_submit_block.php |
| &nbsp; | &nbsp; | &nbsp; |
| `.opc-buttons` | Identifies the button-container for the guest-information, billing-address and shipping-address blocks. | opc_customer_info.php, opc_billing_address.php, opc_shipping_address.php |

<sup>1</sup> Starting with OPC v2.3.0, the `#ottotal` selector is replaced by a configurable selector to enable interoperation with more templates!