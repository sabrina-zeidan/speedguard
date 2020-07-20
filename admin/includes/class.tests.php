<?php
/**
* 
*	Class responsible for SpeedGuard Tests Page View
*/

// WP_List_Table is not loaded automatically 
if( ! class_exists( 'WP_List_Table' ) ) {
    require_once( ABSPATH . 'wp-admin/includes/class-wp-list-table.php' );
}
/**
 * New table class that extends the WP_List_Table
 */
class SpeedGuard_List_Table extends WP_List_Table{
	public function no_items() {
		_e('No pages guarded yet. Add something in the field above for the start.','speedguard');
	}
    public function prepare_items(string $client_id = ''){
        $columns = $this->get_columns();
        $hidden = $this->get_hidden_columns();
        $sortable = $this->get_sortable_columns();
        $data = $this->table_data($client_id);
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
	//Checkbox column
	 function column_cb($item){
        return sprintf(
            '<input type="checkbox" name="guarded-pages[]" value="%s" />', $item['guarded_page_id']
        );    
    }
    //Columns names
	public function get_columns(){
        $columns = array(
			'cb' => '<input type="checkbox" />',
            'guarded_page_title' => __( 'URL', 'speedguard' ),
            'load_time' =>  __( 'LCP', 'speedguard' ),
            'report_link' => __( 'Google PageSpeed Insights Report', 'speedguard' ),
			'report_date' => __( 'Updated', 'speedguard' ),
        );
        return $columns;
    }
	//Hidden columns
    public function get_hidden_columns(){
        return array();
    }
    //Sortable columns
    public function get_sortable_columns(){
        return array('guarded_page_title' => array('guarded_page_title', false),'load_time' => array('load_time', false),'report_date' => array('report_date', false));
    }
	//Table data
    private function table_data(string $client_id = '')    {
        $data = array();		
		$args = array(
					'post_type' => SpeedGuard_Admin::$cpt_name,
					'post_status' => 'publish',
					//TODO limit the number, ajax chunks
					'posts_per_page'   => -1,
					'fields'=>'ids',
					'no_found_rows' => true, 		
					);
		$the_query = new WP_Query( $args );
			if (!empty($client_id)){
			$meta_query = array();
				$meta_query[] = array(
					'relation' => 'AND',
					array(
						'key' => 'speedguard_page_client_id',
						'compare' => '=',
						'value' => $client_id
					
					)
				);				
				$the_query->set('meta_query',$meta_query);	
			}
		$guarded_pages = $the_query->get_posts();
		if( $guarded_pages ) :
		foreach($guarded_pages as $guarded_page_id) {  
			$guarded_page_url = get_post_meta( $guarded_page_id,'speedguard_page_url', true);
			
			
			var_dump($guarded_page_url);
			$guarded_page_type = get_post_meta( $guarded_page_id,'speedguard_item_type', true);
			var_dump($guarded_page_type);
			$guarded_post_id = get_post_meta( $guarded_page_id,'guarded_post_id', true);
			var_dump($guarded_post_id);			
			
			$speedguard_on = get_term_meta( $guarded_post_id,'speedguard_on', true);
			//$speedguard_on = get_post_meta( $guarded_post_id,'speedguard_on', true);
			var_dump($speedguard_on);
		$vv = is_archive($guarded_post_id)	;
var_dump($vv);	
			
			$connection = get_post_meta( $guarded_page_id,'speedguard_page_connection', true);
			$load_time = get_post_meta( $guarded_page_id,'load_time');			
			$load_time = $load_time[0];	
		
			if (!is_array($load_time)){
				$guarded_page_load_time = __('checking','speedguard');
				$start_test = SpeedGuard_Lighthouse::lighthouse_new_test($guarded_page_id);	
			}			
			else {
				$guarded_page_load_time = '<span data-score="'.$load_time['score'].'" class="speedguard-score"><span>●</span> '.$load_time['displayValue'].'</span>';
				}	
			$report_link = add_query_arg( array(
							'url'=> $guarded_page_url,
							'tab' => $connection
							),'https://developers.google.com/speed/pagespeed/insights/' );	
							
			$updated = get_the_modified_date('Y-m-d H:i:s', $guarded_page_id );				
					
				$data[] = array(
					'guarded_page_id' => $guarded_page_id,
					'guarded_page_title' => '<a href="'.$guarded_page_url.'" target="_blank">'.$guarded_page_url.'</a>',
					'load_time' => $guarded_page_load_time,
					'report_link' => '<a href="'.$report_link.'" target="_blank">'.__('Report','speedguard').'</a>',
					'report_date' => $updated,						
                 );
			
        }
		endif;
		wp_reset_postdata();
        				
        return $data;
    }
	
	//Columns names
    public function column_default( $item, $column_name ){
        switch( $column_name ) {
            case 'guarded_page_title':
           // case 'connection':
            case 'load_time':
            case 'report_link':
            case 'report_date': 
                return $item[ $column_name ];
            default:
                return print_r( $item, true ) ;
        }
    }
	//Sort data the variables set in the $_GET
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
			'retest_load_time' => __( 'Retest', 'speedguard')					
		);
		return $actions;
	}
	
	public function process_bulk_action() {
		
        $doaction = $this->current_action();
		if (!empty($doaction) & !empty($_POST['guarded-pages']))	$process_bulk_action = SpeedGuard_Tests::handle_bulk_retest_load_time($doaction, $_POST['guarded-pages']);
		
		
	}    
}

	
	
