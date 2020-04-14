<?php
/**
*
*	Class responsible for adding CPT for SpeedGuard
*/
class SpeedGuard_WebPageTest{ 
	function __construct(){	
	}
	/** New Test */
	public static function webpagetest_new_test($guarded_page_id) {
		$guarded_page_url = get_the_title($guarded_page_id);
		$api_key = Speedguard_Admin::get_this_plugin_option( 'speedguard_api' )['api_key'];
		$location = Speedguard_Admin::get_this_plugin_option( 'speedguard_options' )['test_server_location'];
		$connection = Speedguard_Admin::get_this_plugin_option( 'speedguard_options' )['test_connection_type'];
		$webpagetest_request_request = add_query_arg( array(
							'url'=> $guarded_page_url,
							'f'=>'json',
							'k'=> $api_key,
			  				'runs'=>'3',
							'fvonly'=>'1',
							'location'=> $location.'.'.$connection,
							),'http://www.webpagetest.org/runtest.php' );			
		$response = wp_safe_remote_post($webpagetest_request_request);
		if ( is_wp_error( $response) ) {return false;}
		$response = wp_remote_retrieve_body( $response);				
		$json_response = json_decode($response, true);			
		if ($json_response['statusCode'] == 200){
			$test_data = $json_response['data'];					
			$testId = $test_data['testId'];								
			update_post_meta($guarded_page_id, 'webpagetest_request_test_result_id', $testId );												
			update_post_meta($guarded_page_id, 'load_time', 'waiting' );	
		} 
		else if ($json_response['statusCode'] == 400){
			$notice = $json_response['statusText'];
		//	$redirect_to = add_query_arg( 'speedguard', 'limit_is_reached');
		}  
		
	}
	
	/** Update waitind test results */
	public static function update_waiting_pageload($guarded_page_id) { 
			$webpagetest_request_test_result_id = get_post_meta($guarded_page_id,'webpagetest_request_test_result_id', true);
			$webpagetest_request_test_results_link = 'http://www.webpagetest.org/jsonResult.php?test='.$webpagetest_request_test_result_id; 
			$webpagetest_request_test_results = wp_safe_remote_get($webpagetest_request_test_results_link);							
			if ( is_wp_error( $webpagetest_request_test_results ) ) {return false;}
			$webpagetest_request_test_results = wp_remote_retrieve_body( $webpagetest_request_test_results);
			$webpagetest_request_test_results = json_decode($webpagetest_request_test_results, true);		
				if ($webpagetest_request_test_results["statusCode"] != 200){
					$updated = update_post_meta( $guarded_page_id, 'load_time', 'waiting');	 
				}
				else { 
					$average_speedindex = round(($webpagetest_request_test_results["data"]["average"]["firstView"]["SpeedIndex"]/1000),1);   
					update_post_meta( $guarded_page_id, 'load_time', $average_speedindex);	 
					$completed_date = $webpagetest_request_test_results["data"]["completed"];										
					$updated = update_post_meta( $guarded_page_id, 'webpagetest_request_test_result_date', $completed_date);										
				}
				//$notice =  Speedguard_Admin::set_notice(__('Please wait. Tests are running...','speedguard'),'success' );	 
				return $updated; 	
		}
	/** API credits usage */
	public static function credits_usage() {		
			if (defined ('SPEEDGUARD_WPT_API')){
				$api_key = Speedguard_Admin::get_this_plugin_option( 'speedguard_api' )['api_key'];
				$webpagetest_request_request = add_query_arg( array('k'=> $api_key),'https://www.webpagetest.org/usage.php' );							
				$response = wp_safe_remote_post($webpagetest_request_request);
				if ( is_wp_error( $response) ) {return false;}
				$response = wp_remote_retrieve_body( $response);	
					preg_match_all("'<tr>(.*?)</tr>'si", $response, $match);
					preg_match_all ("/<td.*?>([^`]*?)<\/td>/", $match[1][1], $matches);
					$today_date = $matches[1][0];
					$credits_used = intval((int)$matches[1][1]/3);			
					$credits_limit = intval((int)$matches[1][2]/3);
					$message = sprintf(__( '%1$s of %2$s tests used today', 'speedguard' ),$credits_used,$credits_limit) . "\n\n";
					$result = $message.' '.$today_date; 
			}
		return $result;

}



	

}
new SpeedGuard_WebPageTest;