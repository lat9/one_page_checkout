<?php
// -----
// A One-Page-Checkout diagnostic tool to identify what hash-value is causing a redirection
// back to the checkout_one page upon confirmation.
//
// Usage: A browser-initiated command-line script to check a specific OPC log file for redirection errors.
// Log into your site's Zen Cart admin-console **as a superuser** and enter in the browser's address-bar:
//
// yoursite.com/YOUR_ADMIN/opc_debug_redirection_error.php?id=xx
// 
// The processing will inspect the file /logs/one_page_checkout-xx.log (xx is normally the customers-id followed by a date)
// for the presence of a session-data error that causes a redirection back to the checkout_one page.
//
require 'includes/application_top.php';

// -----
// Helper class to show difference.  See https://www.bitbook.io/how-to-diff-two-strings-in-php-and-show-in-html/ for
// the source.
//
class Diff 
{
    public static function diffArray($old, $new)
    {
        $matrix = array();
        $maxlen = 0;
        foreach ($old as $oindex => $ovalue) {
            $nkeys = array_keys($new, $ovalue);
            foreach ($nkeys as $nindex) {
                $matrix[$oindex][$nindex] = isset($matrix[$oindex - 1][$nindex - 1]) ? $matrix[$oindex - 1][$nindex - 1] + 1 : 1;
                if ($matrix[$oindex][$nindex] > $maxlen) {
                    $maxlen = $matrix[$oindex][$nindex];
                    $omax = $oindex + 1 - $maxlen;
                    $nmax = $nindex + 1 - $maxlen;
                }
            }
        }
        if ($maxlen == 0) {
            return array(array('d'=>$old, 'i'=>$new));
        }
        return array_merge(
            self::diffArray(array_slice($old, 0, $omax), array_slice($new, 0, $nmax)),
            array_slice($new, $nmax, $maxlen),
            self::diffArray(array_slice($old, $omax + $maxlen), array_slice($new, $nmax + $maxlen))
        );
    }
 
    public static function htmlDiff($old, $new)
    {
        $ret = '';
        $diff = self::diffArray(explode(' ', $old), explode(' ', $new));
        foreach ($diff as $k) {
            if (is_array($k)) {
                $ret .= (!empty($k['d'])?'<del>'.implode(' ',$k['d']).'</del> ':'').(!empty($k['i'])?'<ins>'.implode(' ',$k['i']).'</ins> ':'');
            } else {
                $ret .= $k . ' ';
            }
        }
        return $ret;
    }
}

if (empty($_GET['id'])) {
    echo 'Usage:  Enter ' . zen_href_link('opc_debug_redirection_error', 'id=xx') . ', where <em>xx</em> identifies the trailing characters on the One-Page Checkout log-file you wish to inspect.';
} else {
    $log_id = zen_sanitize_string($_GET['id']);
    $log_filename = DIR_FS_LOGS . '/one_page_checkout-' . $log_id . '.log';
    if (!file_exists($log_filename)) {
        echo '<b>Error</b>: The file ' . $log_filename . ' does not exist.  Please try again.';
    } else {
        $fh = fopen($log_filename, 'r');
        if ($fh === false) {
            echo '<b>Error</b>: Could not open ' . $log_filename . ' for reading.';
        } else {
            $action = 'find-first';
            while (($line = fgets($fh)) !== false) {
                switch ($action) {
                    // -----
                    // Looking for the first of the hashSession pairs' start.
                    //
                    case 'find-first':
                        if (strpos($line, 'checkout_one_observer: hashSession') !== false) {
                            $first_hash_date = substr($line, 0, 19);
                            $action = 'get-first';
                            $first_hash = '';
                        }
                        break;

                    // -----
                    // Gathering the session settings on entry, until the ending indication of that
                    // session-hash output.  Next action is to look for the session-settings after
                    // the shipping, payment and order-totals have been run.
                    //
                    case 'get-first':
                        if (strpos($line, 'checkout_one_confirmation: Initial order information') !== false) {
                            $action = 'find-next';
                        } else {
                            $first_hash .= $line;
                        }
                        break;
                        
                    // -----
                    // Looking for the start of the next (i.e. final) hash of the session for comparison.
                    //
                    case 'find-next':
                        if (strpos($line, 'checkout_one_observer: hashSession') !== false) {
                            $last_hash_date = substr($line, 0, 19);
                            $action = 'get-last';
                            $last_hash = '';
                        }
                        break;
                        
                    // -----
                    // Gathering the session settings after the payment, shipping and order-totals have been
                    // run for comparison.
                    //
                    case 'get-last':
                        // -----
                        // If the order-addresses are to be updated, that implies that a matching hash
                        // was found.  Look for a possible start of another.
                        //
                        if (strpos($line, 'OnePageCheckout: updateOrderAddresses') !== false) {
                            echo $first_hash_date . ': Found a successful entry to the <code>checkout_one_confirmation</code> page.<hr><br>';
                            $action = 'find-first';
                        // -----
                        // If a redirection back to checkout_one is detected, output a report of the hash-values' differences.
                        //
                        } elseif (strpos($line, 'checkout_one_confirmation: Something causing redirection back to checkout_one') !== false) {
                            echo $first_hash_date . ': Found a redirection back to <code>checkout_one</code>, difference follows.<br><br>';
                            echo Diff::htmlDiff($first_hash, $last_hash);
                            echo '<hr><br>';
                            $action = 'find-first';
                        // -----
                        // Otherwise, keep gathering that final hash's information.
                        //
                        } else {
                            $last_hash .= $line;
                        }
                        break;
                        
                    default:
                        break;
                }
            }
            fclose($fh);
            echo '<br>Report completed.';
        }
    }
}

require DIR_WS_INCLUDES . 'application_bottom.php';

