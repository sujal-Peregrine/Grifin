<div class="hunting">
	<div class="wrapper clearfix">
		<div class="post-col-12">
		<?php
			$args_club = array('pagename' => 'club', 'posts_per_page' => 1);		
			$wp_query_club = new WP_Query($args_club);	
			while($wp_query_club->have_posts()) {
				$wp_query_club->the_post(); 	
				$griffith_img_srcset = wp_get_attachment_image_url( 
				get_post_thumbnail_id(), 'griffith_large_thumb') .' 600w';
				$griffith_img_size = '(max-width: 667px) 130vw, (max-width: 930px) 92vw, (max-width: 1370px) 48vw, 270px';
				$griffith_img_attr = array('srcset' => $griffith_img_srcset, 'sizes' => $griffith_img_size);
		?>
			<div class="textcenter phone-textleft">
				<?php the_title('<h2>', '</h2>'); ?>
			</div>
			<div class="hunting-desc"><?php the_content(); ?></div>
			<div class="hunting-attachments row clearfix">
            <?php
            	$args = array( 
                  	'post_type' => 'attachment',
                  	'numberposts' => 4,
                  	'order' => 'DESC',
                  	'orderby' => 'menu_order',
                  	'post_status' => null,
                  	'post_parent' => $post->ID
                );
                $griffith_club_attachments = get_posts($args);
                if ($griffith_club_attachments) {
                	foreach ($griffith_club_attachments as $griffith_club_attachment) {
						$griffith_large_img_url = wp_get_attachment_image_src($griffith_club_attachment->ID, 'full'); 
            ?>
				<div class="post-col-3 phone-width-50p pphone-width-100p phone-post-bottom">
					<a href="<?php echo esc_url($griffith_large_img_url[0]); ?>" class="large-image-popup"><?php echo wp_get_attachment_image($griffith_club_attachment->ID, 'griffith_large_thumb', $griffith_img_attr); ?></a>
 				</div>
			<?php  
				}
			} 
            ?>
            </div>
		<?php
			}	
		?>
		</div>
	</div>
</div>