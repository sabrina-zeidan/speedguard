<?php
/**
*
*	Class responsible for email notifications
*/
class SpeedGuard_Notifications{
	function __construct(){
	}    
	public static function test_results_email($type) {	
			//Check if there are any tests running at the moment, and reschedule if so
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
				$waiting_pages = $the_query->posts;
				wp_reset_postdata();
				if (count($waiting_pages) > 0) {
					//In 10 minutes
					wp_schedule_single_event( time() + 10*60, 'speedguard_email_test_results' ); 
				}
				else {
				$speedguard_options = SpeedGuard_Admin::get_this_plugin_option('speedguard_options' );	
				$admin_email = $speedguard_options['email_me_at']; 
				$site_url = parse_url(get_home_url());							
				$site_url = $site_url['host'];					
				//$site_date = get_date_from_gmt(date('Y-m-d H:i:s',time()),'Y-m-d H:i:s');
				$average_site_speed = SpeedGuard_Admin::get_this_plugin_option('speedguard_average');
				$average_site_speed = $average_site_speed['average_load_time'];
				$critical_load_time = $speedguard_options['critical_load_time'];
				if ($type == 'critical_load_time'){
					$subject = sprintf(__('%1$s is slow [SpeedGuard]','speedguard'),$site_url); 
					$note = sprintf(__('%1$s takes more than %2$s seconds to load','speedguard'),$site_url,$critical_load_time);
				}
				else {
					$subject = sprintf(__('%1$s speed report [SpeedGuard]','speedguard'),$site_url);
				}
				$args = array(
					'post_type' => SpeedGuard_Admin::$cpt_name ,
					'post_status' => 'publish',
					'posts_per_page'   => -1, 
					'fields' =>'ids',
					'no_found_rows' => true 
				);
				$the_query = new WP_Query( $args );
				$guarded_pages = $the_query->posts;			
					if( $guarded_pages ) :	
						ob_start();							
								echo '<html><head>
								<title>'.__('SpeedGuard Report','speedguard').'</title>
								<style>
								table {border-collapse: collapse;width: 560px; margin-top: 2em;}
								th, td {text-align: left; padding: 8px;	}
								tr:nth-child(even) {background-color: #f2f2f2;}
								</style>
								</head>
										<body style="padding-top: 50px; padding-bottom: 50px;  background:#fff; color:#000;" >
											<table align="center">  
												<tr>
													<td style="padding: 10px;" bgcolor="#f7f7f7"><p style="text-align:center; font-size: 1.2em; font-weight: bold;" >'.__('Average site speed is','speedguard').' '.$average_site_speed.'s</p>
													<p>												
													'.sprintf(__('See the entire %1$sreport%2$s in the WordPress admin.','speedguard'),'<a href="'.SpeedGuard_Admin::speedguard_page_url('tests').'" target="_blank">','</a>').'</p>												
													</td> 
												</tr>
												<tr> 
													<td width="100%" style="padding: 0;">';
														if (isset($note)) echo '<br>'.$note;						
														echo '<table>  
														<thead><tr style="border: 1px solid #ccc;" >
														<td>'. __( 'URL', 'speedguard' ).'</td>
														<td>'. __( 'Load time', 'speedguard' ).'</td>													
														</tr></thead><tbody>';			
														
															foreach($guarded_pages as $guarded_page_id) {  
																//$guarded_page_url = get_the_title($guarded_page_id);
																$guarded_page_url = get_post_meta($guarded_page_id, 'speedguard_page_url',true );
																$load_time = get_post_meta( $guarded_page_id,'load_time');
																$guarded_page_load_time = $load_time[0]['displayValue'];	
																
						
															echo '<tr style="border: 1px solid #ccc;">
																<td>'.$guarded_page_url.'</td>
																<td>'.$guarded_page_load_time.'</td> 																
																</tr>';
															} 
														
														echo '</tbody>
														</table>													
														<div style="padding: 1em; color:#000;"> 
														<p style="font-size: 1.2em; font-weight: bold;" >'.__('Important questions:','speedguard').'</p>';											
														str_replace( "utm_medium=sidebar", "utm_medium=email_report", SpeedGuardWidgets::important_questions_meta_box()); 		
														echo '
														</div>
													</td> 
												</tr>
												<tr>
													<td style="padding: 10px;color:#5f5a5a; text-align:right; font-size: 0.9em;" bgcolor="#e6e1e1" align="right">'.sprintf(__('This report was requested by administrator of %1$s','speedguard'),$site_url).'. '.sprintf(__('You can change SpeedGuard notification settings %1$shere%2$s any time.','speedguard'),'<a href="'.SpeedGuard_Admin::speedguard_page_url('settings').'" target="_blank">','</a>').'</td>
												</tr>
											</table>
										</body>
									</html>'; 			
						   $message = ob_get_contents();
						   ob_end_clean();		
						$headers = array('Content-Type: text/html; charset=UTF-8');
						wp_mail( $admin_email, $subject, $message, $headers);
					endif;	
					wp_reset_postdata();				
			
				}//if there are no waiting pages
	}
 
}
new SpeedGuard_Notifications;


