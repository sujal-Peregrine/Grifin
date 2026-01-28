<!DOCTYPE html>
<html class="no-js" <?php language_attributes(); ?>>
<head>
	<meta charset="<?php bloginfo('charset'); ?>" />
	<meta name="viewport" content="width=device-width, initial-scale=1.0" />
	<link rel="profile" href="https://gmpg.org/xfn/11" />
	<?php wp_head(); ?>
</head>
<body <?php body_class(); ?>>
	<?php wp_body_open(); ?>
	<?php 
		if (is_home() || is_front_page()) $griffith_head_class = "home-head";
		else $griffith_head_class = "page-head";
	?>
	<div id="page">
		<header class="<?php echo esc_attr($griffith_head_class); ?>">
			<div class="clearfix wrapper">
				<div class="post-col-4">
					<?php get_template_part('template-parts/header/logo'); ?>
				</div>
				<div class="post-col-8">
					<?php 
						get_template_part('template-parts/header/custom-menu'); 
						get_template_part('template-parts/header/mobile-menu'); 
					?>
					<?php echo do_shortcode('[logout_button]'); ?>
				</div>
			</div>
		</header>
		<?php get_template_part('template-parts/component/top-banner'); ?>