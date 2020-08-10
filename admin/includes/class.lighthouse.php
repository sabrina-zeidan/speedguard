<?php
/**
*
*	Class responsible for handling tests via Lighthouse
*/
class SpeedGuard_Lighthouse{ 
//v5 https://developers.google.com/speed/docs/insights/v5/get-started
	function __construct(){	
	}
	/** New Test */
	public static function lighthouse_new_test($guarded_page_id) {
		sleep(3); //So we can use LightHouse without API
		
		$guarded_page_url = get_post_meta($guarded_page_id,'speedguard_page_url', true);		
		$device = SpeedGuard_Admin::get_this_plugin_option( 'speedguard_options' )['test_connection_type'];
		$request = add_query_arg( array(
							'url'=> $guarded_page_url,
							'category' => 'performance',	
							'strategy' => $device,							
							),'https://www.googleapis.com/pagespeedonline/v5/runPagespeed' );	
		$args = array('timeout' => 30);
		$response = wp_safe_remote_get($request, $args);
		if ( is_wp_error( $response) ) { return false;}
		$response = wp_remote_retrieve_body($response);	
		$json_response = json_decode($response, true, 1512);	
			if (!empty($json_response['lighthouseResult'])) {
				

				update_post_meta($guarded_page_id, 'speedguard_page_connection', $device );	
				$code = $guarded_page_url.'|'.$device;
				$my_post = array(
					  'ID'           => $guarded_page_id,
					  'post_title'   => $code,
				  );
				$update_post = wp_update_post( $my_post );		
				$lcp = $json_response['lighthouseResult']['audits']['largest-contentful-paint']; //title, description, score, scoreDisplayMode, displayValue, numericValue
				$updated = update_post_meta($guarded_page_id, 'load_time', $lcp);  
				return $updated; 
			}
			else {
				//TODO error handling
			}  
		
	}	

}
new SpeedGuard_Lighthouse;