<?php

/*
Plugin Name: Gravity Forms Cashnet Add-On
Plugin URI: http://www.gravityforms.com
Description: A Cashnet add-on to demonstrate the use of the Add-On Framework
Version: 1.0

------------------------------------------------------------------------
Copyright 2012-2016 Rocketgenius Inc.

This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 2 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program; if not, write to the Free Software
Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA 02111-1307 USA
*/

if (!defined('ABSPATH')) {
	exit;
}



define( 'GF_CASHNET_ADDON_VERSION', '1.0' );

define('GFCASHNET_PLUGIN_ROOT', dirname(__FILE__) . '/');
define('GFCASHNET_PLUGIN_NAME', basename(dirname(__FILE__)) . '/' . basename(__FILE__));
define('GFCASHNET_PLUGIN_FILE', __FILE__);
define('GFCASHNET_PLUGIN_MIN_PHP', '5.6');
define('GFCASHNET_PLUGIN_VERSION', '2.3.5');

require GFCASHNET_PLUGIN_ROOT . 'includes/functions-global.php';
require GFCASHNET_PLUGIN_ROOT . 'includes/functions.php';

add_action( 'gform_loaded', array( 'GF_cashnet_AddOn_Bootstrap', 'load' ), 5 );

class GF_cashnet_AddOn_Bootstrap {


    public static function load() {

        if ( ! method_exists( 'GFForms', 'include_addon_framework' ) ) {
            return;
        }

        require_once( 'class-gfcashnetaddon.php' );

        GFAddOn::register( 'GFcashnetAddOn' );
    }

}

function gf_cashnet_addon() {
    return GFcashnetAddOn::get_instance();
}