<?php
/*
Plugin Name: Calendar
Plugin URI: http://www.kieranoshea.com
Description: This plugin allows you to display a calendar of all your events and appointments as a page on your site.
Author: Kieran O'Shea
Author URI: http://www.kieranoshea.com
Text Domain: calendar
Domain Path: /languages
Version: 1.3.16
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html
*/

/*  Copyright 2008  Kieran O'Shea  (email : kieran@kieranoshea.com)

    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation; either version 2 of the License, or
    (at your option) any later version.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program; if not, write to the Free Software
    Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/

// Direct access shouldn't be allowed
if ( ! defined( 'ABSPATH' ) ) exit;

// Enable internationalisation
function calendar_load_text_domain() {
    $plugin_dir = plugin_basename(dirname(__FILE__));
    load_plugin_textdomain('calendar', false, $plugin_dir . '/languages');
}
add_action('plugins_loaded', 'calendar_load_text_domain');

// Define the constants & tables used in Calendar
global $wpdb;
define('CALENDAR_TITLE_LENGTH', 30);
define('WP_CALENDAR_TABLE', $wpdb->prefix . 'calendar');
define('WP_CALENDAR_CONFIG_TABLE', $wpdb->prefix . 'calendar_config');
define('WP_CALENDAR_CATEGORIES_TABLE', $wpdb->prefix . 'calendar_categories');

// Check ensure calendar is installed and install it if not - required for
// the successful operation of most functions called from this point on
calendar_check();

// Create a master category for Calendar and its sub-pages
add_action('admin_enqueue_scripts', 'calendar_add_javascript');
add_action('admin_menu', 'calendar_menu');

// Enable the ability for the calendar to be loaded from pages
add_filter('the_content','calendar_insert');
add_filter('the_content','calendar_minical_insert');

// Enable the ability for the lists to be loaded from pages
add_filter('the_content','calendar_upcoming_insert');
add_filter('the_content','calendar_todays_insert');

// Add the function that puts style information in the header
add_action('wp_enqueue_scripts', 'calendar_wp_head');

// Add the function that deals with deleted users
add_action('delete_user', 'calendar_deal_with_deleted_user');

// Add the widgets if we are using version 2.8
add_action('widgets_init', 'calendar_register_today_widget');
add_action('widgets_init', 'calendar_register_upcoming_widget');
add_action('widgets_init', 'calendar_register_minical_widget');

// Add query vars for switching months/years in rendered calendars
add_action('init','calendar_add_query_vars');
function calendar_add_query_vars() { 
    global $wp; 
    $wp->add_query_var('calendar_yr'); 
    $wp->add_query_var('calendar_month');
}

// Add the short code
add_shortcode( 'calendar', 'calendar_shortcode_insert' );
add_filter('widget_text', 'do_shortcode');

// Add feed functionality from separate file
add_action( 'init', 'calendar_feed_init_internal' );
function calendar_feed_init_internal()
{
    add_rewrite_rule( 'calendar-feed$', 'index.php?calendar_feed=1', 'top' );
}

add_filter( 'query_vars', 'calendar_feed_query_vars' );
function calendar_feed_query_vars( $query_vars )
{
    $query_vars[] = 'calendar_feed';
    return $query_vars;
}

add_action( 'parse_request', 'calendar_feed_parse_request' );
function calendar_feed_parse_request( &$wp )
{
    if ( array_key_exists( 'calendar_feed', $wp->query_vars ) ) {
        include 'calendar-feed.php';
        exit();
    }
    return;
}

// Function to display a warning on the admin panel if the calendar plugin is mising setup
add_action( 'admin_notices', 'calendar_setup_incomplete_warning' );
function calendar_setup_incomplete_warning() {
    $incomplete_check = calendar_get_config_value('show_attribution_link');
    if (empty($incomplete_check) && !(get_admin_page_title() == 'Calendar Config')) {
        $args = array( 'page' => 'calendar-config');
        $url = add_query_arg( $args, admin_url( 'admin.php' ) );
        ?>
        <div class="error"><p><strong><?php esc_html_e('Warning','calendar'); ?>:</strong> <?php esc_html_e("Calendar setup incomplete. Go to the ",'calendar') ?><a href="<?php echo esc_url($url) ?>"><?php esc_html_e("calendar plugin settings",'calendar') ?></a><?php esc_html_e(" to complete setup.",'calendar'); ?></p></div>
        <?php
    }
}

// Function to provide time with WordPress offset, localy replaces time()
function calendar_ctwo()
{
  return (time()+(3600*(get_option('gmt_offset'))));
}

// Function to add the calendar style into the header
function calendar_wp_head()
{
    $style = calendar_get_config_value('calendar_style');
    if ($style != '') {
        wp_register_style('calendar-style', false, array(), time());
        wp_enqueue_style('calendar-style');
        wp_add_inline_style('calendar-style', $style);
    }
}

// Function to deal with adding the calendar menus
function calendar_menu() 
{
  // Set admin as the only one who can use Calendar for security
  $allowed_group = 'manage_options';

  // Use the database to *potentially* override the above if allowed
  $configs = calendar_get_config_value('can_manage_events');
  if (!empty($configs)) {
      $allowed_group = $configs;
  }

  // Add the admin panel pages for Calendar. Use permissions pulled from above
   if (function_exists('add_menu_page')) 
     {
       add_menu_page(__('Calendar','calendar'), __('Calendar','calendar'), $allowed_group, 'calendar', 'calendar_edit');
     }
   if (function_exists('add_submenu_page')) 
     {
       add_submenu_page('calendar', __('Manage Calendar','calendar'), __('Manage Calendar','calendar'), $allowed_group, 'calendar', 'calendar_edit');
       // Note only admin can change calendar options
       add_submenu_page('calendar', __('Manage Categories','calendar'), __('Manage Categories','calendar'), 'manage_options', 'calendar-categories', 'calendar_manage_categories');
       add_submenu_page('calendar', __('Calendar Config','calendar'), __('Calendar Options','calendar'), 'manage_options', 'calendar-config', 'calendar_config_edit');
     }
}

// Function to add the javascript to the admin header
function calendar_add_javascript()
{
    wp_enqueue_script( 'calendar_custom_wp_admin_js', plugins_url('javascript.js', __FILE__), array(), '1.3.16', false );
    wp_enqueue_style( 'calendar_custom_wp_admin_css', plugins_url('calendar-admin.css', __FILE__), array(), '1.3.16' );
}

// Function to deal with loading the calendar into pages
function calendar_shortcode_insert($atts) {
    $a = shortcode_atts( array(
        'categories' => '',
        'type' => ''
    ), $atts );
    if ($a['categories'] == '') {
        if ($a['type'] == 'todays') {
            return calendar_todays_events();
        } else if ($a['type'] == 'upcoming') {
            return calendar_upcoming_events();
        } else if ($a['type'] == 'mini') {
            return calendar_minical();
        } else {
            return calendar();
        }
    } else {
        if ($a['type'] == 'todays') {
            return calendar_todays_events( $a['categories'] );
        } else if ($a['type'] == 'upcoming') {
            return calendar_upcoming_events( $a['categories'] );
        } else if ($a['type'] == 'mini') {
            return calendar_minical( $a['categories'] );
        } else {
            return calendar( $a['categories'] );
        }
    }
}
function calendar_insert($content)
{
  if (preg_match('/\{CALENDAR*.+\}/',$content))
    {
      $cat_list = preg_split('/\{CALENDAR\;/',$content);
      if (sizeof($cat_list) > 1) {
	$cat_list = preg_split('/\}/',$cat_list[1]);
        $cat_list = $cat_list[0];
        $cal_output = calendar($cat_list);
      } else {
	$cal_output = calendar();
      }
      $content = preg_replace('/\{CALENDAR*.+\}/',preg_replace('/\$(\d)/','\\\$$1',$cal_output),$content);
    }
  return $content;
}

// Function to show a mini calendar in pages
function calendar_minical_insert($content)
{
  if (preg_match('/\{MINICAL*.+\}/',$content))
    {
      $cat_list= preg_split('/\{MINICAL\;/',$content);
      if (sizeof($cat_list) > 1) {
	$cat_list = preg_split('/\}/',$cat_list[1]);
	$cat_list= $cat_list[0];
	$cal_output = calendar_minical($cat_list);
      } else {
	$cal_output = calendar_minical();
      }
      $content = preg_replace('/\{MINICAL*.+\}/',preg_replace('/\$(\d)/','\\\$$1',$cal_output),$content);
    }
  return $content;
}

// Functions to allow the widgets to be inserted into posts and pages
function calendar_upcoming_insert($content)
{
  if (preg_match('/\{UPCOMING_EVENTS*.+\}/',$content))
    {
      $cat_list= preg_split('/\{UPCOMING_EVENTS\;/',$content);
      if (sizeof($cat_list) > 1) {
	$cat_list = preg_split('/\}/',$cat_list[1]);
	$cat_list= $cat_list[0];
	$cal_output = '<span class="page-upcoming-events">'.calendar_upcoming_events($cat_list).'</span>';
      } else {
	$cal_output = '<span class="page-upcoming-events">'.calendar_upcoming_events().'</span>';
      }
      $content = preg_replace('/\{UPCOMING_EVENTS*.+\}/',preg_replace('/\$(\d)/','\\\$$1',$cal_output),$content);
    }
  return $content;
}
function calendar_todays_insert($content)
{
  if (preg_match('/\{TODAYS_EVENTS*.+\}/',$content))
    {
      $cat_list= preg_split('/\{TODAYS_EVENTS\;/',$content);
      if (sizeof($cat_list) > 1) {
	$cat_list = preg_split('/\}/',$cat_list[1]);
	$cat_list= $cat_list[0];
	$cal_output = '<span class="page-todays-events">'.calendar_todays_events($cat_list).'</span>';
      } else {
	$cal_output = '<span class="page-todays-events">'.calendar_todays_events().'</span>';
      }
      $content = preg_replace('/\{TODAYS_EVENTS*.+\}/',preg_replace('/\$(\d)/','\\\$$1',$cal_output),$content);
    }
  return $content;
}

// Function to check what version of Calendar is installed and install if needed
function calendar_check()
{
  // Checks to make sure Calendar is installed, if not it adds the default
  // database tables and populates them with test data. If it is, then the 
  // version is checked through various means and if it is not up to date 
  // then it is upgraded.

  // Lets see if this is first run and create us a table if it is!
  global $initial_style;

  // Version info
  $calendar_version_option = 'calendar_version';
  $calendar_version = '1.3.16';

  // All this style info will go into the database on a new install
  // This looks nice in the TwentyTen theme
  $initial_style = "    .calnk a:hover {
        background-position:0 0;
        text-decoration:none;  
        color:#000000;
        border-bottom:1px dotted #000000;
     }
    .calnk a:visited {
        text-decoration:none;
        color:#000000;
        border-bottom:1px dotted #000000;
    }
    .calnk a {
        text-decoration:none; 
        color:#000000; 
        border-bottom:1px dotted #000000;
    }
    .calnk a > span {
        display:none; 
    }
    .calnk a:hover > span {
        color:#333333; 
        background:#F6F79B; 
        display:block;
        position:absolute; 
        margin-top:1px; 
        padding:5px; 
        width:auto;
        z-index:100;
        line-height:1.2em;
    }
    .calendar-table {
        border:0 !important;
        width:100% !important;
        border-collapse:separate !important;
        border-spacing:2px !important;
    }
    .calendar-heading {
        height:25px;
        text-align:center;
        background-color:#E4EBE3;
    }
    .calendar-next {
        width:20%;
        text-align:center;
        border:none;
    }
    .calendar-prev {
        width:20%;
        text-align:center;
        border:none;
    }
    .calendar-month {
        width:60%;
        text-align:center;
        font-weight:bold;
        border:none;
    }
    .normal-day-heading {
        text-align:center;
        width:25px;
        height:25px;
        font-size:0.8em;
        border:1px solid #DFE6DE;
        background-color:#EBF2EA;
    }
    .weekend-heading {
        text-align:center;
        width:25px;
        height:25px;
        font-size:0.8em;
        border:1px solid #DFE6DE;
        background-color:#EBF2EA;
        color:#FF0000;
    }
    .day-with-date {
        vertical-align:text-top;
        text-align:left;
        width:60px;
        height:60px;
        border:1px solid #DFE6DE;
    }
    .no-events {

    }
    .day-without-date {
        width:60px;
        height:60px;
        border:1px solid #E9F0E8;
    }
    span.weekend {
        color:#FF0000;
    }
    .current-day {
        vertical-align:text-top;
        text-align:left;
        width:60px;
        height:60px;
        border:1px solid #BFBFBF;
        background-color:#E4EBE3;
    }
    span.event {
        font-size:0.75em;
    }
    .kjo-link {
        font-size:0.75em;
        text-align:center;
    }
    .calendar-date-switcher {
        height:25px;
        text-align:center;
        border:1px solid #D6DED5;
        background-color:#E4EBE3;
    }
    .calendar-date-switcher form {
        margin:2px;
    }
    .calendar-date-switcher input {
        border:1px #D6DED5 solid;
        margin:0;
    }
    .calendar-date-switcher input[type=submit] {
        padding:3px 10px;
    }
    .calendar-date-switcher select {
        border:1px #D6DED5 solid;
        margin:0;
    }
    .calnk a:hover span span.event-title {
        padding:0;
        text-align:center;
        font-weight:bold;
        font-size:1.2em;
        margin-left:0px;
    }
    .calnk a:hover span span.event-title-break {
        display:block;
        width:96%;
        text-align:center;
        height:1px;
        margin-top:5px;
        margin-right:2%;
        padding:0;
        background-color:#000000;
        margin-left:0px;
    }
    .calnk a:hover span span.event-content-break {
        display:block;
        width:96%;
        text-align:center;
        height:1px;
        margin-top:5px;
        margin-right:2%;
        padding:0;
        background-color:#000000;
        margin-left:0px;
    }
    .page-upcoming-events {
        font-size:80%;
    }
    .page-todays-events {
        font-size:80%;
    }
    .calendar-table table,
    .calendar-table tbody,
    .calendar-table tr,
    .calendar-table td {
        margin:0 !important;
        padding:0 !important;
    }
    table.calendar-table {
        margin-bottom:5px !important;
    }
    .cat-key {
        width:100%;
        margin-top:30px;
        padding:5px;
        border:0 !important;
    }
    .cal-separate {
       border:0 !important;
       margin-top:10px;
    }
    table.cat-key {
       margin-top:5px !important;
       border:1px solid #DFE6DE !important;
       border-collapse:separate !important;
       border-spacing:4px !important;
       margin-left:2px !important;
       width:99.5% !important;
       margin-bottom:5px !important;
    }
    .minical-day {
       background-color:#F6F79B;
    }
    .cat-key td {
       border:0 !important;
    }";

  if (get_option($calendar_version_option) != $calendar_version) {
      // Assume this is not a new install until we prove otherwise
      $new_install = false;
      $vone_point_one_upgrade = false;
      $vone_point_two_beta_upgrade = false;

      $wp_calendar_exists = false;
      $wp_calendar_config_exists = false;
      $wp_calendar_config_version_number_exists = false;

      // Determine the calendar version
      $tables = calendar_get_db_tables();
      foreach ($tables as $table) {
          foreach ($table as $value) {
              if ($value == WP_CALENDAR_TABLE) {
                  $wp_calendar_exists = true;
              }
              if ($value == WP_CALENDAR_CONFIG_TABLE) {
                  $wp_calendar_config_exists = true;

                  // We now try and find the calendar version number
                  // This will be a lot easier than finding other stuff
                  // in the future.
                  $version_number = calendar_get_config_value('calendar_version');
                  if ($version_number == "1.2") {
                      $wp_calendar_config_version_number_exists = true;
                  }
              }
          }
      }

      if ($wp_calendar_exists == false && $wp_calendar_config_exists == false) {
          $new_install = true;
      } else if ($wp_calendar_exists == true && $wp_calendar_config_exists == false) {
          $vone_point_one_upgrade = true;
      } else if ($wp_calendar_exists == true && $wp_calendar_config_exists == true && $wp_calendar_config_version_number_exists == false) {
          $vone_point_two_beta_upgrade = true;
      }

      // Now we've determined what the current install is or isn't
      // we perform operations according to the findings
      if ($new_install == true) {
          calendar_create_calendar_table();
          calendar_create_calendar_config_table();
          calendar_insert_config_value('can_manage_events','edit_posts');
          calendar_insert_config_value('calendar_style',$initial_style);
          calendar_insert_config_value('display_author','false');
          calendar_insert_config_value('display_jump','false');
          calendar_insert_config_value('display_todays','true');
          calendar_insert_config_value('display_upcoming','true');
          calendar_insert_config_value('display_upcoming_days','7');
          calendar_insert_config_value('calendar_version','1.2');
          calendar_insert_config_value('enable_categories','false');
          calendar_create_calendar_categories();
      } else if ($vone_point_one_upgrade == true) {
          calendar_add_author_and_description_to_calendar_table();
          calendar_create_calendar_config_table();
          calendar_insert_config_value('can_manage_events','edit_posts');
          calendar_insert_config_value('calendar_style',$initial_style);
          calendar_insert_config_value('display_author','false');
          calendar_insert_config_value('display_jump','false');
          calendar_insert_config_value('display_todays','true');
          calendar_insert_config_value('display_upcoming','true');
          calendar_insert_config_value('display_upcoming_days','7');
          calendar_insert_config_value('calendar_version','1.2');
          calendar_insert_config_value('enable_categories','false');
          calendar_add_link_and_category_to_calendar_table();
          calendar_create_calendar_categories();
      } else if ($vone_point_two_beta_upgrade == true) {
          calendar_insert_config_value('calendar_version','1.2');
          calendar_insert_config_value('enable_categories','false');
          calendar_add_link_and_category_to_calendar_table();
          calendar_create_calendar_categories();
          calendar_update_config_value('calendar_style',$initial_style);
      }
      // We've installed/upgraded now, just need to ensure the correct charsets
      calendar_db_set_charset_for_table(WP_CALENDAR_TABLE);
      calendar_db_set_charset_for_table(WP_CALENDAR_CONFIG_TABLE);
      calendar_db_set_charset_for_table(WP_CALENDAR_CATEGORIES_TABLE);

      // We have feed for the first time, add the config option
      if (empty(calendar_get_config_value('enable_feed'))) {
          calendar_insert_config_value('enable_feed','false');
      }

      // Mark the version as latest
      update_option($calendar_version_option, $calendar_version, 'yes');
  }
}

// Used on the manage events admin page to display a list of events
function calendar_events_display_list(){

	$events = calendar_db_get_all_events();
	if ( !empty($events) )
	{
?>
       	<table class="widefat page fixed" width="100%" cellpadding="3" cellspacing="3">
		        <thead>
			    <tr>
				<th class="manage-column" scope="col"><?php esc_html_e('ID','calendar') ?></th>
				<th class="manage-column" scope="col"><?php esc_html_e('Title','calendar') ?></th>
				<th class="manage-column" scope="col"><?php esc_html_e('Start Date','calendar') ?></th>
				<th class="manage-column" scope="col"><?php esc_html_e('End Date','calendar') ?></th>
		                <th class="manage-column" scope="col"><?php esc_html_e('Time','calendar') ?></th>
				<th class="manage-column" scope="col"><?php esc_html_e('Recurs','calendar') ?></th>
				<th class="manage-column" scope="col"><?php esc_html_e('Repeats','calendar') ?></th>
		                <th class="manage-column" scope="col"><?php esc_html_e('Author','calendar') ?></th>
		                <th class="manage-column" scope="col"><?php esc_html_e('Category','calendar') ?></th>
				<th class="manage-column" scope="col"><?php esc_html_e('Edit','calendar') ?></th>
				<th class="manage-column" scope="col"><?php esc_html_e('Delete','calendar') ?></th>
			    </tr>
		        </thead>
<?php
		$class = '';
		foreach ( $events as $event )
		{
			$class = ($class == 'alternate') ? '' : 'alternate';
			?>
			<tr class="<?php echo esc_html($class); ?>">
				<th scope="row"><?php echo esc_html($event->event_id); ?></th>
				<td><?php echo esc_html($event->event_title); ?></td>
				<td><?php echo esc_html($event->event_begin); ?></td>
				<td><?php echo esc_html($event->event_end); ?></td>
				<td><?php if ($event->event_time == '00:00:00') { echo esc_html__('N/A','calendar'); } else { echo esc_html($event->event_time); } ?></td>
				<td>
				<?php 
					// Interpret the DB values into something human readable
					if ($event->event_recur == 'S') { echo esc_html__('Never','calendar'); } 
					else if ($event->event_recur == 'W') { echo esc_html__('Weekly','calendar'); }
					else if ($event->event_recur == 'M') { echo esc_html__('Monthly (date)','calendar'); }
			                else if ($event->event_recur == 'U') { echo esc_html__('Monthly (day)','calendar'); }
					else if ($event->event_recur == 'Y') { echo esc_html__('Yearly','calendar'); }
				?>
				</td>
				<td>
				<?php
				        // Interpret the DB values into something human readable
					if ($event->event_recur == 'S') { echo esc_html__('N/A','calendar'); }
					else if ($event->event_repeats == 0) { echo esc_html__('Forever','calendar'); }
					else if ($event->event_repeats > 0) { echo esc_html($event->event_repeats).' '.esc_html__('Times','calendar'); }					
				?>
				</td>
				<td><?php $e = get_userdata($event->event_author); echo esc_html($e->display_name); ?></td>
                                <?php
                                $this_cat = calendar_db_get_category_row_by_id($event->event_category);
                                ?>
				<td style="background-color:<?php echo esc_html($this_cat->category_colour);?>;"><?php echo esc_html($this_cat->category_name); ?></td>
				<?php unset($this_cat); ?>
				<td><a href="<?php echo esc_url(admin_url('admin.php?page=calendar&amp;action=edit&amp;event_id='.$event->event_id)) ?>" class='edit'><?php echo esc_html__('Edit','calendar'); ?></a></td>
				<td><a href="
<?php echo esc_url(wp_nonce_url(admin_url('admin.php?page=calendar&amp;action=delete&amp;event_id='.$event->event_id),'calendar-delete_'.$event->event_id)); ?>" class="delete" onclick="return confirm('<?php esc_attr_e('Are you sure you want to delete this event?','calendar'); ?>')"><?php echo esc_html__('Delete','calendar'); ?></a></td>
			</tr>
			<?php
		}
		?>
		</table>
		<?php
	}
	else
	{
		?>
		<p><?php esc_html_e("There are no events in the database!",'calendar')	?></p>
		<?php	
	}
}


// The event edit form for the manage events admin page
function calendar_events_edit_form($mode='add', $event_id=false)
{
	global $users_entries;
	$data = false;
	
	if ( $event_id !== false )
	{
		if ( intval($event_id) != $event_id )
		{
			echo "<div class=\"error\"><p>".esc_html__('Bad Monkey! No banana!','calendar')."</p></div>";
			return;
		}
		else
		{
			$data = calendar_db_get_events_by_id($event_id);
			if ( empty($data) )
			{
				echo "<div class=\"error\"><p>".esc_html__("An event with that ID couldn't be found",'calendar')."</p></div>";
				return;
			}
			$data = $data[0];
		}
		// Recover users entries if they exist; in other words if editing an event went wrong
		if (!empty($users_entries))
		  {
		    $data = $users_entries;
		  }
	}
	// Deal with possibility that form was submitted but not saved due to error - recover user's entries here
	else
	  {
	    $data = $users_entries;
	  }
	
	?>
        <div id="pop_up_cal" style="position:absolute;margin-left:150px;visibility:hidden;background-color:white;layer-background-color:white;z-index:1;"></div>
	<form name="quoteform" id="quoteform" class="wrap" method="post" action="<?php echo esc_url(admin_url('admin.php?page=calendar')); ?>">
		<input type="hidden" name="action" value="<?php echo esc_attr($mode); ?>">
		<input type="hidden" name="event_id" value="<?php echo esc_attr($event_id); ?>">
		<?php 
			if ($event_id != "") {
				$nonce_string = 'calendar-'.$mode.'_'.$event_id;
			} else {
				$nonce_string = 'calendar-'.$mode;
			}
			wp_nonce_field($nonce_string);
		?>
	
		<div id="linkadvanceddiv" class="postbox">
			<div style="float: left; width: 98%; clear: both;" class="inside">
                                <table cellpadding="5" cellspacing="5">
                                <tr>				
				<td><legend><?php esc_html_e('Event Title','calendar'); ?></legend></td>
				<td><input type="text" name="event_title" class="input" size="40" maxlength="<?php echo esc_attr(CALENDAR_TITLE_LENGTH) ?>"
					value="<?php if ( !empty($data) ) echo esc_html($data->event_title); ?>" /></td>
                                </tr>
                                <tr>
				<td style="vertical-align:top;"><legend><?php esc_html_e('Event Description','calendar'); ?></legend></td>
				<td><textarea name="event_desc" class="input" rows="5" cols="50"><?php if ( !empty($data) ) echo wp_kses_post($data->event_desc); ?></textarea></td>
                                </tr>
                                <tr>
				<td><legend><?php esc_html_e('Event Category','calendar'); ?></legend></td>
				<td>	 <select name="event_category">
					     <?php
					         // Grab all the categories and list them
	                                         $cats = calendar_db_get_all_categories();
                                                 foreach($cats as $cat)
						   {
						     echo '<option value="'.esc_attr($cat->category_id).'"';
                                                     if (!empty($data))
						       {
							 if ($data->event_category == $cat->category_id)
							   {
							     echo 'selected="selected"';
							   }
						       }
                                                     echo '>'.esc_html($cat->category_name).'</option>
';
						   }
                                             ?>
                                         </select>
                                </td>
                                </tr>
                                <tr>
				<td><legend><?php esc_html_e('Event Link (Optional)','calendar'); ?></legend></td>
                                <td><input type="text" name="event_link" class="input" size="40" value="<?php if ( !empty($data) ) echo esc_url($data->event_link); ?>" /></td>
                                </tr>
                                <tr>
				<td><legend><?php esc_html_e('Start Date','calendar'); ?></legend></td>
                                <td>    
					<input type="text" name="event_begin" id="event_begin" class="input" size="12"
					value="<?php 
					if ( !empty($data) ) 
					{
						echo esc_attr($data->event_begin);
					}
					else
					{
						echo esc_attr(gmdate("Y-m-d",calendar_ctwo()));
					} 
					?>" />
					<script type="text/javascript">
						var cal_1 = new Calendar({
							element: 'event_begin',
							startDay: <?php echo esc_attr(get_option('start_of_week')); ?>,
							onSelect: function unifydates(element) {
							  document.forms['quoteform'].event_end.value = document.forms['quoteform'].event_begin.value;
							}
						});
					</script>
				</td>
                                </tr>
                                <tr>
				<td><legend><?php esc_html_e('End Date','calendar'); ?></legend></td>
                                <td>    
					<input type="text" name="event_end" id="event_end" class="input" size="12" 
					value="<?php 
					if ( !empty($data) ) 
					{
						echo esc_attr($data->event_end);
					}
					else
					{
						echo esc_attr(gmdate("Y-m-d",calendar_ctwo()));
					}
					?>" />
					<script type="text/javascript">
						var cal_2 = new Calendar({
							element: 'event_end',
							startDay: <?php echo esc_attr(get_option('start_of_week')); ?>,
							minDate: new Date(parseInt(document.forms['quoteform'].event_begin.value.split('-')[0]),parseInt(document.forms['quoteform'].event_begin.value.split('-')[1]-1),parseInt(document.forms['quoteform'].event_begin.value.split('-')[2]))
						});
					</script>
				</td>
                                </tr>
                                <tr>
				<td><legend><?php esc_html_e('Time (hh:mm)','calendar'); ?></legend></td>
				<td>	<input type="text" name="event_time" class="input" size=12
					value="<?php 
					if ( !empty($data) ) 
					{
						if ($data->event_time == "00:00:00")
						{
							echo '';
						}
						else
						{
							echo esc_attr(gmdate("H:i",strtotime($data->event_time)));
						}
					}
					else
					{
						echo esc_attr(gmdate("H:i",calendar_ctwo()));
					}
					?>" /> <?php esc_html_e('Optional, set blank if not required.','calendar'); ?> <?php esc_html_e('Current time difference from GMT is ','calendar'); echo esc_html(get_option('gmt_offset')); esc_html_e(' hour(s)','calendar'); ?>
				</td>
                                </tr>
                                <tr>
				<td><legend><?php esc_html_e('Recurring Events','calendar'); ?></legend></td>
				<td>	<?php
					if (isset($data)) {
					  if ($data->event_repeats != NULL)
					    {
						$repeats = $data->event_repeats;
					    }
					  else
					    {
					        $repeats = 0;
					    }
					}
					else
					{
						$repeats = 0;
					}

					$selected_s = '';
					$selected_w = '';
					$selected_m = '';
					$selected_y = '';
					$selected_u = '';
					if (isset($data)) {
					if ($data->event_recur == "S")
					{
						$selected_s = 'selected="selected"';
					}
					else if ($data->event_recur == "W")
					{
						$selected_w = 'selected="selected"';
					}
					else if ($data->event_recur == "M")
					{
						$selected_m = 'selected="selected"';
					}
					else if ($data->event_recur == "Y")
					{
						$selected_y = 'selected="selected"';
					}
					else if ($data->event_recur == "U")
					  {
					    $selected_u = 'selected="selected"';
					  }
                                        }
					?>
					  <?php esc_html_e('Repeats for','calendar'); ?> 
					<input type="text" name="event_repeats" class="input" size="1" value="<?php echo esc_attr($repeats); ?>" /> 
					<select name="event_recur" class="input">
						<option class="input" <?php echo esc_attr($selected_s); ?> value="S"><?php esc_html_e('None') ?></option>
						<option class="input" <?php echo esc_attr($selected_w); ?> value="W"><?php esc_html_e('Weeks') ?></option>
						<option class="input" <?php echo esc_attr($selected_m); ?> value="M"><?php esc_html_e('Months (date)') ?></option>
						<option class="input" <?php echo esc_attr($selected_u); ?> value="U"><?php esc_html_e('Months (day)') ?></option>
						<option class="input" <?php echo esc_attr($selected_y); ?> value="Y"><?php esc_html_e('Years') ?></option>
					</select><br />
					<?php esc_html_e('Entering 0 means forever. Where the recurrance interval is left at none, the event will not reoccur.','calendar'); ?>
				</td>
                                </tr>
                                </table>
			</div>
			<div style="clear:both; height:1px;">&nbsp;</div>
		</div>
                <input type="submit" name="save" class="button bold" value="<?php esc_attr_e('Save','calendar'); ?> &raquo;" />
	</form>
	<?php
}

// The actual function called to render the manage events page and 
// to deal with posts
function calendar_edit()
{
    global $current_user, $users_entries;

// First some quick cleaning up
$edit = $create = $save = $delete = false;

// Deal with adding an event to the database
if ( isset($_REQUEST['action']) && $_REQUEST['action'] == 'add' )
{
	if (wp_verify_nonce($_POST['_wpnonce'],'calendar-add') == false) {
		?>
		<div class="error"><p><strong><?php esc_html_e('Error','calendar'); ?>:</strong> <?php esc_html_e("Security check failure, try adding the event again",'calendar'); ?></p></div>
		<?php
	} else {
	// Set the variables from source input after nonce verification
        $title = !empty($_REQUEST['event_title']) ? stripslashes($_REQUEST['event_title']) : '';
        $desc = !empty($_REQUEST['event_desc']) ? stripslashes($_REQUEST['event_desc']) : '';
	$begin = !empty($_REQUEST['event_begin']) ? $_REQUEST['event_begin'] : '';
	$end = !empty($_REQUEST['event_end']) ? $_REQUEST['event_end'] : '';
	$time = !empty($_REQUEST['event_time']) ? $_REQUEST['event_time'] : '';
	$recur = !empty($_REQUEST['event_recur']) ? $_REQUEST['event_recur'] : '';
	$repeats = !empty($_REQUEST['event_repeats']) ? $_REQUEST['event_repeats'] : '';
	$category = !empty($_REQUEST['event_category']) ? $_REQUEST['event_category'] : '';
	$linky = !empty($_REQUEST['event_link']) ? $_REQUEST['event_link'] : '';
        
	// Perform some validation on the submitted dates - this checks for valid years and months
	$date_format_one = '/^([0-9]{4})-([0][1-9])-([0-3][0-9])$/';
        $date_format_two = '/^([0-9]{4})-([1][0-2])-([0-3][0-9])$/';
	if ((preg_match($date_format_one,$begin) || preg_match($date_format_two,$begin)) && (preg_match($date_format_one,$end) || preg_match($date_format_two,$end)))
	  {
            // We know we have a valid year and month and valid integers for days so now we do a final check on the date
            $begin_split = explode('-',$begin);
	    $begin_y = $begin_split[0]; 
	    $begin_m = $begin_split[1];
	    $begin_d = $begin_split[2];
            $end_split = explode('-',$end);
	    $end_y = $end_split[0];
	    $end_m = $end_split[1];
	    $end_d = $end_split[2];
            if (checkdate($begin_m,$begin_d,$begin_y) && checkdate($end_m,$end_d,$end_y))
	     {
	       // Ok, now we know we have valid dates, we want to make sure that they are either equal or that the end date is later than the start date
	       if (strtotime($end) >= strtotime($begin))
		 {
		   $start_date_ok = 1;
		   $end_date_ok = 1;
		 }
	       else
		 {
		   ?>
		   <div class="error"><p><strong><?php esc_html_e('Error','calendar'); ?>:</strong> <?php esc_html_e('Your event end date must be either after or the same as your event begin date','calendar'); ?></p></div>
		   <?php
		 }
	     } 
	    else
	      {
		?>
                <div class="error"><p><strong><?php esc_html_e('Error','calendar'); ?>:</strong> <?php esc_html_e('Your date formatting is correct but one or more of your dates is invalid. Check for number of days in month and leap year related errors.','calendar'); ?></p></div>
                <?php
	      }
	  }
	else
	  {
	    ?>
            <div class="error"><p><strong><?php esc_html_e('Error','calendar'); ?>:</strong> <?php esc_html_e('Both start and end dates must be entered and be in the format YYYY-MM-DD','calendar'); ?></p></div>
            <?php
	  }
        // We check for a valid time, or an empty one
        $time_format_one = '/^([0-1][0-9]):([0-5][0-9])$/';
	$time_format_two = '/^([2][0-3]):([0-5][0-9])$/';
        if (preg_match($time_format_one,$time) || preg_match($time_format_two,$time) || $time == '')
          {
            $time_ok = 1;
	    if ($time == '')
	      {
		$time_to_use = '00:00:00';
	      }
	    else if ($time == '00:00')
	      {
		$time_to_use = '00:00:01';
	      }
	    else
	      {
		$time_to_use = $time;
	      }
          }
        else
          {
            ?>
            <div class="error"><p><strong><?php esc_html_e('Error','calendar'); ?>:</strong> <?php esc_html_e('The time field must either be blank or be entered in the format hh:mm','calendar'); ?></p></div>
            <?php
	  }
	// We check to make sure the URL is alright                                                        
	if (preg_match('/^(http)(s?)(:)\/\//',$linky) || $linky == '')
	  {
	    $url_ok = 1;
	  }
	else
	  {
              ?>
              <div class="error"><p><strong><?php esc_html_e('Error','calendar'); ?>:</strong> <?php esc_html_e('The URL entered must either be prefixed with http(s):// or be completely blank','calendar'); ?></p></div>
              <?php
	  }
	// The title must be at least one character in length and no more than CALENDAR_TITLE_LENGTH
	if (mb_strlen($title, "UTF-8") > 0 && mb_strlen($title, "UTF-8") <= CALENDAR_TITLE_LENGTH)
	  {
	    $title_ok =1;
	  }
	else
	  {
              ?>
              <div class="error"><p><strong><?php esc_html_e('Error','calendar'); ?>:</strong> <?php echo esc_html__('The event title must be between 1 and ','calendar').esc_html(CALENDAR_TITLE_LENGTH).esc_html__(' characters in length','calendar'); ?></p></div>
              <?php
	  }
	// We run some checks on recurrance
	$repeats = (int)$repeats;
	if (($repeats == 0 && $recur == 'S') || (($repeats >= 0) && ($recur == 'W' || $recur == 'M' || $recur == 'Y' || $recur == 'U')))
	  {
	    $recurring_ok = 1;
	  }
	else
	  {
              ?>
              <div class="error"><p><strong><?php esc_html_e('Error','calendar'); ?>:</strong> <?php esc_html_e('The repetition value must be 0 unless a type of recurrance is selected in which case the repetition value must be 0 or higher','calendar'); ?></p></div>
              <?php
	  }
	if (isset($start_date_ok) && isset($end_date_ok) && isset($time_ok) && isset($url_ok) && isset($title_ok) && isset($recurring_ok))
	  {
	    calendar_db_insert_event($title,$desc,$begin,$end,$time_to_use,$recur,$repeats,$current_user->ID,$category,$linky);	
	    $result = calendar_db_get_event_id_by_insert_data($title,$desc,$begin,$end,$time_to_use,$recur,$repeats,$current_user->ID,$category,$linky);
	
	    if ( empty($result) || empty($result[0]->event_id) )
	      {
                ?>
		<div class="error"><p><strong><?php esc_html_e('Error','calendar'); ?>:</strong> <?php esc_html_e('An event with the details you submitted could not be found in the database. This may indicate a problem with your database or the way in which it is configured.','calendar'); ?></p></div>
		<?php
	      }
	    else
	      {
		      do_action('add_calendar_entry', 'add');
		?>
		<div class="updated"><p><?php esc_html_e('Event added. It will now show in your calendar.','calendar'); ?></p></div>
		<?php
	      }
	  }
	else
	  {
	    // The form is going to be rejected due to field validation issues, so we preserve the users entries here
            $users_entries = new stdClass();
	    $users_entries->event_title = $title;
	    $users_entries->event_desc = $desc;
	    $users_entries->event_begin = $begin;
	    $users_entries->event_end = $end;
	    $users_entries->event_time = $time;
	    $users_entries->event_recur = $recur;
	    $users_entries->event_repeats = $repeats;
	    $users_entries->event_category = $category;
	    $users_entries->event_link = $linky;
	  }
	}
}
// Permit saving of events that have been edited
else if ( isset($_REQUEST['action']) && $_REQUEST['action'] == 'edit_save' )
{
	$title = !empty($_REQUEST['event_title']) ? stripslashes($_REQUEST['event_title']) : '';
	$desc = !empty($_REQUEST['event_desc']) ? stripslashes($_REQUEST['event_desc']) : '';
	$begin = !empty($_REQUEST['event_begin']) ? $_REQUEST['event_begin'] : '';
	$end = !empty($_REQUEST['event_end']) ? $_REQUEST['event_end'] : '';
	$time = !empty($_REQUEST['event_time']) ? $_REQUEST['event_time'] : '';
	$recur = !empty($_REQUEST['event_recur']) ? $_REQUEST['event_recur'] : '';
	$repeats = !empty($_REQUEST['event_repeats']) ? $_REQUEST['event_repeats'] : '';
	$category = !empty($_REQUEST['event_category']) ? $_REQUEST['event_category'] : '';
	$linky = !empty($_REQUEST['event_link']) ? $_REQUEST['event_link'] : '';
	
	if ( !isset($_REQUEST['event_id']) )
	{
		?>
		<div class="error"><p><strong><?php esc_html_e('Failure','calendar'); ?>:</strong> <?php esc_html_e("You can't update an event if you haven't submitted an event id",'calendar'); ?></p></div>
		<?php		
	}
	elseif (wp_verify_nonce($_POST['_wpnonce'],'calendar-edit_save_'.$_REQUEST['event_id']) == false) {
		?>
		<div class="error"><p><strong><?php esc_html_e('Error','calendar'); ?>:</strong> <?php esc_html_e("Security check failure, try editing the event again",'calendar'); ?></p></div>
		<?php
	}
	else
	{
	  // Perform some validation on the submitted dates - this checks for valid years and months
          $date_format_one = '/^([0-9]{4})-([0][1-9])-([0-3][0-9])$/';
	  $date_format_two = '/^([0-9]{4})-([1][0-2])-([0-3][0-9])$/';
	  if ((preg_match($date_format_one,$begin) || preg_match($date_format_two,$begin)) && (preg_match($date_format_one,$end) || preg_match($date_format_two,$end)))
	    {
	      // We know we have a valid year and month and valid integers for days so now we do a final check on the date
              $begin_split = explode('-',$begin);
	      $begin_y = $begin_split[0];
	      $begin_m = $begin_split[1];
	      $begin_d = $begin_split[2];
	      $end_split = explode('-',$end);
	      $end_y = $end_split[0];
	      $end_m = $end_split[1];
	      $end_d = $end_split[2];
	      if (checkdate($begin_m,$begin_d,$begin_y) && checkdate($end_m,$end_d,$end_y))
		{
		  // Ok, now we know we have valid dates, we want to make sure that they are either equal or that the end date is later than the start date
                  if (strtotime($end) >= strtotime($begin))
		    {
		      $start_date_ok = 1;
		      $end_date_ok = 1;
		    }
		  else
		    {
                      ?>
                      <div class="error"><p><strong><?php esc_html_e('Error','calendar'); ?>:</strong> <?php esc_html_e('Your event end date must be either after or the same as your event begin date','calendar'); ?></p></div>
                      <?php
                    }
		}
	      else
		{
                ?>
                <div class="error"><p><strong><?php esc_html_e('Error','calendar'); ?>:</strong> <?php esc_html_e('Your date formatting is correct but one or more of your dates is invalid. Check for number of days in month and leap year related errors.','calendar'); ?></p></div>
                <?php
                }
	    }
	  else
	    {
            ?>
            <div class="error"><p><strong><?php esc_html_e('Error','calendar'); ?>:</strong> <?php esc_html_e('Both start and end dates must be entered and be in the format YYYY-MM-DD','calendar'); ?></p></div>
            <?php
	    }
	  // We check for a valid time, or an empty one
	  $time_format_one = '/^([0-1][0-9]):([0-5][0-9])$/';
	  $time_format_two = '/^([2][0-3]):([0-5][0-9])$/';
	  if (preg_match($time_format_one,$time) || preg_match($time_format_two,$time) || $time == '')
	    {
	      $time_ok = 1;
	      if ($time == '')
		{
		  $time_to_use = '00:00:00';
		}
	      else if ($time == '00:00')
		{
		  $time_to_use = '00:00:01';
		}
	      else
		{
		  $time_to_use = $time;
		}
	    }
	  else
	    {
            ?>
            <div class="error"><p><strong><?php esc_html_e('Error','calendar'); ?>:</strong> <?php esc_html_e('The time field must either be blank or be entered in the format hh:mm','calendar'); ?></p></div>
            <?php
	    }
          // We check to make sure the URL is alright
	  if (preg_match('/^(http)(s?)(:)\/\//',$linky) || $linky == '')
	    {
	      $url_ok = 1;
	    }
	  else
	    {
	      ?>
	      <div class="error"><p><strong><?php esc_html_e('Error','calendar'); ?>:</strong> <?php esc_html_e('The URL entered must either be prefixed with http:// or be completely blank','calendar'); ?></p></div>
	      <?php
	    }
	  // The title must be at least one character in length and no more than CALENDAR_TITLE_LENGTH
      if (mb_strlen($title, "UTF-8") > 0 && mb_strlen($title, "UTF-8") <= CALENDAR_TITLE_LENGTH)
            {
	      $title_ok =1;
	    }
          else
            {
	      ?>
              <div class="error"><p><strong><?php esc_html_e('Error','calendar'); ?>:</strong> <?php echo esc_html__('The event title must be between 1 and ','calendar').esc_html(CALENDAR_TITLE_LENGTH).esc_html__(' characters in length','calendar'); ?></p></div>
              <?php
	    }
	  // We run some checks on recurrance
	  $repeats = (int)$repeats;
          if (($repeats == 0 && $recur == 'S') || (($repeats >= 0) && ($recur == 'W' || $recur == 'M' || $recur == 'Y' || $recur == 'U')))
            {
              $recurring_ok = 1;
            }
          else
            {
              ?>
              <div class="error"><p><strong><?php esc_html_e('Error','calendar'); ?>:</strong> <?php esc_html_e('The repetition value must be 0 unless a type of recurrance is selected in which case the repetition value must be 0 or higher','calendar'); ?></p></div>
              <?php
	    }
	  if (isset($start_date_ok) && isset($end_date_ok) && isset($time_ok) && isset($url_ok) && isset($title_ok) && isset($recurring_ok))
	    {
	    
	        calendar_db_update_event($title,$desc,$begin,$end,$time_to_use,$recur,$repeats,$current_user->ID,$category,$linky,$_REQUEST['event_id']);
		$result = calendar_db_get_event_id_by_insert_data($title,$desc,$begin,$end,$time_to_use,$recur,$repeats,$current_user->ID,$category,$linky);
		
		if ( empty($result) || empty($result[0]->event_id) )
		{
			?>
			<div class="error"><p><strong><?php esc_html_e('Failure','calendar'); ?>:</strong> <?php esc_html_e('The database failed to return data to indicate the event has been updated sucessfully. This may indicate a problem with your database or the way in which it is configured.','calendar'); ?></p></div>
			<?php
		}
		else
		{
			do_action('add_calendar_entry', 'edit');
			?>
			<div class="updated"><p><?php esc_html_e('Event updated successfully','calendar'); ?></p></div>
			<?php
		}
	    }
          else
	    {
	      // The form is going to be rejected due to field validation issues, so we preserve the users entries here
              $users_entires = new stdClass();
              $users_entries->event_title = $title;
	      $users_entries->event_desc = $desc;
	      $users_entries->event_begin = $begin;
	      $users_entries->event_end = $end;
	      $users_entries->event_time = $time;
	      $users_entries->event_recur = $recur;
	      $users_entries->event_repeats = $repeats;
	      $users_entries->event_category = $category;
	      $users_entries->event_link = $linky;
	      $error_with_saving = 1;
	    }		
	}
}
// Deal with deleting an event from the database
else if ( isset($_REQUEST['action']) && $_REQUEST['action'] == 'delete' )
{
	if ( !isset($_REQUEST['event_id']) )
	{
		?>
		<div class="error"><p><strong><?php esc_html_e('Error','calendar'); ?>:</strong> <?php esc_html_e("You can't delete an event if you haven't submitted an event id",'calendar'); ?></p></div>
		<?php			
	}
	elseif (wp_verify_nonce($_GET['_wpnonce'],'calendar-delete_'.$_REQUEST['event_id']) == false) {
		?>
		<div class="error"><p><strong><?php esc_html_e('Error','calendar'); ?>:</strong> <?php esc_html_e("Security check failure, try deleting the event again",'calendar'); ?></p></div>
		<?php
	}
	else
	{
	        calendar_db_delete_event_by_id($_REQUEST['event_id']);
	        $result = calendar_db_get_event_id_by_id($_REQUEST['event_id']);
		
		if ( empty($result) || empty($result[0]->event_id) )
		{
			do_action('add_calendar_entry', 'delete');
			?>
			<div class="updated"><p><?php esc_html_e('Event deleted successfully','calendar'); ?></p></div>
			<?php
		}
		else
		{
			?>
			<div class="error"><p><strong><?php esc_html_e('Error','calendar'); ?>:</strong> <?php esc_html_e('Despite issuing a request to delete, the event still remains in the database. Please investigate.','calendar'); ?></p></div>
			<?php

		}		
	}
}

// Now follows a little bit of code that pulls in the main 
// components of this page; the edit form and the list of events
?>

<div class="wrap">
	<?php
	if ( (isset($_REQUEST['action']) && $_REQUEST['action'] == 'edit') || (isset($_REQUEST['action']) && $_REQUEST['action'] == 'edit_save' && isset($error_with_saving)))
	{
		?>
		<h2><?php esc_html_e('Edit Event','calendar'); ?></h2>
		<?php
		if ( !isset($_REQUEST['event_id']) )
		{
			echo "<div class=\"error\"><p>".esc_html__("You must provide an event id in order to edit it",'calendar')."</p></div>";
		}
		else
		{
			calendar_events_edit_form('edit_save', $_REQUEST['event_id']);
		}	
	}
	else
	{
		?>
		<h2><?php esc_html_e('Add Event','calendar'); ?></h2>
		<?php calendar_events_edit_form(); ?>
	
		<h2><?php esc_html_e('Manage Events','calendar'); ?></h2>
		<?php
			calendar_events_display_list();
	}
	?>
</div>

<?php
 
}

// Display the admin configuration page
function calendar_config_edit()
{
  global $initial_style;

  if (isset($_POST['permissions']) && isset($_POST['style']) && wp_verify_nonce($_POST['_wpnonce'],'calendar-config') == false) {
		?>
		<div class="error"><p><strong><?php esc_html_e('Error','calendar'); ?>:</strong> <?php esc_html_e("Security check failure, try editing the config again",'calendar'); ?></p></div>
		<?php
  }
  elseif (isset($_POST['permissions']) && isset($_POST['style']))
    {
      if ($_POST['permissions'] == 'subscriber') { $new_perms = 'read'; }
      else if ($_POST['permissions'] == 'contributor') { $new_perms = 'edit_posts'; }
      else if ($_POST['permissions'] == 'author') { $new_perms = 'publish_posts'; }
      else if ($_POST['permissions'] == 'editor') { $new_perms = 'moderate_comments'; }
      else if ($_POST['permissions'] == 'admin') { $new_perms = 'manage_options'; }
      else { $new_perms = 'manage_options'; }

      // We want to sanitize this but the inbuilt function clatters two valid CSS charaters, re-instate them!
      $calendar_style = str_replace("\'","'",str_replace("&gt;",">",wp_filter_nohtml_kses($_POST['style'])));
      $display_upcoming_days = $_POST['display_upcoming_days'];

      if ($_POST['display_author'] == 'on')
	      {
	        $disp_author = 'true';
	      }
      else
	      {
	        $disp_author = 'false';
	      }

      if ($_POST['display_jump'] == 'on')
        {
          $disp_jump = 'true';
        }
      else
        {
          $disp_jump = 'false';
        }

      if ($_POST['display_todays'] == 'on')
        {
          $disp_todays = 'true';
        }
      else
        {
          $disp_todays = 'false';
        }

      if ($_POST['display_upcoming'] == 'on')
        {
          $disp_upcoming = 'true';
        }
      else
        {
          $disp_upcoming = 'false';
        }

      if ($_POST['enable_categories'] == 'on')
        {
          $enable_categories = 'true';
        }
      else
        {
	        $enable_categories = 'false';
        }

      if ($_POST['enable_feed'] == 'on')
        {
            $enable_feed = 'true';
        }
      else
        {
            $enable_feed = 'false';
        }

      if ($_POST['enhance_contrast'] == 'on') {
          $enhance_contrast = 'true';
      } else {
          $enhance_contrast = 'false';
      }

      if ($_POST['show_attribution_link'] == 'on') {
          $show_attribution_link = 'true';
      } else {
          $show_attribution_link = 'false';
      }
      calendar_update_config_value('can_manage_events',$new_perms);
      calendar_update_config_value('calendar_style',$calendar_style);
      calendar_update_config_value('display_author',$disp_author);
      calendar_update_config_value('display_jump',$disp_jump);
      calendar_update_config_value('display_todays',$disp_todays);
      calendar_update_config_value('display_upcoming',$disp_upcoming);
      calendar_update_config_value('display_upcoming_days',$display_upcoming_days);      
      calendar_update_config_value('enable_categories',$enable_categories);
      calendar_update_config_value('enable_feed',$enable_feed);
      
      if (empty(calendar_get_config_value('enhance_contrast'))) {
          calendar_insert_config_value('enhance_contrast','false');
      }
      calendar_update_config_value('enhance_contrast',$enhance_contrast);
      
      if (empty(calendar_get_config_value('show_attribution_link'))) {
          calendar_insert_config_value('show_attribution_link','false');
      }
      calendar_update_config_value('show_attribution_link',$show_attribution_link);

      // Check to see if we are replacing the original style
      if (isset($_POST['reset_styles'])) {
          if ($_POST['reset_styles'] == 'on') {
              calendar_update_config_value('calendar_style',$initial_style);
          }
      }

      echo "<div class=\"updated\"><p><strong>".esc_html__('Settings saved','calendar').".</strong></p></div>";
    }

  // Pull the values out of the database that we need for the form
  $allowed_group = calendar_get_config_value('can_manage_events');
  $calendar_style = calendar_get_config_value('calendar_style');
  $yes_disp_author = '';
  $no_disp_author = '';
  if (calendar_get_config_value('display_author') == 'true') {
      $yes_disp_author = 'selected="selected"';
  } else {
      $no_disp_author = 'selected="selected"';
  }
  $yes_disp_jump = '';
  $no_disp_jump = '';
  if (calendar_get_config_value('display_jump') == 'true') {
      $yes_disp_jump = 'selected="selected"';
  } else {
      $no_disp_jump = 'selected="selected"';
  }
  $yes_disp_todays = '';
  $no_disp_todays = '';
  if (calendar_get_config_value('display_todays') == 'true') {
      $yes_disp_todays = 'selected="selected"';
  } else {
      $no_disp_todays = 'selected="selected"';
  }
  $yes_disp_upcoming = '';
  $no_disp_upcoming = '';
  if (calendar_get_config_value('display_upcoming') == 'true') {
      $yes_disp_upcoming = 'selected="selected"';
  } else {
      $no_disp_upcoming = 'selected="selected"';
  }
  $upcoming_days = calendar_get_config_value('display_upcoming_days');
  $yes_enable_categories = '';
  $no_enable_categories = '';
  if (calendar_get_config_value('enable_categories') == 'true') {
      $yes_enable_categories = 'selected="selected"';
  } else {
      $no_enable_categories = 'selected="selected"';
  }
  $yes_enable_feed = '';
  $no_enable_feed = '';
  if (calendar_get_config_value('enable_feed') == 'true') {
      $yes_enable_feed = 'selected="selected"';
  } else {
      $no_enable_feed = 'selected="selected"';
  }
  $yes_enhance_contrast = '';
  $no_enhance_contrast = '';
  if (calendar_get_config_value('enhance_contrast') == 'true') {
      $yes_enhance_contrast = 'selected="selected"';
  } else {
      $no_enhance_contrast = 'selected="selected"';
  }
  $yes_show_attribution_link = '';
  $no_show_attribution_link = '';
  if (calendar_get_config_value('show_attribution_link') == 'true') {
      $yes_show_attribution_link = 'selected="selected"';
  } else if (calendar_get_config_value('show_attribution_link') == 'false') {
      $no_show_attribution_link = 'selected="selected"';
  }

  $subscriber_selected = '';
  $contributor_selected = '';
  $author_selected = '';
  $editor_selected = '';
  $admin_selected = '';
  if ($allowed_group == 'read') { $subscriber_selected='selected="selected"';}
  else if ($allowed_group == 'edit_posts') { $contributor_selected='selected="selected"';}
  else if ($allowed_group == 'publish_posts') { $author_selected='selected="selected"';}
  else if ($allowed_group == 'moderate_comments') { $editor_selected='selected="selected"';}
  else if ($allowed_group == 'manage_options') { $admin_selected='selected="selected"';}

  // Now we render the form
  ?>
  <div class="wrap">
  <h2><?php esc_html_e('Calendar Options','calendar'); ?></h2>
  <form name="quoteform" id="quoteform" class="wrap" method="post" action="<?php echo esc_url(admin_url('admin.php?page=calendar-config')); ?>">
		<?php wp_nonce_field('calendar-config'); ?>
                <div id="linkadvanceddiv" class="postbox">
                        <div style="float: left; width: 98%; clear: both;" class="inside">
                                <table cellpadding="5" cellspacing="5">
				<tr>
                                <td><legend><?php esc_html_e('Choose the lowest user group that may manage events','calendar'); ?></legend></td>
				<td>        <select name="permissions">
				            <option value="subscriber"<?php echo esc_attr($subscriber_selected) ?>><?php esc_html_e('Subscriber','calendar')?></option>
				            <option value="contributor" <?php echo esc_attr($contributor_selected) ?>><?php esc_html_e('Contributor','calendar')?></option>
				            <option value="author" <?php echo esc_attr($author_selected) ?>><?php esc_html_e('Author','calendar')?></option>
				            <option value="editor" <?php echo esc_attr($editor_selected) ?>><?php esc_html_e('Editor','calendar')?></option>
				            <option value="admin" <?php echo esc_attr($admin_selected) ?>><?php esc_html_e('Administrator','calendar')?></option>
				        </select>
                                </td>
                                </tr>
                                <tr>
				<td><legend><?php esc_html_e('Do you want to display the author name on events?','calendar'); ?></legend></td>
                                <td>    <select name="display_author">
                                        <option value="on" <?php echo esc_attr($yes_disp_author) ?>><?php esc_html_e('Yes','calendar') ?></option>
                                        <option value="off" <?php echo esc_attr($no_disp_author) ?>><?php esc_html_e('No','calendar') ?></option>
                                    </select>
                                </td>
                                </tr>
                                <tr>
				<td><legend><?php esc_html_e('Display a jumpbox for changing month and year quickly?','calendar'); ?></legend></td>
                                <td>    <select name="display_jump">
                                         <option value="on" <?php echo esc_attr($yes_disp_jump) ?>><?php esc_html_e('Yes','calendar') ?></option>
                                         <option value="off" <?php echo esc_attr($no_disp_jump) ?>><?php esc_html_e('No','calendar') ?></option>
                                    </select>
                                </td>
                                </tr>
                                <tr>
				<td><legend><?php esc_html_e('Display todays events?','calendar'); ?></legend></td>
                                <td>    <select name="display_todays">
						<option value="on" <?php echo esc_attr($yes_disp_todays) ?>><?php esc_html_e('Yes','calendar') ?></option>
						<option value="off" <?php echo esc_attr($no_disp_todays) ?>><?php esc_html_e('No','calendar') ?></option>
                                    </select>
                                </td>
                                </tr>
                                <tr>
				<td><legend><?php esc_html_e('Display upcoming events?','calendar'); ?></legend></td>
                                <td>    <select name="display_upcoming">
						<option value="on" <?php echo esc_attr($yes_disp_upcoming) ?>><?php esc_html_e('Yes','calendar') ?></option>
						<option value="off" <?php echo esc_attr($no_disp_upcoming) ?>><?php esc_html_e('No','calendar') ?></option>
                                    </select>
				    <?php esc_html_e('for','calendar'); ?> <input type="text" name="display_upcoming_days" value="<?php echo esc_attr($upcoming_days) ?>" size="1" maxlength="2" /> <?php esc_html_e('days into the future','calendar'); ?>
                                </td>
                                </tr>
                                <tr>
				<td><legend><?php esc_html_e('Enable event categories?','calendar'); ?></legend></td>
                                <td>    <select name="enable_categories">
				                <option value="on" <?php echo esc_attr($yes_enable_categories) ?>><?php esc_html_e('Yes','calendar') ?></option>
						<option value="off" <?php echo esc_attr($no_enable_categories) ?>><?php esc_html_e('No','calendar') ?></option>
                                    </select>
                                </td>
                                </tr>
                                <tr>
                <td><legend><?php esc_html_e('Enable iCalendar feed?','calendar'); ?></legend></td>
                                <td>    <select name="enable_feed">
                        <option value="on" <?php echo esc_attr($yes_enable_feed) ?>><?php esc_html_e('Yes','calendar') ?></option>
                        <option value="off" <?php echo esc_attr($no_enable_feed) ?>><?php esc_html_e('No','calendar') ?></option>
                                    </select>
                                </td>
                                </tr>

                                 <tr>
                                     <td><legend><?php esc_html_e('Enhance foreground contrast against category colour?','calendar'); ?></legend></td>
                                        <td>    <select name="enhance_contrast">
                                                <option value="on" <?php echo esc_attr($yes_enhance_contrast) ?>><?php esc_html_e('Yes','calendar') ?></option>
                                                <option value="off" <?php echo esc_attr($no_enhance_contrast) ?>><?php esc_html_e('No','calendar') ?></option>
                                            </select>
                                        </td>
                                 </tr>

                                <tr>
                <td><legend><?php esc_html_e('Enable attribution link?','calendar'); ?></legend></td>
                                <td>    <select name="show_attribution_link">
                                        <?php if ($yes_show_attribution_link == '' && $no_show_attribution_link == '') { ?>
                                            <option value="on" selected="selected"></option>
                                        <?php } ?>
                                <option value="on" <?php echo esc_attr($yes_show_attribution_link) ?>><?php esc_html_e('Yes','calendar') ?></option>
                        <option value="off" <?php echo esc_attr($no_show_attribution_link) ?>><?php esc_html_e('No','calendar') ?></option>
                                     </select>
                                </td>
                                </tr>
                                <tr>
				<td style="vertical-align:top;"><legend><?php esc_html_e('Configure the stylesheet for Calendar','calendar'); ?></legend></td>
				<td><textarea name="style" rows="10" cols="60" tabindex="2"><?php echo esc_textarea($calendar_style); ?></textarea><br />
                                <input type="checkbox" name="reset_styles" /> <?php esc_html_e('Tick this box if you wish to reset the Calendar style to default','calendar'); ?></td>
                                </tr>
                                </table>
			</div>
                        <div style="clear:both; height:1px;">&nbsp;</div>
	        </div>
                <input type="submit" name="save" class="button bold" value="<?php esc_attr_e('Save','calendar'); ?> &raquo;" />
  </form>
  </div>
  <?php


}

// Function to handle the management of categories
function calendar_manage_categories()
{

  // We do some checking to see what we're doing
  if (isset($_POST['mode']) && $_POST['mode'] == 'add')
    {
      if (wp_verify_nonce($_POST['_wpnonce'],'calendar-category_add') == false) {
        ?>
	  <div class="error"><p><strong><?php esc_html_e('Error','calendar'); ?>:</strong> <?php esc_html_e("Security check failure, try adding the category again",'calendar'); ?></p></div>
	<?php
      } else {
      // Proceed with the save
      calendar_db_insert_category(stripslashes($_POST['category_name']), $_POST['category_colour']);
      echo "<div class=\"updated\"><p><strong>".esc_html__('Category added successfully','calendar')."</strong></p></div>";
      }
    }
  else if (isset($_GET['mode']) && isset($_GET['category_id']) && $_GET['mode'] == 'delete')
    {
      if (wp_verify_nonce($_GET['_wpnonce'],'calendar-category_delete_'.$_GET['category_id']) == false) {
        ?>
	  <div class="error"><p><strong><?php esc_html_e('Error','calendar'); ?>:</strong> <?php esc_html_e("Security check failure, try deleting the category again",'calendar'); ?></p></div>
	<?php
      } else {
        calendar_db_delete_category($_GET['category_id']);
        calendar_db_reset_event_categories_to_default_from_id($_GET['category_id']);
        echo "<div class=\"updated\"><p><strong>".esc_html__('Category deleted successfully','calendar')."</strong></p></div>";
      }
    }
  else if (isset($_GET['mode']) && isset($_GET['category_id']) && $_GET['mode'] == 'edit' && !isset($_POST['mode']))
    {
      $cur_cat = calendar_db_get_category_row_by_id($_GET['category_id']);
      ?>
<div class="wrap">
   <h2><?php esc_html_e('Edit Category','calendar'); ?></h2>
    <form name="catform" id="catform" class="wrap" method="post" action="<?php echo esc_url(admin_url('admin.php?page=calendar-categories')); ?>">
                <input type="hidden" name="mode" value="edit" />
                <input type="hidden" name="category_id" value="<?php echo esc_attr($cur_cat->category_id) ?>" />
		<?php wp_nonce_field('calendar-category_edit_'.$cur_cat->category_id); ?>
                <div id="linkadvanceddiv" class="postbox">
                        <div style="float: left; width: 98%; clear: both;" class="inside">
				<table cellpadding="5" cellspacing="5">
                                <tr>
				<td><legend><?php esc_html_e('Category Name','calendar'); ?>:</legend></td>
                                <td><input type="text" name="category_name" class="input" size="30" maxlength="30" value="<?php echo esc_attr($cur_cat->category_name) ?>" /></td>
				</tr>
                                <tr>
				<td><legend><?php esc_html_e('Category Colour (Hex format)','calendar'); ?>:</legend></td>
                                <td><input type="text" name="category_colour" class="input" size="10" maxlength="7" value="<?php echo esc_attr($cur_cat->category_colour) ?>" /></td>
                                </tr>
                                </table>
                        </div>
                        <div style="clear:both; height:1px;">&nbsp;</div>
                </div>
                <input type="submit" name="save" class="button bold" value="<?php esc_attr_e('Save','calendar'); ?> &raquo;" />
    </form>
</div>
      <?php
    }
  else if (isset($_POST['mode']) && isset($_POST['category_id']) && isset($_POST['category_name']) && isset($_POST['category_colour']) && $_POST['mode'] == 'edit')
    {
      if (wp_verify_nonce($_POST['_wpnonce'],'calendar-category_edit_'.$_POST['category_id']) == false) {
        ?>
	  <div class="error"><p><strong><?php esc_html_e('Error','calendar'); ?>:</strong> <?php esc_html_e("Security check failure, try editing the category again",'calendar'); ?></p></div>
	<?php
      } else {
      // Proceed with the save
      calendar_db_update_category(stripslashes($_POST['category_name']), $_POST['category_colour'], $_POST['category_id']);
        echo "<div class=\"updated\"><p><strong>".esc_html__('Category edited successfully','calendar')."</strong></p></div>";
      }
    }

  $get_mode = 0;
  $post_mode = 0;
  if (isset($_GET['mode'])) {
    if ($_GET['mode'] == 'edit') {
      $get_mode = 1;
    }
  }
  if (isset($_POST['mode'])) {
    if ($_POST['mode'] == 'edit') {
      $post_mode = 1;
    }
  }
  if ($get_mode != 1 || $post_mode == 1)
    {
?>

  <div class="wrap">
    <h2><?php esc_html_e('Add Category','calendar'); ?></h2>
    <form name="catform" id="catform" class="wrap" method="post" action="<?php echo esc_url(admin_url('admin.php?page=calendar-categories')); ?>">
                <input type="hidden" name="mode" value="add" />
                <input type="hidden" name="category_id" value="">
		<?php wp_nonce_field('calendar-category_add'); ?>
                <div id="linkadvanceddiv" class="postbox">
                        <div style="float: left; width: 98%; clear: both;" class="inside">
       				<table cellspacing="5" cellpadding="5">
                                <tr>
                                <td><legend><?php esc_html_e('Category Name','calendar'); ?>:</legend></td>
                                <td><input type="text" name="category_name" class="input" size="30" maxlength="30" value="" /></td>
                                </tr>
                                <tr>
                                <td><legend><?php esc_html_e('Category Colour (Hex format)','calendar'); ?>:</legend></td>
                                <td><input type="text" name="category_colour" class="input" size="10" maxlength="7" value="" /></td>
                                </tr>
                                </table>
                        </div>
		        <div style="clear:both; height:1px;">&nbsp;</div>
                </div>
                <input type="submit" name="save" class="button bold" value="<?php esc_attr_e('Save','calendar'); ?> &raquo;" />
    </form>
    <h2><?php esc_html_e('Manage Categories','calendar'); ?></h2>
<?php
    
    // We pull the categories from the database	
    $categories = calendar_db_get_all_categories();

 if ( !empty($categories) )
   {
     ?>
     <table class="widefat page fixed" width="50%" cellpadding="3" cellspacing="3">
       <thead> 
       <tr>
         <th class="manage-column" scope="col"><?php esc_html_e('ID','calendar') ?></th>
	 <th class="manage-column" scope="col"><?php esc_html_e('Category Name','calendar') ?></th>
	 <th class="manage-column" scope="col"><?php esc_html_e('Category Colour','calendar') ?></th>
	 <th class="manage-column" scope="col"><?php esc_html_e('Edit','calendar') ?></th>
	 <th class="manage-column" scope="col"><?php esc_html_e('Delete','calendar') ?></th>
       </tr>
       </thead>
       <?php
       $class = '';
       foreach ( $categories as $category )
         {
	   $class = ($class == 'alternate') ? '' : 'alternate';
           ?>
           <tr class="<?php echo esc_attr($class); ?>">
	     <th scope="row"><?php echo esc_html($category->category_id); ?></th>
	     <td><?php echo esc_html($category->category_name); ?></td>
	     <td style="background-color:<?php echo esc_attr($category->category_colour); ?>;">&nbsp;</td>
	     <td><a href="<?php echo esc_url(admin_url('admin.php?page=calendar-categories&amp;mode=edit&amp;category_id='.$category->category_id))  ?>" class='edit'><?php echo esc_html__('Edit','calendar'); ?></a></td>
	     <?php
	     if ($category->category_id == 1)
	       {
		 echo '<td>'.esc_html__('N/A','calendar').'</td>';
	       }
             else
	       {
               ?>
               <td><a href="<?php echo esc_url(wp_nonce_url(admin_url('admin.php?page=calendar-categories&amp;mode=delete&amp;category_id='.$category->category_id), 'calendar-category_delete_'.$category->category_id)); ?>" class="delete" onclick="return confirm('<?php echo esc_html__('Are you sure you want to delete this category?','calendar'); ?>')"><?php echo esc_html__('Delete','calendar'); ?></a></td>
               <?php
	       }
                ?>
              </tr>
                <?php
          }
      ?>
      </table>
      <?php
   }
 else
   {
     echo '<p>'.esc_html__('There are no categories in the database - something has gone wrong!','calendar').'</p>';
   }

 ?>
  </div>

 <?php
      } 
}

// Function to indicate the number of the day passed, eg. 1st or 2nd Sunday
function calendar_np_of_day($date)
{
  $instance = 0;
  $dom = gmdate('j',strtotime($date));
  if (($dom-7) <= 0) { $instance = 1; }
  else if (($dom-7) > 0 && ($dom-7) <= 7) { $instance = 2; }
  else if (($dom-7) > 7 && ($dom-7) <= 14) { $instance = 3; }
  else if (($dom-7) > 14 && ($dom-7) <= 21) { $instance = 4; }
  else if (($dom-7) > 21 && ($dom-7) < 28) { $instance = 5; }
  return $instance;
}

// Function to provide date of the nth day passed (eg. 2nd Sunday)
function calendar_dt_of_sun($date,$instance,$day)
{
  $plan = array();
  $plan['Mon'] = 1;
  $plan['Tue'] = 2;
  $plan['Wed'] = 3;
  $plan['Thu'] = 4;
  $plan['Fri'] = 5;
  $plan['Sat'] = 6;
  $plan['Sun'] = 7;
  $proper_date = gmdate('Y-m-d',strtotime($date));
  $begin_month = substr($proper_date,0,8).'01'; 
  $offset = $plan[gmdate('D',strtotime($begin_month))]; 
  $result_day = 0;
  $recon = 0;
  if (($day-($offset)) < 0) { $recon = 7; }
  if ($instance == 1) { $result_day = $day-($offset-1)+$recon; }
  else if ($instance == 2) { $result_day = $day-($offset-1)+$recon+7; }
  else if ($instance == 3) { $result_day = $day-($offset-1)+$recon+14; }
  else if ($instance == 4) { $result_day = $day-($offset-1)+$recon+21; }
  else if ($instance == 5) { $result_day = $day-($offset-1)+$recon+28; }
  return substr($proper_date,0,8).$result_day;
}

// Function to return a prefix which will allow the correct 
// placement of arguments into the query string.
function calendar_permalink_prefix()
{
  // Get the permalink structure from WordPress
  if (is_home()) { 
    $p_link = get_bloginfo('url'); 
    if ($p_link[strlen($p_link)-1] != '/') { $p_link = $p_link.'/'; }
  } else { 
    $p_link = get_permalink(); 
  }

  // Based on the structure, append the appropriate ending
  if (!(strstr($p_link,'?'))) { $link_part = $p_link.'?'; } else { $link_part = $p_link.'&'; }

  return $link_part;
}

// Configure the "Next" link in the calendar
function calendar_next_link($cur_year,$cur_month,$minical = false)
{
  $mod_rewrite_months = array(1=>'jan','feb','mar','apr','may','jun','jul','aug','sep','oct','nov','dec');
  $next_year = $cur_year + 1;

  if ($cur_month == 12)
    {
      if ($minical) { $rlink = ''; } else { $rlink = __('Next','calendar'); }
      return '<a href="' . calendar_permalink_prefix() . 'calendar_month=jan&amp;calendar_yr=' . $next_year . '">'.$rlink.' &raquo;</a>';
    }
  else
    {
      $next_month = $cur_month + 1;
      $month = $mod_rewrite_months[$next_month];
      if ($minical) { $rlink = ''; } else { $rlink = __('Next','calendar'); }
      return '<a href="' . calendar_permalink_prefix() . 'calendar_month='.$month.'&amp;calendar_yr=' . $cur_year . '">'.$rlink.' &raquo;</a>';
    }
}

// Configure the "Previous" link in the calendar
function calendar_prev_link($cur_year,$cur_month,$minical = false)
{
  $mod_rewrite_months = array(1=>'jan','feb','mar','apr','may','jun','jul','aug','sep','oct','nov','dec');
  $last_year = $cur_year - 1;

  if ($cur_month == 1)
    {
      if ($minical) { $llink = ''; } else { $llink = __('Prev','calendar'); }
      return '<a href="' . calendar_permalink_prefix() . 'calendar_month=dec&amp;calendar_yr='. $last_year .'">&laquo; '.$llink.'</a>';
    }
  else
    {
      $next_month = $cur_month - 1;
      $month = $mod_rewrite_months[$next_month];
      if ($minical) { $llink = ''; } else { $llink = __('Prev','calendar'); }
      return '<a href="' . calendar_permalink_prefix() . 'calendar_month='.$month.'&amp;calendar_yr=' . $cur_year . '">&laquo; '.$llink.'</a>';
    }
}

// Print upcoming events
function calendar_upcoming_events($cat_list = '')
{
  // Find out if we should be displaying upcoming events  
  if (calendar_get_config_value('display_upcoming') == 'true')
    {
      // Get number of days we should go into the future 
      $future_days = calendar_get_config_value('display_upcoming_days');
      $day_count = 1;
      
      $output = '';
      while ($day_count < $future_days+1)
	{
	  list($y,$m,$d) = explode("-",gmdate("Y-m-d",mktime($day_count*24,0,0,gmdate("m",calendar_ctwo()),gmdate("d",calendar_ctwo()),gmdate("Y",calendar_ctwo()))));
	  $events = grab_events($y,$m,$d,'upcoming',$cat_list);
	  usort($events, "calendar_time_cmp");
	  if (count($events) != 0) {
	    $output .= '<li>'.wp_date(get_option('date_format'),mktime($day_count*24,0,0,gmdate("m",calendar_ctwo()),gmdate("d",calendar_ctwo()),gmdate("Y",calendar_ctwo()))).'<ul>';
	  } 
	  foreach($events as $event)
	    {
	      if ($event->event_time == '00:00:00') {
		$time_string = ' <span class="calendar_time all_day" style="position:relative;display:inline;width:unset;background:none;">'.esc_html__('all day','calendar').'</span>';
	      }
	      else {
		$time_string = ' <span class="calendar_time" style="position:relative;display:inline;width:unset;background:none;">'.esc_html__('at','calendar').' '.gmdate(get_option('time_format'), strtotime($event->event_time)).'</span>';
	      }
              $output .= '<li>'.calendar_draw_event($event).$time_string.'</li>';
	    }
	  if (count($events) != 0) {
	    $output .= '</ul></li>';
	  }
	  $day_count = $day_count+1;
	}

      if ($output != '')
	{
	  $visual = '<ul>';
	  $visual .= $output;
	  $visual .= '</ul>';
	  return $visual;
	}
    }
}

// Print todays events
function calendar_todays_events($cat_list = '')
{
  // Find out if we should be displaying todays events
  if (calendar_get_config_value('display_todays') == 'true')
    {
      $output = '<ul>';
      $events = grab_events(gmdate("Y",calendar_ctwo()),gmdate("m",calendar_ctwo()),gmdate("d",calendar_ctwo()),'todays',$cat_list);
      usort($events, "calendar_time_cmp");
      foreach($events as $event)
	{
	  if ($event->event_time == '00:00:00') {
	    $time_string = ' <span class="calendar_time all_day" style="position:relative;display:inline;width:unset;background:none;">'.esc_html__('all day','calendar').'</span>';
	  }
	  else {
	    $time_string = ' <span class="calendar_time" style="position:relative;display:inline;width:unset;background:none;">'.esc_html__('at','calendar').' '.gmdate(get_option('time_format'), strtotime($event->event_time)).'</span>';
	  }
	  $output .= '<li>'.calendar_draw_event($event).$time_string.'</li>';
	}
      $output .= '</ul>';
      if (count($events) != 0)
	{
	  return $output;
	}
    }
}

// Function to compare time in event objects
function calendar_time_cmp($a, $b)
{
  if ($a->event_time == $b->event_time) {
    return 0;
  }
  return ($a->event_time < $b->event_time) ? -1 : 1;
}

// Used to draw multiple events
function calendar_draw_events($events)
{
  // We need to sort arrays of objects by time
  usort($events, "calendar_time_cmp");
  $output = '';
  // Now process the events
  foreach($events as $event)
    {
      $output .= '<span class="calendar_bullet" style="position:relative;display:inline;width:unset;background:none;">* </span>'.calendar_draw_event($event).'<br />';
      $output = apply_filters('modify_drawn_event_content', $output, $event);
    }
  return $output;
}

// The widget to show the mini calendar
class calendar_minical_widget extends WP_Widget {
    public function __construct() {
        $widget_options = array( 
            'classname' => 'calendar_minical_widget',
            'description' => 'A calendar of your events',
        );
        parent::__construct( 'calendar_minical_widget', 'Calendar', $widget_options );
    }
    
    public function widget( $args, $instance ) {
        extract($args);
        $the_title = $instance['events_calendar_widget_title'];
        $the_cats = $instance['events_calendar_widget_cats'];
        $widget_title = empty($the_title) ? __('Calendar','calendar') : $the_title;
        $the_events = calendar_minical($the_cats);
        if ($the_events != '') {
            echo wp_kses_post($before_widget);
            echo wp_kses_post($before_title . $widget_title . $after_title);
            echo '<br />'.wp_kses_post($the_events);
            echo wp_kses_post($after_widget);
        }
    }
    
    public function form( $instance ) {
        $widget_title = !empty($instance['events_calendar_widget_title']) ? $instance['events_calendar_widget_title'] : '';
        $widget_cats = !empty($instance['events_calendar_widget_cats']) ? $instance['events_calendar_widget_cats'] : '';
        ?>
        <p>
            <label for="<?php echo esc_attr($this->get_field_id('events_calendar_widget_title')); ?>"><?php esc_html_e('Title','calendar'); ?>:<br />
            <input class="widefat" type="text" id="<?php echo esc_attr($this->get_field_id('events_calendar_widget_title')); ?>" name="<?php echo esc_attr($this->get_field_name('events_calendar_widget_title')); ?>" value="<?php echo esc_attr($widget_title); ?>"/></label>
            <label for="<?php echo esc_attr($this->get_field_id('events_calendar_widget_cats')); ?>"><?php esc_html_e('Comma separated category id list','calendar'); ?>:<br />
            <input class="widefat" type="text" id="<?php echo esc_attr($this->get_field_id('events_calendar_widget_cats')); ?>" name="<?php echo esc_attr($this->get_field_name('events_calendar_widget_cats')); ?>" value="<?php echo esc_attr($widget_cats); ?>"/></label>
        </p>
        <?php 
    }
    
    public function update( $new_instance, $old_instance ) {
        $instance = $old_instance;
        $instance['events_calendar_widget_title'] = stripslashes($new_instance['events_calendar_widget_title']);
        $instance['events_calendar_widget_cats'] = stripslashes($new_instance['events_calendar_widget_cats']);
        return $instance;
    }
}

function calendar_register_minical_widget() { 
    register_widget('calendar_minical_widget');
}

// The widget to show todays events in the sidebar
class calendar_today_widget extends WP_Widget {
    public function __construct() {
        $widget_options = array( 
            'classname' => 'calendar_today_widget',
            'description' => 'A list of your events today',
        );
        parent::__construct( 'calendar_today_widget', 'Today\'s Events', $widget_options );
    }
    
    public function widget( $args, $instance ) {
        extract($args);
        $the_title = $instance['calendar_today_widget_title'];
        $the_cats = $instance['calendar_today_widget_cats'];
        $widget_title = empty($the_title) ? __('Today\'s Events','calendar') : $the_title;
        $the_events = calendar_todays_events($the_cats);
        if ($the_events != '') {
            echo wp_kses_post($before_widget);
            echo wp_kses_post($before_title . $widget_title . $after_title);
            echo wp_kses_post($the_events);
            echo wp_kses_post($after_widget);
        }
    }
    
    public function form( $instance ) {
        $widget_title = !empty($instance['calendar_today_widget_title']) ? $instance['calendar_today_widget_title'] : '';
        $widget_cats = !empty($instance['calendar_today_widget_cats']) ? $instance['calendar_today_widget_cats'] : '';
        ?>
        <p>
            <label for="<?php echo esc_attr($this->get_field_id('calendar_today_widget_title')); ?>"><?php esc_html_e('Title','calendar'); ?>:<br />
            <input class="widefat" type="text" id="<?php echo esc_attr($this->get_field_id('calendar_today_widget_title')); ?>" name="<?php echo esc_attr($this->get_field_name('calendar_today_widget_title')); ?>" value="<?php echo esc_attr($widget_title); ?>"/></label>
            <label for="<?php echo esc_attr($this->get_field_id('calendar_today_widget_cats')); ?>"><?php esc_html_e('Comma separated category id list','calendar'); ?>:<br />
            <input class="widefat" type="text" id="<?php echo esc_attr($this->get_field_id('calendar_today_widget_cats')); ?>" name="<?php echo esc_attr($this->get_field_name('calendar_today_widget_cats')); ?>" value="<?php echo esc_attr($widget_cats); ?>"/></label>
        </p>
        <?php 
    }
    
    public function update( $new_instance, $old_instance ) {
        $instance = $old_instance;
        $instance['calendar_today_widget_title'] = stripslashes($new_instance['calendar_today_widget_title']);
        $instance['calendar_today_widget_cats'] = stripslashes($new_instance['calendar_today_widget_cats']);
        return $instance;
    }
}

function calendar_register_today_widget() { 
    register_widget('calendar_today_widget');
}

// The widget to show upcoming events in the sidebar
class calendar_upcoming_widget extends WP_Widget {
    public function __construct() {
        $widget_options = array( 
            'classname' => 'calendar_upcoming_widget',
            'description' => 'A list of your upcoming events',
        );
        parent::__construct( 'calendar_upcoming_widget', 'Upcoming Events', $widget_options );
    }
    
    public function widget( $args, $instance ) {
        extract($args);
        $the_title = $instance['calendar_upcoming_widget_title'];
        $the_cats = $instance['calendar_upcoming_widget_cats'];
        $widget_title = empty($the_title) ? __('Upcoming events','calendar') : $the_title;
        $the_events = calendar_upcoming_events($the_cats);
        if ($the_events != '') {
            echo wp_kses_post($before_widget);
            echo wp_kses_post($before_title . $widget_title . $after_title);
            echo wp_kses_post($the_events);
            echo wp_kses_post($after_widget);
        }
    }
    
    public function form( $instance ) {
        $widget_title = !empty($instance['calendar_upcoming_widget_title']) ? $instance['calendar_upcoming_widget_title'] : '';
        $widget_cats = !empty($instance['calendar_upcoming_widget_cats']) ? $instance['calendar_upcoming_widget_cats'] : '';
        ?>
        <p>
            <label for="<?php echo esc_attr($this->get_field_id('calendar_upcoming_widget_title')); ?>"><?php esc_html_e('Title','calendar'); ?>:<br />
            <input class="widefat" type="text" id="<?php echo esc_attr($this->get_field_id('calendar_upcoming_widget_title')); ?>" name="<?php echo esc_attr($this->get_field_name('calendar_upcoming_widget_title')); ?>" value="<?php echo esc_attr($widget_title); ?>"/></label>
            <label for="<?php echo esc_attr($this->get_field_id('calendar_upcoming_widget_cats')); ?>"><?php esc_html_e('Comma separated category id list','calendar'); ?>:<br />
            <input class="widefat" type="text" id="<?php echo esc_attr($this->get_field_id('calendar_upcoming_widget_cats')); ?>" name="<?php echo esc_attr($this->get_field_name('calendar_upcoming_widget_cats')); ?>" value="<?php echo esc_attr($widget_cats); ?>"/></label>
        </p>
        <?php 
    }
    
    public function update( $new_instance, $old_instance ) {
        $instance = $old_instance;
        $instance['calendar_upcoming_widget_title'] = stripslashes($new_instance['calendar_upcoming_widget_title']);
        $instance['calendar_upcoming_widget_cats'] = stripslashes($new_instance['calendar_upcoming_widget_cats']);
        return $instance;
    }
}

function calendar_register_upcoming_widget() { 
    register_widget('calendar_upcoming_widget');
}

// A function that determines an appropriate foreground colour from the background
function getContrastYIQ($hexcolor){
    if (preg_match('/#([a-fA-F0-9]{3}){1,2}\b/',$hexcolor)) {
        if (strlen($hexcolor)==4) {
            $r = hexdec(str_repeat(substr($hexcolor,1,1),2));
            $g = hexdec(str_repeat(substr($hexcolor,2,3),2));
            $b = hexdec(str_repeat(substr($hexcolor,3,3),2));
        } elseif (strlen($hexcolor)==7) {
            $r = hexdec(substr($hexcolor,1,2));
            $g = hexdec(substr($hexcolor,3,2));
            $b = hexdec(substr($hexcolor,5,2));
        } else {
            return '#000000';
        }
        $yiq = (($r*299)+($g*587)+($b*114))/1000;
        return ($yiq >= 128) ? '#000000' : '#FFFFFF';
    }
  else {
      return '#000000';
  }
}

// Used to draw an event to the screen
function calendar_draw_event($event)
{

  // Before we do anything we want to know if we                                             
  // should display the author and/or show categories. 
  // We check for this later  
  $display_author = calendar_get_config_value('display_author');
  $show_cat = calendar_get_config_value('enable_categories');
  $contrast = calendar_get_config_value('enhance_contrast');
  $style = '';
  if ($show_cat == 'true')
    {
      $cat_details = calendar_db_get_category_row_by_id($event->event_category);
      if ($contrast == 'true') {
          $fgcolor=getContrastYIQ($cat_details->category_colour);
          $style = 'style="background-color:'.$cat_details->category_colour.'; color:'.$fgcolor.';"';
      } else {
          $style = 'style="background-color:'.$cat_details->category_colour.';"';
      }

    }

  $header_details =  '<span class="event-title" '.$style.'>'.$event->event_title.'</span><br />
<span class="event-title-break"></span><br />';
  if ($event->event_time != "00:00:00")
    {
      $header_details .= '<strong>'.esc_html__('Time','calendar').':</strong> ' . gmdate(get_option('time_format'), strtotime($event->event_time)) . '<br />';
    }
  if ($display_author == 'true')
    {
      $e = get_userdata($event->event_author);
      $header_details .= '<strong>'.esc_html__('Posted by', 'calendar').':</strong> '.$e->display_name.'<br />';
    }
  if ($display_author == 'true' || $event->event_time != "00:00:00")
    {
      $header_details .= '<span class="event-content-break"></span><br />';
    }
  if ($event->event_link != '') { $linky = $event->event_link; }
  else { $linky = '#'; }
  
  $linky = apply_filters('modify_calendar_link', $linky, $event);

  $details = '<span class="calnk"><a href="'.$linky.'" '.$style.'>' . $event->event_title . '<span '.$style.'>' . $header_details . '' . $event->event_desc . '</span></a></span>';

  return $details;
}

// Grab all events for the requested date from calendar
function grab_events($y,$m,$d,$typing,$cat_list = '')
{
  global $wpdb;

     $arr_events = array();

     // Get the date format right
     $date = $y . '-' . $m . '-' . $d;

     // Query the events
     $events = calendar_db_fetch_events_for_date($date, $cat_list);

     if (!empty($events))
       {
	 foreach($events as $event)
	   {
	     if ($event->type == 'Normal')
	       {
		 array_push($arr_events, $event);
	       }
	     else if ($event->type == 'Yearly')
	       {
		 // This is going to get complex so lets setup what we would place in for
		 // an event so we can drop it in with ease

		 // Technically we don't care about the years, but we need to find out if the
		 // event spans the turn of a year so we can deal with it appropriately.
		 $year_begin = gmdate('Y',strtotime($event->event_begin));
		 $year_end = gmdate('Y',strtotime($event->event_end));

		 if ($year_begin == $year_end)
		   {
		     if (gmdate('m-d',strtotime($event->event_begin)) <= gmdate('m-d',strtotime($date)) &&
			 gmdate('m-d',strtotime($event->event_end)) >= gmdate('m-d',strtotime($date)))
		       {
			 array_push($arr_events, $event);
		       }
		   }
		 else if ($year_begin < $year_end)
		   {
		     if (gmdate('m-d',strtotime($event->event_begin)) <= gmdate('m-d',strtotime($date)) ||
			 gmdate('m-d',strtotime($event->event_end)) >= gmdate('m-d',strtotime($date)))
		       {
			 array_push($arr_events, $event);
		       }
		   }
	       }
	     else if ($event->type == 'Monthly')
	       {
		 // This is going to get complex so lets setup what we would place in for
		 // an event so we can drop it in with ease

		 // Technically we don't care about the years or months, but we need to find out if the
		 // event spans the turn of a year or month so we can deal with it appropriately.
		 $month_begin = gmdate('m',strtotime($event->event_begin));
		 $month_end = gmdate('m',strtotime($event->event_end));

		 if (($month_begin == $month_end) && (strtotime($event->event_begin) <= strtotime($date)))
		   {
		     if (gmdate('d',strtotime($event->event_begin)) <= gmdate('d',strtotime($date)) &&
			 gmdate('d',strtotime($event->event_end)) >= gmdate('d',strtotime($date)))
		       {
			 array_push($arr_events, $event);
		       }
		   }
		 else if (($month_begin < $month_end) && (strtotime($event->event_begin) <= strtotime($date)))
		   {
		     if ( ($event->event_begin <= gmdate('Y-m-d',strtotime($date))) && (gmdate('d',strtotime($event->event_begin)) <= gmdate('d',strtotime($date)) ||
			   gmdate('d',strtotime($event->event_end)) >= gmdate('d',strtotime($date))) )
		       {
			 array_push($arr_events, $event);
		       }
		   }
	       }
	     else if ($event->type == 'MonthSun')
	       {
		 // This used to be complex but writing the calendar_dt_of_sun() function helped loads!

		 // Technically we don't care about the years or months, but we need to find out if the
		 // event spans the turn of a year or month so we can deal with it appropriately.
		 $month_begin = gmdate('m',strtotime($event->event_begin));
		 $month_end = gmdate('m',strtotime($event->event_end));

		 // Setup some variables and get some values
		 $dow = gmdate('w',strtotime($event->event_begin));
		 if ($dow == 0) { $dow = 7; }
		 $start_ent_this = calendar_dt_of_sun($date,calendar_np_of_day($event->event_begin),$dow);
		 $start_ent_prev = calendar_dt_of_sun(gmdate('Y-m-d',strtotime($date.'-1 month')),calendar_np_of_day($event->event_begin),$dow);
		 $len_ent = strtotime($event->event_end)-strtotime($event->event_begin);

		 // The grunt work
		 if (($month_begin == $month_end) && (strtotime($event->event_begin) <= strtotime($date)))
		   {
		     // The checks
		     if (strtotime($event->event_begin) <= strtotime($date) && strtotime($event->event_end) >= strtotime($date)) // Handle the first occurance
		       {
			 array_push($arr_events, $event);
		       }
		     else if (strtotime($start_ent_this) <= strtotime($date) && strtotime($date) <= strtotime($start_ent_this)+$len_ent) // Now remaining items
		       {
			 array_push($arr_events, $event);
		       }
		   }
		 else if (($month_begin < $month_end) && (strtotime($event->event_begin) <= strtotime($date)))
		   {
		     // The checks
		     if (strtotime($event->event_begin) <= strtotime($date) && strtotime($event->event_end) >= strtotime($date)) // Handle the first occurance
		       {
                         array_push($arr_events, $event);
		       }
		     else if (strtotime($start_ent_prev) <= strtotime($date) && strtotime($date) <= strtotime($start_ent_prev)+$len_ent) // Remaining items from prev month
		       {
                         array_push($arr_events, $event);
                       }
		     else if (strtotime($start_ent_this) <= strtotime($date) && strtotime($date) <= strtotime($start_ent_this)+$len_ent) // Remaining items starting this month
		       {
                         array_push($arr_events, $event);
                       }
		   }
	       }
	     else if ($event->type == 'Weekly')
	       {
		 // This is going to get complex so lets setup what we would place in for
		 // an event so we can drop it in with ease

		 // Now we are going to check to see what day the original event
		 // fell on and see if the current date is both after it and on
		 // the correct day. If it is, display the event!
		 $day_start_event = gmdate('D',strtotime($event->event_begin));
		 $day_end_event = gmdate('D',strtotime($event->event_end));
		 $current_day = gmdate('D',strtotime($date));

		 $plan = array();
		 $plan['Mon'] = 1;
		 $plan['Tue'] = 2;
		 $plan['Wed'] = 3;
		 $plan['Thu'] = 4;
		 $plan['Fri'] = 5;
		 $plan['Sat'] = 6;
		 $plan['Sun'] = 7;

		 if ($plan[$day_start_event] > $plan[$day_end_event])
		   {
		     if (($plan[$day_start_event] <= $plan[$current_day]) || ($plan[$current_day] <= $plan[$day_end_event]))
		       {
			 array_push($arr_events, $event);
		       }
		   }
		 else if (($plan[$day_start_event] < $plan[$day_end_event]) || ($plan[$day_start_event]== $plan[$day_end_event]))
		   {
		     if (($plan[$day_start_event] <= $plan[$current_day]) && ($plan[$current_day] <= $plan[$day_end_event]))
		       {
			 array_push($arr_events, $event);
		       }
		   }
	       }
	   }
       }

     return $arr_events;
}

// Setup comparison functions for building the calendar later
function calendar_month_comparison($month)
{
  $get_year = (get_query_var('calendar_yr') ? get_query_var('calendar_yr') : null);
  $get_month = (get_query_var('calendar_month') ? get_query_var('calendar_month') : null);
  $current_month = strtolower(gmdate("M", calendar_ctwo()));
  if (isset($get_year) && isset($get_month))
    {
      if ($month == $get_month)
	{
	  return ' selected="selected"';
	}
    }
  elseif ($month == $current_month)
    {
      return ' selected="selected"';
    }
}
function calendar_year_comparison($year)
{
  $get_year = (get_query_var('calendar_yr') ? get_query_var('calendar_yr') : null);
  $get_month = (get_query_var('calendar_month') ? get_query_var('calendar_month') : null);
  $current_year = strtolower(gmdate("Y", calendar_ctwo()));
  if (isset($get_year) && isset($get_month))
    {
      if ($year == $get_year)
	{
	  return ' selected="selected"';
	}
    }
  else if ($year == $current_year)
    {
      return ' selected="selected"';
    }
}

// Actually do the printing of the calendar
// Compared to searching for and displaying events
// this bit is really rather easy!
function calendar($cat_list = '')
{
  global $wpdb;

    $get_year = (get_query_var('calendar_yr') ? get_query_var('calendar_yr') : null);
    $get_month = (get_query_var('calendar_month') ? get_query_var('calendar_month') : null);

    // Deal with the week not starting on a monday
    if (get_option('start_of_week') == 0)
      {
	$name_days = array(1=>__('Sunday','calendar'),__('Monday','calendar'),__('Tuesday','calendar'),__('Wednesday','calendar'),__('Thursday','calendar'),__('Friday','calendar'),__('Saturday','calendar'));
      }
    // Choose Monday if anything other than Sunday is set
    else
      {
	$name_days = array(1=>__('Monday','calendar'),__('Tuesday','calendar'),__('Wednesday','calendar'),__('Thursday','calendar'),__('Friday','calendar'),__('Saturday','calendar'),__('Sunday','calendar'));
      }

    // Carry on with the script
    $name_months = array(1=>__('January','calendar'),__('February','calendar'),__('March','calendar'),__('April','calendar'),__('May','calendar'),__('June','calendar'),__('July','calendar'),__('August','calendar'),__('September','calendar'),__('October','calendar'),__('November','calendar'),__('December','calendar'));

    // If we don't pass arguments we want a calendar that is relevant to today
    if (empty($get_month) || empty($get_year))
    {
        $c_year = gmdate("Y",calendar_ctwo());
        $c_month = gmdate("m",calendar_ctwo());
        $c_day = gmdate("d",calendar_ctwo());
    }

    // Years get funny if we exceed 3000, so we use this check
    if (isset($get_year))
    {    
    if ($get_year <= 3000 && $get_year >= 0 && (int)$get_year != 0)
    {
        // This is just plain nasty and all because of permalinks
        // which are no longer used, this will be cleaned up soon
        if ($get_month == 'jan' || $get_month == 'feb' || $get_month == 'mar' || $get_month == 'apr' || $get_month == 'may' || $get_month == 'jun' || $get_month == 'jul' || $get_month == 'aug' || $get_month == 'sep' || $get_month == 'oct' || $get_month == 'nov' || $get_month == 'dec')
	  {

	       // Again nasty code to map permalinks into something
	       // databases can understand. This will be cleaned up
               $c_year = $wpdb->prepare("%d",$get_year);
               if ($get_month == 'jan') { $t_month = 1; }
               else if ($get_month == 'feb') { $t_month = 2; }
               else if ($get_month == 'mar') { $t_month = 3; }
               else if ($get_month == 'apr') { $t_month = 4; }
               else if ($get_month == 'may') { $t_month = 5; }
               else if ($get_month == 'jun') { $t_month = 6; }
               else if ($get_month == 'jul') { $t_month = 7; }
               else if ($get_month == 'aug') { $t_month = 8; }
               else if ($get_month == 'sep') { $t_month = 9; }
               else if ($get_month == 'oct') { $t_month = 10; }
               else if ($get_month == 'nov') { $t_month = 11; }
               else if ($get_month == 'dec') { $t_month = 12; }
               $c_month = $t_month;
               $c_day = gmdate("d",calendar_ctwo());
        }
	// No valid month causes the calendar to default to today
        else
        {
               $c_year = gmdate("Y",calendar_ctwo());
               $c_month = gmdate("m",calendar_ctwo());
               $c_day = gmdate("d",calendar_ctwo());
        }
    }
    }
    // No valid year causes the calendar to default to today
    else
    {
        $c_year = gmdate("Y",calendar_ctwo());
        $c_month = gmdate("m",calendar_ctwo());
        $c_day = gmdate("d",calendar_ctwo());
    }

    // Fix the days of the week if week start is not on a monday
    if (get_option('start_of_week') == 0)
      {
	$first_weekday = gmdate("w",mktime(0,0,0,$c_month,1,$c_year));
        $first_weekday = ($first_weekday==0?1:$first_weekday+1);
      }
    // Otherwise assume the week starts on a Monday. Anything other 
    // than Sunday or Monday is just plain odd
    else
      {
	$first_weekday = gmdate("w",mktime(0,0,0,$c_month,1,$c_year));
	$first_weekday = ($first_weekday==0?7:$first_weekday);
      }

    $days_in_month = gmdate("t", mktime (0,0,0,$c_month,1,$c_year));

    // Start the table and add the header and naviagtion
    $calendar_body = '';
    $calendar_body .= '
<table cellspacing="1" cellpadding="0" class="calendar-table">
';

    // We want to know if we should display the date switcher
    $date_switcher = calendar_get_config_value('display_jump');
    if ($date_switcher == 'true')
      {
	$calendar_body .= '<tr>
        <td colspan="7" class="calendar-date-switcher">
            <form method="get" action="'.htmlspecialchars($_SERVER['REQUEST_URI']).'">
';
	$qsa = array();
	parse_str($_SERVER['QUERY_STRING'],$qsa);
	foreach ($qsa as $name => $argument)
	  {
	    if ($name != 'calendar_month' && $name != 'calendar_yr' && preg_match("/^[A-Za-z0-9\-\_]+$/",$name) && preg_match("/^[A-Za-z0-9\-\_]+$/",$argument))
	      {
		$calendar_body .= '<input type="hidden" name="'.wp_strip_all_tags($name).'" value="'.wp_strip_all_tags($argument).'" />
';
	      }
	  }

	// We build the months in the switcher
	$calendar_body .= '
            '.esc_html__('Month','calendar').': <select name="calendar_month" style="width:100px;">
            <option value="jan"'.calendar_month_comparison('jan').'>'.esc_html__('January','calendar').'</option>
            <option value="feb"'.calendar_month_comparison('feb').'>'.esc_html__('February','calendar').'</option>
            <option value="mar"'.calendar_month_comparison('mar').'>'.esc_html__('March','calendar').'</option>
            <option value="apr"'.calendar_month_comparison('apr').'>'.esc_html__('April','calendar').'</option>
            <option value="may"'.calendar_month_comparison('may').'>'.esc_html__('May','calendar').'</option>
            <option value="jun"'.calendar_month_comparison('jun').'>'.esc_html__('June','calendar').'</option>
            <option value="jul"'.calendar_month_comparison('jul').'>'.esc_html__('July','calendar').'</option> 
            <option value="aug"'.calendar_month_comparison('aug').'>'.esc_html__('August','calendar').'</option> 
            <option value="sep"'.calendar_month_comparison('sep').'>'.esc_html__('September','calendar').'</option>
            <option value="oct"'.calendar_month_comparison('oct').'>'.esc_html__('October','calendar').'</option> 
            <option value="nov"'.calendar_month_comparison('nov').'>'.esc_html__('November','calendar').'</option> 
            <option value="dec"'.calendar_month_comparison('dec').'>'.esc_html__('December','calendar').'</option> 
            </select>
            '.esc_html__('Year','calendar').': <select name="calendar_yr" style="width:60px;">
';

	// The year builder is string mania. If you can make sense of this, you know your PHP!

	$past = 30;
	$future = 30;
	$fut = 1;
	$f = '';
	$p = '';
	while ($past > 0)
	  {
	    $p .= '            <option value="';
	    $p .= gmdate("Y",calendar_ctwo())-$past;
	    $p .= '"'.calendar_year_comparison(gmdate("Y",calendar_ctwo())-$past).'>';
	    $p .= gmdate("Y",calendar_ctwo())-$past.'</option>
';
	    $past = $past - 1;
	  }
	while ($fut < $future) 
	  {
	    $f .= '            <option value="';
	    $f .= gmdate("Y",calendar_ctwo())+$fut;
	    $f .= '"'.calendar_year_comparison(gmdate("Y",calendar_ctwo())+$fut).'>';
	    $f .= gmdate("Y",calendar_ctwo())+$fut.'</option>
';
	    $fut = $fut + 1;
	  } 
	$calendar_body .= $p;
	$calendar_body .= '            <option value="'.gmdate("Y",calendar_ctwo()).'"'.calendar_year_comparison(gmdate("Y",calendar_ctwo())).'>'.gmdate("Y",calendar_ctwo()).'</option>
';
	$calendar_body .= $f;
        $calendar_body .= '</select>
            <input type="submit" value="'.esc_html__('Go','calendar').'" />
            </form>
        </td>
</tr>
';
      }

    // The header of the calendar table and the links. Note calls to link functions
    $calendar_body .= '<tr>
                <td colspan="7" class="calendar-heading">
                    <table border="0" cellpadding="0" cellspacing="0" width="100%">
                    <tr>
                    <td class="calendar-prev">' . calendar_prev_link($c_year,$c_month) . '</td>
                    <td class="calendar-month">'.$name_months[(int)$c_month].' '.$c_year.'</td>
                    <td class="calendar-next">' . calendar_next_link($c_year,$c_month) . '</td>
                    </tr>
                    </table>
                </td>
</tr>
';

    // Print the headings of the days of the week
    $calendar_body .= '<tr>
';
    for ($i=1; $i<=7; $i++) 
      {
	// Colours need to be different if the starting day of the week is different
	if (get_option('start_of_week') == 0)
	  {
	    $calendar_body .= '        <td class="'.($i<7&&$i>1?'normal-day-heading':'weekend-heading').'">'.$name_days[$i].'</td>
';
	  }
	else
	  {
	    $calendar_body .= '        <td class="'.($i<6?'normal-day-heading':'weekend-heading').'">'.$name_days[$i].'</td>
';
	  }
      }
    $calendar_body .= '</tr>
';
    $go = FALSE;
    for ($i=1; $i<=$days_in_month;)
      {
        $calendar_body .= '<tr>
';
        for ($ii=1; $ii<=7; $ii++)
	  {
            if ($ii==$first_weekday && $i==1)
	      {
		$go = TRUE;
	      }
            elseif ($i > $days_in_month ) 
	      {
	    	$go = FALSE;
	      }
            if ($go) 
	      {
		// Colours again, this time for the day numbers
		if (get_option('start_of_week') == 0)
		  {
		    // This bit of code is for styles believe it or not.
		    $grabbed_events = grab_events($c_year,$c_month,$i,'calendar',$cat_list);
		    $no_events_class = '';
		    if (!count($grabbed_events))
		      {
			$no_events_class = ' no-events';
		      }
		    $calendar_body .= '        <td class="'.(gmdate("Ymd", mktime (0,0,0,$c_month,$i,$c_year))==gmdate("Ymd",calendar_ctwo())?'current-day':'day-with-date').$no_events_class.'"><span '.($ii<7&&$ii>1?'':'class="weekend"').'>'.$i++.'</span><span class="event"><br />' . calendar_draw_events($grabbed_events) . '</span></td>
';
		  }
		else
		  {
		    $grabbed_events = grab_events($c_year,$c_month,$i,'calendar',$cat_list);
		    $no_events_class = '';
	            if (!count($grabbed_events))
		      {
			$no_events_class = ' no-events';
		      }
		    $calendar_body .= '        <td class="'.(gmdate("Ymd", mktime (0,0,0,$c_month,$i,$c_year))==gmdate("Ymd",calendar_ctwo())?'current-day':'day-with-date').$no_events_class.'"><span '.($ii<6?'':'class="weekend"').'>'.$i++.'</span><span class="event"><br />' . calendar_draw_events($grabbed_events) . '</span></td>
';
		  }
	      }
            else 
	      {
		$calendar_body .= '        <td class="day-without-date">&nbsp;</td>
';
	      }
        }
        $calendar_body .= '</tr>
';
    }
    $calendar_body .= '</table>
';
    
    $show_cat = calendar_get_config_value('enable_categories');
    if ($show_cat == 'true')
      {
	$cat_details = calendar_db_get_all_categories($cat_list);
        $calendar_body .= '<table class="cat-key">
<tr><td colspan="2" class="cat-key-cell"><strong>'.esc_html__('Category Key','calendar').'</strong></td></tr>
';
        foreach($cat_details as $cat_detail)
	  {
	    $calendar_body .= '<tr><td style="background-color:'.$cat_detail->category_colour.'; width:20px; height:20px;" class="cat-key-cell"></td>
<td class="cat-key-cell">&nbsp;'.htmlspecialchars($cat_detail->category_name).'</td></tr>';
	  }
        $calendar_body .= '</table>
';
      }

    // A little link to yours truly
    $link_approved = 'false';
    if (calendar_get_config_value('show_attribution_link') == 'true') {
        $link_approved = 'true';
    }

    if ($link_approved == 'true') {
        $linkback_url = '<div class="kjo-link" style="visibility:visible !important;display:block !important;"><p>'.esc_html__('Calendar developed and supported by ', 'calendar').'<a href="http://www.kieranoshea.com">Kieran O\'Shea</a></p></div>
';
    } else {
        $linkback_url = '';
    }
    $calendar_body .= $linkback_url;

    // Phew! After that bit of string building, spit it all out.
    // The actual printing is done by the calling function.
    return $calendar_body;
}

// Used to create a hover will all a day's events in for minical
function calendar_minical_draw_events($events,$day_of_week = '')
{
  // Bring in the category & contrast option
  $show_cat = calendar_get_config_value('enable_categories');
  $contrast = calendar_get_config_value('enhance_contrast');
  // We need to sort arrays of objects by time
  usort($events, "calendar_time_cmp");
  // Only show anything if there are events
  $output = '';
  if (count($events)) {
    $style = '';
    if ($show_cat == 'true') {
        $arr_values = array_values($events);
        $firstevent = array_shift($arr_values);
        $cat_details = calendar_db_get_category_row_by_id($firstevent->event_category);
        if ($contrast == 'true') {
            $fgcolor = getContrastYIQ($cat_details->category_colour);
            $style = 'style="background-color:' . $cat_details->category_colour . '; color:' . $fgcolor . '"';
        } else {
            $style = 'style="background-color:' . $cat_details->category_colour . ';"';
        }
    }

    // Setup the wrapper
    $output = '<span class="calnk"><a href="#" class="minical-day" '.$style.'>'.$day_of_week.'<span '.$style.'>';
    // Now process the events
    foreach($events as $event) {
        if ($event->event_time == '00:00:00') {
            $the_time = '<span class="calendar_time all_day" style="position:relative;display:inline;width:unset;background:none;">'.esc_html__('all day','calendar').'</span>';
        } else {
            $the_time = '<span class="calendar_time" style="position:relative;display:inline;width:unset;background:none;">'.esc_html__('at','calendar').' '.gmdate(get_option('time_format'), strtotime($event->event_time)).'</span>';
        }
        $output .= '<span class="calendar_bullet" style="position:relative;display:inline;width:unset;background:none;">* </span><strong>'.$event->event_title.'</strong> '.$the_time.'<br />';
      }
    // The tail
    $output .= '</span></a></span>';
  } else {
    $output .= $day_of_week;
  }
  return $output;
}

function calendar_minical($cat_list = '') {
  
  global $wpdb;

  $get_year = (get_query_var('calendar_yr') ? get_query_var('calendar_yr') : null);
  $get_month = (get_query_var('calendar_month') ? get_query_var('calendar_month') : null);

  // Deal with the week not starting on a monday                                                                                                                                  
  if (get_option('start_of_week') == 0)
    {
      $name_days = array(1=>__('Su','calendar'),__('Mo','calendar'),__('Tu','calendar'),__('We','calendar'),__('Th','calendar'),__('Fr','calendar'),__('Sa','calendar'));
    }
  // Choose Monday if anything other than Sunday is set                                                                                                                           
  else
    {
      $name_days = array(1=>__('Mo','calendar'),__('Tu','calendar'),__('We','calendar'),__('Th','calendar'),__('Fr','calendar'),__('Sa','calendar'),__('Su','calendar'));
    }

  // Carry on with the script                                                                                                                                                     
  $name_months = array(1=>__('January','calendar'),__('February','calendar'),__('March','calendar'),__('April','calendar'),__('May','calendar'),__('June','calendar'),__('July','\
calendar'),__('August','calendar'),__('September','calendar'),__('October','calendar'),__('November','calendar'),__('December','calendar'));

  // If we don't pass arguments we want a calendar that is relevant to today                                                                                                      
  if (empty($get_month) || empty($get_year))
    {
      $c_year = gmdate("Y",calendar_ctwo());
      $c_month = gmdate("m",calendar_ctwo());
      $c_day = gmdate("d",calendar_ctwo());
    }

  // Years get funny if we exceed 3000, so we use this check                                                                                                                      
  if (isset($get_year))
    {
      if ($get_year <= 3000 && $get_year >= 0 && (int)$get_year != 0)
	{
	  // This is just plain nasty and all because of permalinks
	  // which are no longer used, this will be cleaned up soon
	  if ($get_month == 'jan' || $get_month == 'feb' || $get_month == 'mar' || $get_month == 'apr' || $get_month == 'may' || $get_month == 'jun' || $get_month == 'jul' || $get_month == 'aug' || $get_month == 'sep' || $get_month == 'oct' || $get_month == 'nov' || $get_month == 'dec')
	    {

	      // Again nasty code to map permalinks into something                                                                                                                 
	      // databases can understand. This will be cleaned up
	      $c_year = $wpdb->prepare("%d",$get_year);
	      if ($get_month == 'jan') { $t_month = 1; }
	      else if ($get_month == 'feb') { $t_month = 2; }
	      else if ($get_month == 'mar') { $t_month = 3; }
	      else if ($get_month == 'apr') { $t_month = 4; }
	      else if ($get_month == 'may') { $t_month = 5; }
	      else if ($get_month == 'jun') { $t_month = 6; }
	      else if ($get_month == 'jul') { $t_month = 7; }
	      else if ($get_month == 'aug') { $t_month = 8; }
	      else if ($get_month == 'sep') { $t_month = 9; }
	      else if ($get_month == 'oct') { $t_month = 10; }
	      else if ($get_month == 'nov') { $t_month = 11; }
	      else if ($get_month == 'dec') { $t_month = 12; }
	      $c_month = $t_month;
	      $c_day = gmdate("d",calendar_ctwo());
	    }
	  // No valid month causes the calendar to default to today
	  else
	    {
	      $c_year = gmdate("Y",calendar_ctwo());
	      $c_month = gmdate("m",calendar_ctwo());
	      $c_day = gmdate("d",calendar_ctwo());
	    }
	}
    }
  // No valid year causes the calendar to default to today                                                                                                                        
  else
    {
      $c_year = gmdate("Y",calendar_ctwo());
      $c_month = gmdate("m",calendar_ctwo());
      $c_day = gmdate("d",calendar_ctwo());
    }

  // Fix the days of the week if week start is not on a monday                                                                                                                    
  if (get_option('start_of_week') == 0)
    {
      $first_weekday = gmdate("w",mktime(0,0,0,$c_month,1,$c_year));
      $first_weekday = ($first_weekday==0?1:$first_weekday+1);
    }
  // Otherwise assume the week starts on a Monday. Anything other                                                                                                                 
  // than Sunday or Monday is just plain odd                                                                                                                                      
  else
    {
      $first_weekday = gmdate("w",mktime(0,0,0,$c_month,1,$c_year));
      $first_weekday = ($first_weekday==0?7:$first_weekday);
    }

  $days_in_month = gmdate("t", mktime (0,0,0,$c_month,1,$c_year));

  // Start the table and add the header and naviagtion                                                                                                                            
  $calendar_body = '';
  $calendar_body .= '<div style="width:200px;"><table cellspacing="1" cellpadding="0" class="calendar-table">
';


  // The header of the calendar table and the links. Note calls to link functions
  $calendar_body .= '<tr>
               <td colspan="7" class="calendar-heading" style="height:0;">
                    <table border="0" cellpadding="0" cellspacing="0" width="100%">
                        <tr>
                            <td class="calendar-prev">' . calendar_prev_link($c_year,$c_month,true) . '</td>
                            <td class="calendar-month">'.$name_months[(int)$c_month].' '.$c_year.'</td>
                            <td class="calendar-next">' . calendar_next_link($c_year,$c_month,true) . '</td>
                        </tr>
                    </table>
               </td>
</tr>
';

    // Print the headings of the days of the week
    $calendar_body .= '<tr>
';
    for ($i=1; $i<=7; $i++)
      {
        // Colours need to be different if the starting day of the week is different
	if (get_option('start_of_week') == 0)
          {
            $calendar_body .= '        <td class="'.($i<7&&$i>1?'normal-day-heading':'weekend-heading').'" style="height:0;">'.$name_days[$i].'</td>
';
          }
        else
          {
            $calendar_body .= '        <td class="'.($i<6?'normal-day-heading':'weekend-heading').'" style="height:0;">'.$name_days[$i].'</td>
';
          }
      }
    $calendar_body .= '</tr>
';
    $go = FALSE;
    for ($i=1; $i<=$days_in_month;)
      {
        $calendar_body .= '<tr>
';
        for ($ii=1; $ii<=7; $ii++)
          {
            if ($ii==$first_weekday && $i==1)
              {
                $go = TRUE;
              }
            elseif ($i > $days_in_month )
              {
                $go = FALSE;
              }
            if ($go)
              {
                // Colours again, this time for the day numbers                                                                                                                     
                if (get_option('start_of_week') == 0)
                  {
                    // This bit of code is for styles believe it or not.
		    $grabbed_events = grab_events($c_year,$c_month,$i,'calendar',$cat_list);
                    $no_events_class = '';
                    if (!count($grabbed_events))
                      {
                        $no_events_class = ' no-events';
                      }
                    $calendar_body .= '        <td class="'.(gmdate("Ymd", mktime (0,0,0,$c_month,$i,$c_year))==gmdate("Ymd",calendar_ctwo())?'current-day':'day-with-date').$no_events_class.'" style="height:0;"><span '.($ii<7&&$ii>1?'':'class="weekend"').'>'.calendar_minical_draw_events($grabbed_events,$i++).'</span></td>
';
                  }
                else
                  {
                    $grabbed_events = grab_events($c_year,$c_month,$i,'calendar',$cat_list);
                    $no_events_class = '';
                    if (!count($grabbed_events))
                      {
                        $no_events_class = ' no-events';
                      }
                    $calendar_body .= '        <td class="'.(gmdate("Ymd", mktime (0,0,0,$c_month,$i,$c_year))==gmdate("Ymd",calendar_ctwo())?'current-day':'day-with-date').$no_events_class.'" style="height:0;"><span '.($ii<6?'':'class="weekend"').'>'.calendar_minical_draw_events($grabbed_events,$i++).'</span></td>
';
                  }
              }
            else
              {
                $calendar_body .= '        <td class="day-without-date" style="height:0;">&nbsp;</td>
';
              }
	  }
        $calendar_body .= '</tr>
';
      }
    $calendar_body .= '</table>
';

    // A little link to yours truly
    $link_approved = 'false';
    if (calendar_get_config_value('show_attribution_link') == 'true') {
        $link_approved = 'true';
    }

    if ($link_approved == 'true') {
        $linkback_url = '<div class="kjo-link" style="visibility:visible !important;display:block !important;"><p>'.esc_html__('Calendar by ', 'calendar').'<a href="http://www.kieranoshea.com">Kieran O\'Shea</a></p></div>
';
    } else {
        $linkback_url = '';
    }
    $calendar_body .= $linkback_url;

    // Closing div
    $calendar_body .= '</div>
';
    // Phew! After that bit of string building, spit it all out.
    // The actual printing is done by the calling function.
    return $calendar_body;

}

/* All DB related functions sit below here for ease of review */

