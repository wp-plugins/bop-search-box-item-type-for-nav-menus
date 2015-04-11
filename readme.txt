=== Bop Search Box Item Type For Nav Menus ===
Contributors: joe_bopper
Tags: bop, nav, menu, nav menu, nav menu item type, search, search box, navigation
Requires at least: 3.4
Tested up to: 4.1.1
Stable tag: 1.3.0
License: GPLv2
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Adds search box as a choice of item in navigation menus admin area.

== Description ==

Adds search box as a choice of item in navigation menus admin area.

Features include:

* search box available as a choice in the admin area for navigation menus,
* as many boxes can be added to a nav menu as one likes,
* search boxes can be added as children of other menu items (useful for, e.g., mega-menus),
* capacity to add label, placeholder and css classes from the admin area to each search box,
* the hook get_nav_search_box_form to customise the html output of these search boxes,
* lightweight,
* in keeping styles, html, behaviour, etc., with wordpress defaults,
* works straight out of the box, no configuration needed.

== Installation ==

Simply install and search box should appear as an option in the *Appearance > Menus* section of the admin area upon activation. No configuration needed.

If it fails to appear, open the screen options tab in your Menus admin page and check Search Box.

== Frequently Asked Questions ==
= Q: How do I modify the html output of the search box? =

A: Use the filter hook *get_nav_search_box_form* and return the html you want to see. For example:

`function my_search_form( $current_form, $item, $depth, $args ){
  $new_form = '...my_html...';
  return $new_form;
}
add_filter( 'get_nav_search_box_form', 'my_search_form', 10, 4 );`

Keep in mind that this is being accessed as part of a walk and that $item, $depth and $args are the same as in [Walker_Nav_Menu::start_el()](https://developer.wordpress.org/reference/classes/walker_nav_menu/start_el/). Try to use some of the features demonstrated in the code there.

= Q: Why isn't Search Box appearing as a possible item for menus? =

A: It is most likely that you have it turned off in the Screen Options tab on your Menus admin page.

= Q: Why can't I modify certain fields for my Search Box menu item, e.g., css classes? =

A: It is most likely that you have it turned off in the Screen Options tab on your Menus admin page.

== Changelog ==

= v1.3.0 =
* Abandoned the fix to hidden metaboxes as that area of wordpress itself is quite buggy.
* Moved developer info into a tab in the screen help section
* Moved js to inline document as it is much more brief than it was previously and there's little point in having a separate file

= v1.2.0 =
* Found a workaround to some poor core wp code which shows two unexpected notices when adding a search menu item by ajax into a menu in wp-admin/nav-menus.php.
* Added a fix to wordpress's bizarre decision to hide the plugin from new nav-menu users. Not a problem for most users as most make a menu before activating this plugin.
* Removed some redundant unused code
* Added code comments to help with debugging, etc.

= v1.1.0 =
* Added js and css so the plugin actually works.
* All previous versions updated to include the js and css files which should have shipped in the first place.

= v1.0.1: Very minor changes =
* Added protection against plugin duplication.
* ReadMe improved for greater legibility.

= v1.0.0: Initial release =

== Screenshots ==

1. A view of a *wp-admin/nav-menus.php* screen with the plugin enabled.

2. A view of the expanded search box menu item in the admin area.

== Upgrade Notice ==

= v1.3.0 =
Some small changes. Check the changelog for further details.

= v1.2.0 =
A fair amount of change, but there shouldn't be too much difference to the user experience. In essence, a couple of minor fixes and a clean up.

= v1.1.0 =
Important update. All previous downloads should update to this. The plugins js and css were missing previously. No thanks go to wp-svn for causing this mistake in the first place.

= 1.0.1 =
Very minor update to protect against plugin duplication.