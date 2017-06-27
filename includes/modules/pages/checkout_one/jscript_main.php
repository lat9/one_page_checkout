<?php
// -----
// Part of the One-Page Checkout plugin, provided under GPL 2.0 license by lat9 (cindy@vinosdefrutastropicales.com).
// Copyright (C) 2013-2017, Vinos de Frutas Tropicales.  All rights reserved.
//
?>
<script type="text/javascript"><!--
var selected;
var submitter = null;

function concatExpiresFields(fields) 
{
    return jQuery(":input[name=" + fields[0] + "]").val() + jQuery(":input[name=" + fields[1] + "]").val();
}

function popupWindow(url) 
{
    window.open(url,'popupWindow','toolbar=no,location=no,directories=no,status=no,menubar=no,scrollbars=yes,resizable=yes,copyhistory=no,width=450,height=320,screenX=150,screenY=150,top=150,left=150')
}

function couponpopupWindow(url) 
{
    window.open(url,'couponpopupWindow','toolbar=no,location=no,directories=no,status=no,menubar=no,scrollbars=yes,resizable=yes,copyhistory=no,width=450,height=320,screenX=150,screenY=150,top=150,left=150')
}


function submitonce()
{
    var button = document.getElementById("btn_submit");
    button.style.cursor="wait";
    button.disabled = true;
    setTimeout('button_timeout()', 4000);
    return false;
}

function button_timeout() 
{
    var button = document.getElementById("btn_submit");
    button.style.cursor="pointer";
    button.disabled = false;
}

function zcLog2Console (message)
{
    if (window.console) {
        if (typeof(console.log) == 'function') {
            console.log (message);
        }
    }
}

function submitFunction($gv,$total) 
{
    var arg_count = arguments.length;
    submitter = null;
    var arg_list = '';
    for (var i = 0; i < arg_count; i++) {
        arg_list += arguments[i] + ', ';
    }
    zcLog2Console( 'submitFunction, '+arg_count+' arguments: '+arg_list );
    if (arg_count == 2) {
        var ot_total = document.getElementById( 'ottotal' );
        var total = ot_total.children[0].textContent.substr (1);
        zcLog2Console( 'Current order total: '+total+', text: '+ot_total.children[0].textContent );
        document.getElementById( 'current-order-total' ).value = ot_total.children[0].textContent;
        if (total == 0) {
            zcLog2Console( 'Order total is 0, setting submitter' );
            submitter = 1;
        } else {
            var ot_codes = [].slice.call(document.querySelectorAll( '[id^=disc-]' ));
            for (var i = 0; i < ot_codes.length; i++) {
                if (ot_codes[i].value != '') {
                    submitter = 1;
                }
            }
            var ot_gv = document.getElementsByName( 'cot_gv' );
            if (ot_gv.length != 0) {
                zcLog2Console( 'Checking ot_gv value ('+ot_gv[0].value+') against order total ('+total+')' );
                if (ot_gv[0].value >= total) {
                    submitter = 1;
                }
            }
        }
    }
    zcLog2Console('submitFunction, on exit submitter='+submitter);
}

function methodSelect(theMethod) 
{
    if (document.getElementById(theMethod)) {
        document.getElementById(theMethod).checked = 'checked';
    }
}

function setJavaScriptEnabled ()
{
    document.getElementById( 'javascript-enabled' ).value = '1';
}

function shippingIsBilling () 
{
    var shippingAddress = document.getElementById ('checkoutOneShipto');
    if (shippingAddress) {
        if (document.getElementById ('shipping_billing').checked) {
            shippingAddress.className = 'hiddenField';
            shippingAddress.setAttribute ('className', 'hiddenField'); 
        } else {
            shippingAddress.className = 'visibleField';
            shippingAddress.setAttribute ('className', 'visibleField');
        }
    }
}

<?php
// -----
// The "collectsCartDataOnsite" interface built into Zen Cart magically transformed between
// Zen Cart 1.5.4 and 1.5.5, so this module for the One-Page Checkout plugin includes both
// forms.  That way, if a payment module was written for 1.5.4 it'll work, ditto for those
// written for the 1.5.5 method.
//
// Zen Cart 1.5.4 uses the single-function approach (collectsCardDataOnsite) while the 1.5.5
// approach splits the functions int "doesCollectsCardDataOnsite" and "doCollectsCardDataOnsite".
// 
?>
function collectsCardDataOnsite(paymentValue)
{
    zcLog2Console( 'Checking collectsDardDataOnsite('+paymentValue+') ...' );
    zcJS.ajax({
        url: "ajax.php?act=ajaxPayment&method=doesCollectsCardDataOnsite",
        data: {paymentValue: paymentValue}
    }).done(function( response ) {
        if (response.data == true) {
            zcLog2Console( ' ... it does!' );
            var str = jQuery('form[name="checkout_payment"]').serializeArray();

            zcJS.ajax({
                url: "ajax.php?act=ajaxPayment&method=prepareConfirmation",
                data: str
            }).done(function( response ) {
                jQuery('#checkoutPayment').hide();
                jQuery('#navBreadCrumb').html(response.breadCrumbHtml);
                jQuery('#checkoutPayment').before(response.confirmationHtml);
                jQuery(document).attr('title', response.pageTitle);
                jQuery(document).scrollTop( 0 );
            });
        } else {
            zcLog2Console( ' ... it does not, submitting.' );
            jQuery('form[name="checkout_payment"]')[0].submit();
        }
    });
    return false;
}

