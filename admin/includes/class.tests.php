<?php
/**
* 
*	Class responsible for SpeedGuard Tests Page
*/

// WP_List_Table is not loaded automatically so we need to load it in our application
if( ! class_exists( 'WP_List_Table' ) ) {
    require_once( ABSPATH . 'wp-admin/includes/class-wp-list-table.php' );
}
/**
 * Create a new table class that will extend the WP_List_Table
 */
class SpeedGuard_List_Table extends WP_List_Table{
    /**
     * Prepare the items for the table to process
     *
     * @return Void
     */
	public function no_items() {
    _e('No pages guarded yet. Add new URL in the field above for the start.','speedguard');
	}
    public function prepare_items()
    {
        $columns = $this->get_columns();
        $hidden = $this->get_hidden_columns();
        $sortable = $this->get_sortable_columns();
        $data = $this->table_data();
        usort( $data, array( &$this, 'sort_data' ) );
        $perPage = 20;
        $currentPage = $this->get_pagenum();
        $totalItems = count($data);
        $this->set_pagination_args( array(
            'total_items' => $totalItems,
            'per_page'    => $perPage
        ) );
        $data = array_slice($data,(($currentPage-1)*$perPage),$perPage);
        $this->_column_headers = array($columns, $hidden, $sortable);
        $this->items = $data;
		$this->process_bulk_action();
    }

	 function column_cb($item) {
        return sprintf(
            '<input type="checkbox" name="guarded-pages[]" value="%s" />', $item['guarded_page_id']
        );    
    }
    public function get_columns()
    {
        $columns = array(
			'cb' => '<input type="checkbox" />',
            'guarded_page_title' => __( 'URL', 'speedguard' ),
            'load_time' => __( 'Load time', 'speedguard' ),
            'report_link' => __( 'Report link', 'speedguard' ),
			'report_date' => __( 'Updated', 'speedguard' ),
        );
        return $columns;
    }
    /**
     * Define which columns are hidden
     *
     * @return Array
     */
    public function get_hidden_columns()
    {
        return array();
    }
    /**
     * Define the sortable columns
     *
     * @return Array
     */
    public function get_sortable_columns()
    {
        return array('guarded_page_title' => array('guarded_page_title', false));
    }
    /**
     * Get the table data
     *
     * @return Array
     */
    private function table_data()
    {
        $data = array();
		$args = array(
					'post_type' => 'guarded-page',
					'post_status' => 'publish',
					'posts_per_page'   => -1,
					'fields'=>'ids',					
				);
		$guarded_pages = get_posts( $args );
		foreach($guarded_pages as $guarded_page_id) {  
			$guarded_page_url = get_the_title($guarded_page_id);
			//$guarded_page_load_time = get_post_meta($guarded_page_id, 'load_time',true );
			
			
			$load_time_result = get_post_meta( $guarded_page_id,'load_time', true);
			$load_time_score = get_post_meta( $guarded_page_id,'load_time_score',true);
			if ($load_time_result == 'waiting'){
				$guarded_page_load_time = '<div class="loading" title="'.__('test is running...','speedguard').'"></div>';
			}
			else {
				$guarded_page_load_time = '<span class="speedguard-score score-'.$load_time_score.'">'.$load_time_result.'</span>';
			}		 
				 
			$report_link = 'https://www.webpagetest.org/result/'.get_post_meta($guarded_page_id,'gtmetrix_test_result_id',true);		
			$gtmetrix_test_result_date = get_post_meta($guarded_page_id,'gtmetrix_test_result_date',true);
			if (!empty($gtmetrix_test_result_date)){
			$gmt_report_date = date('Y-m-d H:i:s',$gtmetrix_test_result_date); 
			}
			else {$gmt_report_date = '';}
			$updated = get_date_from_gmt($gmt_report_date,'Y-m-d H:i:s');  	
				$data[] = array(
					'guarded_page_id' => $guarded_page_id,
					'guarded_page_title' => '<a href="'.$guarded_page_url.'" target="_blank">'.$guarded_page_url.'</a>',
					'load_time' => $guarded_page_load_time,
					'report_link' => '<a href="'.$report_link.'" target="_blank">'.__('Report','speedguard').'</a>',
					'report_date' => $updated,						
                 );
        }
        				
        return $data;
    }
    /**
     * Define what data to show on each column of the table
     *
     * @param  Array $item        Data
     * @param  String $column_name - Current column name
     *
     * @return Mixed
     */
	 
