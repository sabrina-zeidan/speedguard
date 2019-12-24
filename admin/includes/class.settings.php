<?php
/**
* 
*	Class responsible for SpeedGuard settings
*/
class SpeedGuard_Settings{
	static  $settings_page_hook = 'speedguard_page_speedguard_settings';
	static public $speedguard_options = 'speedguard_options'; 
		function __construct(){
			//Set defaults here			
			add_action('pre_update_option_speedguard_api', array( $this, 'verify_api_key'),10,3); 
			add_action('pre_update_option_speedguard_options', array( $this, 'default_options_set'),10,3);			
		    add_action('added_option', array( $this, 'default_options_added'),10,2);	
			add_action( 'admin_init', array( $this,'speedguard_settings_api') );
			add_action( 'admin_init', array( $this,'speedguard_settings_general') );
			add_filter ('cron_schedules', array( $this,'speedguard_cron_schedules') ); 
			//send report when load_time is updated by cron automatically
			add_action('speedguard_update_results',  array( $this,'update_results_cron_function')); 
			add_action( 'speedguard_email_test_results', array( $this,'email_test_results_function') );
			//Set speed score when load_time is updated
			add_action( 'updated_post_meta', array( $this,'speed_score_function'), 10, 4 );
			//update Averages when any load_time is updated 
			add_action( 'added_post_meta', array( $this,'load_time_updated_function'), 10, 4 );
			add_action( 'updated_post_meta', array( $this,'load_time_updated_function'), 10, 4 );
			add_action( 'deleted_post_meta', array( $this,'load_time_updated_function'), 10, 4 );
		}
	function speed_score_function( $meta_id, $post_id, $meta_key, $meta_value ){
			if ( 'load_time' == $meta_key && $meta_value != 'waiting' ) {
			$world_average = 6.5;			
				if ($meta_value < 5) { $load_time_score = 'green'; }
				elseif ($average_full_load_time < 8) { $load_time_score = 'yellow'; } 
				else { $load_time_score = 'red'; } 
				update_post_meta( $post_id, 'load_time_score', $load_time_score);
			}
		}
	
	function default_options_set($new_value = '', $old_value = ''){		
				$admin_email = get_option('admin_email');
				if (!isset($new_value['show_dashboard_widget'])) $new_value['show_dashboard_widget'] = 'on';
				if (!isset($new_value['show_ab_widget'])) $new_value['show_ab_widget'] = 'on';
				if (!isset($new_value['check_recurrence'])) $new_value['check_recurrence'] = '1';
				if (!isset($new_value['email_me_at'])) $new_value['email_me_at'] = $admin_email;
				if (!isset($new_value['email_me_case'])) $new_value['email_me_case'] = 'just in case average speed worse than';
				if (!isset($new_value['critical_load_time'])) $new_value['critical_load_time'] = '4';				
				if (!isset($new_value['plugin_rated'])) $new_value['plugin_rated'] = false;		 		
				return $new_value;				
		}
	function default_options_added($option, $new_value){
				if ($option == 'speedguard_api'){			
					//if API key not entered 
					if (!isset($new_value))	return;						
					//if API key is valid SpeedGuard_AUTHORIZED
						if (get_option('speedguard_api')['authorized'] == true){   
							$new_value = $this->default_options_set(array());	 	
						}				
						//if API key is not valid
						else {
							$new_value = array(
								'show_dashboard_widget'=>false,
								'show_ab_widget'=> false,
								'check_recurrence' => false,
								'email_me_at' => false,
								'email_me_case' => false,
								'critical_load_time' => false,
								'plugin_rated' => false,
								);
						} 
					update_option('speedguard_options', $new_value);
				} 
				else if ($option == 'speedguard_options'){
					$speedguard_options = get_option('speedguard_options' );	
					$admin_email = $speedguard_options['email_me_at'];
					$check_recurrence = $speedguard_options['check_recurrence'];				
						wp_clear_scheduled_hook('speedguard_update_results');
						if (!wp_next_scheduled ( 'speedguard_update_results' )) {				
							wp_schedule_event(time(), 'speedguard_interval', 'speedguard_update_results');
						}
											
				}
				
		} 
		
