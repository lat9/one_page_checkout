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
            zcLog2Console ('collectsCartDataOnsite: submitting form');
            $('form[name="checkout_payment"]')[0].submit();
        }
    });
    return false;
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
    $('form[name="checkout_payment"]').submit(function() {
        zcLog2Console ('Form submitted, orderConfirmed ('+orderConfirmed+')');
        if (orderConfirmed) {
            $('#checkoutOneSubmit').attr('disabled', true);
<?php 
if ($flagOnSubmit) { 
?>
            var formPassed = check_form();
            zcLog2Console ('Form checked, passed ('+formPassed+')');
            if (formPassed == false) {
                $('#checkoutOneSubmit').attr('disabled', false);
            }
            return formPassed;
<?php 
} 
?>
        }
    });
    
    $('#checkoutShippingMethod input:radio').click(function() {
        var shippingSelected = $( "input[name=shipping]:checked" ).val();
        zcLog2Console( 'Updating shipping method to '+shippingSelected );
        zcJS.ajax({
            url: "ajax.php?act=ajaxOnePageCheckout&method=updateShipping",
            data: {shipping: shippingSelected}
        }).done(function( response ) {
            $('#orderTotalDivs').html(response.orderTotalHtml);
        });
    });
});
//--></script>