// Function to deal with events posted by a user when that user is deleted
function calendar_deal_with_deleted_user($id) {
    global $wpdb;
    $users_table = $wpdb->prefix."users";
    $substitute_author_id = $wpdb->get_var($wpdb->prepare("SELECT MIN(ID) FROM %i",$users_table),0,0); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching,WordPress.DB.PreparedSQLPlaceholders.UnsupportedIdentifierPlaceholder
    $wpdb->get_results($wpdb->prepare("UPDATE %i SET event_author=%d WHERE event_author=%d",WP_CALENDAR_TABLE,$substitute_author_id,$id)); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching,WordPress.DB.PreparedSQLPlaceholders.UnsupportedIdentifierPlaceholder
}

function calendar_get_config_value($calendar_config_name) {
    global $wpdb;
    return $wpdb->get_var($wpdb->prepare("SELECT config_value FROM %i WHERE config_item=%s", WP_CALENDAR_CONFIG_TABLE, $calendar_config_name)); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching,WordPress.DB.PreparedSQLPlaceholders.UnsupportedIdentifierPlaceholder
}

function calendar_update_config_value($calendar_config_name, $calendar_config_value) {
    global $wpdb;
    $wpdb->get_results($wpdb->prepare("UPDATE %i SET config_value=%s WHERE config_item=%s", WP_CALENDAR_CONFIG_TABLE, $calendar_config_value, $calendar_config_name)); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching,WordPress.DB.PreparedSQLPlaceholders.UnsupportedIdentifierPlaceholder
}