		/** API key verification */
		function verify_api_key($new_value, $old_value, $option){ 
			if(!(isset($new_value['api_key'])) || !($new_value['api_key'])) {
					$new_value['authorized'] = false;
			}
			else {
					$new_value['api_key'] = sanitize_text_field($new_value['api_key']); 			
					$gtmetrix_request = add_query_arg( array(
									'url'=> get_home_url(),
									'f'=>'json',
									'k'=> $new_value['api_key'],
									'runs'=>'1',
									'fvonly'=>'1',
									),'http://www.webpagetest.org/runtest.php' );			
					$response = wp_safe_remote_post($gtmetrix_request);
					if ( is_wp_error( $response) ) {return false;}
					$response = wp_remote_retrieve_body( $response);				
					$json_response = json_decode($response, true);			
						if ($json_response['statusCode'] == 400 && $json_response['statusText'] == 'Invalid API Key'){
							$new_value['authorized'] = false;	 
						}
						else {
						$new_value['authorized'] = true;	
						}
				}
			
			return $new_value;
		}		
		function load_time_updated_function( $meta_id, $post_id, $meta_key, $meta_value ){
			if ( 'load_time' == $meta_key && $meta_value != 'waiting' ) {
				$waiting_tests = get_posts(array('post_type' => 'guarded-page','post_status' => 'publish','posts_per_page'   => -1, 'fields' =>'ids','meta_query' => array(array('key'       => 'load_time','value' => 'waiting', 'compare' => 'LIKE'))));
				if (count($waiting_tests) == 0){					
					$query_args = array('post_type' => 'guarded-page','post_status' => 'publish','posts_per_page'   => -1, 'fields' =>'ids',
											'meta_query'        => array(
												 array(
														'key'       => 'load_time',
														'value' => 0, 
														'compare' => '>',
														'type' => 'DECIMAL',
												 )
										),	);
					$guarded_pages = get_posts( $query_args );
					$guarded_page_load_time_all = array();
						if (count($guarded_pages) > 0) {		
							foreach($guarded_pages as $guarded_page) {
								$guarded_page_load_time = get_post_meta(  $guarded_page,'load_time',true);  
								$guarded_page_load_time_all[] = $guarded_page_load_time;
							}
							$average_load_time = round(array_sum($guarded_page_load_time_all)/count($guarded_pages),1); 
							$min_load_time = min($guarded_page_load_time_all);
							$max_load_time = max($guarded_page_load_time_all);	
							$new_averages = array(
								'average_load_time'=> $average_load_time,
								'min_load_time'=> $min_load_time,
								'max_load_time' => $max_load_time,
								'guarded_pages_count' => count($guarded_pages)							
							); 
						
						}
						else {
						$new_averages = array(
								'average_load_time'=> 0,
								'min_load_time'=> 0,
								'max_load_time' => 0,
								'guarded_pages_count' => 0							
							);
						}
						update_option('speedguard_average', $new_averages);
						
			}
		}
		}
		
		function update_results_cron_function() {					
			$args = array('post_type' => Speedguard_Admin::$cpt_name,'post_status' => 'publish','posts_per_page'   => -1,'fields'=>'ids');
			$guarded_pages = get_posts( $args );
			SpeedGuard_Tests::bulk_action_handler('retest_load_time', $guarded_pages); 
			//SpeedGuardCPT::retest_load_time_action_handler($redirect_to, $doaction, $guarded_pages );
			//if send report on: schedule cron job 
			$speedguard_options = get_option('speedguard_options' );	
			$email_me_case = $speedguard_options['email_me_case'];
				if ($email_me_case != 'never'){												
					if (!wp_next_scheduled ( 'speedguard_email_test_results' )) {				
						//in 20 minutes
						wp_schedule_single_event( time() + 20*60, 'speedguard_email_test_results' ); 
					}
				}
		}	         
		function email_test_results_function() {
			$speedguard_options = get_option('speedguard_options' );	
			$email_me_case = $speedguard_options['email_me_case'];
			if ($email_me_case == 'every time after tests are executed'){
				SpeedGuard_Notifications::test_results_email('regular');
			}
			else if ($email_me_case == 'just in case average speed worse than'){			
				$critical_load_time = $speedguard_options['critical_load_time'];
				$average_load_time = get_option('speedguard_average' )['average_load_time'];
				if ($average_load_time > $critical_load_time){ 
					SpeedGuard_Notifications::test_results_email('critical_load_time'); 
				}
			}
			
		}
		public static function action_admin_init() {
		$notices =  Speedguard_Admin::set_notice('hello','success');
					return $notices;	
					}
		
