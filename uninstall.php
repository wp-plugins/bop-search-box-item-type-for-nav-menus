<?php
if ( !defined( 'WP_UNINSTALL_PLUGIN' ) ) {
    exit();
}

$menus = wp_get_nav_menus();

foreach( $menus as $menu ){
	$items = wp_get_nav_menu_items($menu);
	foreach( $items as $item ){
		if( $item->type == 'search' ){
			wp_delete_post( $item->db_id );
		}
	}
}