function calendar_insert_config_value($calendar_config_name, $calendar_config_value) {
    global $wpdb;
    $wpdb->get_results($wpdb->prepare("INSERT INTO %i SET config_item=%s, config_value=%s", WP_CALENDAR_CONFIG_TABLE, $calendar_config_name, $calendar_config_value)); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching,WordPress.DB.PreparedSQLPlaceholders.UnsupportedIdentifierPlaceholder
}

function calendar_get_db_tables() {
    global $wpdb;
    return $wpdb->get_results("show tables"); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching,WordPress.DB.PreparedSQLPlaceholders.UnsupportedIdentifierPlaceholder
}

function calendar_create_calendar_table() {
    global $wpdb;
    $wpdb->get_results($wpdb->prepare("CREATE TABLE %i (event_id INT(11) NOT NULL AUTO_INCREMENT, event_begin DATE NOT NULL, event_end DATE NOT NULL, event_title VARCHAR(%d) NOT NULL, event_desc TEXT NOT NULL, event_time TIME, event_recur CHAR(1), event_repeats INT(3), event_author BIGINT(20) UNSIGNED, event_category BIGINT(20) UNSIGNED NOT NULL DEFAULT 1, event_link TEXT, PRIMARY KEY (event_id))", WP_CALENDAR_TABLE, CALENDAR_TITLE_LENGTH)); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.SchemaChange,WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching,WordPress.DB.PreparedSQLPlaceholders.UnsupportedIdentifierPlaceholder
}

