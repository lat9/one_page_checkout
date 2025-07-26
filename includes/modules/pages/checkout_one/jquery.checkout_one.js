// -----
// Part of the One-Page Checkout plugin, provided under GPL 2.0 license by lat9.
// Copyright (C) 2013-2025, Vinos de Frutas Tropicales.  All rights reserved.
//
// Last changed: OPC v2.5.5
//
var selected;
var submitter = null;

// -----
// These functions are "legacy", carried over from the like-named module in /includes/modules/pages/checkout_payment
//
function concatExpiresFields(fields) 
{
    return jQuery(':input[name='+fields[0]+']').val()+ jQuery(':input[name='+ fields[1]+']').val();
}

function popupWindow(url) 
{
    window.open(url,'popupWindow','toolbar=no,location=no,directories=no,status=no,menubar=no,scrollbars=yes,resizable=yes,copyhistory=no,width=450,height=320,screenX=150,screenY=150,top=150,left=150')
}

function couponpopupWindow(url) 
{
    window.open(url,'couponpopupWindow','toolbar=no,location=no,directories=no,status=no,menubar=no,scrollbars=yes,resizable=yes,copyhistory=no,width=450,height=320,screenX=150,screenY=150,top=150,left=150')
}

// -----
// Used by various payment modules and checkout-confirmation pages; sets the (presumed) submit button with the
// "btn_submit" id disabled upon the form's submittal.
//
function submitonce()
{
    var button = document.getElementById('btn_submit');
    button.style.cursor = 'wait';
    button.disabled = true;
    setTimeout('button_timeout()', shippingTimeout);
    return false;
}
function button_timeout() 
{
    var button = document.getElementById('btn_submit');
    button.style.cursor = 'pointer';
    button.disabled = false;
}

// -----
// Local to the checkout_one page, provides a common function to log a javascript console
// message.  The checking is required for older (pre IE-9?) versions of Internet Explorer, which
// doesn't instantiate the window.console class unless the debug pane is open.
//
function zcLog2Console(message)
{
    if (window.console) {
        if (typeof(console.log) == 'function') {
            console.log(message);
        }
    }
}

// -----
// Normally used in an onfocus attribute of a payment-module's selection.
//
function methodSelect(theMethod) 
{
    if (document.getElementById(theMethod)) {
        document.getElementById(theMethod).checked = 'checked';
    }
}

// -----
// Not currently used, but might be useful in the future!
//
function setJavaScriptEnabled()
{
    var jsEnabled = document.getElementById('javascript-enabled');
    if (jsEnabled) {
        jsEnabled.value = '1';
    }
}

// -----
// Called by various on-page event handlers, sets the flag that's passed to the checkout_one_confirmation page
// to indicate whether the transition was due to an order-confirmation vs. a credit-class order-total update.
//
var orderConfirmed = 0;
function setOrderConfirmed(value)
{
    orderConfirmed = value;
    jQuery('#confirm-the-order').val(value);
    zcLog2Console('Setting orderConfirmed ('+value+'), submitter ('+submitter+')');
}

