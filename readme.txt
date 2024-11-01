=== WL Opening Hours ===
Contributors: wphostingdev, iverok
Tags: openinghours 
Requires at least: 4.9
Tested up to: 5.7.0
Stable tag: trunk
Requires PHP: 7.0
License: AGPLv3 or later
License URI: http://www.gnu.org/licenses/agpl-3.0.html


== Description ==

*Manage opening hours for several venues using custom post types shortcodes and widgets*

This plugin allows you to manage several sets of opening hours as a custom post type. Each set can be associated with a number of venues. You can display these individually, or you can automatically show the current (ie most recently published) opening hours for a set of venues in one of three views.

If you have several opening hours for a given venue, the most recently published will be used. Thus you can use post scheduling to automatically change opening hours on a given date.

=== How to install the plugin ===
1.  Install the plugin using WordPress’ [built-in installer](https://codex.wordpress.org/Managing_Plugins#Installing_Plugins). The plugin can also be installed manually by upload the plugin files to the /wp-content/plugins/ directory.
2. Activate the plugin through the 'Plugins' screen in WordPress.
3. Go to the WL Opening Hours list, and add your hours
4. Display the hours through widget or shortcode

= Shortcodes =
If not using widgets, the plugin provides a shortcode with these forms:
 * `[show-opening-hours id='postid']` - Show a single set of opening hours using the post id
 * `[show-opening-hours venue='venueslug']` - Show the most recent set of opening hours for the given venue
 * `[show-opening-hours venues='comma separated list of venue slugs' view="one of tabbed,list,accordion"]` - Show several venues' opening hours with the given view
 * `[show-opening-hours view='one of tabbed,list,accordion']` - Show all venues opening hours with the given view


= Filters and Hooks for customization =
There are several filters/hooks you can use to customize the behaviour of this plugin:
 * Filter: 'wl-opening-hours-template-path': Takes a path, a view indicator and data for the display of opening hours, should return a path which will be loaded as a template for displaying the hours
 * Filter: 'wl_openinghours_view': Takes the HTML output for a set of opening hours, the data that genereated it and a view code, should return html
 * Filter: 'wl_opening_hours_views': Takes a map from view name to view slug and should return the same. Use this to extend the plugin with new views together with the above filters

= Customizing templates =
If you copy the files in the "templates" directory of this plugin to a "wl-opening-hours" subdirectory of your child-theme, these will be used instead of the provided templates. The $opening_hours variable will then contain an array from Venue to opening hours, with one of the variables being the post itself. 

= CSS =
Please see the file css/wl-opening-hours.css for the classes used - these are all overridable in your theme

== Changelog ==

= 2019.10.17 version 1.0.1 =
* Minor fixes

= 2019.08.26 version 1.0.0 =
* Initial release

