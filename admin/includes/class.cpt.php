<?php
/**
*
*	Class responsible for adding CPT for SpeedGuard
*/
class SpeedGuardCPT{

	//CPT custom fields
	function __construct(){		
		add_action('admin_init', array($this,'speedguard_cpt') );	
		//for cpt edit page only
		add_action( 'load-edit.php', array( $this, 'on_cpt_page' ) );
		add_action ( 'wp_ajax_' . 'webpagetest_update_waiting_ajax',  array( $this, 'webpagetest_update_waiting_ajax_function') );
		add_action ( 'wp_ajax_' . 'webpagetest_update_waiting_ajax',  array( $this, 'webpagetest_update_waiting_ajax_function') );

	}  
	
	
	function webpagetest_update_waiting_ajax_function() {
		$post_ids = json_decode(stripslashes($_POST['post_ids']));
		  $results = array();
			foreach($post_ids as $guarded_page_id){
				$gtmetrix_test_result_id = get_post_meta($guarded_page_id,'gtmetrix_test_result_id', true);
				$gtmetrix_test_results_link = 'http://www.webpagetest.org/jsonResult.php?test='.$gtmetrix_test_result_id; 
				$gtmetrix_test_results = wp_safe_remote_get($gtmetrix_test_results_link);							
				//if ( is_wp_error( $gtmetrix_test_results ) ) {return false;}
				$gtmetrix_test_results = wp_remote_retrieve_body( $gtmetrix_test_results);
				$gtmetrix_test_results = json_decode($gtmetrix_test_results, true);		
					if ($gtmetrix_test_results["statusCode"] != 200){
						$r['success'] = FALSE;						
					}
					else { 
				
						$average_full_load_time = round(($gtmetrix_test_results["data"]["average"]["firstView"]["fullyLoaded"]/1000),1);  
						update_post_meta( $guarded_page_id, 'load_time', $average_full_load_time);	   
						$completed_date = $gtmetrix_test_results["data"]["completed"];										
						update_post_meta( $guarded_page_id, 'gtmetrix_test_result_date', $completed_date);	
						$r['success'] = TRUE;			 			
				}
				
				array_push($results, array(
					'success' => $r['success'],
					'html' => $guarded_page_id,
					'test_link' => $gtmetrix_test_results_link,
					'status' => $gtmetrix_test_results["statusCode"],
					'average_full_load_time' => $average_full_load_time,
				));	
			}//foreach
			echo json_encode ( $results );
			die();	 
}


	

