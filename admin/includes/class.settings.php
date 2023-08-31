<?php
/**
*
*   Class responsible for the SpeedGuard settings
*/
class SpeedGuard_Settings
{
    static $settings_page_hook = 'speedguard_page_speedguard_settings';
    public static $speedguard_options = 'speedguard_options';
    function __construct()
    {
        //Register Settings sections
        add_action('admin_init', array( $this, 'speedguard_settings'));
        
        //This is Single Install or Multisite PER SITE
        add_action('added_option', array( $this, 'default_options_added'), 10, 2);
        add_action('updated_option', array( $this, 'speedguard_options_updated'), 10, 3);
        add_action('pre_update_option_speedguard_options', array( $this, 'default_options_set'), 10, 2);
        
        //For NETWORK ACTIVATED only
        
        add_action('add_site_option', array( $this, 'default_options_added'), 10, 2);
        add_action('update_site_option', array( $this, 'default_options_added'), 10, 2);
        //Set default plugin settings
        add_action('pre_update_site_option_speedguard_options', array( $this, 'default_options_set'), 10, 2);
        //Update options action function for Multisite
        add_action('network_admin_edit_speedguard_update_settings', array($this, 'speedguard_update_settings'));
    
        //update Averages when any load_time is updated
        add_action('added_post_meta', array( $this,'load_time_updated_function'), 10, 4);
        add_action('updated_post_meta', array( $this,'load_time_updated_function'), 10, 4);
        add_action('deleted_post_meta', array( $this,'load_time_updated_function'), 10, 4);
        add_filter('cron_schedules', array( $this,'speedguard_cron_schedules'));
        //send report when load_time is updated by cron automatically
        add_action('speedguard_update_results', array( $this,'update_results_cron_function'));
        add_action('speedguard_email_test_results', array( $this,'email_test_results_function'));
    }

    function default_options_set($new_value = '', $old_value = '')
    {
                $admin_email = SpeedGuard_Admin::get_this_plugin_option('admin_email');
        if (empty($new_value['show_dashboard_widget'])) {
            $new_value['show_dashboard_widget'] = 'on';
        }
        if (empty($new_value['show_ab_widget'])) {
            $new_value['show_ab_widget'] = 'on';
        }
        if (empty($new_value['check_recurrence'])) {
            $new_value['check_recurrence'] = '1';
        }
        if (empty($new_value['email_me_at'])) {
            $new_value['email_me_at'] = $admin_email;
        }
        if (empty($new_value['email_me_case'])) {
            $new_value['email_me_case'] = 'just in case average speed worse than';
        }
        if (empty($new_value['critical_load_time'])) {
            $new_value['critical_load_time'] = '3';
        }
        if (empty($new_value['test_connection_type'])) {
            $new_value['test_connection_type'] = 'mobile';
        }
                return $new_value;
    }
    function default_options_added($option, $new_value)
    {
        $speedguard_options = SpeedGuard_Admin::get_this_plugin_option('speedguard_options');
        if (empty($speedguard_options)) {
                //TODO set options on activation
                //if just activated + if options are not set yet
                $new_value = $this->default_options_set(array());
                SpeedGuard_Admin::update_this_plugin_option('speedguard_options', $new_value);
        } elseif (!empty($speedguard_options) && $option == 'speedguard_options') { //if updating options
                    $speedguard_options = SpeedGuard_Admin::get_this_plugin_option('speedguard_options');
                    $admin_email = $speedguard_options['email_me_at'];
                    $check_recurrence = $speedguard_options['check_recurrence'];
                        wp_clear_scheduled_hook('speedguard_update_results');
            if (!wp_next_scheduled('speedguard_update_results')) {
                wp_schedule_event(time(), 'speedguard_interval', 'speedguard_update_results');
            }
        }
    }
    function speedguard_options_updated($option, $old_value, $value)
    {
        if ($option == 'speedguard_options') {
                $speedguard_options = SpeedGuard_Admin::get_this_plugin_option('speedguard_options');
                $admin_email = $speedguard_options['email_me_at'];
                $check_recurrence = $speedguard_options['check_recurrence'];
                    wp_clear_scheduled_hook('speedguard_update_results');
                    wp_clear_scheduled_hook('speedguard_email_test_results');
            if (!wp_next_scheduled('speedguard_update_results')) {
                wp_schedule_event(time(), 'speedguard_interval', 'speedguard_update_results');
            }
        }
    }
        