function calendar_create_calendar_config_table() {
    global $wpdb;
    $wpdb->get_results($wpdb->prepare("CREATE TABLE %i (config_item VARCHAR(30) NOT NULL, config_value TEXT NOT NULL, PRIMARY KEY (config_item))", WP_CALENDAR_CONFIG_TABLE)); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.SchemaChange,WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching,WordPress.DB.PreparedSQLPlaceholders.UnsupportedIdentifierPlaceholder
}

function calendar_create_calendar_categories() {
    global $wpdb;
    $wpdb->get_results($wpdb->prepare("CREATE TABLE %i (category_id INT(11) NOT NULL AUTO_INCREMENT, category_name VARCHAR(30) NOT NULL, category_colour VARCHAR(30) NOT NULL, PRIMARY KEY (category_id))", WP_CALENDAR_CATEGORIES_TABLE)); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.SchemaChange,WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching,WordPress.DB.PreparedSQLPlaceholders.UnsupportedIdentifierPlaceholder
    $wpdb->get_results($wpdb->prepare("INSERT INTO %i SET category_id=1, category_name='General', category_colour='#F6F79B'", WP_CALENDAR_CATEGORIES_TABLE)); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching,WordPress.DB.PreparedSQLPlaceholders.UnsupportedIdentifierPlaceholder
}

