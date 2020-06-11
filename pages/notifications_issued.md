# Notifications Issued by One-Page Checkout

*One-Page Checkout* issues various notifications to enable site-specific (or other plugins) to seamlessly integrate with *OPC* without modifications.

## Issued by `$_SESSION['opc']`

These notifications are issued in `function` scope via `$this`, so watching observers have access to the class' _public_ variables as well as those provided in the notification.  This class is an instantiation of `/includes/classes/OnePageCheckout.php`.

| Notifier | Description |
| ----- |  ----- |
| [NOTIFY_OPC_GUEST_CHECKOUT_OVERRIDE](#guest-checkout-override) | Issued at the start of the guest-checkout processing. |
| [NOTIFY_OPC_GUEST_CUSTOMER_INFO_INIT](#add-guest-customer-fields) | Issued at the start of the guest-checkout processing, *if* guest-checkout is enabled. |
| [NOTIFY_OPC_INIT_ADDRESS_FROM_DB](#initialize-customer-address) | Issued prior to the formatting of an address, for account-holding customers. |
| [NOTIFY_OPC_INIT_ADDRESS_FOR_GUEST](#initialize-guest-address) | Issued prior to the formatting of an address, for guest customers. |
| [NOTIFY_OPC_VALIDATE_SAVE_GUEST_INFO](#validate-guest-information) | Issued upon a *change* in a guest-customer's information. |
| [NOTIFY_OPC_ADDRESS_VALIDATION](address-validation) | Issued upon a change to one of a customer's addresses.  Added for *OPC* v2.3.0. |
| [NOTIFY_OPC_ADDRESS_BOOK_SQL](#address-book-sql-override) | Issued while checking to see if a submitted address-change is a pre-existing address.  Added for _OPC_ v2.3.3. |
| [NOTIFY_OPC_ADDRESS_ARRAY_TO_STRING](#address-to-string) | Issued during the processing to determine whether an in-database address-book record matches a change submitted by the customer.  Added for _OPC_ v2.3.3. |
| [NOTIFY_OPC_ADDED_ADDRESS_BOOK_RECORD](#added-address-book-record) | Issued for account-holding customers, when an address-book record has been added. |
| [OPC_ADDED_CUSTOMER_RECORD_FOR_GUEST](#creating-customer-record-for-guest) | Issued during the `checkout_success` page's processing when a guest customer chooses to convert their account to a registered one. |
| [NOTIFY_OPC_CREATED_ADDRESS_BOOK_DB_ENTRY](#create-address-record-for-guest) | Issued during the `checkout_success` page's processing when a guest customer chooses to convert their account to a registered one. |

## Detailed Descriptions
The sections below document the variables supplied by each notification.

-----

### Issued by `$_SESSION['opc']`


#### Guest Checkout Override

This notifier fires at the very beginning of OPC's guest-checkout processing.  A watching observer can indicate that guest-checkout is not allowed.

The following variables are passed with the notification:

| Variable 'name' | Description                                                  |
| :-------------: | :----------------------------------------------------------- |
|       $p1       | n/a.  An empty string.                                       |
|       $p2       | (r/w) Contains a reference to the (boolean)`$allow_guest_checkout` flag, initially set to `true`.  The observer sets this value to `(bool)false` if the guest-checkout should be disallowed. |

#### Add Guest Customer Fields

If guest-checkout is currently enabled, this notifier fires at the beginning of a guest's checkout, giving an observer the opportunity to insert additional fields for that guest-customer.  The base *OPC* processing provides the customer's `firstname`, `lastname`, `email_address`, `telephone` and `dob` fields.

The following variables are passed with the notification:

| Variable 'name' | Description |
| :-----: | :------ |
| $p1 | n/a. An empty string. |
| $p2 | (r/w) Contains a reference to an associative array (initially empty) to contain those additional fields, with the field's key associated with its initial value. |

#### Initialize Customer Address

This notifier fires when a request is received from `tpl_modules_opc_address_block.php` to retrieve the address-related values for an account-holding customer.

The following variables are passed with the notification:

| Variable 'name' | Description |
| :-----: | :------ |
| $p1 | (r/o) Identifies the `address_book_id` for which information is to be returned. |
| $p2 | (r/w) Contains a reference to an associative array initially containing the information retrieved from the `address_book` table for the requested `address_book_id`.  See below for details. |

The associative array provided in `$p2` contains all fields present in the `address_book` for the specified `address_book_id`, with the following changes and/or additions:

1. If a field's name begins with `entry_`, those characters are stripped from the array-key &mdash; e.g. `entry_firstname` becomes simply `firstname`.
2. The `entry_country_id` field's array-key becomes `country`.
3. Two error-related fields (`error` and `error_state_input`) are added, each initialized to `(bool)false`.
4. A boolean field (`country_has_zones`) is added to indicate whether or not the `country` has zones.

#### Initialize Guest Address

This notifier fires when a request is received from `tpl_modules_opc_address_block.php` to retrieve the address-related values for a guest customer.

The following variables are passed with the notification:

| Variable 'name' | Description                                                  |
| :-------------: | :----------------------------------------------------------- |
|       $p1       | n/a. An empty string                                         |
|       $p2       | (r/w) Contains a reference to an associative array initially containing the *default* fields for a guest customer's address.  See below for details. |

The associative array provided in `$p2` contains the following fields:

| Field Name                                                   | Field Value                                                  |
| ------------------------------------------------------------ | ------------------------------------------------------------ |
| gender, company, firstname, lastname, street_address, suburb, city, postcode, state, zone_name | An empty string (''). |
| country, selected_country                                    | The integer value of the store's country (`STORE_COUNTRY`).  |
| zone_id, address_book_id                                     | Set to integer 0. |
| country_has_zones                                            | A boolean flag that indicates whether the store's country has zones. |
| state_field_label                                            | Set to an empty string ('') if `show_pulldown_states` is `false`; otherwise, set to the language constant `ENTRY_STATE`. |
| show_pulldown_states                                         | A boolean flag that indicates whether or not countries with zones will have their zones displayed in a pulldown selection. |
| error, error_state_input, validated                          | Set to (bool)false. |

#### Validate Guest Information

This notifier fires upon a change to a guest-customer's 'base' information. `$_POST` data contains the submitted changes.

An observer has the opportunity to provide additional checks on OPC's base values as well as the customized fields provided by the observer itself.

The following variables are passed with the notification:

| Variable 'name' | Description |
| :-----: | :------ |
| $p1 | (r/o) An associative array, keyed on a`$_POST` array's key, identifying any value errors detected by the base OPC processing. |
| $p2 | (r/w) An associative message-array, initially empty, to be set by the observer to contain any error messages associated with the 'key' field. |
| $p3 | (r/w) An associative field-array, initially empty, to be set by the observer to contain any key/value pairs to be saved in the guest-customer's information. |

In each of the `$p2` and `$p3` arrays, the 'key' is the variable name used in the active template's `tpl_modules_opc_customer_info.php`.  For the message-array (`$p2`), any value is a language-specific message to be displayed, implying that a field data-error was detected.

If neither the base OPC nor the observers' value-checking detects any data-errors, the fields provided in `$p3` are merged with the current guest-customer's information.

#### Address Validation

This notifier fires upon a change to one of the customer's addresses.  An observer has the opportunity to provide additional checks on (and messaging) for OPC's base address values as well as any customized fields provided by the observer itself.

The following variables are passed with the notification:

| Variable 'name' | Description                                                  |
| :-------------: | :----------------------------------------------------------- |
|       $p1       | (r/o) An associative array, identifying which address-type is to be validated and the address-related fields for the validation.  See below for additional details. |
|       $p2       | (r/w) An associative message-array, initially empty, to be set by the observer to contain any error messages associated with the 'key' field.  See below for additional details. |
|       $p3       | (r/w) An associative field-array, initially empty, to be set by the observer to contain any key/value pairs to be saved in the address' information.  See below for additional details. |

###### Address-type and Fields

The array supplied in the notification's `$p1` array contains the following information:

| Key              | Value                                                        |
| ---------------- | ------------------------------------------------------------ |
| `which`          | Identifies the 'type' of address being validated, either 'bill' or 'ship'. |
| `address_values` | An associative array, containing the posted values for the address-form's update. |

###### Additional Messages

This associative array, initially empty, can be set by the observer to contain field-specific error-messages to display to the customer.  Each element of the array is keyed on a field present in the `address_values` supplied in the `$p1` parameter.

If this array is not empty on return to *OPC*, the address' validation is considered to have failed.

###### Additional Fields

This associative array, initially empty, can be set by the observer to contain field-specific values to be recorded upon a successful address validation.  Each element of the array is keyed on a field present in the `address_values` supplied in the `$p1` parameter.

An observer can override the value to be stored for any of OPC's base address fields, e.g. setting a `firstname` value in the array results in the supplied value being recorded for the customer's firstname.

#### Address Book SQL Override

This notifier fires &mdash; for account-holding customers _only_ &mdash; when a customer changes their address and requests that the change be saved as a permanent address.

The following variables are passed with the notification:

| Variable 'name' | Description                                                  |
| :-------------: | :----------------------------------------------------------- |
|       $p1       | (r/o) Contains an associative array, containing the address-related elements used to form the initial address-book SQL query present in `$p2`. |
|       $p2       | (r/w) A string containing the current to-be-issued SQL query used to gather address-book records for the current customer. |

#### Address To String

This notifier fires &mdash; for account-holding customers _only_ &mdash; when a customer changes their address and requests that the change be saved as a permanent address.

The following variables are passed with the notification:

| Variable 'name' | Description                                                  |
| :-------------: | :----------------------------------------------------------- |
|       $p1       | (r/o) Contains an associative array, containing the address-related elements used to form the comparison-address-string contained in `$p2`. |
|       $p2       | (r/w) A string containing the current to-be-issued SQL query used to gather address-book records for the current customer. |

#### Added Address Book Record

This notifier fires &mdash; for account-holding customers *only* &mdash; when a new `address_book` record has been created for the customer.  The observer can use the information provided to add additional fields to that (or other) database tables for the specified address.

The following variables are passed with the notification:

| Variable 'name' | Description |
| :-----: | :------ |
| $p1 | (r/o) Contains an associative array, identifying the `address_book_id` created for the customer. |
| $p2 | (r/w) Contains an associative SQL data-array (in the format supplied to the `$db->perform` method) identifying the fields just written to the `address_book` table on the customer's behalf. |

#### Creating Customer Record for Guest

This notifier fires during the `checkout_success` page's processing (after a guest checkout) when the guest elects to convert their account to a permanent one.

The following variables are passed with the notification:

| Variable 'name' | Description |
| :-----: | :------ |
| $p1 | (r/o) Contains the `customer_id` associated with the newly-created customer. |
| $p2 | (r/w) Contains an associative SQL data-array (in the format supplied to the `$db->perform` method) identifying the fields just written to the `customers` table on the customer's behalf. |

#### Create Address Record for Guest

This notifier fires during the `checkout_success` page's processing (after a guest checkout) when the guest elects to convert their account to a permanent one.

The following variables are passed with the notification:

| Variable 'name' | Description                                                  |
| :-------------: | :----------------------------------------------------------- |
|       $p1       | (r/o) Contains the `address_book_id` associated with the address just created for the customer. |
|       $p2       | (r/w) Contains an associative SQL data-array (in the format supplied to the `$db->perform` method) identifying the fields just written to the `address_book` table on the customer's behalf. |
