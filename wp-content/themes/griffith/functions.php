<?php
	date_default_timezone_set('America/Toronto');
    $dashboard_version = '2024.04';
	$is_login = strtok($_SERVER["REQUEST_URI"], "?");
	if ($is_login == '/wp-login.php') require get_parent_theme_file_path('/inc/login.php');

 	if ($is_login == '/wp-login.php') true;
	$logged_in = false;

	global $pagenow;
	if (is_user_logged_in()) $logged_in = true;
	// if (empty($logged_in) && $pagenow != 'wp-login.php') {
	// 	wp_redirect('/wp-admin/');
	if (empty($logged_in) && !is_admin() && $pagenow != 'wp-login.php') {
		wp_redirect(admin_url());
		exit;
	}

	if (is_admin()) {
		require get_parent_theme_file_path('/inc/dashboard.php');
		require get_parent_theme_file_path('/inc/restrict.php');
	}
	require get_parent_theme_file_path('/inc/optimize.php');
	require get_theme_file_path('/inc/posttypes/font-icons.php');
	require get_theme_file_path('/inc/posttypes/post-resources.php');
	function griffith_font_url() {
		$griffith_fonts_url = "WebFont.load({google:{families:['Roboto:100,300,400,500,700,900,100italic,300italic,400italic,500italic,700italic,900italic']}});";
		return $griffith_fonts_url;
	}
	function griffith_enqueue_script_styles() {
    	wp_enqueue_style('griffith-style', get_stylesheet_uri());
		wp_enqueue_style('griffith-style-minify', get_theme_file_uri('/assets/css/style.min.css'));
    	wp_enqueue_style('font-awesome', get_theme_file_uri('/assets/font-awesome/css/all.min.css'), array(), '5.15.3', 'all');
    	wp_enqueue_script('griffith-webfont', '//ajax.googleapis.com/ajax/libs/webfont/1.6.26/webfont.js', array('jquery'), '', true);
		wp_add_inline_script('griffith-webfont', griffith_font_url());
    	wp_enqueue_script('superfish', get_theme_file_uri('/assets/js/superfish.min.js'), array('jquery'), '', true);
    	wp_enqueue_script('griffith-custom-js', get_stylesheet_directory_uri() . '/assets/js/custom.min.js', array('jquery'), '', true);
		wp_enqueue_style('jquery-magnific', get_theme_file_uri('/assets/js/magnific-popup/magnific-popup.css'),	array('griffith-style'), '');
		wp_enqueue_script('jquery-magnific', get_theme_file_uri('/assets/js/magnific-popup/jquery.magnific-popup.min.js'), array('jquery'), '', true);
    	if (is_front_page()) {
			wp_enqueue_style('flexslider', get_theme_file_uri('/assets/js/flexslider/flexslider.css'), array(), '', 'all');
			wp_enqueue_script('flexslider', get_theme_file_uri('/assets/js/flexslider/jquery.flexslider-min.js'), array('jquery'), '', true);
		}
	} 
	add_action('wp_enqueue_scripts', 'griffith_enqueue_script_styles');
	function griffith_favicons() {
		echo '<link rel="icon" href="' . get_theme_file_uri( '/assets/img/favicon.png' ) . '" sizes="32x32" />' . PHP_EOL;
		echo '<link rel="icon" href="' . get_theme_file_uri( '/assets/img/favicon.png' ) . '" sizes="192x192" />' . PHP_EOL;
		echo '<link rel="apple-touch-icon-precomposed" href="' . get_theme_file_uri( '/assets/img/favicon.png' ) . '" />' . PHP_EOL;
		echo '<meta name="msapplication-TileImage" content="' . get_theme_file_uri( '/assets/img/favicon.png' ) . '" />' . PHP_EOL;
	}
	add_action('wp_head', 'griffith_favicons', 5);
	if (!function_exists('griffith_theme_setup')) {
		function griffith_theme_setup() {
			register_nav_menus( array(
				'primary-menu' => esc_html__('Primary Menu', 'griffith')
			));
			add_theme_support('title-tag');
			add_theme_support('automatic-feed-links');
			add_theme_support('post-thumbnails');
			add_theme_support('html5', array('comment-form', 'comment-list', 'gallery', 'caption'));
			add_theme_support('customize-selective-refresh-widgets');
			add_post_type_support('page', 'excerpt');
			add_theme_support('custom-logo', array(
				'width' => 370,
				'height' => 70,
				'flex-width' => true
			));
			set_post_thumbnail_size(370, 370, true); 
			add_image_size('griffith_thumbs', 400, 400, true);
			add_image_size('griffith_large_thumb', 600, 400, true);
			add_image_size('griffith_large_image', 800, 800, true);
			add_image_size('griffith_agent_thumb', 440, 610, true);
			add_image_size('griffith_bigger_image', 1200, 9999);
			$GLOBALS['content_width'] = 1200;
		}
	}
	add_action('after_setup_theme', 'griffith_theme_setup');
	if (!function_exists('wp_body_open')) {
		function wp_body_open() {
			do_action('wp_body_open');
		}
	}
	if (!function_exists('griffith_custom_excerpt_length')) {
		function griffith_custom_excerpt_length($length) { return 30; }
	}
	add_filter('excerpt_length', 'griffith_custom_excerpt_length', 999);
	if (!function_exists('griffith_script_styles_backend')) {
		function griffith_script_styles_backend() {
			wp_enqueue_media();
			wp_enqueue_style('admin-styles', get_theme_file_uri('/assets/css/admin.min.css'), array(), '', 'all');
			wp_enqueue_script('admin-script', get_theme_file_uri('/assets/js/admin.min.js'), array('jquery'), '', true);
			wp_enqueue_style('font-awesome', get_theme_file_uri('/assets/font-awesome/css/all.min.css'), array(), '5.15.3', 'all');
		}
		add_action('admin_enqueue_scripts', 'griffith_script_styles_backend');
	}
 	if (!function_exists('griffith_image_custom_sizes')) {
		function griffith_image_custom_sizes($sizes) {
			return array_merge($sizes, array(
			'griffith_thumbs' => esc_html__('Theme Thumbnail', 'griffith'),
			'griffith_large_thumb' => esc_html__('Large Thumbnail', 'griffith'),
			'griffith_large_image' => esc_html__('Large Image', 'griffith'),
			'griffith_agent_thumb' => esc_html__('Agent Thumbnail', 'griffith'),
			'griffith_bigger_image' => esc_html__('Bigger Image', 'griffith')
			));
		}
	}
	add_filter('image_size_names_choose', 'griffith_image_custom_sizes');
	if (!function_exists('griffith_sidebar_widgets_init')) {
		function griffith_sidebar_widgets_init() {
			register_sidebar(array(
				'name' => esc_html__('Page Sidebar', 'griffith'),
				'id' => 'griffith_sidebar',
				'description' => esc_html__('Default sidebar widgets for Griffith Island', 'griffith'),
				'before_widget' => '<div id="%1$s" class="widget %2$s post-bottom">',
				'after_widget' => '</div>',
				'before_title' => '<h5>',
				'after_title' => '</h5>'
			));
			register_sidebar(array(
				'name'          => esc_html__('Footer One', 'griffith'),
				'id'            => 'griffith_footer_one',
				'description'   => esc_html__('First footer column widgets for Griffith Island', 'griffith'),
				'before_widget' => '<div id="%1$s" class="widget %2$s post-bottom">',
				'after_widget'  => '</div>',
				'before_title'  => '<h5>',
				'after_title'   => '</h5>',
			));
			register_sidebar(array(
				'name'          => esc_html__('Footer Two', 'griffith'),
				'id'            => 'griffith_footer_two',
				'description'   => esc_html__('Second footer column widgets for Griffith Island', 'griffith'),
				'before_widget' => '<div id="%1$s" class="widget %2$s post-bottom">',
				'after_widget'  => '</div>',
				'before_title'  => '<h5>',
				'after_title'   => '</h5>',
			));
			register_sidebar(array(
				'name'          => esc_html__('Footer Three', 'griffith'),
				'id'            => 'griffith_footer_three',
				'description'   => esc_html__('Third footer column widgets for Griffith Island', 'griffith'),
				'before_widget' => '<div id="%1$s" class="widget %2$s post-bottom">',
				'after_widget'  => '</div>',
				'before_title'  => '<h5>',
				'after_title'   => '</h5>',
			));
		}
	}
	add_action('widgets_init', 'griffith_sidebar_widgets_init');
	function griffith_validate_emails($wpcf7) {
		$submission = WPCF7_Submission::get_instance();
		if ($submission) {
			$postdata = $submission->get_posted_data();
		}
		if (!empty($postdata['subject'])) {
			add_filter('wpcf7_skip_mail', 'abort_mail');
		}
	}
	function abort_mail($wpcf7) {
		return true;
	}
	add_action('wpcf7_before_send_mail', 'griffith_validate_emails');
?>