function calendar_add_author_and_description_to_calendar_table() {
    global $wpdb;
    $wpdb->get_results($wpdb->prepare("ALTER TABLE %i ADD COLUMN event_author BIGINT(20) UNSIGNED", WP_CALENDAR_TABLE)); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.SchemaChange,WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching,WordPress.DB.PreparedSQLPlaceholders.UnsupportedIdentifierPlaceholder
    $wpdb->get_results($wpdb->prepare("UPDATE %i SET event_author=(SELECT MIN(ID) FROM %i)", WP_CALENDAR_TABLE, $wpdb->prefix.'users')); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching,WordPress.DB.PreparedSQLPlaceholders.UnsupportedIdentifierPlaceholder
    $wpdb->get_results($wpdb->prepare("ALTER TABLE %i MODIFY event_desc TEXT NOT NULL", WP_CALENDAR_TABLE)); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.SchemaChange,WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching,WordPress.DB.PreparedSQLPlaceholders.UnsupportedIdentifierPlaceholder
}

function calendar_add_link_and_category_to_calendar_table() {
    global $wpdb;
    $wpdb->get_results($wpdb->prepare("ALTER TABLE %i ADD COLUMN event_category BIGINT(20) UNSIGNED NOT NULL DEFAULT 1", WP_CALENDAR_TABLE)); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.SchemaChange,WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching,WordPress.DB.PreparedSQLPlaceholders.UnsupportedIdentifierPlaceholder
    $wpdb->get_results($wpdb->prepare("ALTER TABLE %i ADD COLUMN event_link TEXT ", WP_CALENDAR_TABLE)); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.SchemaChange,WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching,WordPress.DB.PreparedSQLPlaceholders.UnsupportedIdentifierPlaceholder
}

