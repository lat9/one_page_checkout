<?php
// -----
// Part of the One-Page Checkout plugin, provided under GPL 2.0 license by lat9 (cindy@vinosdefrutastropicales.com).
// Copyright (C) 2013-2016, Vinos de Frutas Tropicales.  All rights reserved.
//
?>
<script type="text/javascript"><!--
var selected;
var submitter = null;

function concatExpiresFields(fields) 
{
    return $(":input[name=" + fields[0] + "]").val() + $(":input[name=" + fields[1] + "]").val();
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
        zcLog2Console( 'Current order total: '+total );
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
?>
function collectsCardDataOnsite(paymentValue)
{
    zcJS.ajax({
        url: "ajax.php?act=ajaxPayment&method=doesCollectsCardDataOnsite",
        data: {paymentValue: paymentValue}
    }).done(function( response ) {
        if (response.data == true) {
            var str = $('form[name="checkout_payment"]').serializeArray();

            zcJS.ajax({
                url: "ajax.php?act=ajaxPayment&method=prepareConfirmation",
                data: str
            }).done(function( response ) {
                $('#checkoutPayment').hide();
                $('#navBreadCrumb').html(response.breadCrumbHtml);
                $('#checkoutPayment').before(response.confirmationHtml);
                $(document).attr('title', response.pageTitle);

            });
        } else {
            $('form[name="checkout_payment"]')[0].submit();
        }
    });
    return false;
}

function doesCollectsCardDataOnsite(paymentValue)
{
    if ($('#'+paymentValue+'_collects_onsite').val()) {
        if ($('#pmt-'+paymentValue).is(':checked')) {
            return true;
        }
    }
    return false;
}

function doCollectsCardDataOnsite(paymentValue)
{
    var str = $('form[name="checkout_payment"]').serializeArray();

    zcJS.ajax({
        url: "ajax.php?act=ajaxPayment&method=prepareConfirmation",
        data: str
    }).done(function( response ) {
        $('#checkoutPayment').hide();
        $('#navBreadCrumb').html(response.breadCrumbHtml);
        $('#checkoutPayment').before(response.confirmationHtml);
        $(document).attr('title', response.pageTitle);
    });
}

var orderConfirmed = 0;
function setOrderConfirmed (value)
{
    orderConfirmed = value;
    $('#confirm-the-order').val( value );
    zcLog2Console ('Setting orderConfirmed ('+value+'), submitter ('+submitter+')');
}

$(document).ready(function(){   
    setOrderConfirmed (0);
    $('#checkoutOneShippingFlag').show();
    
    var timeoutUrl = '<?php echo zen_href_link (FILENAME_LOGIN, '', 'SSL'); ?>';
    var timeoutErrorMessage = '<?php echo JS_ERROR_SESSION_TIMED_OUT; ?>';
    
    function focusOnShipping ()
    {
        var scrollPos =  $( "#checkoutShippingMethod" ).offset().top;
        $(window).scrollTop( scrollPos );
    }
    
    function changeShippingSubmitForm (type, event)
    {
        var shippingSelected = $( "input[name=shipping]:checked" );
        if (shippingSelected.length == 0) {
            alert( '<?php echo ERROR_NO_SHIPPING_SELECTED; ?>' );
            event.preventDefault();
            event.stopPropagation();
            focusOnShipping();
        } else {
            shippingSelected = shippingSelected.val();
<?php
    // -----
    // If a shipping-module has required inputs that should accompany the post, format the necessary
    // jQuery to gather those inputs.
    //
    $additional_shipping_inputs = array ();
    foreach ($quotes as $current_quote) {
        if (isset ($current_quote['required_input_names']) && is_array ($current_quote['required_input_names'])) {
            foreach ($current_quote['required_input_names'] as $current_input_name => $selection_required) {
                $variable_name = base::camelize ($current_input_name);
?>
            var <?php echo $variable_name; ?> = $( "input[name=<?php echo $current_input_name; ?>]<?php echo ($selection_required) ? ':checked' : ''; ?>" ).val();
<?php
                $additional_shipping_inputs[$current_input_name] = $variable_name;
            }
        }
    }

?>
            zcLog2Console( 'Updating shipping method to '+shippingSelected );
            zcJS.ajax({
                url: "ajax.php?act=ajaxOnePageCheckout&method=updateShipping",
                data: {
                    shipping: shippingSelected,
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
                error: function (jqXHR, textStatus, errorThrown) {
                    zcLog2Console('error: status='+textStatus+', errorThrown = '+errorThrown+', override: '+jqXHR);
                    if (textStatus == 'timeout') {
                        alert( timeoutErrorMessage );
                        $(location).attr( 'href', timeoutUrl );
                    }
                },
            }).done(function( response ) {
                $( '#orderTotalDivs' ).html(response.orderTotalHtml);
                
                var shippingError = false;
                $( '#otshipping, #otshipping+br' ).show ();
                if (response.status != 'ok') {
                    if (response.status == 'timeout') {
                        alert( timeoutErrorMessage );
                        $(location).attr( 'href', timeoutUrl );
                    }
                    
                    shippingError = true;
                    if (response.status == 'invalid') {
                        $( '#checkoutShippingMethod input[name=shipping]' ).prop( 'checked', false );
                        $( '#checkoutShippingChoices' ).html( response.shippingHtml );
                        $( '#checkoutShippingChoices' ).on( 'click', 'input[name=shipping]', function ( event ) {
                            changeShippingSubmitForm( 'shipping-only', event );
                        });
                        $( '#otshipping, #otshipping+br' ).hide ();
                        focusOnShipping();
                    }
                    if (response.errorMessage != '') {
                        if (type == 'submit') {
                            alert( response.errorMessage );
                        }
                    }
                }  
                zcLog2Console( 'Shipping method updated, error: '+shippingError ); 
                
                if (type == 'submit') {
                    if (shippingError == true) {
                        zcLog2Console( 'Shipping error, correct to proceed.' );
                        event.stopPropagation ();
                    } else {
                        zcLog2Console ('Form submitted, orderConfirmed ('+orderConfirmed+')');
                        if (orderConfirmed) {
                            $( '#confirm-order' ).attr( 'disabled', true );
<?php   
if ($flagOnSubmit) { 
?>
                            var formPassed = check_form();
                            zcLog2Console ('Form checked, passed ('+formPassed+')');
                            if (formPassed == false) {
                                $( '#confirm-order' ).attr('disabled', false);
                            }
                            return formPassed;
<?php 
} 
?>
                        }
                    }
                }
            });
        }           
    }
    
    $( '#checkoutShippingMethod input[name=shipping]' ).click(function( event ) {
        changeShippingSubmitForm ('shipping-only', event);
    });
    
    $( 'form[name="checkout_payment"]' ).submit(function( event ) {
        if (orderConfirmed) {
            return changeShippingSubmitForm ('submit', event);
        }
    });
});
//--></script>