class SpeedGuard_Tests{
	function __construct(){			
		add_action( 'rest_api_init', array( $this, 'speedguard_rest_api_register_routes') );
	} 
	
	

	 function speedguard_rest_api_register_routes() { 
		register_rest_route( 'speedguard', '/search', array(
			'methods'  => 'GET',
			'callback' => array( $this, 'speedguard_rest_api_search'),
			//'permission_callback' => array( $this, 'get_items_permissions_check'),
		) );
	}
	function get_items_permissions_check( $request ) {
		return current_user_can( 'edit_posts' );
	}
  

	function speedguard_rest_api_search( $request ) {
		if ( empty( $request['term'] ) ) {
			return;
		}		
		function speedguard_search($request){
			$args = array(
				'post_type' => SpeedGuard_Admin::supported_post_types(),
				'post_status' => 'publish',
				'posts_per_page'   => -1,
				'fields'   => 'ids',
				's'             => $request['term'],
				'no_found_rows' => true,  
				'update_post_meta_cache' => false,
				'update_post_term_cache' => false
				);
			$the_query = new WP_Query( $args );								
			$this_blog_found_posts = $the_query->get_posts();
				$temp = array();
				foreach( $this_blog_found_posts as $key => $post_id) { 
					$key = 'ID';
					$temp = array(
						'ID' => $post_id,
						'permalink' => get_permalink($post_id),
						'blog_id' =>  get_current_blog_id(),
						'label' => get_the_title($post_id),
						'type' => 'single'
						);
					$posts[] = $temp;											
				}	
		//Include Terms too, and search all
		$the_terms = get_terms( array(
		  'name__like' => $request['term'],
		  'hide_empty' => false // Optional
		));
		if ( count($the_terms) > 0 ) {
		  foreach ( $the_terms as $term ) {
			$key = 'ID';
					$temp = array(
						'ID' => $term->term_id,
						'permalink' => get_term_link( $term ),
						'blog_id' =>  get_current_blog_id(),
						'label' => $term->name,
						'type' => 'archive'
						);
					$posts[] = $temp;			
					
		  }
		}
		
		
		
				
			return $posts;
		}

		//search all blogs if Network Activated
		if (defined('SPEEDGUARD_MU_NETWORK')) {     
				$sites = get_sites();
				$posts = array();				
				foreach ($sites as $site ) {
					$blog_id = $site->blog_id;				
					switch_to_blog( $blog_id );
						$this_blog_posts = speedguard_search($request);
						$posts = array_merge($posts, $this_blog_posts);
					restore_current_blog();	 				
				}//endforeach					
		}//endif network
		else {		
			$posts = speedguard_search($request);
		}
 		
		return $posts;	

	}

