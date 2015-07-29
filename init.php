<?php 
/*
Plugin Name: Bop Search Box Item Type For Nav Menus
Description: Adds search box as a choice of item in navigation menus.
Version: 1.4.0
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
			,_x( 'Search Box', 'meta-box-title', 'bop-nav-search-box-item' )
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
			
			$menu_item_data['menu-item-description'] = _x( 'Search box', 'menu-item-description', 'bop-nav-search-box-item' );
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

		/**
		 * This filter is defined in wp-admin/includes/nav-menu.php
		 * 
		 * @since 1.0.0
		 */
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
						<input type="hidden" name="menu-item[<?php echo $_nav_menu_placeholder; ?>][menu-item-type_label]" value="<?php echo _x( 'Search Box', 'type-label', 'bop-nav-search-box-item' ) ?>">
						
						<input type="hidden" class="menu-item-title" name="menu-item[<?php echo $_nav_menu_placeholder; ?>][menu-item-title]" value="<?php echo _x( 'Search', 'default-title', 'bop-nav-search-box-item' ) ?>">
						<input type="hidden" class="menu-item-url" name="menu-item[<?php echo $_nav_menu_placeholder; ?>][menu-item-url]" value="<?php echo esc_attr( get_search_link() ); ?>">
						<input type="hidden" class="menu-item-classes" name="menu-item[<?php echo $_nav_menu_placeholder; ?>][menu-item-classes]" value="bop-nav-search">
						
						<input type="checkbox" class="menu-item-object-id" name="menu-item[<?php echo $_nav_menu_placeholder; ?>][menu-item-object-id]" value="<?php echo $_nav_menu_placeholder; ?>" checked="true">
					</li>
				</ul>
			</div>

			<p class="button-controls">
				<span class="add-to-menu">
					<input type="submit"<?php wp_nav_menu_disabled_check( $nav_menu_selected_id ); ?> class="button-secondary right" value="<?php echo esc_attr_x( 'Add to menu', 'meta-box-submit', 'bop-nav-search-box-item' ); ?>" name="add-search-menu-item" id="submit-searchboxitemdiv">
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
			$menu_item->type_label = _x( 'Search Box', 'type-label', 'bop-nav-search-box-item' );
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
		
		/**
		 * This filter is defined in wp-includes/general-template.php
		 * 
		 * @since 1.0.0
		 */
		do_action( 'pre_get_search_form' );
		
		$format = current_theme_supports( 'html5', 'search-form' ) ? 'html5' : 'xhtml';
		
		/**
		 * This filter is defined in wp-includes/general-template.php
		 * 
		 * @since 1.0.0
		 */
		$format = apply_filters( 'search_form_format', $format );
		
		$classes = empty( $item->classes ) ? array() : (array) $item->classes;
		$classes[] = 'menu-item-' . $item->ID;
		
		/**
		 * This filter is defined in wp-includes/general-template.php
		 * 
		 * @since 1.0.0
		 */
		$class_names = join( ' ', apply_filters( 'nav_menu_css_class', array_filter( $classes ), $item, $args, $depth ) );
		$class_names = $class_names ? ' class="' . esc_attr( $class_names ) . '"' : '';
		
		/**
		 * This filter is defined in wp-includes/general-template.php
		 * 
		 * @since 1.0.0
		 */
		$id = apply_filters( 'nav_menu_item_id', 'menu-item-'. $item->ID, $item, $args, $depth );
		$id = $id ? ' id="' . esc_attr( $id ) . '"' : '';
		
		$item_output = $args->before;
		
		ob_start();
		
		if( 'html5' == $format ):
		?>
		<form <?php echo $id . $class_names ?> role="search" method="get" action="<?php echo home_url( '/' ); ?>">
			<label>
				<?php 
				/**
				 * The screen reader text to show before the input box, if any.
				 *
				 * @since 1.4.0
				 *
				 * @param string	html 	The screen reader text to show. (Filtered)
				 * @param object 	$item 	Menu item data from db as std_object.
				 * @param int 		$depth	How many times menu should be prefixed with sub at this point.
				 * @param array		$args	Arguments passed to wp_nav_menu.
				 */
				echo apply_filters( 
					'bop_nav_search_screen_reader_text',
					'<span class="screen-reader-text">' . esc_attr_x( 'Search', 'form-submit-button', 'bop-nav-search-box-item' ) . '</span>',
					$item,
					$depth,
					$args
				) ?>
				<?php 
				/**
				 * Filter output of item attr_title/placeholder.
				 *
				 * @since 1.4.0
				 *
				 * @param bool	 	$item->title	Placeholder. (Filtered)
				 * @param object 	$item 			Menu item data from db as std_object.
				 * @param int 		$depth			How many times menu should be prefixed with sub at this point.
				 * @param array		$args			Arguments passed to wp_nav_menu.
				 */
				$attr_title = esc_attr( apply_filters( 'bop_nav_search_the_attr_title', $item->attr_title, $item, $depth, $args ) );
				?>
				<input type="search" class="search-field" placeholder="<?php echo $attr_title ?>" value="<?php echo get_search_query() ?>" name="s" title="<?php echo $attr_title ?>" />
			</label>
			<?php 
			/**
			 * Determine whether to show the submit button.
			 *
			 * @since 1.4.0
			 *
			 * @param bool	 	true	Show the submit button? (Filtered)
			 * @param object 	$item 	Menu item data from db as std_object.
			 * @param int 		$depth	How many times menu should be prefixed with sub at this point.
			 * @param array		$args	Arguments passed to wp_nav_menu.
			 */
			if( apply_filters( 'bop_nav_search_show_submit_button', true, $item, $depth, $args ) ): ?>
				<input type="submit" id="searchsubmit" value="<?php 
					/**
					 * Filter output of item title.
					 *
					 * @since 1.4.0
					 *
					 * @param bool	 	$item->title	Navigation label (as in admin). (Filtered)
					 * @param object 	$item 			Menu item data from db as std_object.
					 * @param int 		$depth			How many times menu should be prefixed with sub at this point.
					 * @param array		$args			Arguments passed to wp_nav_menu.
					 */
					echo esc_attr( apply_filters( 'bop_nav_search_the_title', $item->title, $item, $depth, $args ) );
				?>" />
			<?php endif ?>
		</form>
		<?php else: ?>
		<form <?php echo $id . $class_names ?> role="search" method="get" action="<?php echo home_url( '/' ); ?>">
			<div>
				<?php 
				/**
				 * The screen reader text to show before the input box, if any.
				 *
				 * @since 1.4.0
				 *
				 * @param string	html 	The screen reader text to show. (Filtered)
				 * @param object 	$item 	Menu item data from db as std_object.
				 * @param int 		$depth	How many times menu should be prefixed with sub at this point.
				 * @param array		$args	Arguments passed to wp_nav_menu.
				 */
				echo apply_filters(
					'bop_nav_search_screen_reader_text',
					'<label class="screen-reader-text" for="s">' . esc_attr_x( 'Search', 'form-submit-button', 'bop-nav-search-box-item' ) . '</label>',
					$item,
					$depth,
					$args
				) ?>
				<input type="text" value="<?php echo get_search_query() ?>" name="s" id="s" />
				<?php 
				/**
				 * Determine whether to show the submit button.
				 *
				 * @since 1.4.0
				 *
				 * @param bool	 	true	Show the submit button? (Filtered)
				 * @param object 	$item 	Menu item data from db as std_object.
				 * @param int 		$depth	How many times menu should be prefixed with sub at this point.
				 * @param array		$args	Arguments passed to wp_nav_menu.
				 */
				if( apply_filters( 'bop_nav_search_show_submit_button', true, $item, $depth, $args ) ): ?>
					<input type="submit" id="searchsubmit" value="<?php 
						/**
						 * Filter output of item title.
						 *
						 * @since 1.4.0
						 *
						 * @param bool	 	$item->title	Navigation label (as in admin). (Filtered)
						 * @param object 	$item 			Menu item data from db as std_object.
						 * @param int 		$depth			How many times menu should be prefixed with sub at this point.
						 * @param array		$args			Arguments passed to wp_nav_menu.
						 */
						echo esc_attr( apply_filters( 'bop_nav_search_the_title', $item->title, $item, $depth, $args ) );
					?>" />
				<?php endif ?>
			</div>
		</form>
		<?php
		endif;
		
		$item_output .= ob_get_clean();
		
		$item_output .= $args->after;
		
		/**
		 * Filter the HTML output of the search form.
		 *
		 * @since 1.0.0
		 *
		 * @param string 	$item_output	The search form HTML output. (Filtered)
		 * @param object 	$item 			Menu item data from db as std_object.
		 * @param int 		$depth			How many times menu should be prefixed with sub at this point.
		 * @param array		$args			Arguments passed to wp_nav_menu.
		 */
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
					'title' => _x( 'Search Box', 'help-tab-title', 'bop-nav-search-box-item' ),
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
		<p><strong><?php _e( 'Developer Info', 'help-tab-subtitle', 'bop-nav-search-box-item' ) ?></strong></p>
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
$bop_nav_search_box_item;
function bop_nav_search_box_item(){
	global $bop_nav_search_box_item;
	if( ! ( $bop_nav_search_box_item instanceof Bop_Nav_Search_Box_Item ) ){
		$bop_nav_search_box_item = new Bop_Nav_Search_Box_Item();
	}
	return $bop_nav_search_box_item;
}

//Initiate once plugins have been loaded (and in a cancelable fashion)
add_action( 'plugins_loaded', 'bop_nav_search_box_item', 1, 0 );

endif;