    function load_time_updated_function($meta_id, $post_id, $meta_key, $meta_value)
    {
        if ('load_time' == $meta_key) {
            $args = array(
                'no_found_rows' => true,
                'post_type' => SpeedGuard_Admin::$cpt_name,
                'post_status' => 'publish',
                'posts_per_page'   => -1,
                'fields' =>'ids',
                'meta_query' => array(
                                    array(
                                        'key' => 'load_time',
                                        'value' => 'waiting',
                                        'compare' => 'NOT LIKE'
                                        )
                                )
                        );
            $the_query = new WP_Query($args);
            $guarded_pages = $the_query->get_posts();
                $guarded_page_load_time_all = array();
            if (count($guarded_pages) > 0) {
                foreach ($guarded_pages as $guarded_page) {
                            $guarded_page_load_time = get_post_meta($guarded_page, 'load_time');
                    if (!empty($guarded_page_load_time[0]['numericValue']) && $guarded_page_load_time[0]['numericValue'] > 0) {
                        $guarded_page_load_time = round(($guarded_page_load_time[0]['numericValue']/1000), 1);
                        $guarded_page_load_time_all[] = $guarded_page_load_time;
                    }
                }
                if (!empty($guarded_page_load_time_all)) {
                        $average_load_time = round(array_sum($guarded_page_load_time_all)/count($guarded_page_load_time_all), 1);
                        $min_load_time = min($guarded_page_load_time_all);
                        $max_load_time = max($guarded_page_load_time_all);
                        $new_averages = array(
                            'average_load_time'=> $average_load_time,
                            'min_load_time'=> $min_load_time,
                            'max_load_time' => $max_load_time,
                            'guarded_pages_count' => count($guarded_page_load_time_all)
                        );
                }
            } else {
                $new_averages = array(
                                    'average_load_time'=> 0,
                                    'min_load_time'=> 0,
                                    'max_load_time' => 0,
                                    'guarded_pages_count' => 0
                                    );
            }
            if ($new_averages) {
                SpeedGuard_Admin::update_this_plugin_option('speedguard_average', $new_averages);
            }
        }
    }
    
    function update_results_cron_function()
    {
            //if send report on: schedule cron job
            $speedguard_options = SpeedGuard_Admin::get_this_plugin_option('speedguard_options');
            $email_me_case = $speedguard_options['email_me_case'];
        if ($email_me_case != 'never') {
            if (!wp_next_scheduled('speedguard_email_test_results')) {
                //in 2 minutes
                wp_schedule_single_event(time() + 2*60, 'speedguard_email_test_results');
            }
        }
            $args = array(
                'post_type' => SpeedGuard_Admin::$cpt_name,
                'post_status' => 'publish',
                'posts_per_page'   => -1,
                'fields'=> 'ids',
                'no_found_rows' => true
                );
            $the_query = new WP_Query($args);
            $guarded_pages = $the_query->get_posts();
            if (!empty($guarded_pages)) {
                foreach ($guarded_pages as $guarded_page_id) {
                    $result = SpeedGuard_Tests::update_speedguard_test($guarded_page_id);
                }
            }
            //wp_reset_postdata();
    }
    function email_test_results_function()
    {
            $speedguard_options = SpeedGuard_Admin::get_this_plugin_option('speedguard_options');
            $email_me_case = $speedguard_options['email_me_case'];
        if ($email_me_case == 'every time after tests are executed') {
            SpeedGuard_Notifications::test_results_email('regular');
        } elseif ($email_me_case == 'just in case average speed worse than') {
            $critical_load_time = $speedguard_options['critical_load_time'];
            $average_load_time = SpeedGuard_Admin::get_this_plugin_option('speedguard_average')['average_load_time'];
            if ($average_load_time > $critical_load_time) {
                SpeedGuard_Notifications::test_results_email('critical_load_time');
            }
        }
    }
    function speedguard_cron_schedules($schedules)
    {
                    $speedguard_options = SpeedGuard_Admin::get_this_plugin_option('speedguard_options');
                    $check_recurrence = $speedguard_options['check_recurrence'];
                            $value = constant('DAY_IN_SECONDS');
                            $interval = (int)$check_recurrence*$value;
                            $schedules['speedguard_interval'] = array(
                                'interval' => $interval, // user input integer in second
                                'display'  => __('SpeedGuard check interval', 'speedguard'),
                            );
                            
                            return $schedules;
    }
        