	public static function handle_bulk_retest_load_time($doaction,$post_ids){
		if ( $doaction == 'retest_load_time' ) {
			foreach ($post_ids as $guarded_page_id){ 
				$updated = get_the_modified_date('Y-m-d H:i:s', $guarded_page_id );
					if ((strtotime("-5 minutes")) > strtotime($updated)){
						//older - go on
						//TODO if there are a few newer and a few old - show notice accordingly
						$test_created = SpeedGuard_Lighthouse::lighthouse_new_test($guarded_page_id);
						$results_updated = add_query_arg( 'speedguard', 'load_time_updated');						
					}
					else {
						$slow_down = add_query_arg( 'speedguard', 'slow_down');	
					}
			$redirect_to = (!empty($results_updated)) ? $results_updated : $slow_down;				
			}				
						
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
	
		
					
	public static function import_data() { 

		$url_from_autocomplete = htmlspecialchars($_POST['speedguard_new_url_permalink']); 
		$direct_input = str_replace(' ', '', htmlspecialchars($_POST['speedguard_new_url'] ));	
		//if nothing was entered either via autocomplete or typein
		if (empty($url_from_autocomplete) && empty ($direct_input)) $redirect_to = add_query_arg( 'speedguard', 'add_new_url_error_empty');		
		//if direct input
				if (empty($url_from_autocomplete) && !empty($direct_input)) {
					//check the input, if it's NOT an url 
					if (!filter_var($direct_input, FILTER_VALIDATE_URL)){
						$redirect_to = add_query_arg( 'speedguard', 'add_new_url_error_not_url');
					}
					//if it IS an url
					else {
						$entered_domain = parse_url($direct_input);
						//if it belongs to the current domain and it's not PRO		
						
						if (($_SERVER['SERVER_NAME'] != $entered_domain['host']) && !defined('SPEEDGUARD_PRO')) {
							//if no					
							$redirect_to = add_query_arg( 'speedguard', 'add_new_url_error_not_current_domain');
						}
						//if yes
						else {
							$url_to_add = $direct_input;
							$guarded_post_id = url_to_postid($url_to_add);
							//MU search
							//$guarded_post_blog_id = htmlspecialchars($_POST['blog_id']);
						}						
					}
				}
				//if it's not direct input but autocomplete select $url_from_autocomplete = $guarded_page_url
				else if (!empty($url_from_autocomplete)){
					$guarded_url_type = htmlspecialchars($_POST['speedguard_item_type']); 
					$guarded_post_id = htmlspecialchars($_POST['speedguard_new_url_id']);				
					$guarded_post_blog_id = htmlspecialchars($_POST['blog_id']);
					$url_to_add = $url_from_autocomplete;
					
				} 
				
				if (!empty($url_to_add)){
						$connection = Speedguard_Admin::get_this_plugin_option( 'speedguard_options' )['test_connection_type'];
						$code = $url_to_add.'|'.$connection;
						$new_target_page = array( 
							'post_title'           => $code,
							'post_status'   => 'publish',	
							'post_type'   => SpeedGuard_Admin::$cpt_name,	 
						);												
						
						if (defined('SPEEDGUARD_MU_NETWORK')) switch_to_blog(get_network()->site_id);   
						$target_page_id = wp_insert_post( $new_target_page );  
						$update_field = update_post_meta($target_page_id, 'speedguard_page_url', $url_to_add);  
						$update_type = update_post_meta($target_page_id, 'speedguard_item_type', $guarded_url_type);  
						//TODO always pass
						if ($guarded_post_blog_id) $update_field = update_post_meta($target_page_id, 'guarded_post_blog_id', $guarded_post_blog_id);  
						$update_field = update_post_meta($target_page_id, 'guarded_post_id', $guarded_post_id); 
							


							
						//check url as guarded
						//TODO for archives
						
						if ($guarded_url_type == 'single'){							  
							
							$set_speedguard_on = update_post_meta($guarded_post_id, 'speedguard_on', array('true',$target_page_id) );
						
						}
						else if ($guarded_url_type == 'archive'){
							$set_speedguard_on = update_term_meta( $guarded_post_id, 'speedguard_on', array('true',$target_page_id));
						}
						if (defined('SPEEDGUARD_MU_NETWORK')) restore_current_blog(); 
						
						//start test 
						//$start_test = SpeedGuard_WebPageTest::webpagetest_new_test($target_page_id);	
						$start_test = SpeedGuard_Lighthouse::lighthouse_new_test($target_page_id);	
						$redirect_to = add_query_arg( 'speedguard', 'new_url_added');	 	
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
		}			
}
new SpeedGuard_Tests; 


