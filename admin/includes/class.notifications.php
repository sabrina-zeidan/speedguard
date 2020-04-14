<?php
/**
*
*	Class responsible for email notification templates
*/
class SpeedGuard_Notifications{
	function __construct(){
	}    
	function test_results_email($type) {	
		if (SpeedGuard_AUTHORIZED){
			$speedguard_options = Speedguard_Admin::get_this_plugin_option('speedguard_options' );	
			$admin_email = $speedguard_options['email_me_at'];
			$site_url = str_replace( 'http://', '', get_home_url() );   
			$site_date = get_date_from_gmt(date('Y-m-d H:i:s',time()),'Y-m-d H:i:s');
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
				if( $guarded_pages ) :	
					ob_start();
							echo '<html><head><title>'.__('SpeedGuard Report','speedguard').'</title></head>
									<body style="padding-top: 50px; padding-bottom: 50px;  background:#f5f5f5;" >
										<table align="center" width="560" bgcolor="#fff" border="0" cellspacing="0" >  
											<tr>
												<td style="padding: 10px;" bgcolor="#c1e6fd" align="left"><p style="text-align:right; font-size: 1.2em; font-weight: bold;" >'.__('SpeedGuard Report for','speedguard').' '.$site_url.'<span style="font-weight:100;"> ['.$site_date.']</span></p><p style="text-align:right; font-size: 0.9em; color:#5f5a5a;">'.sprintf(__('You can stop guarding URLs or add new on %1$sSpeedGuard Tests%2$s page','speedguard'),'<a href="'.Speedguard_Admin::speedguard_page_url('tests').'" target="_blank">','</a>').'</p></td> 
											</tr>
											<tr>
												<td width="100%" style="padding: 0;">';
													if (isset($note)) echo '<br>'.$note;						
													echo '<table width="100%" style="margin-top: 2em; border-collapse: collapse;">  
													<thead><tr style="border: 1px solid #ccc;" >
													<td>'. __( 'URL', 'speedguard' ).'</td>
													<td>'. __( 'Load time', 'speedguard' ).'</td>
													<td>'. __( 'Report', 'speedguard' ).'</td>
													<td>'. __( 'Updated', 'speedguard' ).'</td>
													</tr></thead><tbody>';			
													
														foreach($guarded_pages as $guarded_page_id) {  
															$guarded_page_url = get_the_title($guarded_page_id);
															$guarded_page_load_time = get_post_meta($guarded_page_id, 'load_time',true );
															$report_link = 'https://www.webpagetest.org/result/'.get_post_meta($guarded_page_id,'webpagetest_request_test_result_id',true);		
															$gmt_report_date = date('Y-m-d H:i:s',get_post_meta($guarded_page_id,'webpagetest_request_test_result_date',true)); 
															$updated = get_date_from_gmt($gmt_report_date,'Y-m-d H:i:s');							
														echo '<tr style="border: 1px solid #ccc;">
															<td>'.$guarded_page_url.'</td>
															<td style="padding: 1em 0;">'.$guarded_page_load_time.'</td> 
															<td><a href="'.$report_link.'" target="_blank">'.__('Report','speedguard').'</a></td>
															<td style="font-size:0.8em;">'.$updated.'</td>
															</tr>';
														} 
													
													echo '</tbody>
													</table>
													<div style="padding: 1em; color:#000;"> 
													<p style="font-size: 1.2em; font-weight: bold;" >'.__('Why is my website so slow?','speedguard').'</p>
													'.str_replace( "utm_medium=sidebar", "utm_medium=email_report", SpeedGuardWidgets::tips_meta_box()).'
													</div>
												</td> 
											</tr>
											<tr>
												<td style="padding: 10px;" bgcolor="#e6e1e1" align="right"><p style="color:#5f5a5a; text-align:right; font-size: 0.9em;">'.sprintf(__('This report was requested by administrator of %1$s','speedguard'),$site_url).'<p style="color:#5f5a5a; text-align:right; font-size: 0.9em;">'.sprintf(__('You can change SpeedGuard notification settings %1$shere%2$s any time.','speedguard'),'<a href="'.Speedguard_Admin::speedguard_page_url('settings').'" target="_blank">','</a>').'</td>
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
			}
	}
 
}
new SpeedGuard_Notifications;