    function show_dashboard_widget_fn($args)
    {
        $options = SpeedGuard_Admin::get_this_plugin_option('speedguard_options');
        $field_name = esc_attr($args['label_for']);
        if ($options[$field_name] == 'on') {
            $checked = ' checked="checked" ';
        } else {
            $checked = '';
        }
        echo "<input type='hidden' name='speedguard_options[".$field_name."]' value='off' /><input ".$checked." id='speedguard_options[".$field_name."]' name='speedguard_options[".$field_name."]' type='checkbox' />";
    }
    function show_ab_widget_fn($args)
    {
        $options = SpeedGuard_Admin::get_this_plugin_option('speedguard_options');
        $field_name = esc_attr($args['label_for']);
        if ($options[$field_name] == 'on') {
            $checked = ' checked="checked" ';
        } else {
            $checked = '';
        }
        echo "<input type='hidden' name='speedguard_options[".$field_name."]' value='off' /><input ".$checked." id='speedguard_options[".$field_name."]' name='speedguard_options[".$field_name."]' type='checkbox' />";
    }
    function check_recurrence_fn($args)
    {
        $options = SpeedGuard_Admin::get_this_plugin_option('speedguard_options');
        $field_name = esc_attr($args['label_for']);
        $days =_n(' day', ' days', $options[$field_name], 'speedguard');
        $string = "<input id='speedguard_options[".$field_name."]' name='speedguard_options[".$field_name."]' type='text' class='numbers' size='2' value='".$options[$field_name]."'/> ".$days;
        $instructions = '<div class="note">'.__('If you don\'t want any automatic tests place "0".', 'speedguard').'</div>';
        echo $string.$instructions;
    }
    function email_me_at_fn($args)
    {
        $options = SpeedGuard_Admin::get_this_plugin_option('speedguard_options');
        $field_name = esc_attr($args['label_for']);
        echo "<input id='speedguard_options[".$field_name."]' name='speedguard_options[".$field_name."]' type='text' size='40' value='".$options[$field_name]."'/>";
    }
    function print_description($item)
    {
                echo $item;
    }
    function email_me_case_fn($args)
    {
        $options = SpeedGuard_Admin::get_this_plugin_option('speedguard_options');
        $field_name = esc_attr($args['label_for']);
        $items = array(
            'every time after tests are executed' => __('every time after tests are executed', 'speedguard'),
            'just in case average speed worse than' => __('just in case average speed worse than', 'speedguard'),
            'never' => __('never', 'speedguard')
            );
        foreach ($items as $item => $item_label) {
            $checked = ($options[$field_name] == $item) ? ' checked="checked" ' : '';
    
            echo "<input ".$checked." type='radio' name='speedguard_options[".$field_name."]' id='".$item."' value='".$item."' /><label for='".$item."'>".$item_label."</label>";
            $critical_load_time = $options['critical_load_time'];
            if ($item == 'just in case average speed worse than') {
                $this->critical_load_time_fn(array('label_for'=>'critical_load_time', 'show'=>true));
            }
            echo "</label><br />";
        }
    }
        
    function test_connection_type_fn($args)
    {
        $options = SpeedGuard_Admin::get_this_plugin_option('speedguard_options');
        $field_name = esc_attr($args['label_for']);
        $items = array(
            'mobile' => __('Mobile', 'speedguard'),
            'desktop' => __('Desktop', 'speedguard'),
            );
        echo "<select id='speedguard_options[".$field_name."]' name='speedguard_options[".$field_name."]' >";
        foreach ($items as $item => $item_label) {
            $selected = ($options[$field_name] == $item) ? ' selected="selected" ' : '';
            echo "<option ".$selected." value='$item'>$item_label</option>";
        }
        echo "</select>";
    }
    function critical_load_time_fn($args)
    {
        if (isset($args['show']) && $args['show'] == true) {
            $options = SpeedGuard_Admin::get_this_plugin_option('speedguard_options');
            $field_name = esc_attr($args['label_for']);
            echo " <input type='text' id='speedguard_options[critical_load_time]' name='speedguard_options[critical_load_time]'  class='numbers'  size='2' value='".$options[$field_name]."'> ".__('s', 'speedguard');
        }
    }
    
