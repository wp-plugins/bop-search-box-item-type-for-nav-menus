<?php 
/*
Plugin Name: Bop Search Box Item Type For Nav Menus
Description: Adds search box as a choice of item in navigation menus.
Version: 1.0.1
Author: The Bop
Author URI: http://thebop.biz
License: GPL2
License URI: https://www.gnu.org/licenses/gpl-2.0.html
Domain Path: /languages
Text Domain: bop-nav-search-box-item
*/

defined('ABSPATH') or die("Absolutely not!");

if( ! class_exists( 'Bop_Nav_Search_Box_Item' ) && ! function_exists( 'bop_nav_search_box_item' ) ):

class Bop_Nav_Search_Box_Item {
	
	const CSSURL = 'assets/css/';
	const JSURL = 'assets/js/';
	
	function __construct(){
		$this->url = plugin_dir_url( __FILE__ );
		
		add_action( 'init', array( $this, 'init' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'admin_enqueue_styles_and_scripts' ) );
	}
	
	function init(){
		add_action( 'admin_init', array( $this, 'admin_init' ) );
		add_filter( 'walker_nav_menu_start_el', array( $this, 'walker_nav_menu_start_el' ), 1, 4 );
	}
	
	function admin_init(){
		$this->add_nav_menu_meta_box();
		
		add_action( 'wp_update_nav_menu_item', array( $this, 'wp_update_nav_menu_item' ), 10, 3 );
		add_filter( 'wp_setup_nav_menu_item', array( $this, 'wp_setup_nav_menu_item' ), 10, 1 );
	}
	
	function add_nav_menu_meta_box(){
		global $pagenow;
		if ( 'nav-menus.php' !== $pagenow ){
            return;
		}
		
		add_meta_box(
			'bop_nav_search_box_item_meta_box'
			,__( 'Search Box', 'bop-nav-search-box-item' )
			,array( $this, 'search_meta_box_render' )
			,'nav-menus'
			,'side'
			,'low'
		);
	}
	
	function search_meta_box_render(){
		global $_nav_menu_placeholder, $nav_menu_selected_id;

		$_nav_menu_placeholder = 0 > $_nav_menu_placeholder ? $_nav_menu_placeholder - 1 : -1;

		?>
		<div class="customlinkdiv" id="searchboxitemdiv">
			<div class="tabs-panel-active">
				<ul class="categorychecklist">
					<li>
						<input type="hidden" name="menu-item[<?php echo $_nav_menu_placeholder; ?>][menu-item-type]" value="search">
						<input type="hidden" name="menu-item[<?php echo $_nav_menu_placeholder; ?>][menu-item-type_label]" value="Search">
						
						<input type="hidden" class="menu-item-title" name="menu-item[<?php echo $_nav_menu_placeholder; ?>][menu-item-title]" value="Search Box">
						<input type="hidden" class="menu-item-url" name="menu-item[<?php echo $_nav_menu_placeholder; ?>][menu-item-url]" value="<?php get_search_link(); ?>">
						<input type="hidden" class="menu-item-classes" name="menu-item[<?php echo $_nav_menu_placeholder; ?>][menu-item-classes]" value="bop-nav-search">
						
						<input type="checkbox" class="menu-item-object-id" name="menu-item[<?php echo $_nav_menu_placeholder; ?>][menu-item-object-id]" value="<?php echo $_nav_menu_placeholder; ?>" checked="true">
					</li>
				</ul>
			</div>

			<p class="button-controls">
				<span class="add-to-menu">
					<input type="submit"<?php wp_nav_menu_disabled_check( $nav_menu_selected_id ); ?> class="button-secondary right" value="<?php esc_attr_e( 'Add to menu', 'bop-nav-search-box-item' ); ?>" name="add-search-menu-item" id="submit-searchboxitemdiv">
					<span class="spinner"></span>
				</span>
			</p>
			
			<?php if( current_user_can( 'manage_options' ) ): ?>
				<p class="howto">
					<?php _e( 'To edit the html output of the search box use the hook <strong>get_nav_search_box_form</strong> as you would the hook <a href="https://developer.wordpress.org/reference/hooks/get_search_form/">get_search_form</a>. The difference between these is that there are three additional arguments passed to the hook. These are: $form (the current html), $item (the nav-menu-item), $depth (the current depth of the menu in the walker), $args (the arguments of the menu as given in the wp_nav_menu function call). That is, the same arguments as passed to <a href="https://developer.wordpress.org/reference/hooks/walker_nav_menu_start_el/">walker_nav_menu_start_el</a> hook.', 'bop-nav-search-box-item' ) ?>
				</p>
				<a href="#" class="bop-nav-search-box-item-view-more hide-if-no-js"><?php _e( 'Show Developer Info', 'bop-nav-search-box-item' ) ?></a>
			<?php endif ?>
		</div>
		<?php
	}
	
