=== Bop Search Box Item Type For Nav Menus ===
Contributors: joe_bopper
Tags: bop, nav, menu, nav menu, nav menu item type, search, search box, navigation
Requires at least: 3.4
Tested up to: 4.1
Stable tag: trunk
License: GPLv2
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Adds search box as a choice of item in navigation menus.

== Description ==
Adds search box as a choice of item in navigation menus.

Features include:
*search box available as a choice in the admin area for navigation menus,
*as many boxes can be added to a nav menu as one likes,
*search boxes can be added as children of other menu items (useful for, e.g., mega-menus),
*capacity to add label, placeholder and css classes from the admin area to each search box,
*the hook get_nav_search_box_form to customise the html output of these search boxes,
*lightweight,
*in keeping styles, html, behaviour, etc., with wordpress defaults,
*works straight out of the box, no configuration needed

== Installation ==
Simply install and search box should appear as an option in the Appearance>Menus section of the admin area upon activation. No configuration needed.

== Frequently Asked Questions ==
Q: How do I modify the html output of the search box?

A: Use the filter hook get_nav_search_box_form and return the html you want to see. For example:

function my_search_form( $current_form, $item, $depth, $args ){
  $new_form = \'...my_html...\';
  return $new_form;
}
add_filter( \'get_nav_search_box_form\', \'my_search_form\', 10, 4 );

Keep in mind that this is being accessed as part of a walk and that $item, $depth and $args are the same as in Walker_Nav_Menu::start_el() ( Link: https://developer.wordpress.org/reference/classes/walker_nav_menu/start_el/ ). Try to use some of the features demonstrated in the code there.

== Changelog ==
v1.0: initial release.

== Upgrade Notice ==
No existing upgrades.

== Screenshots ==
1. assets/logo.png