function calendar_db_set_charset_for_table($table_name) {
    global $wpdb;
    $wpdb->get_results($wpdb->prepare("ALTER TABLE %i CONVERT TO CHARACTER SET utf8 COLLATE utf8_unicode_ci", WP_CALENDAR_CONFIG_TABLE)); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.SchemaChange,WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching,WordPress.DB.PreparedSQLPlaceholders.UnsupportedIdentifierPlaceholder
}

function calendar_db_get_all_events() {
    global $wpdb;
    return $wpdb->get_results($wpdb->prepare("SELECT * FROM %i ORDER BY event_begin DESC", WP_CALENDAR_TABLE)); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching,WordPress.DB.PreparedSQLPlaceholders.UnsupportedIdentifierPlaceholder
}

function calendar_db_get_category_row_by_id($category_id) {
    global $wpdb;
    return $wpdb->get_row($wpdb->prepare("SELECT * FROM %i WHERE category_id=%d", WP_CALENDAR_CATEGORIES_TABLE, $category_id)); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching,WordPress.DB.PreparedSQLPlaceholders.UnsupportedIdentifierPlaceholder
}

function calendar_db_get_events_by_id($event_id) {
    global $wpdb;
    return $wpdb->get_results($wpdb->prepare("SELECT * FROM %i WHERE event_id=%d LIMIT 1", WP_CALENDAR_TABLE, $event_id)); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching,WordPress.DB.PreparedSQLPlaceholders.UnsupportedIdentifierPlaceholder
}

