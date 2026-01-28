<div class="resources">
	<div class="wrapper textcenter">
		<h2>Club Resources</h2>
		<div class="clearfix">
			<?php
				$args_resources = array( 
					'post_type' => 'griffith_resources',
					'orderby' => 'meta_value_num',
					'meta_key' => 'griffith_resources_order',
					'order' => 'ASC',
					'posts_per_page' => 4,
					'meta_query' => array(
						array(
							'key' => 'griffith_resources_public',
							'compare' => 'NOT EXISTS'
						)
					)
				);
				$wp_query_resources = new WP_Query( $args_resources);
				set_query_var('postcolumn', 3);	
		    	while ($wp_query_resources->have_posts()) {
					$wp_query_resources->the_post(); 
		    		get_template_part('template-parts/loop/loop', 'resources');	
				}
				wp_reset_query();
			?>	
		</div>
	</div>
</div>