<?php
/** 
*
*	Class responsible for adding CPT for SpeedGuard
*/
class SpeedGuardWidgets{
	function __construct(){ 
		$options = Speedguard_Admin::get_this_plugin_option( 'speedguard_options' );
			if ($options['show_dashboard_widget'] === 'on'){			
			add_action( 'wp_'.(THIS_PLUGIN_NETWORK_ACTIVATED ? 'network_' : ''). 'dashboard_setup', array( &$this,'speedguard_dashboard_widget') ); 
		} 
		if ($options['show_ab_widget'] === 'on'){	add_action( 'admin_bar_menu', array( $this,'speedguard_admin_bar_widget'),710);}
	}
/** Admin Bar widget */
	function speedguard_admin_bar_widget($wp_admin_bar ) { 
		if((current_user_can('update_core'))&&(is_singular(SpeedGuard_Admin::supported_post_types()))) {
			global $post; 
		
				$speedguard_on = get_post_meta($post->ID,'speedguard_on', true);
				if ($speedguard_on && $speedguard_on[0] == 'true'){
						//var_dump($post);
					$page_load_speed = get_post_meta($speedguard_on[1],'load_time', true);
					//var_dump($page_load_speed);
					if ($page_load_speed != "waiting") { 					
						$title = sprintf(__( '%1$ss', 'speedguard' ),$page_load_speed);	
						$href = Speedguard_Admin::speedguard_page_url('tests').'#speedguard-add-new-url-meta-box';
						$class = get_post_meta($speedguard_on[1],'load_time_score', true); 
						$atitle = __('This page load time','speedguard');
					}
					else {
						$title = sprintf(__( 'In process', 'speedguard' ),$page_load_speed);	
						$href = Speedguard_Admin::speedguard_page_url('tests').'#speedguard-add-new-url-meta-box';
						$class = get_post_meta($speedguard_on[1],'load_time_score', true); 
						$atitle = __('Results are currently being updated','speedguard');
					}
					
					
					
				}
				else {
					$add_url_link = add_query_arg( array(
							'speedguard'=> 'add_new_url',
							'new_url_id'=>$post->ID,
							),Speedguard_Admin::speedguard_page_url('tests')); 
 							
					$title = '<form action="'.$add_url_link.'" method="post">
						<input type="hidden" name="speedguard" value="add_new_url" /> 
						<input type="hidden" id="blog_id" name="blog_id" value="" />
						<input type="hidden" name="speedguard_new_url_id" value="'.$post->ID.'" />
						<input type="hidden" id="speedguard_new_url_permalink" name="speedguard_new_url_permalink" value="'.get_permalink($post).'"/>
											<button style="border: 0;  background: transparent; color:inherit; cursor:pointer;">'.__('Test this page load time','speedguard').'</button></form>';
					$href = Speedguard_Admin::speedguard_page_url('tests');
					$class='';
					$atitle='';
					}
			$args = array( 
				'id'    => 'speedguard_ab',
				'title' => $title,
				'href'  => $href,
				'meta'  => array( 
				'class' => 'menupop '.$class, 
				'title' => $atitle,
				'target' => 'blank'
				)
			);
			$wp_admin_bar->add_node( $args );
		}
	}
/** Dashboard widget */
	function speedguard_dashboard_widget() {  
		wp_add_dashboard_widget('speedguard_dashboard_widget', __('Site Speed Results [Speedguard]','speedguard'), array($this,'speedguard_dashboard_widget_function'),'',array( 'echo' => 'true'));	
		//Widget position
		global $wp_meta_boxes;
		$normal_dashboard = $wp_meta_boxes['dashboard'.(THIS_PLUGIN_NETWORK_ACTIVATED?'-network' :'')]['normal']['core']; 
		$example_widget_backup = array( 'speedguard_dashboard_widget' => $normal_dashboard['speedguard_dashboard_widget'] );
		unset( $normal_dashboard['speedguard_dashboard_widget'] ); 
		$sorted_dashboard = array_merge( $example_widget_backup, $normal_dashboard );
		$wp_meta_boxes['dashboard']['normal']['core'] = $sorted_dashboard;
	}
	
	public static function speedguard_dashboard_widget_function($post = '', $args = '') {
			$speedguard_average = Speedguard_Admin::get_this_plugin_option('speedguard_average' );	
			$average_load_time = $speedguard_average['average_load_time'];
					if (isset($average_load_time)){
						$min_load_time = $speedguard_average['min_load_time'];
						$max_load_time = $speedguard_average['max_load_time'];			
						$content =  "<div class='speedguard-results'>
						<div class='result-column'><p class='result-numbers'>$max_load_time</p>".__('Worst','speedguard')."</div>
						<div class='result-column'><p class='result-numbers average'>$average_load_time</p>".__('Average Load Time','speedguard')."</div>
						<div class='result-column'><p class='result-numbers'>$min_load_time</p>".__('Best','speedguard')."</div>	
						<a href='".Speedguard_Admin::speedguard_page_url('tests')."#speedguard-tips-meta-box' class='button button-primary' target='_blank'>".__('Improve','speedguard')."</a> 
						</div>
						";						
					}
					else {
					$content = sprintf(__( 'First %1$sadd URLs%2$s that should be guarded.', 'speedguard' ),
					'<a href="' .Speedguard_Admin::speedguard_page_url('tests').'#speedguard-add-new-url-meta-box">',
					'</a>'
					);
					}
					echo $content;
					/**if (isset($args['args']['echo'])){ echo $content; } 
					else {return $content;}**/
		}

