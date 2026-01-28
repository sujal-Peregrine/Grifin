<div class="btn-close"><a href="javascript:;"><i class="fal fa-times-circle fa-3x"></i></a></div>
<nav>
<?php
wp_nav_menu( array( 
	'theme_location' => 'primary-menu',
	'fallback_cb' => '',
	'container_class' => 'mobile-menu',
	'container_id' => '',
	'menu_id' => 'main-menu',
	'menu_class' => ''
));
?>
</nav>