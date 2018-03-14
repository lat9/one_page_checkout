// -----
// Part of the One-Page Checkout plugin, provided under GPL 2.0 license by lat9 (cindy@vinosdefrutastropicales.com).
// Copyright (C) 2018, Vinos de Frutas Tropicales.  All rights reserved.
//

// -----
// Main processing section, starts when the browser has finished and the page is "ready" ...
//
// Note: This script-file is included in the page-load iff the store has enabled state dropdowns!
//
jQuery(document).ready(function() {
    // -----
    // Initialize the display for the dropdown vs. hand-entry of the state fields.  If the initially-selected
    // country doesn't have zones, the dropdown will contain only 1 element ('Type a choice below ...').
    //
    initializeStateZones = function() 
    {
        if (jQuery('#stateZone-bill > option').length == 1) {
            jQuery('#stateZone-bill, #stateZone-bill+span, #stateZone-bill+span+br').hide();
        } else {
            jQuery('#state-bill, #state-bill+span').hide();
            jQuery('#stateZone-bill').show();
        }
        if (jQuery('#stateZone-ship > option').length == 1) {
            jQuery('#stateZone-ship, #stateZone-ship+span, #stateZone-ship+span+br').hide();
        } else {
            jQuery('#state-ship, #state-ship+span').hide();
            jQuery('#stateZone-ship').show();
        }
    }
    initializeStateZones();
    
    // -----
    // Monitor the billing- and shipping-address blocks for changes to the selected country.
    //
    jQuery(document).on('change', '#country-bill', function(event) {
        updateCountryZones('bill', this.value);
    });
    jQuery(document).on('change', '#country-ship', function(event) {
        updateCountryZones('ship', this.value);
    });
    
    // -----
    // This function provides the processing needed when a country has been changed.  It makes
    // use of the c2z (countries-to-zones) array, built and provided by the jscript_main.php's
    // processing.  The value for "textPleaseSelect" is set there, too.
    //
    function updateCountryZones(which, selected_country)
    {
        var countryHasZones = false;
        var countryZones = '<option selected="selected" value="0">' + textPleaseSelect + '</option>';
        jQuery.each(jQuery.parseJSON(c2z), function(country_id, country_zones) {
            if (selected_country == country_id) {
                countryHasZones = true;
                jQuery.each(country_zones, function(zone_id, zone_name) {
                    countryZones += "<option value='" + zone_id + "'>" + zone_name + "</option>";
                });
            }
        });
        if (countryHasZones) {
            jQuery('#state-'+which+', #state-'+which+'+span').hide();
            jQuery('#stateZone-'+which).html(countryZones);
            jQuery('#stateZone-'+which+', #stateZone-'+which+'+span'+', #stateZone-'+which+'+span+br').show();
        } else {
            jQuery('#stateZone-'+which+', #stateZone-'+which+'+span'+', #stateZone-'+which+'+span+br').hide();
            jQuery('#state-'+which+', #state-'+which+'+span').show();
        }
    }
});