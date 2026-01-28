<?php
	$profile = wp_get_current_user();
	function griffith_admin_icons() {
		echo '<link rel="icon" href="'.get_parent_theme_file_uri('/assets/img/favicon.png').'" sizes="32x32" />' . PHP_EOL;
		echo '<link rel="icon" href="'.get_parent_theme_file_uri('/assets/img/favicon.png').'" sizes="192x192" />' . PHP_EOL;
		echo '<link rel="apple-touch-icon-precomposed" href="'.get_parent_theme_file_uri('/assets/img/favicon.png').'" />' . PHP_EOL;
		echo '<meta name="msapplication-TileImage" content="'.get_parent_theme_file_uri('/assets/img/favicon.png').'" />' . PHP_EOL;
	}
	add_action('admin_head', 'griffith_admin_icons');

	function griffith_dashboard_icons() {
		wp_enqueue_style('icons-stylesheet', get_theme_file_uri('/assets/css/icons.css'), array(), filemtime(get_template_directory() . '/assets/css/icons.css'), 'all');
	}
	add_action('admin_head', 'griffith_dashboard_icons', 99);

	function griffith_dashboard_title() {
		global $dashboard_settings;
		return $dashboard_settings['brokerage'] . ' Dashboard';
	}
	add_filter('admin_title', 'griffith_dashboard_title', 10, 2);

    function griffith_footer_text() {
         echo '<span id="footer-note"><strong>© ' . date('Y') . ' LOCN Technology Corporation &#8212; All Rights Resevered.</strong></span>';
    }
    add_filter('admin_footer_text', 'griffith_footer_text');

	function griffith_footer_version() {
		global $dashboard_version;
		return '<strong>Version ' . $dashboard_version . '</strong>';
	}
	add_filter('update_footer', 'griffith_footer_version', 11);

	function griffith_dashboard_hide_menu() {
		remove_menu_page('wpseo_dashboard');
	}
	add_action('admin_menu', 'griffith_dashboard_hide_menu', 11);

	function griffith_dashboard_menu_order($__return_true) {
		return array(
			'index.php',
			'separator1',
			'edit.php',
			'edit.php?post_type=page',
			'edit.php?post_type=griffith_resources',
			'bookings',
			'calendar',
			'upload.php',
			'themes.php',
			'plugins.php',
			'users.php',
			'profile.php',
			'tools.php',
			'options-general.php',
	   );
	}
	add_filter('custom_menu_order', 'griffith_dashboard_menu_order');
	add_filter('menu_order', 'griffith_dashboard_menu_order');

	function griffith_color_scheme() {
		$suffix  = is_rtl() ? '-rtl' : '';
		wp_admin_css_color(
			'royal',
			_x('Websites', 'Griffith Websites Color Scheme'),
			get_theme_file_uri('/assets/dashboard/colors'.$suffix.'.css'),
			array('#464646', '#6D6D6D', '#D6D6D6', '#F1F1F1'),
			array(
				'base'    => '#999',
				'focus'   => '#ccc',
				'current' => '#ccc',
			)
		);
	}
	add_action('admin_init', 'griffith_color_scheme');

	add_filter('get_user_option_admin_color', function($color_scheme) {
		$color_scheme = 'griffith';
		return $color_scheme;
	}, 5);

	function griffith_avatar($args, $id_or_email) {
		$args['url'] = '/wp-content/themes/griffith/assets/img/avatar.png';
		return $args;
	}
	add_filter('get_avatar_data', 'griffith_avatar', 100, 2);

	function griffith_unregister_tags() {
		unregister_taxonomy_for_object_type('post_tag', 'post');
	}
	add_action('init', 'griffith_unregister_tags');

	function griffith_unregister_cats() {
		unregister_taxonomy_for_object_type('category', 'post');
	}
	add_action('init', 'griffith_unregister_cats');

    function griffith_dashboard_name() {
		global $user;
		global $dashboard_settings;
        if ($GLOBALS['title'] != 'Dashboard') {
            return;
        }
        if ($dashboard_settings['id'] == $user) $GLOBALS['title'] = 'Welcome to the ' . $dashboard_settings['brokerage'] . ' Dashboard';
		else $GLOBALS['title'] = __(null); 
    }
    add_action('admin_head', 'griffith_dashboard_name');
	add_filter('use_widgets_block_editor', '__return_false');
	add_filter('use_block_editor_for_post', '__return_false');
?>