	function wp_update_nav_menu_item( $menu_id, $menu_item_db_id, $args ){
		if( $args['menu-item-type'] == 'search' ){
			//update_post_meta( $menu_item_db_id, '_menu_item_type_label', 'Search Box' );
		}
	}
	
	function wp_setup_nav_menu_item( $menu_item ){
		if( $menu_item && $menu_item->type == 'search' ){
			$menu_item->type_label = 'Search Box';
		}
		return $menu_item;
	}
	
	function walker_nav_menu_start_el( $item_output, $item, $depth, $args ){
		if( $item->type != 'search' ){
			return $item_output;
		}
		
		do_action( 'pre_get_search_form' );
		
		$format = current_theme_supports( 'html5', 'search-form' ) ? 'html5' : 'xhtml';
		$format = apply_filters( 'search_form_format', $format );
		
		$classes = empty( $item->classes ) ? array() : (array) $item->classes;
		$classes[] = 'menu-item-' . $item->ID;
		$class_names = join( ' ', apply_filters( 'nav_menu_css_class', array_filter( $classes ), $item, $args, $depth ) );
		$class_names = $class_names ? ' class="' . esc_attr( $class_names ) . '"' : '';
		
		$id = apply_filters( 'nav_menu_item_id', 'menu-item-'. $item->ID, $item, $args, $depth );
		$id = $id ? ' id="' . esc_attr( $id ) . '"' : '';
		
		$item_output = $args->before;
		
		ob_start();
		
		if( 'html5' == $format ):
		?>
		<form <?php echo $id . $class_names ?> role="search" method="get" action="<?php echo home_url( '/' ); ?>">
			<label>
				<span class="screen-reader-text"><?php echo esc_attr_x( apply_filters( 'the_title', $item->title, $item->ID ), 'label', 'bop-nav-search-box-item' ) ?></span>
				<input type="search" class="search-field" placeholder="<?php echo esc_attr_x( $item->attr_title, 'label', 'bop-nav-search-box-item' ) ?>" value="<?php echo get_search_query() ?>" name="s" title="<?php echo esc_attr_x( $item->attr_title, 'label', 'bop-nav-search-box-item' ) ?>" />
			</label>
			<input type="submit" class="search-submit" value="<?php echo esc_attr_x( 'Search', 'submit button' ) ?>" />
		</form>
		<?php else: ?>
		<form <?php echo $id . $class_names ?> role="search" method="get" action="<?php echo home_url( '/' ); ?>">
			<div>
				<label class="screen-reader-text" for="s"><?php echo esc_attr_x( apply_filters( 'the_title', $item->title, $item->ID ), 'label', 'bop-nav-search-box-item' ) ?></label>
				<input type="text" value="<?php echo get_search_query() ?>" name="s" id="s" />
				<input type="submit" id="searchsubmit" value="<?php echo esc_attr_x( 'Search', 'submit button', 'bop-nav-search-box-item' ) ?>" />
			</div>
		</form>
		<?php
		endif;
		
		$item_output .= ob_get_clean();
		
		$item_output .= $args->after;
		
		return apply_filters( 'get_nav_search_box_form', $item_output, $item, $depth, $args );
	}
	
	function admin_enqueue_styles_and_scripts( $hook ){
		if( $hook == 'nav-menus.php' ){
			wp_register_style( 'bop_nav_search_box_item_admin_css', $this->url . self::CSSURL .  'style.css', false, '1.0.0' );
			wp_enqueue_style( 'bop_nav_search_box_item_admin_css' );
			
			wp_register_script( 'bop_nav_search_box_item_admin_script', $this->url . self::JSURL .  'nav-menus.js', array( 'jquery' ), '1.0' );
			wp_enqueue_script( 'bop_nav_search_box_item_admin_script' );
			wp_localize_script( 'bop_nav_search_box_item_admin_script', 'bop_nav_search_box_item_admin_script_local', array( 'show_dev_info'=>__( 'Show Developer Info', 'bop-nav-search-box-item' ), 'hide_dev_info'=>__( 'Hide Developer Info', 'bop-nav-search-box-item' ) ) );
		}
	}
}


function bop_nav_search_box_item(){
	if( ! ( $o = wp_cache_get( 'setup', 'bop_nav_search_box_item' ) ) ){
		$o = new Bop_Nav_Search_Box_Item();
		wp_cache_set( 'setup', $o, 'bop_nav_search_box_item' );
	}
	return $o;
}

bop_nav_search_box_item();

endif;