function calendar_db_get_all_categories($category_ids = null) {
    global $wpdb;
    if (!empty($category_ids)) {
        $cat_ids = explode(',', $category_ids);
        return $wpdb->get_results($wpdb->prepare(sprintf("SELECT * FROM `%scalendar_categories` WHERE category_id IN (%s)", $wpdb->prefix, implode( ',', array_fill( 0, count( $cat_ids ), '%s' ) )), $cat_ids)); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching,WordPress.DB.PreparedSQLPlaceholders.UnsupportedIdentifierPlaceholder
    } else {
        return $wpdb->get_results($wpdb->prepare("SELECT * FROM %i", WP_CALENDAR_CATEGORIES_TABLE)); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching,WordPress.DB.PreparedSQLPlaceholders.UnsupportedIdentifierPlaceholder
    }
}

function calendar_db_insert_event($title,$desc,$begin,$end,$time_to_use,$recur,$repeats,$user_id,$category,$linky) {
    global $wpdb;
    $wpdb->get_results($wpdb->prepare("INSERT INTO %i SET event_title=%s, event_desc=%s, event_begin=%s, event_end=%s, event_time=%s, event_recur=%s, event_repeats=%s, event_author=%d, event_category=%d, event_link=%s",WP_CALENDAR_TABLE,$title,$desc,$begin,$end,$time_to_use,$recur,$repeats,$user_id,$category,$linky)); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching,WordPress.DB.PreparedSQLPlaceholders.UnsupportedIdentifierPlaceholder
}