    public function column_default( $item, $column_name ){
        switch( $column_name ) {
            case 'guarded_page_title':
            case 'load_time':
            case 'report_link':
            case 'report_date': 
                return $item[ $column_name ];
            default:
                return print_r( $item, true ) ;
        }
    }
    /**
     * Allows you to sort the data by the variables set in the $_GET
     *
     * @return Mixed
     */
    private function sort_data( $a, $b ){
        // Set defaults
        $orderby = 'guarded_page_title';
        $order = 'asc';
        // If orderby is set, use this as the sort column
        if(!empty($_GET['orderby']))
        {
            $orderby = $_GET['orderby'];
        }
        // If order is set use this as the order
        if(!empty($_GET['order']))
        {
            $order = $_GET['order'];
        }
        $result = strcmp( $a[$orderby], $b[$orderby] );
        if($order === 'asc')
        {
            return $result;
        }
        return -$result;
    }
	//Edit actions
	public function get_bulk_actions() {
		$actions = array(
			'delete'    => __( 'Stop guarding', 'speedguard'),
			'retest_load_time' => __( 'Retest load time', 'speedguard')					
		);
		return $actions;
	}
	
	public function process_bulk_action() {
	       // security check!
        /**
		if ( isset( $_POST['_wpnonce'] ) && ! empty( $_POST['_wpnonce'] ) ) {
            $nonce  = filter_input( INPUT_POST, '_wpnonce', FILTER_SANITIZE_STRING );
            $action = 'bulk-' . $this->_args['plural'];

            if ( ! wp_verify_nonce( $nonce, $action ) )
                wp_die( 'Nope! Security check failed!' );

        }
		**/ 
        $doaction = $this->current_action();
		if (!empty($doaction))	$process_bulk_action = SpeedGuard_Tests::handle_bulk_retest_load_time($doaction, $_POST['guarded-pages']);
	}    
}

	
	
