<?php
/**
 * The admin-specific functionality of the plugin.
 *
 * @link       http://sabrinazeidan.com/
 * @since      1.0.0
 * 
 * @package    Speedguard
 * @subpackage Speedguard/admin 
 */

/**
 * The admin-specific functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the admin-specific stylesheet and JavaScript.
 *
 * @package    Speedguard
 * @subpackage Speedguard/admin
 * @author     Sabrina Zeidan <sabrinazeidan@gmail.com>
 */
 
class Speedguard_Admin {
	static public $cpt_name = 'guarded-page';	
	/**
	 * The ID of this plugin.
	 * 
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $plugin_name    The ID of this plugin.
	 */
	private $plugin_name; 
	private $version;

	//public function __construct( $plugin_name, $version, $network ) { 
	public function __construct( $plugin_name, $version ) { 
		$this->plugin_name = $plugin_name;
		$this->version = $version; 
		//Multisite		
		if (!function_exists('is_plugin_active_for_network')) require_once( ABSPATH . '/wp-admin/includes/plugin.php' );					 
		if (is_plugin_active_for_network( 'speedguard/speedguard.php' )) define( 'SPEEDGUARD_MU_NETWORK', true);				 
		if (is_multisite() && !(is_plugin_active_for_network('speedguard/speedguard.php'))) define( 'SPEEDGUARD_MU_PER_SITE', true);

		//Menu items and Admin notices
		add_action((defined('SPEEDGUARD_MU_NETWORK') ? 'network_' : ''). 'admin_menu', array( $this, 'speedguard_admin_menu' ) );
		add_action((defined('SPEEDGUARD_MU_NETWORK') ? 'network_' : ''). 'admin_notices', array( $this, 'show_admin_notices'));			
  
		//If Network activated don't load stuff on subsites. Load on the main site of the Multisite network or for regular WP install
		global $blog_id;		
		if (!(is_plugin_active_for_network( 'speedguard/speedguard.php' )) || (is_plugin_active_for_network( 'speedguard/speedguard.php' )) && (is_main_site($blog_id) )) {
		require_once plugin_dir_path( __FILE__ ) . '/includes/class.widgets.php';
		require_once plugin_dir_path( __FILE__ ) . '/includes/class.settings.php'; 
		require_once plugin_dir_path( __FILE__ ) . '/includes/class.tests.php'; 
		require_once plugin_dir_path( __FILE__ ) . '/includes/class.lighthouse.php'; 
		require_once plugin_dir_path( __FILE__ ) . '/includes/class.notifications.php'; 	

		add_action('admin_init', array($this,'speedguard_cpt') );	
		add_filter('admin_body_class', array( $this, 'body_classes_filter'));
		add_action('transition_post_status', array( $this,'guarded_page_unpublished_hook'),10,3);
		add_action('before_delete_post', array( $this,'before_delete_test_hook'), 10, 1);	
		//MU Headers alredy sent fix
		add_action('init', array( $this, 'app_output_buffer'));
		
		//add_action('wp_head',array( $this, 'fix_backwards_compatibility_wpt') );
		
		 // Add removable query args
+        add_filter( 'removable_query_args', array( $this, 'removable_query_args' ) );
		}	
	}
	
	 public function removable_query_args( $query_args ) {
		if (Speedguard_Admin::is_screen('settings,tests,clients')){	
			$new_query_args = array('speedguard');
			$query_args = array_merge( $query_args, $new_query_args );
		}
        return $query_args;
    }




	function fix_backwards_compatibility_wpt(){
		if (Speedguard_Admin::is_screen('tests')){	
			if (get_transient('speedguard-notice-activation')){

			$args = array(
				'post_type' => Speedguard_Admin::$cpt_name ,
				'post_status' => 'publish',
				'posts_per_page'   => -1, 
				'fields' =>'ids',
					'meta_query'        => array(
						array(
							'key'       => 'load_time',
							'value' => 0, 
							'compare' => '>',
							'type' => 'DECIMAL', 
						 )
					),
				'no_found_rows' => true 
			);
			$the_query = new WP_Query( $args );
			$guarded_pages = $the_query->get_posts();			
				if( $guarded_pages ){
				$process_bulk_action = SpeedGuard_Tests::handle_bulk_retest_load_time('retest_load_time', $guarded_pages);	
				}
				
				delete_transient( 'speedguard-notice-activation' );
			}
		}	
	}
	