	/*Meta boxes*/ 
	public static function add_meta_boxes(){
		wp_nonce_field('closedpostboxes', 'closedpostboxesnonce', false ); 		
		add_meta_box( 'api-meta-box', __('API Key','speedguard'), array('SpeedGuard_Settings','api_meta_box'), '', 'api', 'core' );
		
		if (SpeedGuard_AUTHORIZED){			
			add_meta_box( 'settings-meta-box', __('SpeedGuard Settings','speedguard'), array('SpeedGuard_Settings','settings_meta_box'), '', 'normal', 'core' );		
			add_meta_box( 'speedguard-speedresults-meta-box', __('Site Speed Results','speedguard'), array('SpeedGuardWidgets', 'speedguard_dashboard_widget_function'			), '', 'main-content', 'core' );
			add_meta_box( 'speedguard-add-new-url-meta-box', __('Add new','speedguard'), array('SpeedGuardWidgets', 'add_new_url_meta_box'), '', 'main-content', 'core' );
			add_meta_box( 'tests-list-meta-box', __('Tests results','speedguard'), array('SpeedGuard_Tests', 'tests_list_metabox' ), '', 'main-content', 'core' );
			add_meta_box( 'speed-score-legend-meta-box',__('Speed Score','speedguard'), array('SpeedGuardWidgets', 'speed_score_legend_meta_box'), '', 'main-content', 'core' );
			
			
			
			add_meta_box( 'speedguard-api-credits-meta-box', __('API Credits','speedguard'), array('SpeedGuard_Settings','credits_meta_box'),'','side','core'); 
			add_meta_box( 'speedguard-tips-meta-box', __('Why is my website so slow?','speedguard'), array('SpeedGuard_Settings', 'tips_meta_box' ), '', 'side', 'core' ); 			
			add_filter( "postbox_classes_speedguard_page_speedguard_settings_api-meta-box", 'minify_my_metabox' );						
						function minify_my_metabox( $classes ) {
							array_push( $classes, 'closed' );
							return $classes;
						}			
						
		}
					
	}		
	/*Meta Boxes Widgets*/ 
	public static function speed_score_legend_meta_box(){
		$content = '<table>
									<tr>
									<td><span class="speedguard-score score-green"></span></td>
									<td>0 — 2.9'.__('s','speedguard').'</td>
									<td>'.__('Better than average. Probably, your site speed has no direct negative impact on search ranking. Yet, you may significantly improve user experience (which is another important search ranking factor) by reducing site load time. This is especially true for e-commerce websites. In most cases for WordPress websites Speed Index can be improved to 2 seconds without significant changes.','speedguard').'</td>
									</tr>
									<tr>
									<td><span class="speedguard-score score-yellow"></span></td>
									<td>3 — 5.9'.__('s','speedguard').'</td>
									<td>'.__('Not bad, but not good either. Average Speed Index is 6s [2018], so this a is mediocre result. Reducing your site speed index at least to 3 seconds will help you to outrank your competitors on Google.','speedguard').'</td>
									</tr>
									<tr>
									<td><span class="speedguard-score score-red"></span></td>
									<td>6'.__('s','speedguard').' '.__('and more','speedguard').'</td>
									<td>'.__('Worse than the average. Your SE rankings are definitely harmed by your site speed. There might be a long list of reasons why your website is slow and a lot of work to do. But the good news is, you may see first positive results as soon as you start.','speedguard').'</td>  
									</tr> 
									</table>
									<p>* Tests are performed from Dulles, VA using Chrome browser and Cable connection (5/1 Mbps, 28ms RTT)</p>';
		echo $content;
	}
	
	public static function add_new_url_meta_box(){
		$content = '<form name="speedguard_add_url" id="speedguard_add_url"  method="post" action="">   
		<input class="form-control"  type="text" id="speedguard_new_url" name="speedguard_new_url" value="" placeholder="'.__('Start typing the title of the post, page or custom post type...','speedguard').'" autofocus="autofocus"/>
		<input type="hidden" id="blog_id" name="blog_id" value="" />
		<input type="hidden" id="speedguard_new_url_permalink" name="speedguard_new_url_permalink" value=""/> 
		<input type="hidden" id="speedguard_new_url_id" name="speedguard_new_url_id" value=""/>
		<input type="hidden" name="speedguard" value="add_new_url" />
		<input type="submit" name="Submit" value="'.__('Add','speedguard').'" />
		</form>';
		echo $content;
	}
	
	public static function credits_meta_box($post = '', $args = ''){
		$content = Speedguard_WebPageTest::credits_usage(); 
		echo $content;
		/**
		if (isset($args['args']['echo'])){ echo $content; } 
		else {return $content;}	
		**/
	}
	
	public static function tips_meta_box(){
	$nonce = wp_nonce_field('closedpostboxes', 'closedpostboxesnonce', false ); 
		$response = wp_safe_remote_post('http://sabrinazeidan.com/speedguard/tips/',array(
			'method'      => 'POST',
			'body'        => array(
				'lang'=> get_user_locale(get_current_user_id()),
				'password' => 'tips_request'
			)));		
		if ( is_wp_error( $response) ) {return false;}
		$response = wp_remote_retrieve_body( $response);	
		$json_response = json_decode($response, true);					
		$title = '<b>'.$json_response['tips']['title'].'</b>';
		$description = '<p>'.$json_response['tips']['description'].'</p>';
		$link = '<p>'.$json_response['tips']['link'].'</p>';
		$content = $nonce.$title.$description.$link;
		echo $content;
		/**
		if (isset($args['args']['echo'])){ echo $content; } 
		else {return $content;}	
		**/
	}	


}
new SpeedGuardWidgets;