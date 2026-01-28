<?php 
	get_header(); 
	$griffith_img_srcset = wp_get_attachment_image_url(get_post_thumbnail_id(), 'griffith_bigger_image') .' 1200w';
	$griffith_img_size = '(max-width: 667px) 130vw, (max-width: 930px) 92vw, (max-width: 1370px) 96vw, 1200px';
	$griffith_img_attr = array('srcset' => $griffith_img_srcset, 'sizes' => $griffith_img_size);				
?>
	<section class="page-content">
		<div class="wrapper">
			<?php if (has_post_thumbnail()) { ?>
				<div class="page-banner-img">
					<div class="post-col-12">
						<?php the_post_thumbnail('griffith_bigger_image', $griffith_img_attr); ?>
					</div>
				</div>
			<?php } ?>
			<div class="post-col-12">
				<div class="page-desc">
					<?php 
						the_title('<h2>', '</h2>');
						get_template_part('template-parts/component/default-content');
					?>
				</div>
			</div>
            <?php
                $args = array( 
                    'post_type'  => 'attachment',
                    'numberposts' => -1,
                    'order' => 'DESC',
                    'orderby' => 'menu_order',
                    'post_status' => null,
                    'post_parent' => $post->ID,
                    'post__not_in' => array(get_post_thumbnail_id($post->ID))
                );
                $griffith_club_attachments = get_posts($args);
                if ($griffith_club_attachments) {     
                    echo "<div class='hunting-attachments center-items'>";
                    foreach ($griffith_club_attachments as $griffith_club_attachment) {
                        $griffith_large_img_url = wp_get_attachment_image_src($griffith_club_attachment->ID, 'full'); 
                        $griffith_img_asrcset = wp_get_attachment_image_url(get_post_thumbnail_id(), 'griffith_large_thumb') .' 600w';
                        $griffith_img_asize = '(max-width: 667px) 130vw, (max-width: 930px) 92vw, (max-width: 1370px) 48vw, 270px';
                        $griffith_img_aattr = array('srcset' => $griffith_img_asrcset, 'sizes' => $griffith_img_asize);		
          	?>
            <div class="post-col-3 tablet-post-col-4 phone-width-50p pphone-width-100p post-bottom">
            	<a href="<?php echo esc_url($griffith_large_img_url[0]); ?>" title="" class="large-image-popup"><?php echo wp_get_attachment_image($griffith_club_attachment->ID, 'griffith_large_thumb', $griffith_img_aattr); ?></a>
            </div>
            <?php  
                    }
                    echo "</div>";
                }  
            ?>
		</div>
	</section>
<?php get_footer(); ?>