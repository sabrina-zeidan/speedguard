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
	//static public $cpt_url = 'edit.php?post_type=guarded-page'; 
	static public $cpt_url = 'admin.php?page=speedguard_tests'; 

	
	
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
	public function __construct( $plugin_name, $version ) { 
		$this->plugin_name = $plugin_name;
		$this->version = $version;
		require_once plugin_dir_path( __FILE__ ) . '/includes/class.widgets.php';
		require_once plugin_dir_path( __FILE__ ) . '/includes/class.settings.php'; 
		require_once plugin_dir_path( __FILE__ ) . '/includes/class.tests.php'; 
		require_once plugin_dir_path( __FILE__ ) . '/includes/class.webpagetest.php'; 
		require_once plugin_dir_path( __FILE__ ) . '/includes/class.notifications.php'; 
		add_action('admin_menu', array( $this, 'speedguard_admin_menu' ));
		add_action( 'network_admin_menu', array( $this, 'speedguard_admin_menu' ) );
		add_action('admin_notices', array( $this, 'show_admin_notices'));
		add_action('before_delete_post', array( $this,'delete_guarded_page_data') ); 
		add_filter('admin_body_class', array( $this, 'body_classes_filter'));
		add_action('admin_init', array($this,'speedguard_cpt') );	
		add_action('before_delete_post',array( $this,'before_cpt_delete')); 
		
		//Constants
		$speedguard_api = get_option('speedguard_api' );	
        define( 'SpeedGuard_AUTHORIZED', isset( $speedguard_api['authorized'] ) && $speedguard_api['authorized'] ? true : false );		
	}
	public static function is_screen($screens){
		//screens
		//dashboard,settings,tests,plugins, edit-guaded-page
		$screens = explode(",",$screens);
		$screens = str_replace(
			array('tests','settings'), 
			array('toplevel_page_speedguard_tests','speedguard_page_speedguard_settings'),$screens
		);
		$screens[] = 'edit-guarded-page';
		//Multisite screens
		foreach ($screens as $screen){
			$screens[] = $screen.'-network';
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
			$labels = array( 'name' => __('Speedguard :: Guarded pages','speedguard'), 'not_found' => __('No pages guarded yet. Add new URL in field above for the start.','speedguard'));
			$args = array( 			
				'public'      => false, 
				'exclude_from_search'      => true, 
				'publicly_queryable'      => true, 
				'show_ui'      => true, 
				'labels'      => $labels,
				'supports' => array('title','custom-fields'),	
			);
			//register_post_type( Speedguard_Admin::$cpt_name, $args );
			register_post_type( 'guarded-page', $args );
		}
		public static function before_cpt_delete($ID) {
				$guarded_post_id = get_post_meta($ID,'guarded_post_id', true);
				update_post_meta($guarded_post_id, 'speedguard_on', 'false');  						
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
			$speedguard_average = get_option('speedguard_average' );
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
		$this->main_page = add_menu_page(__( 'SpeedGuard', 'speedguard' ), __( 'SpeedGuard', 'speedguard' ), 'manage_options', 'speedguard_tests', array( 'SpeedGuard_Tests', 'tests_page'),'data:image/svg+xml;base64,PD94bWwgdmVyc2lvbj0iMS4wIiBlbmNvZGluZz0idXRmLTgiPz48IURPQ1RZUEUgc3ZnIFs8IUVOVElUWSBuc19mbG93cyAiaHR0cDovL25zLmFkb2JlLmNvbS9GbG93cy8xLjAvIj5dPjxzdmcgdmVyc2lvbj0iMS4yIiBiYXNlUHJvZmlsZT0idGlueSIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIiB4bWxuczp4bGluaz0iaHR0cDovL3d3dy53My5vcmcvMTk5OS94bGluayIgeG1sbnM6YT0iaHR0cDovL25zLmFkb2JlLmNvbS9BZG9iZVNWR1ZpZXdlckV4dGVuc2lvbnMvMy4wLyIgeD0iMHB4IiB5PSIwcHgiIHdpZHRoPSI5MXB4IiBoZWlnaHQ9IjkxcHgiIHZpZXdCb3g9Ii0wLjUgLTAuNSA5MSA5MSIgeG1sOnNwYWNlPSJwcmVzZXJ2ZSI+PGRlZnM+PC9kZWZzPjxwYXRoIGZpbGw9IiM4Mjg3OEMiIGQ9Ik04NS42NDYsNDAuNjQ1Yy0yLjQwNCwwLTQuMzU1LDEuOTUyLTQuMzU1LDQuMzU1YzAsMjAuMDEzLTE2LjI3NywzNi4yOS0zNi4yOSwzNi4yOUMyNC45ODgsODEuMjksOC43MDksNjUuMDEzLDguNzA5LDQ1QzguNzA5LDI0Ljk4OCwyNC45ODgsOC43MDksNDUsOC43MDljMi40MDQsMCw0LjM1NC0xLjk1MSw0LjM1NC00LjM1NFM0Ny40MDQsMCw0NSwwQzIwLjE4NywwLDAsMjAuMTg3LDAsNDVjMCwyNC44MTQsMjAuMTg3LDQ1LDQ1LDQ1YzI0LjgxNCwwLDQ1LTIwLjE4Niw0NS00NUM5MCw0Mi41OTcsODguMDQ5LDQwLjY0NSw4NS42NDYsNDAuNjQ1eiIvPjxwYXRoIGZpbGw9IiM4Mjg3OEMiIGQ9Ik00Ny4zMiwzMC42MjRjLTEuMjM2LDEuODA1LTEuOTIzLDMuODA5LTIuMzkzLDUuNjc1Yy00Ljc3NiwwLjA0MS04LjYzNywzLjkyLTguNjM3LDguNzAxYzAsNC44MDcsMy45MDIsOC43MSw4LjcwOSw4LjcxYzQuODA3LDAsOC43MS0zLjkwMyw4LjcxLTguNzFjMC0xLjE1OC0wLjIzOC0yLjI1OS0wLjY0OC0zLjI3MmMxLjU0My0xLjE0OSwzLjEyOC0yLjU1NSw0LjMyNC00LjM5NmMxLjI5MS0yLjA4MywxLjkyNS00LjgwOCwzLjA5NC03LjE3N2MxLjExOS0yLjM5OCwyLjI4NC00Ljc3MSwzLjIzNi03LjA3OGMxLjAwNi0yLjI3OSwxLjg3Ny00LjQ1LDIuNjMxLTYuMzA5YzEuNDg3LTMuNzI1LDIuMzYxLTYuMjg2LDIuMzYxLTYuMjg2YzAuMDY3LTAuMTk3LDAuMDMyLTAuNDI0LTAuMTE2LTAuNTkyYy0wLjIyMS0wLjI1LTAuNjAyLTAuMjczLTAuODQ4LTAuMDU2YzAsMC0yLjAyNiwxLjc5NC00Ljg5Nyw0LjYwMmMtMS40MjMsMS40MDgtMy4wOTIsMy4wNTItNC44MTEsNC44NTRjLTEuNzY3LDEuNzY5LTMuNTA0LDMuNzU3LTUuMjkxLDUuNzEzQzUxLjAxOSwyNi45OTQsNDguNzQ4LDI4LjYyNiw0Ny4zMiwzMC42MjR6Ii8+PC9zdmc+','81');
    $this->tests_page_hook = add_submenu_page('speedguard_tests', __('Speed Tests','speedguard'), __('Speed Tests','speedguard'), 'manage_options', 'speedguard_tests' ); 
    $this->settings_page_hook = add_submenu_page('speedguard_tests',  __( 'Settings', 'speedguard' ),  __( 'Settings', 'speedguard' ), 'manage_options', 'speedguard_settings',array( 'SpeedGuard_Settings', 'settings_page') );		
	}   
	//Plugin Admin Notices	
	public static function set_notice( $message, $class) {  return "<div class='notice notice-$class is-dismissible'><p>$message</p></div>"; }
	function show_admin_notices() {	  
		$speedguard_options = get_option('speedguard_options' );
		if (!(SpeedGuard_AUTHORIZED)){
			$settings_url = admin_url('admin.php?page=speedguard_settings');
			$message = sprintf(__( 'To start monitoring your site load time, please %1$senter API Key%2$s!', 'speedguard' ),'<a href="' .$settings_url. '" target="_blank">','</a>');
			$notices =  Speedguard_Admin::set_notice( $message,'warning' );	  
		}
		else if (!(Speedguard_Admin::is_screen('tests')) && (get_option('speedguard_average')['guarded_pages_count']) < 1){
			$speedguard_tests_url = admin_url('admin.php?page=speedguard');
			$message = sprintf(__( 'Everything is ready to test your site speed! %1$sAdd some pages%2$s to start tests.', 'speedguard' ),'<a href="' .$speedguard_tests_url. '">','</a>');
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
				'<input type="text" size="30" readonly  name="admin_email" value="'.get_option('admin_email').'">');					

				$notices =  Speedguard_Admin::set_notice($ask_to_rate.$second,'success'); 										
			}
		}				
		if ( !empty( $_POST['speedguard_rating']) && $_POST['speedguard_rating'] == 'I already did' ){				
			$speedguard_options['plugin_rated'] = true;
			update_option('speedguard_options', $speedguard_options);
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
		if ( ! empty( $_REQUEST['speedguard'] ) && $_REQUEST['speedguard'] == 'limit_is_reached' ) {
				$private_instance_url = 'https://github.com/WPO-Foundation/webpagetest-docs/blob/master/user/Private%20Instances/README.md';
				$message = sprintf(__( 'Test limit is reached. Please wait for tomorrow or get %1$sprivate instance%2$s.', 'speedguard' ),
					'<a href="' .$private_instance_url. '" target="_blank">','</a>'	);					
				$notices =  Speedguard_Admin::set_notice($message,'error' );	 
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
	//Delete data when original post deleted
	function delete_guarded_page_data($postid){	
		$post_type = get_post_type($postid);		
		if ( (get_post_type($postid)) == Speedguard_Admin::$cpt_name ) return;
		$speedguard_on = get_post_meta($postid,'speedguard_on', true);
			if ($speedguard_on && $speedguard_on[0] == 'true'){
				$args = array(
					'post_type' => Speedguard_Admin::$cpt_name,
					'post_status' => 'publish',
					'posts_per_page'   => 1,
					'fields'=>'ids',
					'meta_query' => array(array('key' => 'guarded_post_id','value' => $postid,'compare' => 'LIKE'))
				);
				$connected_guarded_page = get_posts( $args );
					foreach ($connected_guarded_page as $connected_guarded_page_id){
						wp_delete_post( $connected_guarded_page_id, true); 
					}
			}
	}

		

	 


	
	
}
