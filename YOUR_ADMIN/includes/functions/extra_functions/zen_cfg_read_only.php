<?php
/**
 * zen_cfg_read_only.php
 *
 * @package
 * @copyright Copyright 2004-2015 Andrew Berezin eCommerce-Service.com
 * @copyright Copyright 2003-2015 Zen Cart Development Team
 * @license http://www.zen-cart.com/license/2_0.txt GNU Public License V2.0
 * @version $Id: zen_cfg_read_only.php, v 1.1 22.01.2015 12:52:38 AndrewBerezin $
 */
// -----
// Note: In-core starting with zc158!
//
if (!function_exists('zen_cfg_read_only')) {
    /**
     * Function for configuration values that are read-only, e.g. a plugin's version number
     */
    function zen_cfg_read_only($text, $key = '')
    {
        $name = (!empty($key)) ? 'configuration[' . $key . ']' : 'configuration_value';
        $text = htmlspecialchars_decode($text, ENT_COMPAT);

        return $text . zen_draw_hidden_field($name, $text);
    }
}