function calendar_db_get_event_id_by_insert_data($title,$desc,$begin,$end,$time_to_use,$recur,$repeats,$user_id,$category,$linky) {
    global $wpdb;
    return $wpdb->get_results($wpdb->prepare("SELECT event_id FROM %i WHERE event_title=%s AND event_desc=%s AND event_begin=%s AND event_end=%s AND event_time=%s AND event_recur=%s AND event_repeats=%s AND event_author=%d AND event_category=%d AND event_link=%s LIMIT 1",WP_CALENDAR_TABLE,$title,$desc,$begin,$end,$time_to_use,$recur,$repeats,$user_id,$category,$linky)); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching,WordPress.DB.PreparedSQLPlaceholders.UnsupportedIdentifierPlaceholder
}

function calendar_db_update_event($title,$desc,$begin,$end,$time_to_use,$recur,$repeats,$user_id,$category,$linky,$event_id) {
    global $wpdb;
    $wpdb->get_results($wpdb->prepare("UPDATE %i SET event_title=%s, event_desc=%s, event_begin=%s, event_end=%s, event_time=%s, event_recur=%s, event_repeats=%s, event_author=%d, event_category=%d, event_link=%s WHERE event_id=%s",WP_CALENDAR_TABLE,$title,$desc,$begin,$end,$time_to_use,$recur,$repeats,$user_id,$category,$linky,$event_id)); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching,WordPress.DB.PreparedSQLPlaceholders.UnsupportedIdentifierPlaceholder
}

function calendar_db_delete_event_by_id($event_id) {
    global $wpdb;
    $wpdb->get_results($wpdb->prepare("DELETE FROM %i WHERE event_id=%s",WP_CALENDAR_TABLE,$event_id)); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching,WordPress.DB.PreparedSQLPlaceholders.UnsupportedIdentifierPlaceholder
}

function calendar_db_get_event_id_by_id($event_id) {
    global $wpdb;
    return $wpdb->get_results($wpdb->prepare("SELECT event_id FROM %i WHERE event_id=%s",WP_CALENDAR_TABLE,$event_id)); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching,WordPress.DB.PreparedSQLPlaceholders.UnsupportedIdentifierPlaceholder
}

function calendar_db_insert_category($category_name, $category_colour) {
    global $wpdb;
    $wpdb->get_results($wpdb->prepare("INSERT INTO %i SET category_name=%s, category_colour=%s",WP_CALENDAR_CATEGORIES_TABLE, $category_name,$category_colour)); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching,WordPress.DB.PreparedSQLPlaceholders.UnsupportedIdentifierPlaceholder
}

function calendar_db_update_category($category_name, $category_colour, $category_id) {
    global $wpdb;
    $wpdb->get_results($wpdb->prepare("UPDATE %i SET category_name=%s, category_colour=%s WHERE category_id=%d",WP_CALENDAR_CATEGORIES_TABLE, $category_name,$category_colour,$category_id)); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching,WordPress.DB.PreparedSQLPlaceholders.UnsupportedIdentifierPlaceholder
}

function calendar_db_delete_category($category_id) {
    global $wpdb;
    $wpdb->get_results($wpdb->prepare("DELETE FROM %i WHERE category_id=%d",WP_CALENDAR_CATEGORIES_TABLE,$category_id)); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching,WordPress.DB.PreparedSQLPlaceholders.UnsupportedIdentifierPlaceholder
}

function calendar_db_reset_event_categories_to_default_from_id($category_id) {
    global $wpdb;
    $wpdb->get_results($wpdb->prepare("UPDATE %i SET event_category=1 WHERE event_category=%d",WP_CALENDAR_TABLE,$category_id)); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching,WordPress.DB.PreparedSQLPlaceholders.UnsupportedIdentifierPlaceholder
}

function calendar_db_fetch_events_for_date($date, $category_list = null) {
    global $wpdb;
    // Query all events based on type
    $events =$wpdb->get_results($wpdb->prepare("SELECT a.*,'Normal' AS type  FROM %i AS a WHERE a.event_begin <= %s AND a.event_end >= %s AND a.event_recur = 'S' UNION ALL SELECT b.*,'Yearly' AS type FROM %i AS b WHERE b.event_recur = 'Y' AND EXTRACT(YEAR FROM %s) >= EXTRACT(YEAR FROM b.event_begin) AND b.event_repeats = 0 UNION ALL SELECT c.*,'Yearly' AS type FROM %i AS c WHERE c.event_recur = 'Y' AND EXTRACT(YEAR FROM %s) >= EXTRACT(YEAR FROM c.event_begin) AND c.event_repeats != 0 AND (EXTRACT(YEAR FROM %s)-EXTRACT(YEAR FROM c.event_begin)) <= c.event_repeats UNION ALL SELECT d.*,'Monthly' AS type FROM %i AS d WHERE d.event_recur = 'M' AND EXTRACT(YEAR FROM %s) >= EXTRACT(YEAR FROM d.event_begin) AND d.event_repeats = 0 UNION ALL SELECT e.*,'Monthly' AS type FROM %i AS e WHERE e.event_recur = 'M' AND EXTRACT(YEAR FROM %s) >= EXTRACT(YEAR FROM e.event_begin) AND e.event_repeats != 0 AND (PERIOD_DIFF(EXTRACT(YEAR_MONTH FROM %s),EXTRACT(YEAR_MONTH FROM e.event_begin))) <= e.event_repeats UNION ALL SELECT f.*,'MonthSun' AS type FROM %i AS f WHERE f.event_recur = 'U' AND EXTRACT(YEAR FROM %s) >= EXTRACT(YEAR FROM f.event_begin) AND f.event_repeats = 0 UNION ALL SELECT g.*,'MonthSun' AS type FROM %i AS g WHERE g.event_recur = 'U' AND EXTRACT(YEAR FROM %s) >= EXTRACT(YEAR FROM g.event_begin) AND g.event_repeats != 0 AND (PERIOD_DIFF(EXTRACT(YEAR_MONTH FROM %s),EXTRACT(YEAR_MONTH FROM g.event_begin))) <= g.event_repeats UNION ALL SELECT h.*,'Weekly' AS type FROM %i AS h WHERE h.event_recur = 'W' AND %s >= h.event_begin AND h.event_repeats = 0 UNION ALL SELECT i.*,'Weekly' AS type FROM %i AS i WHERE i.event_recur = 'W' AND %s >= i.event_begin AND i.event_repeats != 0 AND (i.event_repeats*7) >= (TO_DAYS(%s) - TO_DAYS(i.event_end)) ORDER BY event_id", WP_CALENDAR_TABLE, $date, $date, WP_CALENDAR_TABLE, $date, WP_CALENDAR_TABLE, $date, $date, WP_CALENDAR_TABLE, $date, WP_CALENDAR_TABLE, $date, $date, WP_CALENDAR_TABLE, $date, WP_CALENDAR_TABLE, $date, $date, WP_CALENDAR_TABLE, $date, WP_CALENDAR_TABLE, $date, $date)); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching,WordPress.DB.PreparedSQLPlaceholders.UnsupportedIdentifierPlaceholder
     
     // Filter the events found based on the category list, if present
     if (!empty($category_list)) {
         $allowed_categories = explode(',', $category_list);
         $filtered_events = array();
         foreach($events as $event) {
             if (in_array($event->event_category, $allowed_categories)) {
                 array_push($filtered_events, $event);
             }
         }
         return $filtered_events;
     } else {
         return $events;
     }
}

?>