	public static function capability() {
		$capability = 'manage_options';
		return $capability;
	}
	
	public static function supported_post_types() {
		$args = array('publicly_queryable'   => true);
		$output = 'names'; 
		$operator = 'and'; 
		$supported_post_types = get_post_types( $args, $output, $operator ); 
		unset($supported_post_types['attachment']);
		$supported_post_types['page'] = 'page';
		return $supported_post_types;
	}
	//Remove meta when test is deleted
	public static function before_delete_test_hook( $postid ) {		
		if  (get_post_type($postid) == Speedguard_Admin::$cpt_name){
			$guarded_item_id = get_post_meta($postid,'guarded_post_id', true);
			$guarded_item_type = get_post_meta($postid,'speedguard_item_type', true);
			if (defined('SPEEDGUARD_MU_NETWORK')){ 
				$blog_id = get_post_meta($postid,'guarded_post_blog_id', true); 				
				switch_to_blog($blog_id);
			}				
			if ($guarded_item_type == 'single') update_post_meta($guarded_item_id, 'speedguard_on', 'false'); 
			else if	($guarded_item_type == 'archive') update_term_meta($guarded_item_id, 'speedguard_on', 'false');
	
			if (defined('SPEEDGUARD_MU_NETWORK')) switch_to_blog(get_network()->site_id); 
		}
	}
	public static function guarded_page_unpublished_hook( $new_status, $old_status, $post ) {
		//Delete test data when original post got unpublished
			if (($old_status == 'publish') &&  ($new_status != 'publish')&& (get_post_type($post->ID)) != Speedguard_Admin::$cpt_name){
					$speedguard_on = get_post_meta($post->ID,'speedguard_on', true);
					if ($speedguard_on && $speedguard_on[0] == 'true'){						
						//delete test on the main blog
						if (defined('SPEEDGUARD_MU_NETWORK')) switch_to_blog(1);    
						$args = array(
							'post_type' => Speedguard_Admin::$cpt_name,   
							'post_status' => 'publish',
							'posts_per_page'   => 1,
							'fields'=>'ids',
							'meta_query' => array(array('key' => 'guarded_post_id','value' => $post->ID,'compare' => 'LIKE')),
							'no_found_rows' => true
							
						);
						$the_query = new WP_Query( $args );
						$connected_guarded_page = $the_query->get_posts();
						if( $connected_guarded_page ) :
							foreach ($connected_guarded_page as $connected_guarded_page_id){
								wp_delete_post( $connected_guarded_page_id, true); 
							}
						if (defined('SPEEDGUARD_MU_NETWORK')) restore_current_blog();  						
						//uncheck speedguard_on
						update_post_meta($post->ID, 'speedguard_on', 'false');  	 
						endif;
						wp_reset_postdata();
					}			
			}
	}

	public static function speedguard_page_url($page) {
		if ($page == 'tests'){
			$admin_page_url = defined('SPEEDGUARD_MU_NETWORK') ? network_admin_url('admin.php?page=speedguard_tests'): admin_url('admin.php?page=speedguard_tests');
		}
		else if ($page == 'settings'){ 
			$admin_page_url = defined('SPEEDGUARD_MU_NETWORK') ? network_admin_url('admin.php?page=speedguard_settings'): admin_url('admin.php?page=speedguard_settings');
		} 
		return $admin_page_url;
	}	 
	
	 
	// Wordpress functions 'get_site_option' and 'get_option'
	public static function get_this_plugin_option($option_name) {
		if(defined('SPEEDGUARD_MU_NETWORK')) {
			return get_site_option($option_name);
		}
		else {  
			return get_option($option_name);
		} 
	}
	// Wordpress functions 'update_site_option' and 'update_option'
	public static function update_this_plugin_option($option_name, $option_value) {
		if(defined('SPEEDGUARD_MU_NETWORK')) {
			return update_site_option($option_name, $option_value);
		}
		else {
			return update_option($option_name, $option_value);
		}
	}
	// Wordpress functions 'delete_site_option' and 'delete_option'
	public static function delete_this_plugin_option($option_name) {
		if(defined('SPEEDGUARD_MU_NETWORK')) {
			return delete_site_option($option_name);
		}
		else {
			return delete_option($option_name);
		}
	}
	
