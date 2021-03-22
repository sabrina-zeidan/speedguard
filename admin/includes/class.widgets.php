<?php
/** 
*
*	Class responsible for adding metaboxes
*/
class SpeedGuardWidgets{
	function __construct(){ 
		$options = SpeedGuard_Admin::get_this_plugin_option( 'speedguard_options' );
		if (!empty($options)){
		if ($options['show_dashboard_widget'] === 'on')	add_action( 'wp_'.(defined('SPEEDGUARD_MU_NETWORK') ? 'network_' : ''). 'dashboard_setup', array( $this,'speedguard_dashboard_widget') ); 
		if (($options['show_ab_widget'] === 'on') && !is_admin()) add_action( 'admin_bar_menu', array( $this,'speedguard_admin_bar_widget'),710);
		}
	}

	function speedguard_admin_bar_widget($wp_admin_bar ) { 
		if (!current_user_can('manage_options')) return;	
		if (is_front_page()) {
			$type = 'homepage'; 
			$current_item_id = '';
			$current_item_link = get_site_url(); //TODO Multisite
			//Check if it's already guarded				
				$homepage_found = SpeedGuard_Tests::is_homepage_guarded();
				if (!empty($homepage_found)){							
					$is_guarded = true;
					$test_id = $homepage_found;
					$load_time = get_post_meta( $test_id,'load_time');							
				}
				else {
					$is_guarded = false;
				}							
		}
		else if (is_singular(SpeedGuard_Admin::supported_post_types())) {
			global $post; 
			$type = 'single';
			$current_item_id = $post->ID;
			$current_item_link = get_permalink($current_item_id);
			$speedguard_on = get_post_meta($current_item_id,'speedguard_on', true);		
			if ($speedguard_on && $speedguard_on[0] == 'true'){
				$is_guarded = true;
				$test_id = $speedguard_on[1];
				$load_time = get_post_meta( $test_id,'load_time');					
			}
			else {
				$is_guarded = false;
			}							
		}
		else if (is_archive() && !is_post_type_archive() && !is_date()) {
			$type = 'archive';
			$current_item_id = get_queried_object()->term_id;
			$current_item_link = get_term_link($current_item_id);
			$speedguard_on = get_term_meta($current_item_id,'speedguard_on', true);
			if ($speedguard_on && $speedguard_on[0] == 'true'){
				$is_guarded = true;
				$test_id = $speedguard_on[1];
				$load_time = get_post_meta( $test_id,'load_time');					
			}
			else {
				$is_guarded = false;
			}					
			
		}
		
		//The output			
		//There is the load time
		if (isset($is_guarded) && !empty($load_time) && $load_time == 'waiting') {
				$title = __('Testing...','speedguard');
				$href = '';				
				$atitle = __('Test is running currently','speedguard');
			}
		else if (isset($is_guarded) && $is_guarded === true) { 
		
			if ((!empty($load_time[0]['displayValue'])) && (!empty($load_time[0]['score']))) { 
					$title = '<span data-score="'.$load_time[0]['score'].'" class="speedguard-score"><span>●</span> '.$load_time[0]['displayValue'].'</span>';
					$href = SpeedGuard_Admin::speedguard_page_url('tests').'#speedguard-add-new-url-meta-box';				
					$atitle = __('This page load time','speedguard');
			}
		}
		else if (isset($is_guarded) && $is_guarded === false) { //Item is not guarded or test is in process currently
				$add_url_link = add_query_arg( array(
						'speedguard'=> 'add_new_url',
						'new_url_id'=> $current_item_id,
						),SpeedGuard_Admin::speedguard_page_url('tests')); 
				$title = '<form action="'.$add_url_link.'" method="post">
				<input type="hidden" id="blog_id" name="blog_id" value="" />
				<input type="hidden" name="speedguard" value="add_new_url" /> 
				<input type="hidden" name="speedguard_new_url_id" value="'.$current_item_id.'" />	
				<input type="hidden" id="speedguard_new_url_permalink" name="speedguard_new_url_permalink" value="'.$current_item_link.'"/>
				<input type="hidden" id="speedguard_item_type" name="speedguard_item_type" value="'.$type.'"/> 
				<button style="border: 0;  background: transparent; color:inherit; cursor:pointer;">'.__('Test speed','speedguard').'</button></form>';
				$href = SpeedGuard_Admin::speedguard_page_url('tests');
				$atitle='';
		}			
			$args = array( 
					'id'    => 'speedguard_ab',
					'title' => isset($title) ? $title : '',
					'href'  => isset($href) ? $href : '',
					'meta'  => array( 
					'class' => 'menupop', 
					'title' => isset($atitle) ? $atitle : '',
					'target' => 'blank'
					)
				);
			$wp_admin_bar->add_node( $args );
	//}
		
	}
	function speedguard_dashboard_widget() {  
		wp_add_dashboard_widget('speedguard_dashboard_widget', __('Site Speed Results [Speedguard]','speedguard'), array($this,'speedguard_dashboard_widget_function'),'',array( 'echo' => 'true'));	
		//Widget position
			global $wp_meta_boxes;
			$normal_dashboard = $wp_meta_boxes['dashboard'.(defined('SPEEDGUARD_MU_NETWORK') ?'-network' :'')]['normal']['core']; 
			$example_widget_backup = array( 'speedguard_dashboard_widget' => $normal_dashboard['speedguard_dashboard_widget'] );
			unset( $normal_dashboard['speedguard_dashboard_widget'] ); 
			$sorted_dashboard = array_merge( $example_widget_backup, $normal_dashboard );
			$wp_meta_boxes['dashboard']['normal']['core'] = $sorted_dashboard;
	}	
	public static function speedguard_dashboard_widget_function($post = '', $args = '') {
			$speedguard_average = SpeedGuard_Admin::get_this_plugin_option('speedguard_average' );	
			if (is_array($speedguard_average)) 	$average_load_time = $speedguard_average['average_load_time'];
					if (!empty($average_load_time)){
						$min_load_time = $speedguard_average['min_load_time'];
						$max_load_time = $speedguard_average['max_load_time'];			
						$content =  "<div class='speedguard-results'>
						<div class='result-column'><p class='result-numbers'>$max_load_time</p>".__('Worst','speedguard')."</div>
						<div class='result-column'><p class='result-numbers average'>$average_load_time</p>".__('Average Load Time','speedguard')."</div>
						<div class='result-column'><p class='result-numbers'>$min_load_time</p>".__('Best','speedguard')."</div>	
						<a href='".SpeedGuard_Admin::speedguard_page_url('tests')."#speedguard-important-questions-meta-box' class='button button-primary' target='_blank'>".__('Improve','speedguard')."</a> 
						</div>
						";						
					}
					else {
					$content = sprintf(__( 'First %1$sadd URLs%2$s that should be guarded.', 'speedguard' ),
					'<a href="' .SpeedGuard_Admin::speedguard_page_url('tests').'#speedguard-add-new-url-meta-box">',
					'</a>'
					);
					}
					echo $content;
		}

