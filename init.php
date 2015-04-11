<?php 
/*
Plugin Name: Bop Search Box Item Type For Nav Menus
Description: Adds search box as a choice of item in navigation menus.
Version: 1.3.0
Author: The Bop
Author URI: http://thebop.biz
License: GPL2
License URI: https://www.gnu.org/licenses/gpl-2.0.html
Domain Path: /languages
Text Domain: bop-nav-search-box-item
*/

//Stop entry from outside a wp environment
defined('ABSPATH') or die("Absolutely not!");

//Stop any plugin duplication or conflict
if( ! class_exists( 'Bop_Nav_Search_Box_Item' ) && ! function_exists( 'bop_nav_search_box_item' ) ):

//Load languages for plugin
function bop_nav_search_box_item_load_languages(){
	load_plugin_textdomain( 'bop-nav-search-box-item', false, basename(dirname(__FILE__)).'/languages' );
}
add_action( 'plugins_loaded', 'bop_nav_search_box_item_load_languages' );

//Declare singleton class (module)
class Bop_Nav_Search_Box_Item {
	
	/*
	 * Constants locating the css and js folder
	 *
	 */
	const CSSURL = 'assets/css/';
	const JSURL = 'assets/js/';
	
	/*
	 * Constructor function to set up this object's vars and actions.
	 * As this class is only supposed to be instantiated once, it is safe to
	 * add actions like init, which themselves would be called only once by a
	 * plugin, in general.
	 *
	 */
	function __construct(){
		$this->url = plugin_dir_url( __FILE__ );
		
		//inits
		add_action( 'init', array( $this, 'on_init' ) );
		add_action( 'admin_init', array( $this, 'on_admin_init' ) );
		
		//Admin js & css
		add_action( 'admin_enqueue_scripts', array( $this, 'admin_enqueue_styles_and_scripts' ) );
		
		//Help Tab
		add_action( 'admin_head-nav-menus.php', array( $this, 'on_load_nav_menus_screen_head' ) );
	}
	
	/*
	 * Code to run at init.
	 * This doesn't really need to be here but I have it in every plugin.
	 *
	 */
	function on_init(){
		add_filter( 'walker_nav_menu_start_el', array( $this, 'walker_nav_menu_start_el' ), 1, 4 );
	}
	
	/*
	 * Code to run at admin_init.
	 *
	 */
	function on_admin_init(){
		$this->add_nav_menu_meta_box();
		
		$this->fix_ajax_functionality();
		
		add_filter( 'wp_setup_nav_menu_item', array( $this, 'wp_setup_nav_menu_item' ), 10, 1 );
	}
	
	/*
	 * Add the search meta box to the left on the nav-menus.php page.
	 *
	 */
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
	
	/*
	 * Make sure our ajax function is called before the core one
	 *
	 */
	function fix_ajax_functionality(){
		if( $priority = has_action( 'wp_ajax_add-menu-item', 'wp_ajax_add_menu_item' ) ){
			remove_action( 'wp_ajax_add-menu-item', 'wp_ajax_add_menu_item', $priority );
			add_action( 'wp_ajax_add-menu-item', array( $this, 'wp_ajax_add_menu_item' ), 1 );
			add_action( 'wp_ajax_add-menu-item', 'wp_ajax_add_menu_item', 1 );
			return;
		}
		add_action( 'wp_ajax_add-menu-item', array( $this, 'wp_ajax_add_menu_item' ), 1 );
	}
	
	/*
	 * Mostly a rewrite of the core function of the same name to exclusively
	 * handle menu items with type search. Also stops the core function from
	 * handling these search menu items.
	 *
	 */
	function wp_ajax_add_menu_item(){
		check_ajax_referer( 'add-menu_item', 'menu-settings-column-nonce' );
		
		if ( ! current_user_can( 'edit_theme_options' ) )
			wp_die( -1 );
		
		require_once ABSPATH . 'wp-admin/includes/nav-menu.php';
		
		$menu_items_data = array();
		$search_keys = array();
		foreach( (array)$_POST['menu-item'] as $k=>$menu_item_data ){
			
			if( ! isset( $menu_item_data['menu-item-type'] ) || $menu_item_data['menu-item-type'] !== 'search' )
				continue;
			
			$menu_item_data['menu-item-description'] = __( 'Search box in menu.', 'bop-nav-search-box-item' );
			$menu_items_data[] = $menu_item_data;
			$search_keys[] = $k;
		}
		
		foreach( $search_keys as $k ){
			unset( $_POST['menu-item'][$k] );
		}
		
		if( ! $menu_items_data ){
			return;
		}
		
		$item_ids = wp_save_nav_menu_items( 0, $menu_items_data );
		if ( is_wp_error( $item_ids ) )
			wp_die( 0 );

		$menu_items = array();

		foreach ( (array) $item_ids as $menu_item_id ) {
			$menu_obj = get_post( $menu_item_id );
			if ( ! empty( $menu_obj->ID ) ) {
				$menu_obj = wp_setup_nav_menu_item( $menu_obj );
				$menu_obj->label = $menu_obj->title; // don't show "(pending)" in ajax-added items
				$menu_items[] = $menu_obj;
			}
		}

		/** This filter is documented in wp-admin/includes/nav-menu.php */
		$walker_class_name = apply_filters( 'wp_edit_nav_menu_walker', 'Walker_Nav_Menu_Edit', $_POST['menu'] );

		if ( ! class_exists( $walker_class_name ) )
			wp_die( 0 );

		if ( ! empty( $menu_items ) ) {
			$args = array(
				'after' => '',
				'before' => '',
				'link_after' => '',
				'link_before' => '',
				'walker' => new $walker_class_name,
			);
			echo walk_nav_menu_tree( $menu_items, 0, (object) $args );
		}
		if( ! $_POST['menu-item'] ){
			wp_die();
		}
	}
	
