<?php
/*
   Plugin Name: WL Opening Hours
   Plugin URI: https://wordpress.org/plugins/wl-opening-hours/
   Description: Opening-hours shortcodes and widgets for any number of departments/venues
   Author: WP Hosting
   Author URI: https://webloft.no/
   Text-domain: wl-opening-hours
   Domain Path: /languages
   Version: 1.0.1
   License: AGPLv3 or later
   License URI: http://www.gnu.org/licenses/agpl-3.0.html

   This file is part of the WordPress plugin WL Opening Hours 
   Copyright (C) 2018 WP Hosting AS

   Checkout with WL Opening Hours is free software: you can redistribute it and/or modify
   it under the terms of the GNU Affero General Public License as published by
   the Free Software Foundation, either version 3 of the License, or
   (at your option) any later version.

   Checkout with WL Opening Hours is distributed in the hope that it will be useful,
   but WITHOUT ANY WARRANTY; without even the implied warranty of
   MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
   GNU Affero General Public License for more details.

   You should have received a copy of the GNU Affero General Public License
   along with this program.  If not, see <http://www.gnu.org/licenses/>.



 */

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

require_once(dirname(__FILE__) . "/WLOpeningHours.class.php");
require_once(dirname(__FILE__) . "/WLOpeningHoursWidget.class.php");

    /* Instantiate the singleton, stash it in a global and add hooks. IOK 2018-10-26 */
global $WLOpeningHours;
$WLOpeningHours = new WLOpeningHours();
register_activation_hook(__FILE__,array($WLOpeningHours,'activate'));
register_uninstall_hook(__FILE__, 'WLOpeningHours::uninstall');

if (is_admin()) {
        add_action('admin_init',array($WLOpeningHours,'admin_init'));
        add_action('admin_menu',array($WLOpeningHours,'admin_menu'));
} else {
        add_action('wp_footer', array($WLOpeningHours,'footer'));
}
add_action('init',array($WLOpeningHours,'init'));
add_action( 'plugins_loaded', array($WLOpeningHours,'plugins_loaded'));
add_action('widgets_init', array($WLOpeningHours,'widgets_init'));



?>