	/*Meta boxes*/ 
	public static function add_meta_boxes(){
		wp_nonce_field('closedpostboxes', 'closedpostboxesnonce', false ); 		
			add_meta_box( 'settings-meta-box', __('SpeedGuard Settings','speedguard'), array('SpeedGuard_Settings','settings_meta_box'), '', 'normal', 'core' );		
			add_meta_box( 'speedguard-speedresults-meta-box', __('Site Speed Results','speedguard'), array('SpeedGuardWidgets', 'speedguard_dashboard_widget_function'			), '', 'main-content', 'core' );
			add_meta_box( 'speedguard-add-new-url-meta-box', __('Add new','speedguard'), array('SpeedGuardWidgets', 'add_new_url_meta_box'), '', 'main-content', 'core' );
			add_meta_box( 'tests-list-meta-box', __('Test results','speedguard'), array('SpeedGuard_Tests', 'tests_list_metabox' ), '', 'main-content', 'core' );
			add_meta_box( 'speed-score-legend-meta-box',__('Largest Contentful Paint (LCP)','speedguard'), array('SpeedGuardWidgets', 'speed_score_legend_meta_box'), '', 'main-content', 'core' );	
			add_meta_box( 'speedguard-important-questions-meta-box', __('Important questions:','speedguard'), array('SpeedGuardWidgets', 'important_questions_meta_box' ), '', 'side', 'core' );			
			add_meta_box( 'speedguard-about-meta-box', __('Do you like this plugin?','speedguard'), array('SpeedGuardWidgets', 'about_meta_box' ), '', 'side', 'core' );				
					
					
	}		
	/*Meta Boxes Widgets*/ 
	public static function speed_score_legend_meta_box(){
		$cwv_link = 'https://web.dev/lcp/';
		$content = '<table>
									<tr><td><p>'.__('','speedguard').'
									
									'.sprintf(__('We all know that site\'s loading speed was impacting Google ranking for quite a while now. But recently (late May 2020) company has revealed more details about %1$sCore Web Vitals%2$s — metrics that Google will be using to rank websites.','speedguard'),'<a href="' .$cwv_link. '" target="_blank">','</a>').'</p><p>								
									'.sprintf(__('%1$sLargest Contentful Paint%2$s is one of them. It measures how quickly the page\'s "main content" loads	— the bulk of the text or image (within the viewport, so before the user scrolls). ','speedguard'),'<strong>','</strong>').'</p><p>
									'.__('The intention of these changes is to improve how users perceive the experience of interacting with a web page.','speedguard').'
									</p>
									</td></tr>
									<tr>
									<td>
									<img 
    src="'.plugin_dir_url( __DIR__ ) . 'assets/images/lcp.svg" 
    alt="Largest Contentful Paint chart"/>
									</td>
									</tr>									
									</table>
									';
		echo $content;
	}
	public static function add_new_url_meta_box(){		
		$content = '<form name="speedguard_add_url" id="speedguard_add_url"  method="post" action="">   
		<input class="form-control"  type="text" id="speedguard_new_url" name="speedguard_new_url" value="" placeholder="'.__('Start typing the title of the post, page or custom post type...','speedguard').'" autofocus="autofocus"/>
		<input type="hidden" id="blog_id" name="blog_id" value="" />
		<input type="hidden" id="speedguard_new_url_permalink" name="speedguard_new_url_permalink" value=""/> 
		<input type="hidden" id="speedguard_item_type" name="speedguard_item_type" value=""/> 
		<input type="hidden" id="speedguard_new_url_id" name="speedguard_new_url_id" value=""/>
		<input type="hidden" name="speedguard" value="add_new_url" />
		<input type="submit" name="Submit" class="button action" value="'.__('Add','speedguard').'" />
		</form>';
		echo $content;
	}

	
	public static function important_questions_meta_box(){
		$links = array(
					sprintf(__( '%1$sHow fast should a website load?%2$s', 'speedguard' ),
					'<a href="https://sabrinazeidan.com/how-fast-should-my-website-load/?utm_source=speedguard&utm_medium=sidebar&utm_campaign=important_questions" target="_blank">','</a>'),					
					sprintf(__( '%1$sHow to serve scaled images to speed up your site?%2$s', 'speedguard' ),
					'<a href="https://sabrinazeidan.com/serve-scaled-images-wordpress/?utm_source=speedguard&utm_medium=sidebar&utm_campaign=important_questions" target="_blank">','</a>'),
					sprintf(__( '%1$sHow to speed up YouTube videos on your site?%2$s', 'speedguard' ),
					'<a href="https://sabrinazeidan.com/embed-youtube-video-wordpress-without-slowing/?utm_source=speedguard&utm_medium=sidebar&utm_campaign=important_questions" target="_blank">','</a>'),
					sprintf(__( '%1$s5 popular recommendations that don’t work%2$s', 'speedguard' ),
					'<a href="https://sabrinazeidan.com/how-to-speed-up-wordpress-this-dont-work/?utm_source=speedguard&utm_medium=sidebar&utm_campaign=important_questions" target="_blank">','</a>')	
					);
		echo '<ul>';
		foreach ($links as $link){
			echo '<li>'.$link.'</li>';
			
		}
		echo '</ul>';
	}	
	
	public static function about_meta_box(){
		$picture = '<a href="https://sabrinazeidan.com/?utm_source=speedguard&utm_medium=sidebar&utm_campaign=avatar" target="_blank"><div id="szpic"></div></a>';
		$hey = sprintf(__( 'Hey!%1$s My name is %3$sSabrina%4$s. 
		%1$sI speed up websites everyday, and I built this plugin because I needed a simple tool to monitor site speed and notify me if something is not right.%2$s
		%1$sHope it will be helpful for you too.%2$s
		%2$s', 'speedguard' ),'<p>','</p>','<a href="https://sabrinazeidan.com/?utm_source=speedguard&utm_medium=sidebar&utm_campaign=sabrina" target="_blank">','</a>');	
	
		$rate_link = 'https://wordpress.org/support/plugin/speedguard/reviews/?rate=5#new-post';
		$rate_it = sprintf(__( 'If you like it, I would greatly appreciate if you add your %1$s★★★★★%2$s to spread the love.', 'speedguard' ),
					'<a class="rate-link" href="' .$rate_link. '" target="_blank">','</a>'	);	
		$translate_link = 'https://translate.wordpress.org/projects/wp-plugins/speedguard/';
		$translate_it = sprintf(__( 'You can also help to %1$stranslate it to your language%2$s so that more people will be able to use it ❤︎', 'speedguard' ),
					'<a href="' .$translate_link. '" target="_blank">','</a>');	
		
		$cheers		= sprintf(__( 'Cheers!' , 'speedguard' ));	
					
		$content = $picture.$hey.'<p>'.$rate_it.'</p><p>'.$translate_it .'<p>'. $cheers; 
		echo $content;
	}	
}
new SpeedGuardWidgets;