function doesCollectsCardDataOnsite(paymentValue)
{
    zcLog2Console( 'Checking doesCollectsCardDataOnsite('+paymentValue+') ...' );
    if (jQuery('#'+paymentValue+'_collects_onsite').val()) {
        if (jQuery('#pmt-'+paymentValue).is(':checked')) {
            zcLog2Console( '... it does!' );
            return true;
        }
    }
    zcLog2Console( '... it does not.' );
    return false;
}

function doCollectsCardDataOnsite(paymentValue)
{
    var str = jQuery('form[name="checkout_payment"]').serializeArray();

    zcLog2Console( 'doCollectsCardDataOnsite('+paymentValue+')' );
    zcJS.ajax({
        url: "ajax.php?act=ajaxPayment&method=prepareConfirmation",
        data: str
    }).done(function( response ) {
        jQuery('#checkoutPayment').hide();
        jQuery('#navBreadCrumb').html(response.breadCrumbHtml);
        jQuery('#checkoutPayment').before(response.confirmationHtml);
        jQuery(document).attr('title', response.pageTitle);
        jQuery(document).scrollTop( 0 );
    });
}

var orderConfirmed = 0;
function setOrderConfirmed (value)
{
    orderConfirmed = value;
    jQuery('#confirm-the-order').val( value );
    zcLog2Console ('Setting orderConfirmed ('+value+'), submitter ('+submitter+')');
}

