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

	/**
	 * The version of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $version    The current version of this plugin.
	 */
	private $version;

	/**
	 * Initialize the class and set its properties.
	 *
	 * @since    1.0.0
	 * @param      string    $plugin_name       The name of this plugin.
	 * @param      string    $version    The version of this plugin.
	 */
	//public function __construct( $plugin_name, $version, $network ) { 
	public function __construct( $plugin_name, $version ) { 
		$this->plugin_name = $plugin_name;
		$this->version = $version;
		
		//Only on main site of the Multisite network or for regular WP install
		global $blog_id;		
		if (!(is_plugin_active_for_network( 'speedguard/speedguard.php' )) || (is_plugin_active_for_network( 'speedguard/speedguard.php' )) && (is_main_site($blog_id) )) {
		require_once plugin_dir_path( __FILE__ ) . '/includes/class.widgets.php';
		require_once plugin_dir_path( __FILE__ ) . '/includes/class.settings.php'; 
		require_once plugin_dir_path( __FILE__ ) . '/includes/class.tests.php'; 
		require_once plugin_dir_path( __FILE__ ) . '/includes/class.webpagetest.php'; 
		require_once plugin_dir_path( __FILE__ ) . '/includes/class.notifications.php'; 		
		
		add_action('admin_init', array($this,'speedguard_cpt') );	
		add_filter('admin_body_class', array( $this, 'body_classes_filter'));
		add_action( 'load-toplevel_page_speedguard_tests', array( $this, 'not_authorized_redirect' ) );		
		}		
			
		
		//Constants
		$speedguard_api = Speedguard_Admin::get_this_plugin_option('speedguard_api');	
        define( 'SpeedGuard_AUTHORIZED', isset( $speedguard_api['authorized'] ) && $speedguard_api['authorized'] ? true : false );
		
		add_action('transition_post_status', array( $this,'guarded_page_unpublished_hook'),10,3);
		add_action('before_delete_post', array( $this,'before_delete_test_hook'), 10, 1);	
		
		//Menu items and Admin notices
		add_action((THIS_PLUGIN_NETWORK_ACTIVATED ? 'network_' : ''). 'admin_menu', array( $this, 'speedguard_admin_menu' ) );
		add_action((THIS_PLUGIN_NETWORK_ACTIVATED ? 'network_' : ''). 'admin_notices', array( $this, 'show_admin_notices'));	
		//MU Headers alredy sent fix
		add_action('init', array( $this, 'app_output_buffer')); 
	}

	function app_output_buffer() {
	ob_start();
	}
	
	public static function before_delete_test_hook( $postid ) {
		//When test is deleted
		if  (get_post_type($postid) == Speedguard_Admin::$cpt_name){
			$guarded_post_id = get_post_meta($postid,'guarded_post_id', true);
			if (THIS_PLUGIN_NETWORK_ACTIVATED){ 
				$blog_id = get_post_meta($postid,'guarded_post_blog_id', true); 				
				switch_to_blog($blog_id);
			}				
				update_post_meta($guarded_post_id, 'speedguard_on', 'false');  	 
			if (THIS_PLUGIN_NETWORK_ACTIVATED) switch_to_blog(get_network()->site_id); 
		}
	}
	public static function guarded_page_unpublished_hook( $new_status, $old_status, $post ) {
		//Delete test data when original post got unpublished
			if (($old_status == 'publish') &&  ($new_status != 'publish')&& (get_post_type($post->ID)) != Speedguard_Admin::$cpt_name){
					$speedguard_on = get_post_meta($post->ID,'speedguard_on', true);
					if ($speedguard_on && $speedguard_on[0] == 'true'){						
						//delete test on the main blog
						if (THIS_PLUGIN_NETWORK_ACTIVATED) switch_to_blog(1);    
						$args = array(
							'post_type' => Speedguard_Admin::$cpt_name,   
							'post_status' => 'publish',
							'posts_per_page'   => 1,
							'fields'=>'ids',
							'meta_query' => array(array('key' => 'guarded_post_id','value' => $post->ID,'compare' => 'LIKE'))
							
						);
						$connected_guarded_page = get_posts( $args ); 
							foreach ($connected_guarded_page as $connected_guarded_page_id){
								wp_delete_post( $connected_guarded_page_id, true); 
							}
						if (THIS_PLUGIN_NETWORK_ACTIVATED) restore_current_blog();  						
						//uncheck speedguard_on
						update_post_meta($post->ID, 'speedguard_on', 'false');  	 
						
					}			
			}
	}

	public static function speedguard_page_url($page) {
		if ($page == 'tests'){
			$admin_page_url = THIS_PLUGIN_NETWORK_ACTIVATED ? network_admin_url('admin.php?page=speedguard_tests'): admin_url('admin.php?page=speedguard_tests');
		}
		else if ($page == 'settings'){ 
			$admin_page_url = THIS_PLUGIN_NETWORK_ACTIVATED ? network_admin_url('admin.php?page=speedguard_settings'): admin_url('admin.php?page=speedguard_settings');
		} 
		return $admin_page_url;
	}	 
	
	 
	// Wordpress functions 'get_site_option' and 'get_option'
	public static function get_this_plugin_option($option_name) {
		if('THIS_PLUGIN_NETWORK_ACTIVATED'== true) {
			return get_site_option($option_name);
		}
		else {  
			return get_option($option_name);
		} 
	}
	// Wordpress functions 'update_site_option' and 'update_option'
	public static function update_this_plugin_option($option_name, $option_value) {
		if('THIS_PLUGIN_NETWORK_ACTIVATED'== true) {
			return update_site_option($option_name, $option_value);
		}
		else {
			return update_option($option_name, $option_value);
		}
	}
	// Wordpress functions 'delete_site_option' and 'delete_option'
	public static function delete_this_plugin_option($option_name) {
		if('THIS_PLUGIN_NETWORK_ACTIVATED' === true) {
			return delete_site_option($option_name);
		}
		else {
			return delete_option($option_name);
		}
	}
	
	function not_authorized_redirect() { 		
		if (!SpeedGuard_AUTHORIZED){			
			wp_safe_redirect(Speedguard_Admin::speedguard_page_url('settings')); 
			exit;
		}
	}
	
	public static function is_screen($screens){
		//screens: dashboard,settings,tests,plugins
		$screens = explode(",",$screens);
		$screens = str_replace(
			array('tests','settings'), 
			array('toplevel_page_speedguard_tests','speedguard_page_speedguard_settings'),$screens
		);
		//Multisite screens
		if (MULTISITE){
			foreach ($screens as $screen){
				$screens[] = $screen.'-network';
			}  	
		}
		$current_screen = get_current_screen()->id;
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
			'publicly_queryable'      => true, 
			'show_ui'      => false, 
			'supports' => array('title','custom-fields'),	
		); 			
		register_post_type( 'guarded-page', $args );	
	}		
	//Plugin Styles
	public function enqueue_styles() { 
		if (Speedguard_Admin::is_screen('dashboard,settings,tests')){		
			wp_enqueue_style( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'assets/css/speedguard-admin.css', array(), $this->version ); 
		}
	}
	//Plugin Scripts 
	public function enqueue_scripts() {
		if (Speedguard_Admin::is_screen('settings,tests,plugins')){	
			wp_enqueue_script('jquery');
			wp_enqueue_script('common');
			wp_enqueue_script('wp-lists');
			wp_enqueue_script('postbox');  
			
		}
		wp_enqueue_script( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'assets/js/speedguard-admin.js', array( 'jquery','jquery-ui-autocomplete'), $this->version, false  );
	}
	//Plugin Body classes
	function body_classes_filter($classes) {		
		if (Speedguard_Admin::is_screen('settings,tests,dashboard')){	
			$speedguard_average = Speedguard_Admin::get_this_plugin_option('speedguard_average' );
			if ( ($speedguard_average['guarded_pages_count'] < 1) ) $classes = $classes.' no-guarded-pages'; 		
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
	function speedguard_admin_menu() {
		$this->main_page = add_menu_page(__( 'SpeedGuard', 'speedguard' ), __( 'SpeedGuard', 'speedguard' ), 'update_core', 'speedguard_tests', array( 'SpeedGuard_Tests', 'tests_page'),'data:image/svg+xml;base64,PD94bWwgdmVyc2lvbj0iMS4wIiBlbmNvZGluZz0idXRmLTgiPz48IURPQ1RZUEUgc3ZnIFs8IUVOVElUWSBuc19mbG93cyAiaHR0cDovL25zLmFkb2JlLmNvbS9GbG93cy8xLjAvIj5dPjxzdmcgdmVyc2lvbj0iMS4yIiBiYXNlUHJvZmlsZT0idGlueSIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIiB4bWxuczp4bGluaz0iaHR0cDovL3d3dy53My5vcmcvMTk5OS94bGluayIgeG1sbnM6YT0iaHR0cDovL25zLmFkb2JlLmNvbS9BZG9iZVNWR1ZpZXdlckV4dGVuc2lvbnMvMy4wLyIgeD0iMHB4IiB5PSIwcHgiIHdpZHRoPSI5MXB4IiBoZWlnaHQ9IjkxcHgiIHZpZXdCb3g9Ii0wLjUgLTAuNSA5MSA5MSIgeG1sOnNwYWNlPSJwcmVzZXJ2ZSI+PGRlZnM+PC9kZWZzPjxwYXRoIGZpbGw9IiM4Mjg3OEMiIGQ9Ik04NS42NDYsNDAuNjQ1Yy0yLjQwNCwwLTQuMzU1LDEuOTUyLTQuMzU1LDQuMzU1YzAsMjAuMDEzLTE2LjI3NywzNi4yOS0zNi4yOSwzNi4yOUMyNC45ODgsODEuMjksOC43MDksNjUuMDEzLDguNzA5LDQ1QzguNzA5LDI0Ljk4OCwyNC45ODgsOC43MDksNDUsOC43MDljMi40MDQsMCw0LjM1NC0xLjk1MSw0LjM1NC00LjM1NFM0Ny40MDQsMCw0NSwwQzIwLjE4NywwLDAsMjAuMTg3LDAsNDVjMCwyNC44MTQsMjAuMTg3LDQ1LDQ1LDQ1YzI0LjgxNCwwLDQ1LTIwLjE4Niw0NS00NUM5MCw0Mi41OTcsODguMDQ5LDQwLjY0NSw4NS42NDYsNDAuNjQ1eiIvPjxwYXRoIGZpbGw9IiM4Mjg3OEMiIGQ9Ik00Ny4zMiwzMC42MjRjLTEuMjM2LDEuODA1LTEuOTIzLDMuODA5LTIuMzkzLDUuNjc1Yy00Ljc3NiwwLjA0MS04LjYzNywzLjkyLTguNjM3LDguNzAxYzAsNC44MDcsMy45MDIsOC43MSw4LjcwOSw4LjcxYzQuODA3LDAsOC43MS0zLjkwMyw4LjcxLTguNzFjMC0xLjE1OC0wLjIzOC0yLjI1OS0wLjY0OC0zLjI3MmMxLjU0My0xLjE0OSwzLjEyOC0yLjU1NSw0LjMyNC00LjM5NmMxLjI5MS0yLjA4MywxLjkyNS00LjgwOCwzLjA5NC03LjE3N2MxLjExOS0yLjM5OCwyLjI4NC00Ljc3MSwzLjIzNi03LjA3OGMxLjAwNi0yLjI3OSwxLjg3Ny00LjQ1LDIuNjMxLTYuMzA5YzEuNDg3LTMuNzI1LDIuMzYxLTYuMjg2LDIuMzYxLTYuMjg2YzAuMDY3LTAuMTk3LDAuMDMyLTAuNDI0LTAuMTE2LTAuNTkyYy0wLjIyMS0wLjI1LTAuNjAyLTAuMjczLTAuODQ4LTAuMDU2YzAsMC0yLjAyNiwxLjc5NC00Ljg5Nyw0LjYwMmMtMS40MjMsMS40MDgtMy4wOTIsMy4wNTItNC44MTEsNC44NTRjLTEuNzY3LDEuNzY5LTMuNTA0LDMuNzU3LTUuMjkxLDUuNzEzQzUxLjAxOSwyNi45OTQsNDguNzQ4LDI4LjYyNiw0Ny4zMiwzMC42MjR6Ii8+PC9zdmc+','81');
    $this->tests_page_hook = add_submenu_page('speedguard_tests', __('Speed Tests','speedguard'), __('Speed Tests','speedguard'), 'update_core', 'speedguard_tests' ); 
    $this->settings_page_hook = add_submenu_page('speedguard_tests',  __( 'Settings', 'speedguard' ),  __( 'Settings', 'speedguard' ), 'update_core', 'speedguard_settings',array( 'SpeedGuard_Settings', 'my_settings_page_function') );		
	}   
	//Plugin Admin Notices	
	public static function set_notice( $message, $class) {  return "<div class='notice notice-$class is-dismissible'><p>$message</p></div>"; }
	public static function show_admin_notices() {	

		if (!current_user_can('update_core')) return;
	 
			$speedguard_options = Speedguard_Admin::get_this_plugin_option('speedguard_options' );
			$speedguard_api_key = Speedguard_Admin::get_this_plugin_option('speedguard_api' );
			$api_key = $speedguard_api_key['api_key'];		
		if (!(SpeedGuard_AUTHORIZED) && !(Speedguard_Admin::is_screen('settings'))){
			$message = sprintf(__( 'To start monitoring your site load time, please %1$senter API Key%2$s!', 'speedguard' ),'<a href="' .Speedguard_Admin::speedguard_page_url('settings'). '" target="_blank">','</a>'); 			
			$notices =  Speedguard_Admin::set_notice( $message,'warning' );	  
		}
		else if ($api_key && !(SpeedGuard_AUTHORIZED) && (Speedguard_Admin::is_screen('settings'))){
			$notices = Speedguard_Admin::set_notice(__('API key you have entered is not valid.','speedguard'),'error' );	 
		}
		else if ((SpeedGuard_AUTHORIZED) && !(Speedguard_Admin::is_screen('tests')) && (Speedguard_Admin::get_this_plugin_option('speedguard_average')['guarded_pages_count']) < 1){
			$message = sprintf(__( 'Everything is ready to test your site speed! %1$sAdd some pages%2$s to start tests.', 'speedguard' ),'<a href="' .Speedguard_Admin::speedguard_page_url('tests'). '">','</a>');
			$notices =  Speedguard_Admin::set_notice( $message,'warning' );	  
		}
		else if (Speedguard_Admin::is_screen('settings,tests')){	
			$plugin_dir_created = filemtime(WP_PLUGIN_DIR.'/speedguard' );
			$days_later = strtotime('+7 days', $plugin_dir_created);		
			if (($speedguard_options['plugin_rated']  != true) && (time() > $days_later)){										
				$ask_to_rate = sprintf(__( 'Awesome, you\'ve been using SpeedGuard for more than 1 week. May I ask you to give it a 5-star rating on WordPress?  %1$sOk, you deserved it%2$sI already did%3$sNot good enough%4$s', "speedguard" ),
				'<form id="rate-speedguard" action="" method="post"><a href="https://wordpress.org/support/plugin/speedguard/reviews/?rate=5#new-post" target="_blank">',
				'</a> | <input type="submit" style="border: 0;  background: transparent; color: #0073aa; cursor:pointer;text-decoration:underline;" name="speedguard_rating" value="',
				'">| <span id="leave-feedback" style="border: 0;  background: transparent; color: #0073aa; cursor:pointer;text-decoration:underline;">',
				'</span>');	
				$second = sprintf(__( '%1$sHow can I make SpeedGuard plugin better for you?%2$s %3$sI will be happy to receive support to my email %7$s regarding the issues I am reporting.(Please include a detailed description)%4$s %5$sSend feedback%6$s', "speedguard" ),
				'<div style="display:none; margin-top: 2em;" id="feedback-form"><p><em>',
				'</em></p><textarea rows="2" style="width: 100%;" id="speedguard_feedback" name="speedguard_feedback"></textarea>',
				'<p><input name="answer_to_email" type="checkbox" checked="checked"><label for="answer_to_email">',
				'</label></p>',
				'<input type="submit" name="speedguard_rating" class="button button-secondary" value="',
				'"></div></form>',
				'<input type="text" size="30" readonly  name="admin_email" value="'.Speedguard_Admin::get_this_plugin_option('admin_email').'">');		
				$notices =  Speedguard_Admin::set_notice($ask_to_rate.$second,'success'); 										
			}
		}				
		if ( !empty( $_POST['speedguard_rating']) && $_POST['speedguard_rating'] == 'I already did' ){				
			$speedguard_options['plugin_rated'] = true;
			Speedguard_Admin::update_this_plugin_option('speedguard_options', $speedguard_options);
			$notices =  Speedguard_Admin::set_notice(__('Thank you friend!','speedguard'),'success' );	 
		}
		if ( !empty( $_POST['speedguard_rating']) && $_POST['speedguard_rating'] == 'Send feedback' ){	
			$plugin_author_email = 'sabrinazeidan@gmail.com';
			$site_url = str_replace( 'http://', '', get_home_url() );   				
				if ( isset($_POST['answer_to_email']) && $_POST['answer_to_email'] == true){	
					$answer_to_email = "Here is my email: ".$_POST['admin_email']; 					
				}
				else $answer_to_email = "Email not provided";
			wp_mail($plugin_author_email,'New feedback for SpeedGuard',$_POST['speedguard_feedback'].' <p>from '.$site_url.' <p>'.$answer_to_email, array('Content-Type: text/html; charset=UTF-8'));
			$notices =  Speedguard_Admin::set_notice(__('Thank you for your honest opinion!','speedguard'),'success' );	 
		}
		if ( ! empty( $_POST['speedguard'] ) && $_POST['speedguard'] == 'add_new_url' ) {
			$notices = SpeedGuard_Tests::import_data($_POST);
		}
		if ( ! empty( $_REQUEST['speedguard'] ) && $_REQUEST['speedguard'] == 'retest_load_time' ) {
			$notices = SpeedGuard_WebPageTest::update_waiting_pageload($_REQUEST);   
		}
		if ( ! empty( $_REQUEST['speedguard'] ) && $_REQUEST['speedguard'] == 'retesting_load_time' ) {
			$notices = Speedguard_Admin::set_notice(__('Please wait. Tests are running...','speedguard'),'success' );	  
		}
		if ( ! empty( $_REQUEST['speedguard'] ) && $_REQUEST['speedguard'] == 'limit_is_reached' ) {
				$private_instance_url = 'https://github.com/WPO-Foundation/webpagetest-docs/blob/master/user/Private%20Instances/README.md';
				$message = sprintf(__( 'Test limit is reached. Please wait for tomorrow or get %1$sprivate instance%2$s.', 'speedguard' ),
					'<a href="' .$private_instance_url. '" target="_blank">','</a>'	);					
				$notices =  Speedguard_Admin::set_notice($message,'error' );	 
		}
		if ( ! empty( $_REQUEST['speedguard'] ) && $_REQUEST['speedguard'] == 'add_new_url_error' ) {
			$notices =  Speedguard_Admin::set_notice(__('Please select the post you want to add.','speedguard'),'warning' );	  
		}
		if ( ! empty( $_REQUEST['settings-updated'] ) && $_REQUEST['settings-updated'] == 'true' ) {
			$notices =  Speedguard_Admin::set_notice(__('Settings are updated!'),'success' );   			 
		 
		}
		if ( ! empty( $_REQUEST['speedguard'] ) && $_REQUEST['speedguard'] == 'new_url_added' ) {
			$notices =  Speedguard_Admin::set_notice(__('New URL is successfully added!','speedguard'),'success' );   						
							
		}
		if ( ! empty( $_REQUEST['speedguard'] ) && $_REQUEST['speedguard'] == 'load_time_updated' ) { 
			$notices =  Speedguard_Admin::set_notice(__('Results are successfully updated!','speedguard'),'success' );	  
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
}