	/*
	 * Fill the meta box with the required html.
	 *
	 */
	function search_meta_box_render(){
		global $_nav_menu_placeholder, $nav_menu_selected_id;

		$_nav_menu_placeholder = 0 > $_nav_menu_placeholder ? $_nav_menu_placeholder - 1 : -1;

		?>
		<div class="customlinkdiv" id="searchboxitemdiv">
			<div class="tabs-panel-active">
				<ul class="categorychecklist">
					<li>
						<input type="hidden" name="menu-item[<?php echo $_nav_menu_placeholder; ?>][menu-item-type]" value="search">
						<input type="hidden" name="menu-item[<?php echo $_nav_menu_placeholder; ?>][menu-item-type_label]" value="Search Box">
						
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
		</div>
		<script type="text/javascript">
			(function($){
				$(window).on('load', function(){
					$('#submit-searchboxitemdiv').on('click', function(e){
						e.preventDefault();
						$('#searchboxitemdiv').addSelectedToMenu();
					});
				});
			})(jQuery);
		</script>
		<?php
	}
	
	/*
	 * Function to add the only bit missing in preparation of the menu item,
	 * its type label.
	 *
	 */
	function wp_setup_nav_menu_item( $menu_item ){
		if( isset( $menu_item->type ) && $menu_item->type == 'search' ){
			$menu_item->type_label = 'Search Box';
		}
		return $menu_item;
	}
	
	/*
	 * Function to output the search form in a menu. Includes actions for site
	 * owners, themes, etc., to vary the html.
	 *
	 */
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
	
	/*
	 * Enqueue styles and scripts and localize them for languages.
	 *
	 */
	function admin_enqueue_styles_and_scripts( $hook ){
		if( $hook == 'nav-menus.php' ){
			wp_register_style( 'bop_nav_search_box_item_admin_css', $this->url . self::CSSURL .  'style.css', false, '1.0.0' );
			wp_enqueue_style( 'bop_nav_search_box_item_admin_css' );
		}
	}
	
	/*
	 * Add help tab.
	 *
	 */
	function on_load_nav_menus_screen_head(){
		if( current_user_can( 'manage_options' ) ){
			get_current_screen()->add_help_tab(
				array(
					'title' => __( 'Search Box', 'bop-nav-search-box-item' ),
					'id' => 'bop_nav_search_box_item_help_tab',
					'callback' => array( $this, 'help_tab' )
				)
			);
		}
	}
	
	/*
	 * Help tab html.
	 *
	 */
	function help_tab(){
		?>
		<p><strong><?php _e( 'Developer Info', 'bop-nav-search-box-item' ) ?></strong></p>
		<p>
			<?php _e( 'To edit the html output of the search box use the hook <strong>get_nav_search_box_form</strong> as you would the hook <a href="https://developer.wordpress.org/reference/hooks/get_search_form/">get_search_form</a>. The difference between these is that there are three additional arguments passed to the hook. These are: $form (the current html), $item (the nav-menu-item), $depth (the current depth of the menu in the walker), $args (the arguments of the menu as given in the wp_nav_menu function call). That is, the same arguments as passed to <a href="https://developer.wordpress.org/reference/hooks/walker_nav_menu_start_el/">walker_nav_menu_start_el</a> hook.', 'bop-nav-search-box-item' ); ?>
		</p>
		<?php 
	}
}

/*
 * This function calls the class singleton defined above.
 * This means the class above behaves like a module or
 * namespace for procedural programming purposes and runs or
 * queues a number of once only pieces of code on
 * instantiation.
 *
 */
function bop_nav_search_box_item(){
	if( ! ( $o = wp_cache_get( 'setup', 'bop_nav_search_box_item' ) ) ){
		$o = new Bop_Nav_Search_Box_Item();
		wp_cache_set( 'setup', $o, 'bop_nav_search_box_item' );
	}
	return $o;
}

//Instantiate plugin setup class.
bop_nav_search_box_item();

endif;
