<?php
// -----
// Part of the One-Page Checkout plugin, provided under GPL 2.0 license by lat9 (cindy@vinosdefrutastropicales.com).
// Copyright (C) 2023, Vinos de Frutas Tropicales.  All rights reserved.
//
// Last updated for OPC v2.4.6
//
if (!isset($_SESSION['opc']) || $_SESSION['opc']->isGuestCheckout() === false) {
    return;
}
?>
<script>
    jQuery(document).ready(function() {
        jQuery('#PPECbutton a').on('click', function(e) {
            e.preventDefault();
            zcJS.ajax({
                url: 'ajax.php?act=ajaxOnePageCheckout&method=resetGuestCheckout'
            }).done(function() {
                document.location = jQuery('#PPECbutton a').attr('href');
            });
        })
    });
</script>