	public static function is_screen($screens){
		//screens: dashboard,settings,tests,plugins, clients
		$screens = explode(",",$screens);
		$screens = str_replace(
			array('tests','settings','clients'), 
			array('toplevel_page_speedguard_tests','speedguard_page_speedguard_settings','speedguard_page_speedguard_clients'),$screens
		);
		require_once(ABSPATH . 'wp-admin/includes/screen.php');
		//Multisite screens
		if (defined('SPEEDGUARD_MU_NETWORK')){
			foreach ($screens as $screen){
				$screens[] = $screen.'-network';
			}  	
		}
		$current_screen = get_current_screen();
		if ($current_screen) $current_screen = $current_screen->id;
		if (in_array(($current_screen), $screens)){
			$return = TRUE;
		}
		else {
			$return = FALSE;
		}
		return $return;	
	}
	public static function speedguard_cpt() {
		$args = array( 			
			'public'      => true, 
			'exclude_from_search'      => true, 
			'publicly_queryable'      => true, 
			'show_ui'      => true, 
			'supports' => array('title','custom-fields'),	
		); 			
		register_post_type( 'guarded-page', $args );	
	}		
	//Plugin Styles
	public function enqueue_styles() { 
		if (Speedguard_Admin::is_screen('dashboard,settings,tests')){		
			//wp_enqueue_style( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'assets/css/speedguard-admin.css', array(), $this->version ); 
			wp_enqueue_style( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'assets/css/speedguard-admin.css', array(), date('h:i:s') ); 
		}
	}
	//Plugin Scripts 
	public function enqueue_scripts() {
		if (Speedguard_Admin::is_screen('settings,tests,plugins,clients')){	
			wp_enqueue_script('jquery');
			wp_enqueue_script('common');
			wp_enqueue_script('wp-lists');
			wp_enqueue_script('postbox');  
			
		}
		wp_enqueue_script( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'assets/js/speedguard-admin.js', array( 'jquery','jquery-ui-autocomplete'), date('h:i:s'), false  );
		
		wp_enqueue_script('speedguard-search',	plugin_dir_url( __FILE__ ) . 'assets/js/speedguard-search.js',	array( 'jquery' ), $this->version, true);
		wp_localize_script(		'speedguard-search',		'speedguard-search',		array('search_api' => home_url( '/wp-json/speedguard/search' ), 
		//SpeedGuard_Tests::speedguard_search($request)
		//'nonce' => wp_create_nonce('wp_rest')
		));
		
	}
	//Plugin Body classes
	function body_classes_filter($classes) {		
		if (Speedguard_Admin::is_screen('settings,tests,dashboard')){	
			$speedguard_average = Speedguard_Admin::get_this_plugin_option('speedguard_average' );
			if ( isset($speedguard_average['guarded_pages_count']) && ($speedguard_average['guarded_pages_count'] < 1) ) $classes = $classes.' no-guarded-pages'; 		
		} 
		if (Speedguard_Admin::is_screen('plugins')){	
			if (get_transient('speedguard-notice-activation')){
				$classes = $classes.' speedguard-just-activated'; 
				delete_transient( 'speedguard-notice-activation' );
			}
		}		
		return $classes;
	}
	//Plugin Item in Admin Menu
	public function speedguard_admin_menu() {
		add_menu_page(__( 'SpeedGuard', 'speedguard' ), __( 'SpeedGuard', 'speedguard' ), 'manage_options', 'speedguard_tests', array( 'SpeedGuard_Tests', 'tests_page'),'data:image/svg+xml;base64,PD94bWwgdmVyc2lvbj0iMS4wIiBlbmNvZGluZz0idXRmLTgiPz48IURPQ1RZUEUgc3ZnIFs8IUVOVElUWSBuc19mbG93cyAiaHR0cDovL25zLmFkb2JlLmNvbS9GbG93cy8xLjAvIj5dPjxzdmcgdmVyc2lvbj0iMS4yIiBiYXNlUHJvZmlsZT0idGlueSIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIiB4bWxuczp4bGluaz0iaHR0cDovL3d3dy53My5vcmcvMTk5OS94bGluayIgeG1sbnM6YT0iaHR0cDovL25zLmFkb2JlLmNvbS9BZG9iZVNWR1ZpZXdlckV4dGVuc2lvbnMvMy4wLyIgeD0iMHB4IiB5PSIwcHgiIHdpZHRoPSI5MXB4IiBoZWlnaHQ9IjkxcHgiIHZpZXdCb3g9Ii0wLjUgLTAuNSA5MSA5MSIgeG1sOnNwYWNlPSJwcmVzZXJ2ZSI+PGRlZnM+PC9kZWZzPjxwYXRoIGZpbGw9IiM4Mjg3OEMiIGQ9Ik04NS42NDYsNDAuNjQ1Yy0yLjQwNCwwLTQuMzU1LDEuOTUyLTQuMzU1LDQuMzU1YzAsMjAuMDEzLTE2LjI3NywzNi4yOS0zNi4yOSwzNi4yOUMyNC45ODgsODEuMjksOC43MDksNjUuMDEzLDguNzA5LDQ1QzguNzA5LDI0Ljk4OCwyNC45ODgsOC43MDksNDUsOC43MDljMi40MDQsMCw0LjM1NC0xLjk1MSw0LjM1NC00LjM1NFM0Ny40MDQsMCw0NSwwQzIwLjE4NywwLDAsMjAuMTg3LDAsNDVjMCwyNC44MTQsMjAuMTg3LDQ1LDQ1LDQ1YzI0LjgxNCwwLDQ1LTIwLjE4Niw0NS00NUM5MCw0Mi41OTcsODguMDQ5LDQwLjY0NSw4NS42NDYsNDAuNjQ1eiIvPjxwYXRoIGZpbGw9IiM4Mjg3OEMiIGQ9Ik00Ny4zMiwzMC42MjRjLTEuMjM2LDEuODA1LTEuOTIzLDMuODA5LTIuMzkzLDUuNjc1Yy00Ljc3NiwwLjA0MS04LjYzNywzLjkyLTguNjM3LDguNzAxYzAsNC44MDcsMy45MDIsOC43MSw4LjcwOSw4LjcxYzQuODA3LDAsOC43MS0zLjkwMyw4LjcxLTguNzFjMC0xLjE1OC0wLjIzOC0yLjI1OS0wLjY0OC0zLjI3MmMxLjU0My0xLjE0OSwzLjEyOC0yLjU1NSw0LjMyNC00LjM5NmMxLjI5MS0yLjA4MywxLjkyNS00LjgwOCwzLjA5NC03LjE3N2MxLjExOS0yLjM5OCwyLjI4NC00Ljc3MSwzLjIzNi03LjA3OGMxLjAwNi0yLjI3OSwxLjg3Ny00LjQ1LDIuNjMxLTYuMzA5YzEuNDg3LTMuNzI1LDIuMzYxLTYuMjg2LDIuMzYxLTYuMjg2YzAuMDY3LTAuMTk3LDAuMDMyLTAuNDI0LTAuMTE2LTAuNTkyYy0wLjIyMS0wLjI1LTAuNjAyLTAuMjczLTAuODQ4LTAuMDU2YzAsMC0yLjAyNiwxLjc5NC00Ljg5Nyw0LjYwMmMtMS40MjMsMS40MDgtMy4wOTIsMy4wNTItNC44MTEsNC44NTRjLTEuNzY3LDEuNzY5LTMuNTA0LDMuNzU3LTUuMjkxLDUuNzEzQzUxLjAxOSwyNi45OTQsNDguNzQ4LDI4LjYyNiw0Ny4zMiwzMC42MjR6Ii8+PC9zdmc+','81');
		add_submenu_page('speedguard_tests', __('Speed Tests','speedguard'), __('Speed Tests','speedguard'), 'manage_options', 'speedguard_tests' ); 
		add_submenu_page('speedguard_tests',  __( 'Settings', 'speedguard' ),  __( 'Settings', 'speedguard' ), 'manage_options', 'speedguard_settings',array( 'SpeedGuard_Settings', 'my_settings_page_function') );
	}
	//Plugin Admin Notices
	public static function set_notice( $message, $class) {  return "<div class='notice notice-$class is-dismissible'><p>$message</p></div>"; }
	public static function show_admin_notices() {
	$speedguard_average = Speedguard_Admin::get_this_plugin_option('speedguard_average' );	
		if (!current_user_can('manage_options')) return;
			$speedguard_options = Speedguard_Admin::get_this_plugin_option('speedguard_options' );		
		if (!(Speedguard_Admin::is_screen('tests')) && (!isset($speedguard_average['guarded_pages_count']) )) {
			
			$message = sprintf(__( 'Everything is ready to test your site speed! %1$sAdd some pages%2$s to start testing.', 'speedguard' ),'<a href="' .Speedguard_Admin::speedguard_page_url('tests'). '">','</a>');
			$notices =  Speedguard_Admin::set_notice( $message,'warning' );	  
		}
		
		
		if ( ! empty( $_POST['speedguard'] ) && $_POST['speedguard'] == 'add_new_url' ) {
			//var_dump($_POST);
			$notices = SpeedGuard_Tests::import_data();
		}
		//TODO show notice while tests are running
		if ( ! empty( $_REQUEST['speedguard'] ) && $_REQUEST['speedguard'] == 'retest_load_time' ) {
			$notices = Speedguard_Admin::set_notice(__('Wait a sec','speedguard'),'success' );	 
		}

		if ( ! empty( $_REQUEST['speedguard'] ) && $_REQUEST['speedguard'] == 'retesting_load_time' ) {
			$notices = Speedguard_Admin::set_notice(__('Updated','speedguard'),'success' );	  
		}
		if ( ! empty( $_REQUEST['speedguard'] ) && $_REQUEST['speedguard'] == 'add_new_url_error_empty' ) {
			$notices =  Speedguard_Admin::set_notice(__('Please select the post you want to add.','speedguard'),'warning' );	  
		}
		if ( ! empty( $_REQUEST['speedguard'] ) && $_REQUEST['speedguard'] == 'add_new_url_error_not_current_domain' ) {
			$notices =  Speedguard_Admin::set_notice(__('SpeedGuard only monitors pages from current website.','speedguard'),'warning' );	  
		}
		if ( ! empty( $_REQUEST['speedguard'] ) && $_REQUEST['speedguard'] == 'add_new_url_error_not_url' ) {
			$notices =  Speedguard_Admin::set_notice(__('Please enter valid URL or select the post you want to add.','speedguard'),'warning' );	  
		}
		if ( ! empty( $_REQUEST['settings-updated'] ) && $_REQUEST['settings-updated'] == 'true' ) {
			$notices =  Speedguard_Admin::set_notice(__('Settings have been updated!'),'success' );   			 
		 
		}
		if ( ! empty( $_REQUEST['speedguard'] ) && $_REQUEST['speedguard'] == 'new_url_added' ) {
			$notices =  Speedguard_Admin::set_notice(__('New URL is successfully added!','speedguard'),'success' );   						
							
		}
		if ( ! empty( $_REQUEST['speedguard'] ) && $_REQUEST['speedguard'] == 'slow_down' ) { 
			$notices =  Speedguard_Admin::set_notice(__('You are moving to fast. Wait at least 5 minutes before updating the tests','speedguard'),'warning' );	  
		}
		if ( ! empty( $_REQUEST['speedguard'] ) && $_REQUEST['speedguard'] == 'load_time_updated' ) { 
			$notices =  Speedguard_Admin::set_notice(__('Results have been successfully updated!','speedguard'),'success' );	  
		}
		if ( ! empty( $_REQUEST['speedguard'] ) && $_REQUEST['speedguard'] == 'delete_guarded_pages' ) { 			
			$notices =  Speedguard_Admin::set_notice(__('Selected pages are not guarded anymore!','speedguard'),'success' );						
		}
		
		//On plugin deactivation   
		if((Speedguard_Admin::is_screen('plugins')) && (get_transient( 'speedguard-notice-deactivation'))){   
		//	$notices =  Speedguard_Admin::set_notice(__('Bye!','speedguard'),'success' );	
		//	delete_transient( 'speedguard-notice-deactivation' );		 	
		} 	 		
		if (isset($notices)) print $notices;
	}
	
	function app_output_buffer() {
		ob_start();
	}

	
}
