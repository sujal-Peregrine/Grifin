<nav>
<?php
wp_nav_menu(array( 
	'theme_location' => 'primary-menu',
	'fallback_cb' => false,
	'container_class' => 'theme-menu floatright',
	'container_id' => 'dropdown',
	'menu_id' => 'main-menu',
	'menu_class' => 'sf-menu clearfix'
));
?>
</nav>