jQuery(document).ready(function(){
    var elementsMissing = false;
    if (jQuery( 'form[name="checkout_payment"]' ).length == 0) {
        elementsMissing = true;
        zcLog2Console( 'Missing form[name="checkout_payment"]' );
    }
    if (jQuery( '#orderTotalDivs' ).length == 0) {
        elementsMissing = true;
        zcLog2Console ( 'Missing #orderTotalDivs' );
    }
    if (jQuery( '#current-order-total' ).length == 0) {
        elementsMissing = true;
        zcLog2Console ( 'Missing #current-order-total' );
    }
<?php
if (!$is_virtual_order) {
?>
    if (jQuery( '#otshipping' ).length == 0) {
        elementsMissing = true;
        zcLog2Console ( 'Missing #otshipping' );
    }
<?php
}
?>
    if (elementsMissing) {
        alert( 'Please contact the store owner; some required elements of this page are missing.' );
    }
    
    // -----
    // Disallow the Enter key (so that all form-submittal actions occur via "click"), except when that
    // key is pressed within a textarea section.
    //
    jQuery(document).on("keypress", ":input:not(textarea)", function(event) {
        return event.keyCode != 13;
    });
    
    setOrderConfirmed (0);
    jQuery( '#checkoutOneShippingFlag' ).show();
    
    zcLog2Console ( 'jQuery version: '+jQuery().jquery );
    
    var timeoutUrl = '<?php echo zen_href_link (FILENAME_LOGIN, '', 'SSL'); ?>';
    var sessionTimeoutErrorMessage = '<?php echo JS_ERROR_SESSION_TIMED_OUT; ?>';
    var ajaxTimeoutErrorMessage = '<?php echo JS_ERROR_AJAX_TIMEOUT; ?>';
    
    function focusOnShipping ()
    {
        var scrollPos =  jQuery( "#checkoutShippingMethod" ).offset().top;
        jQuery(window).scrollTop( scrollPos );
    }

    function changeShippingSubmitForm (type)
    {
        var shippingSelected = jQuery( 'input[name=shipping]' );
        if (shippingSelected.is( ':radio' )) {
            shippingSelected = jQuery( 'input[name=shipping]:checked' );
        }
        if (shippingSelected.length == 0 && type != 'shipping-billing') {
            alert( '<?php echo ERROR_NO_SHIPPING_SELECTED; ?>' );
            focusOnShipping();
        } else {
            shippingSelected = shippingSelected.val();
            var shippingIsBilling = jQuery( '#shipping_billing' ).is( ':checked' );
<?php
    // -----
    // If the current order has generated shipping quotes (i.e. it's got at least one physical product), check to see if a 
    // shipping-module has required inputs that should accompany the post, format the necessary jQuery to gather those inputs.
    //
    if (isset ($quotes) && is_array ($quotes)) {
        $additional_shipping_inputs = array ();
        foreach ($quotes as $current_quote) {
            if (isset ($current_quote['required_input_names']) && is_array ($current_quote['required_input_names'])) {
                foreach ($current_quote['required_input_names'] as $current_input_name => $selection_required) {
                    $variable_name = base::camelize ($current_input_name);
?>
            var <?php echo $variable_name; ?> = jQuery( "input[name=<?php echo $current_input_name; ?>]<?php echo ($selection_required) ? ':checked' : ''; ?>" ).val();
<?php
                    $additional_shipping_inputs[$current_input_name] = $variable_name;
                }
            }
        }
    }
?>
            zcLog2Console( 'Updating shipping method to '+shippingSelected+', processing type: '+type );
            zcJS.ajax({
                url: "ajax.php?act=ajaxOnePageCheckout&method=updateShipping",
                data: {
                    shipping: shippingSelected,
                    shipping_is_billing: shippingIsBilling,
                    shipping_request: type,
<?php
    if (count ($additional_shipping_inputs) != 0) {
        foreach ($additional_shipping_inputs as $current_input_name => $current_input_value) {
?>
                <?php echo $current_input_name;?>: <?php echo $current_input_value; ?>,
<?php
        }
    }
?>
                },
                timeout: <?php echo (int)((defined ('CHECKOUT_ONE_SHIPPING_TIMEOUT')) ? CHECKOUT_ONE_SHIPPING_TIMEOUT : 5000); ?>,
                error: function (jqXHR, textStatus, errorThrown) {
                    zcLog2Console('error: status='+textStatus+', errorThrown = '+errorThrown+', override: '+jqXHR);
                    if (textStatus == 'timeout') {
                        alert( ajaxTimeoutErrorMessage );
                    }
                    shippingError = true;
                },
            }).done(function( response ) {
                jQuery( '#orderTotalDivs' ).html(response.orderTotalHtml);
                
                var shippingError = false;
                jQuery( '#otshipping, #otshipping+br' ).show ();
                if (response.status == 'ok') {
                    if (type == 'shipping-billing') {
                        jQuery( '#checkoutShippingChoices' ).html( response.shippingHtml );
                        jQuery( '#checkoutShippingContentChoose' ).html( response.shippingMessage );
                        jQuery( '#checkoutShippingChoices' ).on( 'click', 'input[name=shipping]', function( event ) {
                            changeShippingSubmitForm( 'shipping-only' );
                        });                        
                    }
                } else {
                    if (response.status == 'timeout') {
                        alert( sessionTimeoutErrorMessage );
                        jQuery(location).attr( 'href', timeoutUrl );
                    }
                    
                    shippingError = true;
                    if (response.status == 'invalid') {
                        jQuery( '#checkoutShippingMethod input[name=shipping]' ).prop( 'checked', false );
                        jQuery( '#checkoutShippingChoices' ).html( response.shippingHtml );
                        jQuery( '#checkoutShippingChoices' ).on( 'click', 'input[name=shipping]', function( event ) {
                            changeShippingSubmitForm( 'shipping-only' );
                        });
                        jQuery( '#otshipping, #otshipping+br' ).hide();
                        focusOnShipping();
                    }
                    if (response.errorMessage != '') {
                        if (type == 'submit' || type == 'shipping-billing' || type == 'submit-cc') {
                            alert( response.errorMessage );
                        }
                    }
                }  
                zcLog2Console( 'Shipping method updated, error: '+shippingError ); 
                
                if (type == 'submit' || type == 'submit-cc') {
                    if (shippingError == true) {
                        zcLog2Console( 'Shipping error, correct to proceed.' );
                    } else {
                        zcLog2Console ('Form submitted, type ('+type+'), orderConfirmed ('+orderConfirmed+')');
                        if (type == 'submit-cc') {
                            jQuery( 'form[name="checkout_payment"]' ).submit();
                        } else if (orderConfirmed) {
                            jQuery( '#confirm-order' ).attr( 'disabled', true );
<?php
// -----
// If there is at least one payment method available, include the jQuery handling to actually submit the form.
//   
if ($flagOnSubmit) { 
?>
                            var formPassed = check_form();
                            zcLog2Console ('Form checked, passed ('+formPassed+')');
                            
                            if (formPassed) {
                                jQuery( '#confirm-order' ).attr('disabled', false);
                                jQuery( 'form[name="checkout_payment"]' ).submit();
                            }
<?php 
} 
?>
                        }
                    }
                }
            });
        }           
    }
    
    jQuery( '#checkoutShippingMethod input[name=shipping]' ).click(function( event ) {
        changeShippingSubmitForm ('shipping-only', event);
    });
    
    
    jQuery( '#shipping_billing' ).click(function( event ) {
        shippingIsBilling();
        changeShippingSubmitForm( 'shipping-billing' );
        
    });
    
    jQuery( '.opc-cc-submit' ).click(function( event ) {
        zcLog2Console( 'Submitting credit-class request' );
        setOrderConfirmed(0);
        changeShippingSubmitForm( 'submit-cc' );
    });
    
    jQuery( '#confirm-order' ).click(function( event ) {
        submitFunction(0,0); 
        setOrderConfirmed (1);

        zcLog2Console( 'Submitting order-creating form' );
        changeShippingSubmitForm( 'submit' );
    });
});
//--></script>