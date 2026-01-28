<?php 
$griffith_logo_id = get_theme_mod('custom_logo');
$griffith_logo = wp_get_attachment_image_src($griffith_logo_id , 'full');
$griffith_logo_path = get_stylesheet_directory_uri() . '/assets/img/logo.png';
?>
<div class="logo">
<?php
	if (has_custom_logo()) {
		echo "<a href='" . esc_url(home_url('/')) . "'>";
		echo "<img src='" . esc_url($griffith_logo[0]) . "' alt='" . get_bloginfo('name') . "' /></a>";
	}
	else {
		echo "<a href='" . esc_url(home_url('/')) . "'>";
		echo "<img src='" . esc_url($griffith_logo_path) . "' alt='" . get_bloginfo('name') . "' /></a>";
	}
?>
</div>