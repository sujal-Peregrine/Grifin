<?php
	function griffith_disable_widgets() {
		global $wp_meta_boxes;
		unset($wp_meta_boxes['dashboard']);
    	$wp_meta_boxes['dashboard']['normal']['core'] = array();
    	$wp_meta_boxes['dashboard']['side']['core'] = array();
		remove_meta_box('dashboard_site_health', 'dashboard', 'normal');
		remove_meta_box('dashboard_incoming_links', 'dashboard', 'normal');
		remove_meta_box('dashboard_plugins', 'dashboard', 'normal');
		remove_meta_box('dashboard_primary', 'dashboard', 'side');
		remove_meta_box('dashboard_secondary', 'dashboard', 'normal');
		remove_meta_box('dashboard_quick_press', 'dashboard', 'side');
		remove_meta_box('dashboard_recent_drafts', 'dashboard', 'side');
		remove_meta_box('dashboard_recent_comments', 'dashboard', 'normal');
		remove_meta_box('dashboard_right_now', 'dashboard', 'normal');
		remove_meta_box('dashboard_activity', 'dashboard', 'normal');
	}
	add_action('wp_dashboard_setup', 'griffith_disable_widgets');

	function griffith_disable_customizer($wp_customize) {
		$wp_customize->remove_section('custom_css');
  		$wp_customize->remove_panel('nav_menus');
		$wp_customize->remove_panel('themes');
  		$wp_customize->remove_section('title_tagline');
  		$wp_customize->remove_section('static_front_page');
		$wp_customize->remove_panel('widgets');
	}
	add_action('customize_register', 'griffith_disable_customizer', 20);
	
	function griffith_remove_menus($wp_admin_bar) {
		remove_menu_page('edit.php');
		remove_menu_page('edit-comments.php');
		remove_menu_page('options-general.php');
		remove_menu_page('tools.php');
		//remove_menu_page('users.php');
       	global $submenu;
       	unset($submenu['themes.php'][6]);
	}
	add_action('admin_menu', 'griffith_remove_menus', 9999);

	function griffith_profiles_remove_css() {
		wp_enqueue_style('profiles-stylesheet', get_theme_file_uri('/assets/css/profiles.css'), array(), filemtime(get_template_directory() . '/assets/css/profiles.css'), 'all');
		wp_enqueue_script('profiles-script', get_theme_file_uri('/assets/js/profiles.js'), array('jquery'), filemtime(get_template_directory() . '/assets/js/profiles.js'), true);
	}
	add_action('admin_head','griffith_profiles_remove_css', 99);
    remove_action('admin_color_scheme_picker', 'admin_color_scheme_picker');
	add_filter('months_dropdown_results', '__return_empty_array');
	add_filter('wp_dropdown_cats', '__return_false');

	function griffith_remove_page_attribute() {
		remove_post_type_support('page','page-attributes');
	}
	add_action('init', 'griffith_remove_page_attribute');

	function griffith_remove_update_core() {
		add_filter('pre_site_transient_update_core', '__return_null');
	}
	add_action('admin_head', 'griffith_remove_update_core');
	remove_action('welcome_panel', 'wp_welcome_panel');
?>