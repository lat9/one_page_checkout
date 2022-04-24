<?php
// -----
// Part of the One-Page Checkout plugin, provided under GPL 2.0 license by lat9 (cindy@vinosdefrutastropicales.com).
// Copyright (C) 2013-2022, Vinos de Frutas Tropicales.  All rights reserved.
//
?>
<script>
    var submitter = null;
    function popupWindow(url) 
    {
        window.open(url,'popupWindow','toolbar=no,location=no,directories=no,status=no,menubar=no,scrollbars=yes,resizable=yes,copyhistory=no,width=450,height=320,screenX=150,screenY=150,top=150,left=150')
    } 

    function couponpopupWindow(url) {
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
<?php
// -----
// In normal circumstances, the form on the checkout_one_confirmation page auto-submits via the following
// jQuery.  This should happen **only if ** the active payment method doesn't require the confirmation page
// to be shown.
//
if (!$confirmation_required) {
?>
    jQuery(document).ready(function(){
        jQuery('body', 'html').css({ 
            "overflow": "hidden",
            "height": "100%",
            "background": "none"
        });
        jQuery('#navBreadCrumb, #bannerSix, #bannerOne, #checkoutOneConfirmationButtons').hide();
        jQuery('#checkoutOneConfirmationLoading').show();
        jQuery('form[name="checkout_confirmation"]').submit();
    });
<?php
}
?>
</script>