		function speedguard_cron_schedules($schedules){
					$speedguard_options = get_option('speedguard_options' );	
					$check_recurrence = $speedguard_options['check_recurrence'];
							$value = constant( 'DAY_IN_SECONDS' ); 
							$interval = (int)$check_recurrence*$value;
							$schedules['speedguard_interval'] = array(
								'interval' => $interval, // user input integer in second 
								'display'  => __( 'SpeedGuard check interval','speedguard' ),
							);
							
						return $schedules; 
		}				
	
	function speedguard_settings_general() { 
		register_setting( 'speedguard', 'speedguard_options' ); 
		add_settings_section( 'speedguard_widget_settings_section','','','speedguard');	  
		add_settings_field( 'speedguard_show_dashboard_widget', __( 'Show site average load time on Dashboard', 'speedguard' ), array($this,'show_dashboard_widget_fn'), 'speedguard', 'speedguard_widget_settings_section',['label_for' => 'show_dashboard_widget']);
		add_settings_field( 'speedguard_ab_widget', __( 'Show current page load time in Admin Bar', 'speedguard' ), array($this,'show_ab_widget_fn'), 'speedguard', 'speedguard_widget_settings_section',['label_for' => 'show_ab_widget']);
		add_settings_section( 'speedguard_reports_section', '','','speedguard');	  
		add_settings_field( 'speedguard_check_recurrence', __( 'Check pageload speed every', 'speedguard' ),array($this,'check_recurrence_fn'), 'speedguard','speedguard_reports_section',['label_for' => 'check_recurrence']);
		add_settings_field( 'speedguard_email_me_at', __( 'Send me report at', 'speedguard' ),array($this,'email_me_at_fn'), 'speedguard','speedguard_reports_section',['label_for' => 'email_me_at']);
		add_settings_field( 'speedguard_email_me_case', '',array($this,'email_me_case_fn'), 'speedguard','speedguard_reports_section',['label_for' => 'email_me_case']);
		add_settings_field( 'speedguard_critical_load_time', '',array($this,'critical_load_time_fn'), 'speedguard','speedguard_hidden_section',['label_for' => 'critical_load_time']); 
		add_settings_field( 'speedguard_plugin_rated', '',array($this,'plugin_rated_fn'), 'speedguard','speedguard_hidden_section',['label_for' => 'plugin_rated']); 
	}
	
