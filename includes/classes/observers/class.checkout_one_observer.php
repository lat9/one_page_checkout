<?php
// -----
// Part of the One-Page Checkout plugin, provided under GPL 2.0 license by lat9 (cindy@vinosdefrutastropicales.com).
// Copyright (C) 2013-2016, Vinos de Frutas Tropicales.  All rights reserved.
//
if (!defined('IS_ADMIN_FLAG')) {
  die('Illegal Access');
}

class checkout_one_observer extends base 
{
    public function __construct() 
    {
        global $current_page_base;
        $this->enabled = false;
        
        require (DIR_WS_CLASSES . 'Vinos_Browser.php');
        $browser = new Vinos_Browser ();
        $unsupported_browser = ($browser->getBrowser () == Vinos_Browser::BROWSER_IE && $browser->getVersion () < 9);
        $this->browser = $browser->getBrowser() . '::' . $browser->getVersion ();
        
        if (!$unsupported_browser && defined ('CHECKOUT_ONE_ENABLED') && CHECKOUT_ONE_ENABLED == 'true') {
            $this->enabled = true;
            $this->debug = (CHECKOUT_ONE_DEBUG == 'true' || CHECKOUT_ONE_DEBUG == 'full');
            if ($this->debug && CHECKOUT_ONE_DEBUG_EXTRA != '' && CHECKOUT_ONE_DEBUG_EXTRA != '*') {
                $debug_customers = explode (',', CHECKOUT_ONE_DEBUG_EXTRA);
                if (!in_array ($_SESSION['customer_id'], $debug_customers)) {
                    $this->debug = false;
                }
            }
            $this->debug_logfile = DIR_FS_LOGS . '/myDEBUG-one_page_checkout-' . $_SESSION['customer_id'] . '.log';
            $this->current_page_base = $current_page_base;
            
            $this->attach ($this, array ('NOTIFY_HEADER_START_CHECKOUT_SHIPPING', 'NOTIFY_HEADER_START_CHECKOUT_PAYMENT', 'NOTIFY_HEADER_START_CHECKOUT_SHIPPING_ADDRESS', 'NOTIFY_HEADER_START_CHECKOUT_CONFIRMATION', 'NOTIFY_HEADER_END_CHECKOUT_SUCCESS'));
        }
    }
  
    public function update (&$class, $eventID, $p1a) 
    {
        switch ($eventID) {     
            case 'NOTIFY_HEADER_START_CHECKOUT_SHIPPING':
            case 'NOTIFY_HEADER_START_CHECKOUT_PAYMENT':
            case 'NOTIFY_HEADER_START_CHECKOUT_CONFIRMATION':
                $this->debug_message ('checkout_one redirect: ', true, 'checkout_one_observer');
                zen_redirect (zen_href_link (FILENAME_CHECKOUT_ONE, zen_get_all_get_params (), 'SSL'));
                break;
                
            case 'NOTIFY_HEADER_START_CHECKOUT_SHIPPING_ADDRESS':
                $_SESSION['shipping_billing'] = false;
                break;
      
            case 'NOTIFY_HEADER_END_CHECKOUT_SUCCESS':
                unset ($GLOBALS[_SESSION]['shipping_billing']);
                break;
 
            default:
                break;
        }
    }
    
    public function debug_message ($message, $include_request = false, $other_caller = '')
    {
        if ($this->debug) {
            $extra_info = '';
            if ($include_request) {
                $the_request = $_REQUEST;
                foreach ($the_request as $name => $value) {
                    if (strpos ($name, 'cc_number') !== false || strpos ($name, 'cc_cvv') !== false || strpos ($name, 'card-number') !== false || strpos ($name, 'cv2-number') !== false) {
                        unset ($the_request[$name]);
                    }
                }
                $extra_info = print_r ($the_request, true);
            }
            
            // -----
            // Change any occurrences of [code] to ["code"] in the logs so that they can be properly posted between [CODE} tags on the Zen Cart forums.
            //
            $message = str_replace ('[code]', '["code"]', $message);
            error_log (date ('Y-m-d H:i:s') . ' ' . (($other_caller != '') ? $other_caller : $this->current_page_base) . ": $message$extra_info" . PHP_EOL, 3, $this->debug_logfile);
            $this->notify ($message);
        }
    }
    
    public function hashSession ()
    {
        if (isset ($_SESSION['shipping']) && !isset ($_SESSION['shipping']['extras'])) {
            $_SESSION['shipping']['extras'] = '';
        }
        $hash_values = var_export ($_SESSION, true);
//        $this->debug_message ("hashSession returning an md5 of $hash_values", false, 'checkout_one_observer');
        return md5 ($hash_values);
    }
 
}