	function on_cpt_page() { 
		$screen = get_current_screen();	
		if ( ($screen->id == 'edit-'.Speedguard_Admin::$cpt_name) ) {
		if (SpeedGuard_AUTHORIZED){
			add_action( 'admin_footer','ajax_waiting'); 
			function ajax_waiting() {
				$args = array(
					'post_type' => 'guarded-page',
					'post_status' => 'publish',
					'posts_per_page'   => -1,
					'fields'=>'ids',
					'meta_query' => array(array('key' => 'load_time','value' => 'waiting','compare' => 'LIKE'))
				);
				$waiting_pages = get_posts( $args );					
				?>
				<script type="text/javascript">
					jQuery(document).ready(function($){					
					var waiting_pages_number = <?php echo count($waiting_pages); ?>; 
					var waiting_pages = <?php echo json_encode( array_values( $waiting_pages ) ); ?>;
					var waiting_pages = JSON.stringify(waiting_pages);					
					//if there are guarded pages waiting for results
						if ( parseInt ( waiting_pages_number ) > 0 ) {
							console.log("There are tests waiting :",waiting_pages_number); // string 1 rad baz tubular
								function update_test_results() {
									$.ajax(ajaxurl,{
										type: 'POST',
										data: { action: 'webpagetest_update_waiting_ajax',					
												post_ids: waiting_pages, 
												},
										dataType: 'json',
										//complete : function ( response ) {   // to develop always	}, 
										success: function ( response ) {
													response.forEach(function(response) {									
														if ( response.success ) {
															console.log("Success here:",response.html); 
															 $("#post-"+response.html+" .load_time").empty().append(response.average_full_load_time);
															var location_updated = location.href.replace(/retest_load_time[^&$]*/i, 'load_time_updated');
															//window.location_updated; // This is not jQuery but simple plain ol' JS
															window.location.replace(location_updated); // This is not jQuery but simple plain ol' JS
															
														} else {	
															console.log("NO Success:",response); 
															//callback update_test_results again in 10 seconds
															setTimeout(function () {
																update_test_results();
															}, 20000) 
														}										
													  }) 
												},
											error: function ( errorThrown ) {
												console.log( errorThrown ); 
											}, 
										});
										  }
							
						update_test_results();	

					}
					}); 
	
				</script>
				<?php
			
		}
	
	
			add_action('admin_footer', 'page_view_reconstruct');	
			function page_view_reconstruct() {   
				$main_area = '
				<div id="poststuff" class="metabox-holder has-right-sidebar">
					<!--Sidebar -->
					<div id="side-info-column" class="inner-sidebar">
						<div id="side-sortables" class="meta-box-sortables">
							<div id="speedguard-api-credits-meta-box" class="postbox ">
								<button type="button" class="handlediv" aria-expanded="true"><span class="toggle-indicator" aria-hidden="true"></span></button><h2 class="handle"><span>'.__('API Credits','speedguard').'</span></h2>
								<div class="inside">'.SpeedGuardWidgets::credits_meta_box().'</div>
							</div>
							<div id="speedguard-tips-meta-box" class="postbox">
								<button type="button" class="handlediv" aria-expanded="true"><span class="toggle-indicator" aria-hidden="true"></span></button><h2 class="handle"><span>'.__('Why is my website so slow?','speedguard').'</span></h2>
								<div class="inside">'.SpeedGuardWidgets::tips_meta_box().'</div>
							</div>
						</div>
					</div>
					<!--Main area-->
					<div id="post-body" class="has-sidebar">
						<div id="post-body-content" class="has-sidebar-content">
							<div id="normal-sortables" class="meta-box-sortables">
								<div id="speedguard-speedresults-meta-box" class="postbox ">
									<button type="button" class="handlediv" aria-expanded="true"><span class="toggle-indicator" aria-hidden="true"></span></button><h2 class="hndle ui-sortable-handle"><span>'.__('Site Speed Results','speedguard').'</span></h2>
									<div class="inside">'.SpeedGuardWidgets::speedguard_dashboard_widget_function().'</div>
								</div>
								<div id="speedguard-add-new-url-meta-box" class="postbox ">
									<button type="button" class="handlediv" aria-expanded="true"><span class="toggle-indicator" aria-hidden="true"></span></button><h2 class="hndle"><span>'.__('Add new URL', 'speedguard').'</span></h2>
									<div class="inside">
										<form name="speedguard_add_url" id="speedguard_add_url" action="" method="post">   
											<input type="text" id="speedguard_new_url" name="speedguard_new_url" value="" placeholder="'.__('Start typing the title of the post','speedguard').'" autofocus="autofocus"/>
											<input type="hidden" id="speedguard_new_url_permalink" name="speedguard_new_url_permalink" value=""/>
											<input type="hidden" id="speedguard_new_url_id" name="speedguard_new_url_id" value=""/>
											<input type="hidden" name="speedguard" value="add_new_url" />
											<input type="submit" name="Submit" value="'.__('Add','speedguard').'" />
										</form>
									</div>
								</div>
								
								<div id="speedguard-results-meta-box" class="postbox ">
									<button type="button" class="handlediv" aria-expanded="true"><span class="screen-reader-text">Toggle panel: Results</span><span class="toggle-indicator" aria-hidden="true"></span></button><h2 class="hndle"><span>'.__('Tests Results','speedguard').'</span></h2>
									<div class="inside" id="place-here"></div>
								</div>
								<div id="speedguard-site-speed-score" class="postbox ">
									<button type="button" class="handlediv" aria-expanded="true"><span class="toggle-indicator" aria-hidden="true"></span></button><h2 class="hndle"><span>'.__('Speed Score','speedguard').'</span></h2>
									<div class="inside">
									<table>
									<tr>
									<td><span class="speedguard-score score-green"></span></td>
									<td>1s — 4.9'.__('s','speedguard').'</td>
									<td>'.__('Better than average. Probably, your site speed has no direct negative impact on search ranking. Yet, you may significantly improve user experience (which is another important search ranking factor) by reducing site load time. This is especially true for ecommerce websites. In most cases load time can be improved to 2 seconds.','speedguard').'</td>
									</tr>
									<tr>
									<td><span class="speedguard-score score-yellow"></span></td>
									<td>5s — 7.9'.__('s','speedguard').'</td>
									<td>'.__('Not bad, but not good either. World average full load time is 6.5s [2018], so this is mediocre result. Reducing your site load time at least to 4 seconds will help you to outrank your competitors on Google.','speedguard').'</td>
									</tr>
									<tr>
									<td><span class="speedguard-score score-red"></span></td>
									<td>8'.__('s','speedguard').' '.__('and more','speedguard').'</td>
									<td>'.__('Worse than the average. Your SE rankings are definitely harmed by your site speed. There might be a long list of reasons why your website is slow and a lot of work to do. But the good news is, you may see first positive results as soon as you start.','speedguard').'</td>  
									</tr> 
									</table>
									</div>
								</div>
							</div>	
						</div>
					</div>
				</div>';
			echo $main_area;  
			}
			add_action( 'admin_footer','autocomplete_search'); 
			
			function autocomplete_search() {
				$args = array(
					'post_type' => 'post',
					'post_status' => 'publish',
					'posts_per_page'   => -1 // all posts
				);

				$posts = get_posts( $args );
					if( $posts ) :
						foreach( $posts as $k => $post ) {
							$source[$k]['ID'] = $post->ID;
							$source[$k]['label'] = $post->post_title; // The name of the post
							$source[$k]['permalink'] = get_permalink( $post->ID );
						} 

				?>
				<script type="text/javascript">
					jQuery(document).ready(function($){
						var posts = <?php echo json_encode( array_values( $source ) ); ?>;
						jQuery( 'input[name="speedguard_new_url"]' ).autocomplete({
							source: posts,
							minLength: 2,
							select: function(event, ui) {
								event.preventDefault();
								$("#speedguard_new_url").val(ui.item.label);
								$("#speedguard_new_url_permalink").val(ui.item.permalink);
								$("#speedguard_new_url_id").val(ui.item.ID);
							}
						});
					}); 		
				</script>
				<?php
				endif;
			}			
			
			//Hide views
			add_filter('views_edit-'.Speedguard_Admin::$cpt_name,'update_guarded_page_views');
			function update_guarded_page_views($views) {}
			//Disable month dropdown
			add_filter('disable_months_dropdown', 'filter_months_dropdown',10,2);
			function filter_months_dropdown( $disable, $post_type ){ $disable = true; return $disable; }
			//Add custom columns
			add_filter( 'manage_'.Speedguard_Admin::$cpt_name.'_posts_columns', 'set_custom_edit_mycpt_columns' );
			function set_custom_edit_mycpt_columns( $columns ) {
			   unset( $columns['date'] );
			   unset( $columns['title'] );
			   $columns['guarded_page_title'] = __( 'URL', 'speedguard' );
			   $columns['load_time'] = __( 'Load time', 'speedguard' );
			   $columns['report_link'] = __( 'Report link', 'speedguard' ); 
			   $columns['report_date'] = __( 'Updated', 'speedguard' );
			  return $columns;
			}
			add_action('before_delete_post','before_cpt_delete'); 
			function before_cpt_delete($ID) {
				$guarded_post_id = get_post_meta($ID,'guarded_post_id', true);
				update_post_meta($guarded_post_id, 'speedguard_on', 'false');  						
			}
			add_filter( 'bulk_post_updated_messages', 'cpt_post_updated_messages_filter', 10, 2 );
			function cpt_post_updated_messages_filter( $bulk_messages, $bulk_counts ) {
				$bulk_messages[Speedguard_Admin::$cpt_name] = array( 
					 'deleted'   => _n( '%s not guarded anymore', '%s not guarded anymore', $bulk_counts['deleted'] ),
				);
			return $bulk_messages;
			}		
			//Add data to custom columns
			add_action( 'manage_'.Speedguard_Admin::$cpt_name.'_posts_custom_column' , 'custom_mycpt_column', 10, 2 );
			function custom_mycpt_column( $column, $post_id ) {
			  switch ( $column ) {				
				case 'guarded_page_title' :
				 $title = get_the_title($post_id);
				 $permalink = get_permalink(get_post_meta($post_id,'guarded_post_id',true));
				 echo '<a href="'.$permalink.'" target="_blank">'.$title.'</a>';
				 break;
				case 'report_date' :
				$date = get_post_meta($post_id,'gtmetrix_test_result_date',true);
					if ($date){
						$gmt_report_date = date('Y-m-d H:i:s',(int)$date);
						echo get_date_from_gmt($gmt_report_date,'Y-m-d H:i:s');
					}
				  break;
				case 'load_time' :
				 $load_time_result = get_post_meta(  $post_id,'load_time', true);
				 $load_time_score = get_post_meta( $post_id,'load_time_score',true);
				 if ($load_time_result == 'waiting'){echo '<div class="loading" title="'.__('test is running...','speedguard').'"></div>';}
				 else {echo '<span class="speedguard-score score-'.$load_time_score.'">'.$load_time_result.'</span>';} 
				 break;
				case 'report_link' :
				  $report_url= 'https://www.webpagetest.org/result/'.get_post_meta($post_id,'gtmetrix_test_result_id', true );
				  $report_link = sprintf(__( '%1$sReport%2$s', 'speedguard' ),'<a href="' .$report_url. '" target="_blank">','</a>'	);
				  echo $report_link;
				  break;
				} 
			}
			//Manage default columns
			//add_action( 'manage_posts_columns' , 'custom_default_column', 10, 2 );
			function custom_default_column( $column, $post_id ) {
				switch ( $column ) {
					case 'title' :
					  $title = get_the_title($post_id);
					  $permalink = get_permalink(get_post_meta($post_id,'guarded_post_id',true  ));
					  echo '<a href="'.$permalink.'" target="_blank">hi '.$title.'</a>';
					  break;
				} 
				return $columns;
		  }
			
			//Make them sortable

			//Remove quick edit menu
			add_filter('post_row_actions', 'remove_quick_edit_menu', 10, 2);
			function remove_quick_edit_menu($actions, $post){ }
			//Edit actions
			add_filter( 'bulk_actions-edit-'.Speedguard_Admin::$cpt_name, 'register_my_bulk_actions' );
			function register_my_bulk_actions($bulk_actions) {
				//remove default
				unset( $bulk_actions['edit'] );
				unset( $bulk_actions['trash'] );	
				//Add custom bulk action
				$bulk_actions['retest_load_time'] = __( 'Retest load time', 'speedguard');			
				$bulk_actions['delete'] = __( 'Stop guarding', 'speedguard');			
				return $bulk_actions;
			}
			//Handle newly added bulk action form submission
			//add_filter( 'handle_bulk_actions-edit-'.Speedguard_Admin::$cpt_name, array( $this, 'retest_load_time_action_handler'), 10, 3 );		

 
		}
		else {   
		wp_safe_redirect( admin_url('admin.php?page=speedguard_settings')); exit;
		}
		}
	}
	public function speedguard_cpt() {
		$labels = array( 'name' => __('Speedguard :: Guarded pages','speedguard'), 'not_found' => __('No pages guarded yet. Add new URL in field above for the start.','speedguard'));
		$args = array( 			
			'public'      => false, 
			'exclude_from_search'      => true, 
			'publicly_queryable'      => false, 
			'show_ui'      => true, 
			'labels'      => $labels,
			'supports' => array('title','custom-fields'),	
		);
		register_post_type( Speedguard_Admin::$cpt_name, $args );
	}  
	public static function import_data() { 
				$guarded_page_url = htmlspecialchars($_POST['speedguard_new_url_permalink']); 
				if ($guarded_page_url){
					$guarded_post_id = htmlspecialchars($_POST['speedguard_new_url_id']); 
					//Check if it's already exists
					$post_exists = post_exists($guarded_page_url);
					$post_type = get_post_type($post_exists);
					if (($post_exists) && ($post_type == SpeedGuard_Admin::$cpt_name) ){ 
					
						$notice =  Speedguard_Admin::set_notice(sprintf(__( '%1$s is already guarded!', 'speedguard' ),$guarded_page_url),'warning' );	
					}
					else {
						$new_target_page = array( 
						'post_title'           => $guarded_page_url,
						'post_status'   => 'publish',	
						'post_type'   => SpeedGuard_Admin::$cpt_name,	 
						);												
						$target_page_id = wp_insert_post( $new_target_page );
						$update_field = update_post_meta($target_page_id, 'guarded_post_id', $guarded_post_id);	
						//check url as guarded
						$update_field = update_post_meta($guarded_post_id, 'speedguard_on', array('true',$target_page_id) );
						//start test 
						$start_test = SpeedGuard_WebPageTest::webpagetest_new_test($target_page_id);									
						$notice =  Speedguard_Admin::set_notice(sprintf(__( '%1$s is successfully added!', 'speedguard' ),$guarded_page_url),'success' );	
					}							
				}
				else {
					$notice =  Speedguard_Admin::set_notice(__('Please select the post you want to add.','speedguard'),'warning' );	
				}
				return $notice;
	}
 
	/**Running speed tests**/
	function retest_load_time_action_handler( $redirect_to, $doaction, $post_ids ){ 
		//if ( $doaction !== 'retest_load_time' ) {	return $redirect_to; }
		foreach ( $post_ids as $guarded_page_id ) {
			$load_time = get_post_meta($guarded_page_id,'load_time', true);
			//If newly send
			if ($load_time != 'waiting'){
				$error_notice = SpeedGuard_WebPageTest::webpagetest_new_test($guarded_page_id);
			}					
			$errors = SpeedGuard_WebPageTest::update_waiting_pageload($guarded_page_id); 			
		}
		if ($error_notice){
			$redirect_to = add_query_arg( 'speedguard', 'limit_is_reached', $redirect_to );
			return $redirect_to;
		}
		else {
			$redirect_to = add_query_arg( 'speedguard', 'retest_load_time', $redirect_to );
			return $redirect_to;	
		}	 	
	}
	
	
	
		
		
			
			
			
	
	
	


}
new SpeedGuardCPT;


