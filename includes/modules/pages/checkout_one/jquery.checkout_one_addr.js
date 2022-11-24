// -----
// Part of the One-Page Checkout plugin, provided under GPL 2.0 license by lat9.
// Copyright (C) 2018-2022, Vinos de Frutas Tropicales.  All rights reserved.
//
// Last updated: OPC v2.4.4
//

// -----
// Main processing section, starts when the browser has finished and the page is "ready" ...
//
// Note: Starting with OPC v2.3.2, this script is *always* loaded.
//
jQuery(document).ready(function() {
    var last_country_bill = jQuery('#country-bill option:selected').val();
    var last_country_ship = jQuery('#country-ship option:selected').val();
    
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
    // Note: Monitoring *all* input changes, too, to workaround browsers' autofill processing.
    //
    jQuery(document).on('change', '#country-bill, #checkoutOneBillto input', function(event) {
        if (last_country_bill != jQuery('#country-bill option:selected').val()) {
            last_country_bill = jQuery('#country-bill option:selected').val();
            updateCountryZones('bill', jQuery('#country-bill option:selected').val());
        }
    });
    jQuery(document).on('change', '#country-ship, #checkoutOneSendto input', function(event) {
        if (last_country_ship != jQuery('#country-ship option:selected').val()) {
            last_country_ship = jQuery('#country-ship option:selected').val();
            updateCountryZones('ship', jQuery('#country-ship option:selected').val());
        }
    });

    // -----
    // This function provides the processing needed when a country has been changed.  It makes
    // use of the c2z (countries-to-zones) array, built and provided by the jscript_main.php's
    // processing.  The value for "textPleaseSelect" is set there, too.
    //
    function updateCountryZones(which, selected_country)
    {
        var countryHasZones = false;
        var countryZones = '<option selected="selected" value="">' + textPleaseSelect + '<' + '/option>';
        jQuery.each(JSON.parse(c2z), function(country_id, country_zones) {
            if (selected_country == country_id) {
                countryHasZones = true;
                var countryZonesArray = [];
                jQuery.each(country_zones, function(zone_id, zone_name) {
                    countryZonesArray[zone_name] = zone_id;
                });
                Object.keys(countryZonesArray).sort().forEach(function(zone_name) {
                    countryZones += '<option value="' + countryZonesArray[zone_name] + '">' + zone_name + '<' + '/option>';
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