    function speedguard_update_settings()
    {
        check_admin_referer('speedguard-options');
        global $new_whitelist_options;
        $options = $new_whitelist_options['speedguard'];
        foreach ($options as $option) {
            if (isset($_POST[$option])) {
                update_site_option($option, $_POST[$option]);
            }
        }
        wp_redirect(add_query_arg(array('page' => 'speedguard_settings','settings-updated' => 'true'), network_admin_url('admin.php')));
        exit;
    }
    function speedguard_settings()
    {
        //General Settings
        register_setting('speedguard', 'speedguard_options');
        add_settings_section('speedguard_widget_settings_section', '', '', 'speedguard');
        add_settings_field('speedguard_options', __('Show site average load time on Dashboard', 'speedguard'), array($this,'show_dashboard_widget_fn'), 'speedguard', 'speedguard_widget_settings_section', ['label_for' => 'show_dashboard_widget']);
        
        add_settings_field('speedguard_ab_widget', __('Show current page load time in Admin Bar', 'speedguard'), array($this,'show_ab_widget_fn'), 'speedguard', 'speedguard_widget_settings_section', ['label_for' => 'show_ab_widget']);
        add_settings_section('speedguard_reports_section', '', '', 'speedguard');
        add_settings_field('speedguard_check_recurrence', __('Check pageload speed every', 'speedguard'), array($this,'check_recurrence_fn'), 'speedguard', 'speedguard_reports_section', ['label_for' => 'check_recurrence']);
        add_settings_field('speedguard_email_me_at', __('Send me report at', 'speedguard'), array($this,'email_me_at_fn'), 'speedguard', 'speedguard_reports_section', ['label_for' => 'email_me_at']);
        add_settings_field('speedguard_email_me_case', '', array($this,'email_me_case_fn'), 'speedguard', 'speedguard_reports_section', ['label_for' => 'email_me_case']);
        add_settings_field('speedguard_test_connection_type', __('Device', 'speedguard'), array($this,'test_connection_type_fn'), 'speedguard', 'speedguard_reports_section', ['label_for' => 'test_connection_type']);
        
        
        add_settings_field('speedguard_critical_load_time', '', array($this,'critical_load_time_fn'), 'speedguard', 'speedguard_hidden_section', ['label_for' => 'critical_load_time']);
    }
    function speedguard_settings_general()
    {
    }

    public static function my_settings_page_function()
    {
        if (SpeedGuard_Admin::is_screen('settings')) {
            SpeedGuardWidgets::add_meta_boxes();
            ?>
            <div class="wrap">        
                <h2><?php _e('SpeedGuard :: Settings', 'speedguard'); ?></h2>               
                        <div id="poststuff" class="metabox-holder has-right-sidebar">
                            <div id="side-info-column" class="inner-sidebar">
                                <?php do_meta_boxes('', 'side', 0); ?>
                            </div>
                            <div id="post-body" class="has-sidebar">
                                <div id="post-body-content" class="has-sidebar-content">
                                <form method="post" action="<?php print_r(defined('SPEEDGUARD_MU_NETWORK') ? 'edit.php?action=speedguard_update_settings' : 'options.php'); ?>">
                                <?php  do_meta_boxes('', 'normal', 0); ?>
                                </form> 

              
                                </div>
                            </div>
                        </div>  
                    </form>
            </div>
            <?php
        }
    }
                 
    public static function settings_meta_box()
    {
        settings_fields('speedguard');
        do_settings_sections('speedguard');
        submit_button(__('Save Settings', 'speedguard'), 'primary', 'submit', false);
    }
}
new SpeedGuard_Settings;
