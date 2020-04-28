<?php
/** 
*
*	Class responsible for adding metaboxes
*/
class SpeedGuardWidgets{
	function __construct(){ 
		$options = Speedguard_Admin::get_this_plugin_option( 'speedguard_options' );
		if (!empty($options)){
		if ($options['show_dashboard_widget'] === 'on')	add_action( 'wp_'.(defined('SPEEDGUARD_MU_NETWORK') ? 'network_' : ''). 'dashboard_setup', array( $this,'speedguard_dashboard_widget') ); 
		if ($options['show_ab_widget'] === 'on') add_action( 'admin_bar_menu', array( $this,'speedguard_admin_bar_widget'),710);
		}
	}

	function speedguard_admin_bar_widget($wp_admin_bar ) { 
		if((current_user_can('manage_options'))&&(is_singular(SpeedGuard_Admin::supported_post_types()))) {
			global $post; 		
				$speedguard_on = get_post_meta($post->ID,'speedguard_on', true);
				if ($speedguard_on && $speedguard_on[0] == 'true'){
					$page_load_speed = get_post_meta($speedguard_on[1],'load_time', true);
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
			$speedguard_average = Speedguard_Admin::get_this_plugin_option('speedguard_average' );	
			//var_dump($speedguard_average);	
			if (is_array($speedguard_average)) 	$average_load_time = $speedguard_average['average_load_time'];
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
		}

	/*Meta boxes*/ 
	public static function add_meta_boxes(){
		wp_nonce_field('closedpostboxes', 'closedpostboxesnonce', false ); 		
		add_meta_box( 'api-meta-box', __('API Key','speedguard'), array('SpeedGuard_Settings','api_meta_box'), '', 'api', 'core' );
		 
		if (defined('SPEEDGUARD_WPT_API')){			
			add_meta_box( 'settings-meta-box', __('SpeedGuard Settings','speedguard'), array('SpeedGuard_Settings','settings_meta_box'), '', 'normal', 'core' );		
			add_meta_box( 'speedguard-speedresults-meta-box', __('Site Speed Results','speedguard'), array('SpeedGuardWidgets', 'speedguard_dashboard_widget_function'			), '', 'main-content', 'core' );
			add_meta_box( 'speedguard-add-new-url-meta-box', __('Add new','speedguard'), array('SpeedGuardWidgets', 'add_new_url_meta_box'), '', 'main-content', 'core' );
			add_meta_box( 'tests-list-meta-box', __('Test results','speedguard'), array('SpeedGuard_Tests', 'tests_list_metabox' ), '', 'main-content', 'core' );
			add_meta_box( 'speed-score-legend-meta-box',__('Speed Score','speedguard'), array('SpeedGuardWidgets', 'speed_score_legend_meta_box'), '', 'main-content', 'core' );	
			add_meta_box( 'speedguard-api-credits-meta-box', __('API Credits','speedguard'), array('SpeedGuard_Settings','credits_meta_box'),'','side','core'); 
			add_meta_box( 'speedguard-tips-meta-box', __('Why is my website so slow?','speedguard'), array('SpeedGuard_Settings', 'tips_meta_box' ), '', 'side', 'core' ); 	
			add_meta_box( 'speedguard-about-meta-box', __('Do you like this plugin?','speedguard'), array('SpeedGuardWidgets', 'about_meta_box' ), '', 'side', 'core' );			
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
									<td>'.__('Better than average. Your site speed propably has no direct negative impact on search ranking. However, you may improve user experience significantly (which is another important search ranking factor) by reducing site load time. This is especially true for e-commerce websites. In most cases for WordPress websites Speed Index can be improved to 2 seconds without significant changes.','speedguard').'</td>
									</tr>
									<tr>
									<td><span class="speedguard-score score-yellow"></span></td>
									<td>3 — 5.9'.__('s','speedguard').'</td>
									<td>'.__('Not bad, but not good either. Average Speed Index is 6s [2018], so this a is mediocre result. Reducing your site speed index to at least to 3 seconds will help you to outrank your competitors on Google.','speedguard').'</td>
									</tr>
									<tr>
									<td><span class="speedguard-score score-red"></span></td>
									<td>6'.__('s','speedguard').' '.__('and more','speedguard').'</td>
									<td>'.__('Worse than the average. Your SE rankings are definitely harmed by your site speed. There might be a long list of reasons why your website is slow, and potentially a lot of work to do. But the good news is, you may see the first positive results as soon as you start.','speedguard').'</td>  
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
		<input type="hidden" id="speedguard_new_url_id" name="speedguard_new_url_id" value=""/>
		<input type="hidden" name="speedguard" value="add_new_url" />
		<input type="submit" name="Submit" value="'.__('Add','speedguard').'" />
		</form>';
		echo $content;
	}
	public static function credits_meta_box($post = '', $args = ''){
		$content = Speedguard_WebPageTest::credits_usage(); 
		echo $content;
	}
	public static function tips_meta_box(){
			$external_links = array(
				'hosting' => array('test' => 'http://www.bytecheck.com/', 'siteground' => 'https://bit.ly/SPDGRD_SiteGround'),
				'images' => array('shortpixel' => 'https://shortpixel.com/pricing', 'imagify' => 'https://imagify.io'),
				'cdn' => array('maxcdn' =>'https://www.maxcdn.com/pricing/entrepreneur/'),
				'caching' => array('wprocket' => 'https://bit.ly/SPDGRD_WPR'),
				'backup' => array('blogvault' => 'https://bit.ly/SPDGRD_BlogVault'),
			);
			
				$the_tips = array(
				array('title' =>__('It might be your caching plugin.','speedguard'), 
				'description' =>__( 'There are some basic things that ALL caching plugins do like: browser caching, server side caching, GZIP compression etc.','speedguard').'<p>'.__( 'There are some caching plugins that go further and take care of your database, minify CSS and JS files, defer their load etc.','speedguard').'</p><p>'.sprintf(__('My favourite one is %1$sWP Rocket%2$s. Because it’s not a caching plugin at all. It does much much more: from YouTube video lazy-loading to DNS-prefetching.','speedguard'),'<a href="' .$external_links['caching']['wprocket']. '" target="_blank">','</a>').'</p><p>'.sprintf(__('But the most important thing is that all WP Rocket’s features are aimed to improve real users’ experience and %1$sreduce the time before users can actually interact with your site%2$s. This is exactly what SpeedGuard measures, by the way — Speed Index.','speedguard'),'<strong>','</strong>').'</p><p>'.__('While site content that is not crucial at the moment is being loaded in the background, a user is already viewing your website. Isn’t that wonderful?','speedguard')),
				array('title' =>__('It might be your hosting.','speedguard'), 
				'description' =>__( 'The slowness of your website may be caused by slow server response time of your hosting. Google recommends keeping server response time under 200ms. An overloaded or poorly configured server may take up to 2 seconds to respond before your site even start to render.','speedguard').'<p><b>'.__( 'How to detect if your hosting is slow?','speedguard').'</b></p><p>'.sprintf(__('%1$sTest your website%2$s to see how long it takes your server to load.','speedguard'),'<a href="' .$external_links['hosting']['test']. '" target="_blank">','</a>').'</p><p>'.sprintf(__('If your numbers are above 500ms you should definitely consider upgrade your hosting plan or move to the faster hosting provider. For example, SiteGround offers %1$sfast servers optimized for WordPress%2$s even in the minimal plan that starts from 3.95 €/Mo.','speedguard'),'<a href="' .$external_links['hosting']['siteground']. '" target="_blank">','</a>').'</p>',
				'link' => ''),
				array('title' => __('It might be your media.','speedguard'), 
				'description' => __('Loading images may take up to 90% of page load time. It might take 5 seconds to load a regular image and less than half a second to load its optimized version. That\'s why you can make your site load times faster just by reducing your images size.','speedguard').'<p><b>'.sprintf(__('Proper image compression for WordPress:%1$sis lossless%2$syou definitely don\'t want your images to become pixelated. After a lossless compression your images will look just the same as the original ones.%3$shas no file size limit%4$sis automatic and bulk%5$s','speedguard'),'</b></p><ul><li>','<br>','</li><li>','</li><li>','</li></ul>').'<p>'.sprintf(__('Install %1$sShortPixel plugin%2$s to get all these (even with free plan).','speedguard'),'<a href="' .$external_links['images']['shortpixel']. '" target="_blank">','</a>'), 'link' => ''), 
				array('title' => __('It might be the long queue.','speedguard'), 
				'description' => __('In order to show your website page to the user, his browser should load all necessary resources from your server. But the number of concurrent connections is limited.','speedguard').sprintf(__('%1$s%2$sThe access might be blocked%3$s until one of the previous resources has been fully retrieved and requests may wait in queue before they are processed. Rather than hosting your website a single server, you can distribute the files and load across multiple systems.','speedguard'),'<p>','<b>','</b>').sprintf(__('%1$sThis is how a %2$sContent Delivery Network%3$s (CDN) works. It provides  an easy way to increase the speed of your website while also lowering the latency. MaxCDN offers %4$sWordPress plans%5$s starting from 9$/mo.','speedguard'),'<p>','<b>','</b>','<a href="' .$external_links['cdn']['maxcdn']. '" target="_blank">','</a>'), 'link' => '')  
				); 
				$rand_keys = array_rand($the_tips, 1); 
				$tip_content = $the_tips[$rand_keys];		
		$title = '<b>'.$tip_content['title'].'</b>';
		$description = '<p>'.$tip_content['description'].'</p>';
		$link = '<p>'.$tip_content['link'].'</p>';
		$content = $title.$description.$link;
		echo $content;
	}
	public static function about_meta_box(){
		$picture = '<a href="https://sabrinazeidan.com" target="_blank"><div id="szpic"></div></a>';
		$hey = sprintf(__( 'Hey!%1$s My name is Sabrina, I\'m the author of this plugin. %2$s', 'speedguard' ),'<p>','</p>');	
		$rate_link = 'https://wordpress.org/support/plugin/speedguard/reviews/?rate=5#new-post';
		$rate_it = sprintf(__( 'If you like it, I would greatly appreciate if you add your %1$s★★★★★%2$s to spread the love.', 'speedguard' ),
					'<a href="' .$rate_link. '" target="_blank">','</a>'	);	
		$translate_link = 'https://translate.wordpress.org/projects/wp-plugins/speedguard/';
		$translate_it = sprintf(__( 'You can also help to %1$stranslate it to your language%2$s so that more people will be able to use it ❤︎', 'speedguard' ),
					'<a href="' .$translate_link. '" target="_blank">','</a>');	
		
		$cheers		= sprintf(__( 'Cheers! %1$sP.S. By the way, I use all the services mentioned above myself, that\'s why I recommend them.%2$s' , 'speedguard' ),'<p>','</p>');	
					
		$content = $picture.$hey.'<p>'.$rate_it.'</p><p>'.$translate_it .'<p>'. $cheers; 
		echo $content;
	}	
}
new SpeedGuardWidgets;