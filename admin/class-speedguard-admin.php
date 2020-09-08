<?php
/**
 * The admin-specific functionality of the plugin.
 *
 * @link       http://sabrinazeidan.com/
 * @since      1.0.0
 * @package    Speedguard
 * @subpackage Speedguard/admin 
 * @author     Sabrina Zeidan <sabrinazeidan@gmail.com>
 */
 
class SpeedGuard_Admin {
	static public $cpt_name = 'guarded-page';	
	private $plugin_name; 
	private $version;

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
		
		 // Add removable query args
        add_filter( 'removable_query_args', array( $this, 'removable_query_args' ) );
		add_filter((defined('SPEEDGUARD_MU_NETWORK') ? 'network_admin_' : ''). 'plugin_action_links_speedguard/speedguard.php', array( $this, 'speedguard_actions_links' ) );
		}

		add_action( 'admin_footer', array($this, 'run_waiting_tests_ajax')); 	
		add_action( 'wp_ajax_run_waiting_tests', array($this, 'run_waiting_tests' ));

	}
	function run_waiting_tests_ajax(){ 
		if (SpeedGuard_Admin::is_screen('tests') || SpeedGuard_Admin::is_screen('clients')) {
			$args = array( 					
					'post_type' => SpeedGuard_Admin::$cpt_name,
					'post_status' => 'publish',
					'posts_per_page'   => -1,
					'fields'=>'ids',
					'meta_query' => array(array('key' => 'load_time','value' => 'waiting','compare' => 'LIKE')),
					'fields' => 'ids',
					'no_found_rows' => true, 
				);
			
				$the_query = new WP_Query( $args );
				$waiting_pages = $the_query->get_posts();
				wp_reset_postdata();
				if (count($waiting_pages) < 1) return;
				
	?>
		<script type="text/javascript" >
			jQuery(document).ready(function($) {
				var data = {
					'action': 'run_waiting_tests',
					'post_ids': <?php echo json_encode( array_values( $waiting_pages ) ); ?>,
				};
			jQuery.post(ajaxurl, data, function(response) {
				setTimeout(function () {
					window.location.replace(window.location.href + "&speedguard=load_time_updated");
					}, 10000) 
			});
			});
		</script>
	<?
		}
	}	

	function run_waiting_tests() {
		global $wpdb; 
		$posts_ids = $_POST['post_ids'] ;
		foreach ($posts_ids as $post_id){
			$test_created = SpeedGuard_Lighthouse::lighthouse_new_test($post_id);
		}
		echo count($posts_ids);
		wp_die();
	}

	
	public function speedguard_actions_links( array $actions ) {
		return array_merge( array(
			'settings' => sprintf(__( '%1$sSettings%2$s', 'speedguard' ),'<a href="' .SpeedGuard_Admin::speedguard_page_url('settings'). '">','</a>'),
			'tests' => sprintf(__( '%1$sTests%2$s', 'speedguard' ),'<a href="' .SpeedGuard_Admin::speedguard_page_url('tests'). '">','</a>')
		), $actions );
	}
	 public function removable_query_args( $query_args ) {
		if (SpeedGuard_Admin::is_screen('settings,tests,clients')){	
			$new_query_args = array('speedguard','new_url_id');
			$query_args = array_merge( $query_args, $new_query_args );
		}
        return $query_args;
    }

	function fix_backwards_compatibility_wpt(){
		if (SpeedGuard_Admin::is_screen('tests')){	
			if (get_transient('speedguard-notice-activation')){

			$args = array(
				'post_type' => SpeedGuard_Admin::$cpt_name ,
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
				
				//delete_transient( 'speedguard-notice-activation' );
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
		if  (get_post_type($postid) == SpeedGuard_Admin::$cpt_name){
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
			if (($old_status == 'publish') &&  ($new_status != 'publish')&& (get_post_type($post->ID)) != SpeedGuard_Admin::$cpt_name){
					$speedguard_on = get_post_meta($post->ID,'speedguard_on', true);
					if ($speedguard_on && $speedguard_on[0] == 'true'){						
						//delete test on the main blog
						if (defined('SPEEDGUARD_MU_NETWORK')) switch_to_blog(1);    
						$args = array(
							'post_type' => SpeedGuard_Admin::$cpt_name,   
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
			'public'      => false, 
			'exclude_from_search'      => true, 
			//'publicly_queryable'      => true, 
			'show_ui'      => true, 
			'supports' => array('title','custom-fields'),	
		); 			
		register_post_type( 'guarded-page', $args );	
	}		
	//Plugin Styles
	public function enqueue_styles() { 
		if (SpeedGuard_Admin::is_screen('dashboard,settings,tests') || !is_admin()){		
			//wp_enqueue_style( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'assets/css/speedguard-admin.css', array(), $this->version ); 
			wp_enqueue_style( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'assets/css/speedguard-admin.css', array(), date('h:i:s') ); 
		}
	}
	//Plugin Scripts 
	public function enqueue_scripts() {
		if (SpeedGuard_Admin::is_screen('settings,tests,plugins,clients')){	
			wp_enqueue_script('jquery');
			wp_enqueue_script('common');
			wp_enqueue_script('wp-lists');
			wp_enqueue_script('postbox');  
			
		}
		if (SpeedGuard_Admin::is_screen('settings,tests,plugins,clients') || !is_admin()){
			wp_enqueue_script( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'assets/js/speedguard-admin.js', array( 'jquery','jquery-ui-autocomplete'), date('h:i:s'), false  );
		}
		if (SpeedGuard_Admin::is_screen('tests')){
			wp_enqueue_script('speedguardsearch',	plugin_dir_url( __FILE__ ) . 'assets/js/speedguard-search.js',	array( 'jquery' ), $this->version, true);
			wp_localize_script(	'speedguardsearch',		'speedguardsearch',		
				array(
					'search_url' => home_url( '/wp-json/speedguard/search?term=' ),
					'nonce' => wp_create_nonce('wp_rest') 
			));
		}
			
	}
	//Plugin Body classes
	function body_classes_filter($classes) {		
		if (SpeedGuard_Admin::is_screen('settings,tests,dashboard')){	
			$speedguard_average = SpeedGuard_Admin::get_this_plugin_option('speedguard_average' );
			if ( isset($speedguard_average['guarded_pages_count']) && ($speedguard_average['guarded_pages_count'] < 1) ) $classes = $classes.' no-guarded-pages'; 		
		} 
		if (SpeedGuard_Admin::is_screen('plugins')){	
			if (get_transient('speedguard-notice-activation')){
				$classes = $classes.' speedguard-just-activated'; 
			//	delete_transient( 'speedguard-notice-activation' );
			}
		}		
		return $classes;
	}
	//Plugin Item in Admin Menu
	function speedguard_admin_menu() {
		$this->main_page = add_menu_page(__( 'SpeedGuard', 'speedguard' ), __( 'SpeedGuard', 'speedguard' ), 'manage_options', 'speedguard_tests', array( 'SpeedGuard_Tests', 'tests_page'),'data:image/svg+xml;base64,PD94bWwgdmVyc2lvbj0iMS4wIiBlbmNvZGluZz0idXRmLTgiPz48IURPQ1RZUEUgc3ZnIFs8IUVOVElUWSBuc19mbG93cyAiaHR0cDovL25zLmFkb2JlLmNvbS9GbG93cy8xLjAvIj5dPjxzdmcgdmVyc2lvbj0iMS4yIiBiYXNlUHJvZmlsZT0idGlueSIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIiB4bWxuczp4bGluaz0iaHR0cDovL3d3dy53My5vcmcvMTk5OS94bGluayIgeG1sbnM6YT0iaHR0cDovL25zLmFkb2JlLmNvbS9BZG9iZVNWR1ZpZXdlckV4dGVuc2lvbnMvMy4wLyIgeD0iMHB4IiB5PSIwcHgiIHdpZHRoPSI5MXB4IiBoZWlnaHQ9IjkxcHgiIHZpZXdCb3g9Ii0wLjUgLTAuNSA5MSA5MSIgeG1sOnNwYWNlPSJwcmVzZXJ2ZSI+PGRlZnM+PC9kZWZzPjxwYXRoIGZpbGw9IiM4Mjg3OEMiIGQ9Ik04NS42NDYsNDAuNjQ1Yy0yLjQwNCwwLTQuMzU1LDEuOTUyLTQuMzU1LDQuMzU1YzAsMjAuMDEzLTE2LjI3NywzNi4yOS0zNi4yOSwzNi4yOUMyNC45ODgsODEuMjksOC43MDksNjUuMDEzLDguNzA5LDQ1QzguNzA5LDI0Ljk4OCwyNC45ODgsOC43MDksNDUsOC43MDljMi40MDQsMCw0LjM1NC0xLjk1MSw0LjM1NC00LjM1NFM0Ny40MDQsMCw0NSwwQzIwLjE4NywwLDAsMjAuMTg3LDAsNDVjMCwyNC44MTQsMjAuMTg3LDQ1LDQ1LDQ1YzI0LjgxNCwwLDQ1LTIwLjE4Niw0NS00NUM5MCw0Mi41OTcsODguMDQ5LDQwLjY0NSw4NS42NDYsNDAuNjQ1eiIvPjxwYXRoIGZpbGw9IiM4Mjg3OEMiIGQ9Ik00Ny4zMiwzMC42MjRjLTEuMjM2LDEuODA1LTEuOTIzLDMuODA5LTIuMzkzLDUuNjc1Yy00Ljc3NiwwLjA0MS04LjYzNywzLjkyLTguNjM3LDguNzAxYzAsNC44MDcsMy45MDIsOC43MSw4LjcwOSw4LjcxYzQuODA3LDAsOC43MS0zLjkwMyw4LjcxLTguNzFjMC0xLjE1OC0wLjIzOC0yLjI1OS0wLjY0OC0zLjI3MmMxLjU0My0xLjE0OSwzLjEyOC0yLjU1NSw0LjMyNC00LjM5NmMxLjI5MS0yLjA4MywxLjkyNS00LjgwOCwzLjA5NC03LjE3N2MxLjExOS0yLjM5OCwyLjI4NC00Ljc3MSwzLjIzNi03LjA3OGMxLjAwNi0yLjI3OSwxLjg3Ny00LjQ1LDIuNjMxLTYuMzA5YzEuNDg3LTMuNzI1LDIuMzYxLTYuMjg2LDIuMzYxLTYuMjg2YzAuMDY3LTAuMTk3LDAuMDMyLTAuNDI0LTAuMTE2LTAuNTkyYy0wLjIyMS0wLjI1LTAuNjAyLTAuMjczLTAuODQ4LTAuMDU2YzAsMC0yLjAyNiwxLjc5NC00Ljg5Nyw0LjYwMmMtMS40MjMsMS40MDgtMy4wOTIsMy4wNTItNC44MTEsNC44NTRjLTEuNzY3LDEuNzY5LTMuNTA0LDMuNzU3LTUuMjkxLDUuNzEzQzUxLjAxOSwyNi45OTQsNDguNzQ4LDI4LjYyNiw0Ny4zMiwzMC42MjR6Ii8+PC9zdmc+','81');
    $this->tests_page_hook = add_submenu_page('speedguard_tests', __('Speed Tests','speedguard'), __('Speed Tests','speedguard'), 'manage_options', 'speedguard_tests' ); 
    $this->settings_page_hook = add_submenu_page('speedguard_tests',  __( 'Settings', 'speedguard' ),  __( 'Settings', 'speedguard' ), 'manage_options', 'speedguard_settings',array( 'SpeedGuard_Settings', 'my_settings_page_function') );		
	}   
	//Plugin Admin Notices	
	public static function set_notice( $message, $class) {  return "<div class='notice notice-$class is-dismissible'><p>$message</p></div>"; }
	public static function show_admin_notices() {
	$speedguard_average = SpeedGuard_Admin::get_this_plugin_option('speedguard_average' );	
		if (!current_user_can('manage_options')) return;
			$speedguard_options = SpeedGuard_Admin::get_this_plugin_option('speedguard_options' );		
		if (!(SpeedGuard_Admin::is_screen('tests')) && (!isset($speedguard_average['guarded_pages_count']) )) {
			$message = sprintf(__( 'Everything is ready to test your site speed! %1$sAdd some pages%2$s to start testing.', 'speedguard' ),'<a href="' .SpeedGuard_Admin::speedguard_page_url('tests'). '">','</a>');
			//$notices =  SpeedGuard_Admin::set_notice( $message,'warning' );	
			//TODO Notice, if nothing except homepage is added
		}
		
		if((SpeedGuard_Admin::is_screen('plugins')) && (get_transient( 'speedguard-notice-activation'))){   
			$add_homepage = SpeedGuard_Tests::add_test(get_site_url());
			delete_transient( 'speedguard-notice-deactivation' );	
			if (empty(get_transient( 'speedguard-homepage-added-previousely'))){
				$message = sprintf(__( 'Homepage speed test has just started. Would you like to %1$stest some other pages%2$s as well?', 'speedguard' ),'<a href="' .SpeedGuard_Admin::speedguard_page_url('tests'). '">','</a>');
				$notices =  SpeedGuard_Admin::set_notice( $message,'success' );	  
			}
				
		} 		 		
		
		if ( ! empty( $_POST['speedguard'] ) && $_POST['speedguard'] == 'add_new_url' ) {
			
			//$notices = SpeedGuard_Tests::add_test($_POST);
			//$notices = SpeedGuard_Tests::add_test($_POST['speedguard_new_url_permalink'], $_POST['speedguard_item_type'],$_POST['speedguard_new_url_id'],$_POST['blog_id']);
			$url = (!empty($_POST['speedguard_new_url_permalink'])) ? $_POST['speedguard_new_url_permalink'] : $_POST['speedguard_new_url'];
			$notices = SpeedGuard_Tests::add_test($url, $_POST['speedguard_item_type'],$_POST['speedguard_new_url_id'],$_POST['blog_id']);
		}
		//TODO show notice while tests are running
		if ( ! empty( $_REQUEST['speedguard'] ) && $_REQUEST['speedguard'] == 'retest_load_time' ) {
			$notices = SpeedGuard_Admin::set_notice(__('Tests are running...','speedguard'),'success' );	 
		}
		
		if ( ! empty( $_REQUEST['speedguard'] ) && $_REQUEST['speedguard'] == 'add_new_url_error_empty' ) {
			$notices =  SpeedGuard_Admin::set_notice(__('Please select the post you want to add.','speedguard'),'warning' );	  
		}
		if ( ! empty( $_REQUEST['speedguard'] ) && $_REQUEST['speedguard'] == 'add_new_url_error_not_current_domain' ) {
			$notices =  SpeedGuard_Admin::set_notice(__('SpeedGuard only monitors pages from current website.','speedguard'),'warning' );	  
		}
		if ( ! empty( $_REQUEST['speedguard'] ) && $_REQUEST['speedguard'] == 'add_new_url_error_not_url' ) {
			$notices =  SpeedGuard_Admin::set_notice(__('Please enter valid URL or select the post you want to add.','speedguard'),'warning' );	  
		}
		if ( ! empty( $_REQUEST['settings-updated'] ) && $_REQUEST['settings-updated'] == 'true' ) {
			$notices =  SpeedGuard_Admin::set_notice(__('Settings have been updated!'),'success' );   			 
		 
		}
		if ( ! empty( $_REQUEST['speedguard'] ) && $_REQUEST['speedguard'] == 'already_guarded' ) {
			//$process_bulk_action = SpeedGuard_Tests::handle_bulk_retest_load_time('retest_load_time', $guarded_pages);	
			$notices =  SpeedGuard_Admin::set_notice(__('This URL is already guarded!','speedguard'),'warning' );   												
		}
		if ( ! empty( $_REQUEST['speedguard'] ) && $_REQUEST['speedguard'] == 'new_url_added' ) {
			$notices =  SpeedGuard_Admin::set_notice(__('New URL is successfully added!','speedguard'),'success' );   						
							
		}
		if ( ! empty( $_REQUEST['speedguard'] ) && $_REQUEST['speedguard'] == 'slow_down' ) { 
			$notices =  SpeedGuard_Admin::set_notice(__('You are moving to fast. Wait at least 5 minutes before updating the tests','speedguard'),'warning' );	  
		}
		if ( ! empty( $_REQUEST['speedguard'] ) && $_REQUEST['speedguard'] == 'load_time_updated' ) { 
			$notices =  SpeedGuard_Admin::set_notice(__('Results are being updated!','speedguard'),'success' );	  
		}
		if ( ! empty( $_REQUEST['speedguard'] ) && $_REQUEST['speedguard'] == 'delete_guarded_pages' ) { 			
			$notices =  SpeedGuard_Admin::set_notice(__('Selected pages are not guarded anymore!','speedguard'),'success' );						
		}
		
		//On plugin deactivation   
		if((SpeedGuard_Admin::is_screen('plugins')) && (get_transient( 'speedguard-notice-deactivation'))){   
		//	$notices =  SpeedGuard_Admin::set_notice(__('Bye!','speedguard'),'success' );	
		//	delete_transient( 'speedguard-notice-deactivation' );		 	
		} 	 		
		if (isset($notices)) print $notices;
	}
	
	function app_output_buffer() {
		ob_start();
	}

	
}
