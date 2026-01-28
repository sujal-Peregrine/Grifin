<div class="history">
	<div class="wrapper clearfix">
	<?php
		$args_history = array('pagename' => 'story', 'posts_per_page' => 1);		
		$wp_query_history = new WP_Query($args_history);	
		while($wp_query_history->have_posts()) {
			$wp_query_history->the_post(); 	
			$griffith_img_srcset = wp_get_attachment_image_url(get_post_thumbnail_id(), 'griffith_large_thumb') .' 400w';
			$griffith_img_size = '(max-width: 667px) 130vw, (max-width: 930px) 92vw, (max-width: 1370px) 48vw, 380px';
			$griffith_img_attr = array('srcset' => $griffith_img_srcset, 'sizes' => $griffith_img_size);					
	?>
		<div class="post-col-5">
		<?php 
			if (has_post_thumbnail()) {
				the_post_thumbnail('griffith_large_thumb', $griffith_img_attr);
			}
		?>
		</div>
		<div class="post-col-7">
			<?php the_title('<h2>', '</h2>'); ?>
			<div class="history-desc"><?php the_content(); ?></div>
		</div>
	<?php
		}
	?>
	</div>
</div>