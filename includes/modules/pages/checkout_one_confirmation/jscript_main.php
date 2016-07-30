<?php
// -----
// Part of the One-Page Checkout plugin, provided under GPL 2.0 license by lat9 (cindy@vinosdefrutastropicales.com).
// Copyright (C) 2013-2016, Vinos de Frutas Tropicales.  All rights reserved.
//
?>
<script type="text/javascript"><!--
$(document).ready(function(){
    $('#navBreadCrumb').hide();
    $('#checkoutOneConfirmationLoading').show();
    $('body', 'html').css({ 
        "overflow": "hidden",
        "height": "100%",
        "background": "none"
    });
    $('form[name="confirmation_one"]').submit();
});
//--></script>