// -----
// Main processing section, starts when the browser has finished and the page is "ready" ...
//
jQuery(document).ready(function() {
    // -----
    // There are a bunch of "required" elements for this submit-less form to be properly handled.  Check
    // to see that they're present, alerting the customer (hopefully the owner!) if any of those elements
    // are missing.
    //
    var elementsMissing = false;
    if (jQuery('form[name="checkout_payment"]').length == 0) {
        elementsMissing = true;
        zcLog2Console('Missing form[name="checkout_payment"]');
    }
    if (jQuery('#orderTotalDivs').length == 0) {
        elementsMissing = true;
        zcLog2Console('Missing #orderTotalDivs');
    }

    // -----
    // Hide the shipping and/or payment blocks if the associated address is either
    // not yet entered or not validated.
    //
    var checkMissingElements = shippingChoiceAvailable && paymentChoiceAvailable;
    if (!displayShippingBlock) {
        checkMissingElements = false;
        jQuery('#checkoutShippingMethod').hide();
        jQuery('#otshipping').hide();
        jQuery('#otshipping+br').hide();
    }
    if (!displayPaymentBlock) {
        checkMissingElements = false;
        jQuery('#checkoutPaymentMethod').hide();
    }

    // -----
    // Account for the fact that some portions of the page aren't rendered if no
    // shipping is available and/or the temporary shipping/billing addresses have
    // not yet been entered and don't check for these known-to-be-missing blocks
    // in those "corner-cases".
    //
    if (checkMissingElements) {
        if (jQuery('#current-order-total').length == 0) {
            elementsMissing = true;
            zcLog2Console ('Missing #current-order-total');
        }
        if (jQuery('#opc-order-confirm').length == 0) {
            elementsMissing = true;
            zcLog2Console('Missing #opc-order-confirm');
        }
        if (jQuery('#opc-order-review').length == 0) {
            elementsMissing = true;
            zcLog2Console('Missing #opc-order-review');
        }
    }

    if (!virtual_order && checkMissingElements) {
        if (jQuery('#otshipping').length == 0) {
            elementsMissing = true;
            zcLog2Console('Missing #otshipping');
        }
    }

    if (elementsMissing) {
        alert('Please contact the store owner; some required elements of this page are missing.');
    }

    // -----
    // Called by the on-click event processor for the "Shipping Address, same as Billing?" checkbox, checks
    // to see that the ship-to address section is present (it's not for virtual orders) and, if so, either
    // hides or shows that address based on the checkbox status.
    //
    // Requires:
    // - checkbox, id="shipping_billing"
    //
    function shippingIsBilling()
    {
        if (jQuery('#checkoutOneShipto').length) {
            if (jQuery("#shipping_billing").is(':checked')) {
                jQuery('#checkoutOneShipto').hide();
                jQuery('#opc-billing-title').text(billingShippingTitle);
            } else {
                jQuery('#checkoutOneShipto').show();
                jQuery('#opc-billing-title').text(billingTitle);
            }
        }
    }

    // -----
    // Perform some page-load type operations, initializing the "environment".  These functions
    // were performed by the on_load_main.js file from prior versions.
    //
    shippingIsBilling();
    setJavaScriptEnabled();

    // -----
    // Retrieve the order's current total. It might be modified on a shipping-/payment-module
    // change.  Used when submitting the form.
    //
    zcJS.ajax({
        url: 'ajax.php?act=ajaxOnePageCheckout&method=getOrderTotal',
        timeout: shippingTimeout,
        error: function (jqXHR, textStatus, errorThrown) {
            zcLog2Console('error: status='+textStatus+', errorThrown = '+errorThrown+', override: '+jqXHR);
            if (textStatus == 'timeout') {
                alert(ajaxTimeoutShippingErrorMessage);
            }
            shippingError = true;
        },
    }).done(function(response) {
        // -----
        // Handle any redirects required, based on the AJAX response's status.
        //
        checkForRedirect(response);

        jQuery('#current-order-total').val(response.total);
    });

    // -----
    // Disallow the Enter key (so that all form-submittal actions occur via "click"), except when that
    // key is pressed within a textarea section.
    //
    jQuery(document).on('keypress', ':input:not(textarea)', function(event) {
        return event.keyCode != 13;
    });

    // -----
    // This function displays either the "review-order" or "confirm-order", based
    // on the currently-selected payment method.  If no payment method is selected,
    // the "confirm-order" button is displayed and javascript "injected" by the Zen Cart
    // payment class will alert if no payment method is currently chosen.
    //
    // v2.4.0: Adding a 'global' flag that indicates whether the currently-selected
    // payment method handles the overall submission of the order-placement form.
    //
    var paymentMethodHandlesSubmit = false;
    function setFormSubmitButton()
    {
        var payment_module = null;
        if (document.checkout_payment.payment) {
            if (document.checkout_payment.payment.length) {
                for (var i = 0; i < document.checkout_payment.payment.length; i++) {
                    if (document.checkout_payment.payment[i].checked) {
                        payment_module = document.checkout_payment.payment[i].value;
                    }
                }
            } else if (document.checkout_payment.payment.checked) {
                payment_module = document.checkout_payment.payment.value;
            } else if (document.checkout_payment.payment.value) {
                payment_module = document.checkout_payment.payment.value;
            }
        }
        zcLog2Console('setFormSubmitButton, payment-module: '+payment_module);
        jQuery('#opc-order-review, #opc-order-confirm').hide();
        if (payment_module == null || confirmation_required.indexOf(payment_module) == -1) {
            jQuery('#opc-order-confirm').show();
            if (payment_module != null && paymentsThatSubmit.indexOf(payment_module) != -1) {
                paymentMethodHandlesSubmit = true;
            } else {
                paymentMethodHandlesSubmit = false;
            }
            zcLog2Console('Showing "confirm", paymentMethodHandlesSubmit ('+paymentMethodHandlesSubmit+')');
        } else {
            jQuery('#opc-order-review').show();
            zcLog2Console( 'Showing "review"' );
        }
        if ((jQuery('#privacy').length != 0 && !jQuery('#privacy').is(':checked')) || (jQuery('#conditions').length != 0 && !jQuery('#conditions').is(':checked'))) {
            zcLog2Console('setFormSubmitButton, disabling Review and Confirm buttons.');
            jQuery('#checkoutOneSubmit').addClass('opc-disabled');
        } else {
            zcLog2Console('setFormSubmitButton, enabling Review and Confirm buttons.');
            jQuery('#checkoutOneSubmit').removeClass('opc-disabled');
        }
    }
    setFormSubmitButton();

    // -----
    // When the checkbox associated with the site's privacy policy and/or conditions acceptance
    // is changed, set the form's submit button accordingly.
    //
    jQuery(document).on('change', '#privacy, #conditions', function() {
        setFormSubmitButton();
    });

    setOrderConfirmed(0);
    jQuery('#checkoutOneShippingFlag').show();

    zcLog2Console('jQuery version: '+jQuery().jquery);

    function focusOnShipping ()
    {
        var scrollPos =  jQuery('#checkoutShippingMethod').offset().top;
        jQuery(window).scrollTop( scrollPos );
    }

    // -----
    // Used by the on-page processing and also by various "credit-class" order-totals (e.g. ot_coupon, ot_gv) to
    // initialize the checkout_payment form's submittal.  The (global) "submitter" value is set on return to either
    // null/0 (payment-handling required) or 1 (no payment-handling required) and is used by the Zen Cart payment class
    // to determine whether to "invoke" the selected payment method.
    // 
    submitFunction = function(gv, total)
    {
        submitter = null;
        if (gv >= total) {
            submitter = 1;
        }
        zcLog2Console('submitFunction, on exit submitter='+submitter);
    }

    var lastPaymentValue = null;

    doesCollectsCardDataOnsite = function(paymentValue)
    {
        zcLog2Console('Checking doesCollectsCardDataOnsite('+paymentValue+') ...');
        if (jQuery('#'+paymentValue+'_collects_onsite').val()) {
            if (jQuery('#pmt-'+paymentValue).is(':checked')) {
                zcLog2Console('... it does!');
                lastPaymentValue = paymentValue;
                return true;
            }
        }
        zcLog2Console('... it does not.');
        lastPaymentValue = null;
        return false;
    }

    doCollectsCardDataOnsite = function()
    {
        var str = jQuery('form[name="checkout_payment"]').serializeArray();

        zcLog2Console('doCollectsCardDataOnsite for '+lastPaymentValue);
        zcJS.ajax({
            url: 'ajax.php?act=ajaxPayment&method=prepareConfirmation',
            data: str
        }).done(function(response) {
            // -----
            // On return from a successful AJAX request, the updated HTML includes some
            // 'jQuery(document).ready(function()' processing to be performed, but the document's
            // already 'ready'.  Need to change the '.ready' to '.ajaxComplete' to allow that
            // jQuery to do its thing.
            //
            jQuery('#checkoutPayment').hide();
            jQuery('#navBreadCrumb').html(response.breadCrumbHtml.replace(/\.ready/g, '.ajaxComplete'));
            jQuery('#checkoutPayment').before(response.confirmationHtml.replace(/\.ready/g, '.ajaxComplete'));
            jQuery(document).attr('title', response.pageTitle);
            jQuery(document).scrollTop( 0 );
            if (confirmation_required.indexOf( lastPaymentValue ) == -1) {
                zcLog2Console('Preparing to submit form, since confirmation is not required for "'+lastPaymentValue+'", per the required list: "'+confirmation_required);
                jQuery('#checkoutOneLoading').show();
                jQuery('#checkoutConfirmationDefault').hide();
                
                // -----
                // Wait, since this is the last on the .ajaxComplete 'chain' of events until
                // all the just-downloaded jQuery finishes, then submit the AJAX-supplied form.
                //
                jQuery(document).ajaxComplete(function() {
                    jQuery('form[name="checkout_confirmation"]')[0].submit();
                });
            } else {
                zcLog2Console('Confirmation required, displaying for '+lastPaymentValue+'.');
                jQuery('#checkoutConfirmDefault').show();
            }
        });
    }

    // -----
    // Two "helper" functions, used to indicate "progress" during the various AJAX calls.  The
    // cursor changes to "wait" when the AJAX call starts and back to "normal" upon return.
    //   
    jQuery(document).ajaxStart(function() {
        jQuery('*').css('cursor', 'wait');
    });

    jQuery(document).ajaxStop(function() {
        jQuery('*').css('cursor', '');
    });

    // -----
    // A function, called after each AJAX request, to determine if the response indicates
    // that a redirect is required.
    //
    function checkForRedirect(response)
    {
        // -----
        // If a session timeout was detected by the AJAX handler, display a message to the customer
        // and redirect to the login page.
        //
        if (response.status === 'timeout') {
            alert(sessionTimeoutErrorMessage);
            window.location.replace(timeoutUrl);
        }

        // -----
        // If the AJAX handler has detected that OPC is no longer enabled, display a message to the customer
        // and redirect to the checkout_shipping page.
        //
        if (response.status === 'unavailable') {
            alert(ajaxNotAvailableMessage);
            window.location.replace(checkoutShippingUrl);
        }

        // -----
        // If the AJAX handler has detected that OPC detected an internal 'state' mismatch,
        // display the specified message to the customer and reload the checkout_one page.
        //
        if (response.status === 'reload') {
            if (response.errorMessage.length !== 0) {
                alert(response.errorMessage);
            }
            window.location.reload(true);
        }
    }

    function submitCheckoutForm(submit_type)
    {
        submitFunction(jQuery('input[name=cot_gv]').val(), jQuery('#current-order-total').val());
        setOrderConfirmed(1);
        zcLog2Console('Form being submitted, submit_type('+submit_type);

        jQuery('#confirm-the-order').attr('disabled', true);

        // -----
        // If there is at least one payment method available, submit the form.
        //
        if (flagOnSubmit) {
            setFormSubmitButton();
            var formPassed = check_form();
            zcLog2Console('Form checked, passed (' + formPassed + ')');

            if (paymentMethodHandlesSubmit == true) {
                zcLog2Console('Deferring form submittal to the currently-selected payment method.');
            } else {
                if (formPassed) {
                    // -----
                    // If we're submitting based on a "Confirm Order" button-click,
                    // activate the OPC overlay, disable that button and set the document's
                    // cursor to the 'wait' state.
                    //
                    if (submit_type === 'confirm') {
                        jQuery('*').css('cursor', 'wait');
                        jQuery('#checkoutPayment > .opc-overlay').addClass('active');
                        jQuery('#opc-order-confirm').attr('disabled', true);
                    }
                    jQuery('#confirm-the-order').attr('disabled', false);
                    // -----
                    // If the currently-selected payment method handles the submission of the
                    // payment-form, defer the submission to its handling.
                    //
                    jQuery('form[name="checkout_payment"]').submit();
                }
            }
        }
    }

    // -----
    // When the shipping-choice is changed, make the AJAX call to recalculate the order-totals based
    // on that shipping selection.
    //
    jQuery(document).on('change', 'input[name=shipping]', function() {
        var shippingData = {
            shipping_selection: jQuery(this).val(),
        };

        if (additionalShippingInputs.length != 0) {
            var shippingInputs = {};
            jQuery.each(additionalShippingInputs, function(field_name, values) {
                if (jQuery('select[name="'+field_name+'"]').length !== 0) {
                    shippingInputs[field_name] = jQuery('select[name="'+field_name+'"]').val();
                } else {
                    shippingInputs[field_name] = jQuery('input[name="'+field_name+'"]'+values['parms']).val();
                }
            });
            console.log(shippingInputs);
            shippingData = jQuery.extend(shippingData, shippingInputs);
        }

        zcLog2Console('Updating shipping method to '+jQuery(this).val());
        zcJS.ajax({
            url: 'ajax.php?act=ajaxOnePageCheckout&method=updateShippingSelection',
            data: shippingData,
            timeout: shippingTimeout,
            error: function (jqXHR, textStatus, errorThrown) {
                zcLog2Console('error: status='+textStatus+', errorThrown = '+errorThrown+', override: '+jqXHR);
                if (textStatus == 'timeout') {
                    alert(ajaxTimeoutShippingErrorMessage);
                }
                shippingError = true;
            },
        }).done(function(response) {
            // -----
            // Handle any redirects required, based on the AJAX response's status.
            //
            checkForRedirect(response);

            if (response.errorMessage !== '') {
                alert(response.errorMessage);
            } else {
                jQuery('#orderTotalDivs').html(response.orderTotalHtml);
                jQuery('#current-order-total').val(response.total);
                var index = 0;
                jQuery('.productTax').each(function() {
                    if (index < Object.keys(response.productTax).length) {
                        var key = Object.keys(response.productTax)[index];
                        jQuery(this).text(response.productTax[key]);
                        index++;
                    }
                });
            }
        });
    });

    // -----
    // When the billing=shipping box is clicked, show/hide the shipping-address
    // block ... based on the current selection.
    //
    // Note: While it appears redundant to be calling shippingIsBilling in two
    // spots, that function's side-effect is to hide the shipping address block
    // when 'shipping=billing' is checked.
    //
    jQuery(document).on('click', '#shipping_billing', function() {
        if (jQuery('#shipping_billing').is(':checked')) {
            zcJS.ajax({
                url: 'ajax.php?act=ajaxOnePageCheckout&method=setShippingEqualBilling',
                timeout: shippingTimeout,
                error: function (jqXHR, textStatus, errorThrown) {
                    zcLog2Console('error: status='+textStatus+', errorThrown = '+errorThrown+', override: '+jqXHR);
                    if (textStatus == 'timeout') {
                        alert(ajaxTimeoutShippingErrorMessage);
                    }
                    shippingError = true;
                },
            }).done(function(response) {
                // -----
                // Handle any redirects required, based on the AJAX response's status.
                //
                checkForRedirect(response);
            });
        }

        shippingIsBilling();
    });

    // -----
    // The tpl_checkout_one_default.php processing has applied 'class="opc-cc-submit"' to each credit-class
    // order-total's "Apply" button.  When one of those "Apply" buttons is clicked, note that the order has
    // **not** been confirmed and the transition to (and back from) the checkout_one_confirmation page
    // where that credit-class processing will record its changes.
    //
    jQuery(document).on('click', '.opc-cc-submit', function() {
        zcLog2Console('Submitting credit-class request');
        setOrderConfirmed(0);
        jQuery('input[name="action"]').val('cc-submit');
        jQuery('form[name="checkout_payment"]').submit();
    });

    // -----
    // When a different payment method is chosen, determine whether the payment will require a confirmation-
    // page display, change the form's pseudo-submit button to reflect either "Review" or "Confirm".
    //
    // Additionally, need to "register" the selected payment method in the session and re-load the
    // order-totals block to account for totals that are payment-method-specific (e.g. ot_cod_fee).
    //
    jQuery(document).on('change', 'input[name=payment]', function() {
        setFormSubmitButton();

        var paymentSelected = jQuery('input[name=payment]');
        if (paymentSelected.is(':radio')) {
            paymentSelected = jQuery('input[name=payment]:checked');
        }
        if (paymentSelected.length == 0) {
            paymentSelected = '';
        } else {
            paymentSelected = paymentSelected.val();
        }

        var paymentData = {
            payment: paymentSelected
        };

        zcLog2Console('Updating payment method to '+paymentSelected);
        zcJS.ajax({
            url: 'ajax.php?act=ajaxOnePageCheckout&method=updatePaymentMethod',
            data: paymentData,
            timeout: shippingTimeout,
            error: function (jqXHR, textStatus, errorThrown) {
                zcLog2Console('error: status='+textStatus+', errorThrown = '+errorThrown+', override: '+jqXHR);
                if (textStatus == 'timeout') {
                    alert(ajaxTimeoutPaymentErrorMessage);
                }
            },
        }).done(function( response ) {
            // -----
            // Handle any redirects required, based on the AJAX response's status.
            //
            checkForRedirect(response);

            jQuery('#orderTotalDivs').html(response.orderTotalHtml);
            jQuery('#current-order-total').val(response.total);
        });
    });

    // -----
    // When the form's pseudo-submit "Review" button, the user is ready
    // to submit their order.  Set up the various "hidden" fields to reflect the order's current state,
    // note that this is an order-review request, and cause the order to be submitted.
    //
    jQuery(document).on('click', '#opc-order-review', function() {
        submitCheckoutForm('review');
    });

    // -----
    // When the form's pseudo-submit "Confirm" button, is clicked, the user is ready
    // to submit their order.  Set up the various "hidden" fields to reflect the order's current state,
    // note that this is an order-confirmation request, and cause the order to be submitted.
    //
    jQuery(document).on('click', '#opc-order-confirm', function() {
        submitCheckoutForm('confirm');
    });

    // -----
    // Monitor the billing- and shipping-address blocks for changes.
    //
    jQuery(document).on('change', '#select-address-bill', function() {
        useSelectedAddress('bill', this.value);
    });
    jQuery(document).on('change', '#select-address-ship', function() {
        useSelectedAddress('ship', this.value);
    });
    function useSelectedAddress(which, address_id)
    {
        zcLog2Console('useSelectedAddress('+which+', '+address_id+')');
        jQuery('#checkoutPayment > .opc-overlay').addClass('active');
        zcJS.ajax({
            url: 'ajax.php?act=ajaxOnePageCheckout&method=setAddressFromSavedSelections',
            data: {
                which: which,
                address_id: address_id,
                shipping_is_billing: jQuery("#shipping_billing").is(':checked')
            },
            timeout: shippingTimeout,
            error: function (jqXHR, textStatus, errorThrown) {
                zcLog2Console('error: status='+textStatus+', errorThrown = '+errorThrown+', override: '+jqXHR);
                if (textStatus == 'timeout') {
                    alert(ajaxTimeoutSetAddressErrorMessage);
                }
            },
        }).done(function(response) {
            location.reload();
        });
    }

    function changeBillingFields(event)
    {
        if (jQuery('#address-form-bill').is(':visible')) {
            jQuery(this).addClass('opc-changed');
            jQuery('#checkoutOneBillto .opc-buttons, #opc-bill-save, #opc-add-bill, #opc-add-bill+label').show();
            jQuery('#opc-bill-edit').hide();
            jQuery('#checkoutPayment > .opc-overlay').addClass('active');
            jQuery('#checkoutOneGuestInfo, #checkoutOneBillto').addClass('opc-view');
        }
    }
    jQuery(document).on('focus', '#checkoutOneGuestInfo input, #checkoutOneBillto input, #checkoutOneBillto select:not(#select-address-bill)', changeBillingFields);

    function restoreBilling()
    {
        if (jQuery('#checkoutOneGuestInfo').length) {
            restoreCustomerInfo();
        } else {
            restoreAddressValues('bill', '#checkoutOneBillto');
        }
        jQuery('#checkoutPayment > .opc-overlay').removeClass('active');
        jQuery('#checkoutOneGuestInfo, #checkoutOneBillto').removeClass('opc-view');
        jQuery('#checkoutOneBillto .opc-buttons').hide();
    }
    jQuery(document).on('click', '#opc-bill-cancel', restoreBilling);

    function saveBilling()
    {
        if (jQuery('#checkoutOneGuestInfo').length) {
            saveCustomerInfo();
        } else {
            saveAddressValues('bill', '#checkoutOneBillto');
        }
    }
    jQuery(document).on('click', '#opc-bill-save', saveBilling);

    function editBilling()
    {
        jQuery('#address-bill').hide();
        jQuery('#address-form-bill').show();
        jQuery('#checkoutOneBillto .opc-buttons').show();
        jQuery('#opc-bill-save, #opc-add-bill, #opc-add-bill+label').hide();
    }
    jQuery(document).on('click', '#opc-bill-edit', editBilling);
    
    function editShipping()
    {
        jQuery('#address-ship').hide();
        jQuery('#address-form-ship').show();
        jQuery('#checkoutOneShipto .opc-buttons').show();
        jQuery('#opc-ship-save, #opc-add-ship, #opc-add-ship+label').hide();
    }
    jQuery(document).on('click', '#opc-ship-edit', editShipping);

    function changeShippingFields(event)
    {
        if (jQuery('#address-form-ship').is(':visible')) {
            jQuery(this).addClass('opc-changed');
            jQuery('#checkoutOneShipto .opc-buttons, #opc-ship-save, #opc-add-ship, #opc-add-ship+label').show();
            jQuery('#checkoutPayment > .opc-overlay').addClass('active');
            jQuery('#checkoutOneShipto').removeClass('visibleField');
            jQuery('#checkoutOneShipto').addClass('opc-view');
        }
    }
    jQuery(document).on('focus', '#checkoutOneShipto input, #checkoutOneShipto select:not(#select-address-ship)', changeShippingFields);

    function restoreShipping()
    {
        restoreAddressValues('ship', '#checkoutOneShipto');
        jQuery('#checkoutPayment > .opc-overlay').removeClass('active');
        jQuery('#checkoutOneShipto').removeClass('opc-view');
        jQuery('#checkoutOneShipto .opc-buttons').hide();
    }
    jQuery(document).on('click', '#opc-ship-cancel', restoreShipping);

    function saveShipping()
    {
        saveAddressValues('ship', '#checkoutOneShipto');
    }
    jQuery(document).on('click', '#opc-ship-save', saveShipping);

    function restoreAddressValues(which, address_block)
    {
        zcLog2Console('restoreAddressValues('+which+', '+address_block+')');
        zcJS.ajax({
            url: 'ajax.php?act=ajaxOnePageCheckout&method=restoreAddressValues',
            data: {
                which: which
            },
            timeout: shippingTimeout,
            error: function (jqXHR, textStatus, errorThrown) {
                zcLog2Console('error: status='+textStatus+', errorThrown = '+errorThrown+', override: '+jqXHR);
                if (textStatus == 'timeout') {
                    alert(ajaxTimeoutRestoreAddressErrorMessage);
                }
            },
        }).done(function( response ) {
            // -----
            // Handle any redirects required, based on the AJAX response's status.
            //
            checkForRedirect(response);
            
            jQuery(address_block).replaceWith(response.addressHtml);
            if (typeof initializeStateZones != 'undefined') {
                initializeStateZones();
            }
            if (which == 'ship') {
                jQuery(document).on('change', '#checkoutOneShipto input, #checkoutOneShipto select:not(#select-address-ship)', changeShippingFields);
                jQuery(document).on('click', '#opc-ship-cancel', restoreShipping);
                jQuery(document).on('click', '#opc-ship-save', saveShipping);
            } else {
                jQuery(document).on('change', '#checkoutOneBillto input, #checkoutOneBillto select:not(#select-address-bill)', changeBillingFields);
                jQuery(document).on('click', '#opc-bill-cancel', restoreBilling);
                jQuery(document).on('click', '#opc-bill-save', saveBilling);
            }
        });
    }

    function saveAddressValues(which, address_block)
    {
        zcLog2Console('saveAddressValues('+which+', '+address_block+')');

        zcJS.ajax({
            url: 'ajax.php?act=ajaxOnePageCheckout&method=validateAddressValues',
            data: {
                which: which,
                gender: jQuery('input[name="gender['+which+']"]:checked').val(),
                company: jQuery('input[name="company['+which+']"]').val(),
                firstname: jQuery('input[name="firstname['+which+']"]').val(),
                lastname: jQuery('input[name="lastname['+which+']"]').val(),
                street_address: jQuery('input[name="street_address['+which+']"]').val(),
                suburb: jQuery('input[name="suburb['+which+']"]').val(),
                city: jQuery('input[name="city['+which+']"]').val(),
                state: jQuery('input[name="state['+which+']"]').val(),
                zone_id: jQuery('select[name="zone_id['+which+']"] option:selected').val(),
                postcode: jQuery('input[name="postcode['+which+']"]').val(),
                zone_country_id: jQuery('select[name="zone_country_id['+which+']"] option:selected').val(),
                shipping_billing: jQuery('#shipping_billing').is(':checked'),
                add_address: jQuery('#opc-add-'+which).prop('checked')
            },
            timeout: shippingTimeout,
            error: function (jqXHR, textStatus, errorThrown) {
                zcLog2Console('error: status='+textStatus+', errorThrown = '+errorThrown+', override: '+jqXHR);
                if (textStatus == 'timeout') {
                    alert(ajaxTimeoutValidateAddressErrorMessage);
                }
            },
        }).done(function(response) {
            // -----
            // Handle any redirects required, based on the AJAX response's status.
            //
            checkForRedirect(response);

            // -----
            // If the response returns a non-empty array of messages, there were one or more
            // "issues" with the submitted information.  Highlight the errant fields and display
            // the messages at the bottom of the active address-block.
            //
            var messageBlock = '#messages-'+which;
            if (response.messages.length != 0) {
                var focusSet = false;
                jQuery(messageBlock).html('<ul></ul>').addClass('opc-error');
                jQuery(address_block+' input, '+address_block+' select').removeClass('opc-error');
                jQuery.each(response.messages, function(field_name, message) {
                    jQuery(messageBlock+' ul').append('<li>'+message+'</li>');
                    if (jQuery('input[name="'+field_name+'['+which+']"]').length) {
                        jQuery('input[name="'+field_name+'['+which+']"]').addClass('opc-error').removeClass('opc-changed');
                        if (!focusSet) {
                            focusSet = true;
                            jQuery('input[name="'+field_name+'['+which+']"]').focus();
                        }
                    } else {
                        jQuery('select[name="'+field_name+'['+which+']"]').addClass('opc-error').removeClass('opc-changed');
                        if (!focusSet) {
                            focusSet = true;
                            jQuery('select[name="'+field_name+'['+which+']"]').focus();
                        }
                    }
                });
            // -----
            // Otherwise, the address-update was successful.  Since the shipping, payment and order-total
            // modules might have address-related dependencies, simply hard-refresh (i.e. from the
            // server) the page's display to allow that processing to do its thing.
            //
            } else {
                window.location.reload(true);
            }
        });
    }

    // -----
    // If we get here successfully, the jQuery processing for the page looks OK so we'll hide the
    // alternate-checkout link and display the "normal" one-page checkout form.
    //
    jQuery('#checkoutPaymentNoJs').hide();
    jQuery('#checkoutPayment').show();

    // -----
    // If the checkout process is currently being performed in "guest" mode, make sure that any
    // required fields in the guest-login and billing-address blocks are currently filled-in 
    // and, if not, give focus to the first required input in the block.
    //
    if (jQuery('#checkoutOneGuestInfo').length) {
        jQuery('#checkoutOneGuestInfo, #checkoutOneBillto').find('input').each(function() {
            if (jQuery(this).is(':visible') && jQuery(this).prop('required') && jQuery(this).val() == '') {
                jQuery('#checkoutOneBillto .opc-buttons').show();
                jQuery('#opc-bill-cancel').hide();
                jQuery('#checkoutPayment > .opc-overlay').addClass('active');
                jQuery('#checkoutOneGuestInfo, #checkoutOneBillto').addClass('opc-view');
                jQuery(this).focus();
                return false;
            }
        });
    }

    // -----
    // If the checkout process is currently being performed for a registered-account customer
    // who has not (yet) created their primary address, enable the "Save" button on the
    // billing-address block and focus on the first required billing-address field.
    //
    if (jQuery('#opc-need-primary-address').length) {
        jQuery('#checkoutOneBillto').find('input').each(function() {
            if (jQuery(this).is(':visible') && jQuery(this).prop('required') && jQuery(this).val() == '') {
                jQuery(this).focus();
                return false;
            }
        });
        jQuery('#checkoutOneBillto .opc-buttons').show();
        jQuery('#opc-bill-cancel, #checkoutOneShippingFlag').hide();
    }

    // -----
    // Methods to restore/save the guest-customer's information.
    //
    function restoreCustomerInfo()
    {
        zcLog2Console('restoreCustomerInfo, starts ...');
        zcJS.ajax({
            url: 'ajax.php?act=ajaxOnePageCheckout&method=restoreCustomerInfo',
            timeout: shippingTimeout,
            error: function (jqXHR, textStatus, errorThrown) {
                zcLog2Console('error: status='+textStatus+', errorThrown = '+errorThrown+', override: '+jqXHR);
                if (textStatus == 'timeout') {
                    alert(ajaxTimeoutRestoreCustomerErrorMessage);
                }
            },
        }).done(function(response) {
            // -----
            // Handle any redirects required, based on the AJAX response's status.
            //
            checkForRedirect(response);

            jQuery('#checkoutOneGuestInfo').html(response.infoHtml);
            restoreAddressValues('bill', '#checkoutOneBillto');
        });
    }

    function saveCustomerInfo()
    {
        zcLog2Console('saveCustomerInfo, starts ...');
        zcJS.ajax({
            url: 'ajax.php?act=ajaxOnePageCheckout&method=validateCustomerInfo',
            data: jQuery('#checkoutOneGuestInfo input, #checkoutOneGuestInfo select').serialize(),
            timeout: shippingTimeout,
            error: function (jqXHR, textStatus, errorThrown) {
                zcLog2Console('error: status='+textStatus+', errorThrown = '+errorThrown+', override: '+jqXHR);
                if (textStatus == 'timeout') {
                    alert(ajaxTimeoutValidateCustomerErrorMessage);
                }
            },
        }).done(function(response) {
            // -----
            // Handle any redirects required, based on the AJAX response's status.
            //
            checkForRedirect(response);

            // -----
            // If the response returns a non-empty array of messages, there were one or more
            // "issues" with the submitted information.  Highlight the errant fields and display
            // the messages at the bottom of the guest-information block.
            //
            var messageBlock = '#messages-guest';
            if (response.messages.length != 0) {
                var focusSet = false;
                jQuery(messageBlock).html('<ul></ul>').addClass('opc-error');
                jQuery('#checkoutOneGuestInfo input').removeClass('opc-error');
                jQuery.each(response.messages, function(field_name, message) {
                    jQuery(messageBlock+' ul').append('<li>'+message+'</li>');
                    if (jQuery('input[name="'+field_name+'"]').length) {
                        jQuery('input[name="'+field_name+'"]').addClass('opc-error').removeClass('opc-changed');
                        if (!focusSet) {
                            focusSet = true;
                            jQuery('input[name="'+field_name+'"]').focus();
                        }
                    }
                });
            } else {
                saveAddressValues('bill', '#checkoutOneBillto');
            }
        });
    }
});