	function show_dashboard_widget_fn( $args ) {
		$options = get_option('speedguard_options');
		$field_name = esc_attr( $args['label_for'] );
		if($options[$field_name] == 'on') { $checked = ' checked="checked" '; }	else { $checked = '';}
		echo "<input type='hidden' name='speedguard_options[".$field_name."]' value='off' /><input ".$checked." id='speedguard_options[".$field_name."]' name='speedguard_options[".$field_name."]' type='checkbox' />";
	}
	function show_ab_widget_fn( $args ) {
		$options = get_option('speedguard_options');
		$field_name = esc_attr( $args['label_for'] );
		if($options[$field_name] == 'on') { $checked = ' checked="checked" '; }	else { $checked = '';}
		echo "<input type='hidden' name='speedguard_options[".$field_name."]' value='off' /><input ".$checked." id='speedguard_options[".$field_name."]' name='speedguard_options[".$field_name."]' type='checkbox' />";
	}
	function check_recurrence_fn( $args ) {
		$options = get_option('speedguard_options');
		$field_name = esc_attr( $args['label_for'] );
		$days =_n(' day',' days',$options[$field_name],'speedguard');
		$string = "<input id='speedguard_options[".$field_name."]' name='speedguard_options[".$field_name."]' type='text' class='numbers' size='2' value='".$options[$field_name]."'/> ".$days;
		echo $string;
}
	function email_me_at_fn( $args ) {
		$options = get_option('speedguard_options');
		$field_name = esc_attr( $args['label_for'] ); 
		echo "<input id='speedguard_options[".$field_name."]' name='speedguard_options[".$field_name."]' type='text' size='40' value='".$options[$field_name]."'/>";			
	}
	function print_description( $item ) {
				echo $item;
			}
	function email_me_case_fn( $args ) {
		$options = get_option('speedguard_options');
		$field_name = esc_attr( $args['label_for'] );
		$items = array(
			'every time after tests are executed' => __('every time after tests are executed','speedguard'),
			'just in case average speed worse than' => __('just in case average speed worse than','speedguard'),
			'never' => __('never','speedguard')
			);
		foreach($items as $item=>$item_label) {

			$checked = ($options[$field_name] == $item) ? ' checked="checked" ' : '';		
	
			echo "<input ".$checked." type='radio' name='speedguard_options[".$field_name."]' id='".$item."' value='".$item."' /><label for='".$item."'>".$item_label."</label>";
			$critical_load_time = $options['critical_load_time'];
			if ($item == 'just in case average speed worse than') $this->critical_load_time_fn(array('label_for'=>'critical_load_time', 'show'=>true));
			echo "</label><br />";
		
		}
	}
	function critical_load_time_fn( $args ) { 
	if ( isset($args['show']) && $args['show'] == true){
		$options = get_option('speedguard_options');
		$field_name = esc_attr( $args['label_for'] );
		echo " <input type='text' id='speedguard_options[critical_load_time]' name='speedguard_options[critical_load_time]'  class='numbers'  size='2' value='".$options[$field_name]."'> ".__('s','speedguard');
		}
	} 
	function plugin_rated_fn( $args ) {
		$options = get_option('speedguard_options');
		$field_name = esc_attr( $args['label_for'] );
		echo "<input type='hidden' name='speedguard_options[".$field_name."]' value='".$options['plugin_rated']."' />";
	}
	function speedguard_settings_api() {
		register_setting( 'speedguard_api', 'speedguard_api' ); 
		add_settings_section( 'speedguard_api_section','','', 'speedguard_api'); 
		add_settings_field('speedguard_api_key',  __( 'Enter API key:', 'speedguard' ),  array($this,'speedguard_field_api'), 'speedguard_api', 'speedguard_api_section');
	} 
	function speedguard_field_api( $args ) {		 
		$options = get_option('speedguard_api');
		$api_key = $options['api_key'];
		echo "<input id='speedguard_api_key' name='speedguard_api[api_key]' size='40' type='text' value='{$api_key}' />";
		if ($api_key && !(SpeedGuard_AUTHORIZED)) echo '<p><em>API key you have entered is not valid.</em></p>';
		
	} 
			
	
	public static function settings_page() {
		if (Speedguard_Admin::is_screen('settings')){		
			SpeedGuardWidgets::add_meta_boxes();						
			?>
			<div class="wrap">        
				<h2><?php _e( 'SpeedGuard :: Settings', 'speedguard' ); ?></h2>		
				
						<div id="poststuff" class="metabox-holder has-right-sidebar">
							<div id="side-info-column" class="inner-sidebar">
								<?php 							
								do_meta_boxes( '', 'side', 0 ); ?>
							</div>
							<div id="post-body" class="has-sidebar">
								<div id="post-body-content" class="has-sidebar-content">
								<form method="post" action="options.php">
								<?php	do_meta_boxes( '', 'api', 0);	?>
							</form>
							<form method="post" action="options.php">
								<?php   // wp_nonce_field( 'update-options' );
								do_meta_boxes( '', 'normal', 0 );?>
							</form>						
								</div>
							</div>
						</div>	
					</form>
			</div>
			<?php 
			}
		}			 
		public static function credits_meta_box(){echo SpeedGuardWidgets::credits_meta_box();}	
		public static function tips_meta_box(){echo SpeedGuardWidgets::tips_meta_box();}			
		public static function api_meta_box(){
			settings_fields( 'speedguard_api' );do_settings_sections('speedguard_api'); submit_button(__( 'Save API Key','speedguard'),'primary','submit',false );
			$options = get_option('speedguard_api');
			$api_key = $options['api_key'];
			if (!SpeedGuard_AUTHORIZED){			
			$get_api_key_url = 'http://www.webpagetest.org/getkey.php';
			$get_api_key_link = sprintf(__( 'Fill out this %1$sshort form%2$s.', 'speedguard' ),
					'<a href="' .$get_api_key_url. '" target="_blank">',
					'</a>'
					);
	
		
			$instructions = '<div id="api-instructions"><b>'.__( 'To obtain API key and start monitor your site speed for free:','speedguard').'</b></p><ol><li>'.$get_api_key_link.'
			</li><li>'.__( 'Check your email and confirm request','speedguard').'</li><li>'.__('You will receive email with "WebPagetest API Key" subject. Copy your API key from this email into the field and press "Save API Key".','speedguard').'</li></ol></div>';  
			echo $instructions;
			}
		}	 
		public static function settings_meta_box(){settings_fields('speedguard');do_settings_sections( 'speedguard' );  submit_button( __( 'Save Settings','speedguard'),'primary','submit',false );}

		
}
new SpeedGuard_Settings; 


