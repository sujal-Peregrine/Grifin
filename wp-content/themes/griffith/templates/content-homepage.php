<?php
/*
	Template Name: Homepage
*/
	get_header(); 
?>
<section class="home-content">
<?php
if (is_user_logged_in()) {
	get_template_part('template-parts/pages/home/club-resources');
}
else {
	get_template_part('template-parts/pages/home/public-resources');
}
?>
</section> 
<?php get_footer(); ?>