class SpeedGuard_Tests{
	function __construct(){		
		add_action ( 'wp_ajax_' . 'webpagetest_update_waiting_ajax',  array( 'SpeedGuard_Tests', 'webpagetest_update_waiting_ajax_function') );	
		
	} 
	public static function handle_bulk_retest_load_time($doaction,$post_ids) {
		if ( $doaction == 'retest_load_time' ) {
			foreach ($post_ids as $guarded_page_id) { 
				$load_time = get_post_meta($guarded_page_id,'load_time', true);
				if ($load_time != 'waiting'){
					$test_created = SpeedGuard_WebPageTest::webpagetest_new_test($guarded_page_id);
				}					
				$updated = SpeedGuard_WebPageTest::update_waiting_pageload($guarded_page_id); 					
			}
		$redirect_to = add_query_arg( 'speedguard', 'retesting_load_time');				
		}
		else if ( $doaction == 'delete' ) {
			foreach ($post_ids as $guarded_page_id) { 
				  wp_delete_post( $guarded_page_id, true); 
			}
			$redirect_to = add_query_arg( 'speedguard', 'delete_guarded_pages');
		}		
		if (isset($redirect_to)){
			wp_safe_redirect( esc_url_raw($redirect_to) );
			exit;
		}
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


	public static function import_data() {  
				$guarded_page_url = htmlspecialchars($_POST['speedguard_new_url_permalink']); 
				if ($guarded_page_url){
					$guarded_post_id = htmlspecialchars($_POST['speedguard_new_url_id']); 
					
						$new_target_page = array( 
						'post_title'           => $guarded_page_url,
						'post_status'   => 'publish',	
						'post_type'   => SpeedGuard_Admin::$cpt_name,	 
						);												
						
						if (THIS_PLUGIN_NETWORK_ACTIVATED) switch_to_blog(get_network()->site_id);   
						$target_page_id = wp_insert_post( $new_target_page );  
						$update_field = update_post_meta($target_page_id, 'guarded_post_blog_id', htmlspecialchars($_POST['blog_id']));   
						$update_field = update_post_meta($target_page_id, 'guarded_post_id', $guarded_post_id);	
						if (THIS_PLUGIN_NETWORK_ACTIVATED) restore_current_blog(); 
						
						
						
						if (THIS_PLUGIN_NETWORK_ACTIVATED) switch_to_blog( htmlspecialchars($_POST['blog_id']));   
						//check url as guarded
						$update_field = update_post_meta($guarded_post_id, 'speedguard_on', array('true',$target_page_id) );
						if (THIS_PLUGIN_NETWORK_ACTIVATED) restore_current_blog(); 
						//start test 
						$start_test = SpeedGuard_WebPageTest::webpagetest_new_test($target_page_id);	
						$redirect_to = add_query_arg( 'speedguard', 'new_url_added');	 
									
				}   
				else {
	
					$redirect_to = add_query_arg( 'speedguard', 'add_new_url_error');				
				}
			if (isset($redirect_to)){
			 wp_safe_redirect( esc_url_raw($redirect_to) );  
			exit; 
		}
	}
	
	
	public static function tests_list_metabox()  {
			$exampleListTable = new SpeedGuard_List_Table();
			echo '<form id="wpse-list-table-form" method="post">';
			$exampleListTable->prepare_items();  
			$exampleListTable->display();
			echo '</form>';
		}
		
		public static function tests_page() { 
			if (Speedguard_Admin::is_screen('tests')){
				SpeedGuardWidgets::add_meta_boxes();				
		?>
		
			<div class="wrap">        
				<h2><?php _e( 'Speedguard :: Guarded pages', 'speedguard' ); ?></h2>		
						<div id="poststuff" class="metabox-holder has-right-sidebar">
							<div id="side-info-column" class="inner-sidebar">
								<?php 	
								
								do_meta_boxes( '', 'side', 0 ); ?>
							</div>
							<div id="post-body" class="has-sidebar">
								<div id="post-body-content" class="has-sidebar-content">
								<?php	do_meta_boxes( '', 'main-content', '');	?>
								</div>
							</div>
						</div>	
					</form>
			</div>
			<?php 
			}
			
			
			
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
														var location_updated = location.href.replace(/retesting_load_time|new_url_added[^&$]*/i, 'load_time_updated');  
														//	var location_updated = location.href.replace(/new_url_added[^&$]*/i, 'load_time_updated'); 
															console.log("Location: ",location_updated); 
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
		add_action( 'admin_footer','autocomplete_search');  			
			function autocomplete_search() {
			if (THIS_PLUGIN_NETWORK_ACTIVATED) {     
				$sites = get_sites();				
				foreach ($sites as $site ) {
					$blog_id = $site->blog_id;				
						switch_to_blog( $blog_id );
						//$get_posts = array();  
							$already_guarded_posts = array();
							$args = array(
								'post_type' => SpeedGuard_Admin::supported_post_types(),
								'post_status' => 'publish',
								'posts_per_page'   => -1, 
								'fields'   => 'ids',
								'meta_query' => array(array('key' => 'speedguard_on','value' => 'true','compare' => 'LIKE'))								
							);
							$already_guarded_posts = get_posts( $args );								
							$get_site_posts = array();
								$args = array(  
								'post_type' => SpeedGuard_Admin::supported_post_types(),
								'post_status' => 'publish',
								'posts_per_page'   => -1, // all posts
								'fields'   => 'ids',
								'exclude' => $already_guarded_posts
							);
							$get_site_posts = get_posts( $args );
							$temp = array();
							$all_posts = array();
							foreach( $get_site_posts as $key => $post_id) { 
								$key = 'ID';
								$temp['ID']  = $post_id;
								$temp['blog_id']  = $blog_id;								
								$posts[] = $temp;							
							} 							
						restore_current_blog();	 				
				}//endforeach				
			}//endif network
			else {
				$already_guarded_posts = array();
							$args = array(
								'post_type' => SpeedGuard_Admin::supported_post_types(),
								'post_status' => 'publish',
								'posts_per_page'   => -1, 
								'fields'   => 'ids',
								'meta_query' => array(array('key' => 'speedguard_on','value' => 'true','compare' => 'LIKE'))								
							);
				$already_guarded_posts = get_posts( $args );
				$args = array(
				'post_type' => SpeedGuard_Admin::supported_post_types(),
				'post_status' => 'publish',
				'posts_per_page'   => -1,
				'fields'   => 'ids',
				'exclude' => $already_guarded_posts
				);
				$posts = get_posts( $args );	
			}
				if( $posts ) :
						foreach( $posts as $k => $p ) {	
						if (THIS_PLUGIN_NETWORK_ACTIVATED) switch_to_blog($p['blog_id']);
						if (THIS_PLUGIN_NETWORK_ACTIVATED) $source[$k]['blog_id'] = $p['blog_id'];
							$source[$k]['ID'] = THIS_PLUGIN_NETWORK_ACTIVATED ? $p['ID'] : $p ;
							$source[$k]['permalink'] = get_permalink( $source[$k]['ID']);
							$source[$k]['label'] = get_the_title( $source[$k]['ID']);
						if (THIS_PLUGIN_NETWORK_ACTIVATED) restore_current_blog();
							
						}  
				?>
				<script type="text/javascript">
					jQuery(document).ready(function($){
						var posts = <?php echo json_encode( array_values( $source ) ); ?>;
						console.log("Posts :",posts);  
						jQuery( 'input[name="speedguard_new_url"]' ).autocomplete({
							source: posts,
							minLength: 2,
							select: function(event, ui) {
								event.preventDefault();
								$("#speedguard_new_url").val(ui.item.label);
								console.log("Label :",ui.item.label);  
								$("#speedguard_new_url_permalink").val(ui.item.permalink);
								$("#speedguard_new_url_id").val(ui.item.ID);
								$("#blog_id").val(ui.item.blog_id);
								console.log("Blog id :",ui.item.blog_id);   
							} 
						});
					}); 		
				</script>
				<?php
				endif;
			}
		}			
}
new SpeedGuard_Tests; 


