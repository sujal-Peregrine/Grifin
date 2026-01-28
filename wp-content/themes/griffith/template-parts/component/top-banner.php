<?php 
  $griffith_img_srcset = wp_get_attachment_image_url(get_post_thumbnail_id(), 'full');
  $griffith_img_size = '(max-width: 667px) 130vw, (max-width: 930px) 92vw, (max-width: 1370px) 48vw, 380px';
  $griffith_img_attr = array('srcset' => $griffith_img_srcset, 'sizes' => $griffith_img_size);          
?>
<?php if (is_home() || is_front_page()) { ?>
<section class="top-banner textcenter">
	<div class="banner-main">
      	<div class="banner-image">
        	<div class="overlay">&nbsp;</div>
        	<?php 
          		if (is_front_page()) {
            		if (has_post_thumbnail()) {
              			the_post_thumbnail('full', $griffith_img_attr);
					}
					else {
            ?>
			<div class="banner-flexslider phone-post-bottom">
            	<ul class="slides">
                <?php
					$args = array(
                        'post_type' => 'attachment',
                        'numberposts' => -1,
                        'order' => 'DESC',
                        'orderby' => 'menu_order',
                        'post_status' => null,
                        'post_parent' => $post->ID
                      );
                      $griffith_attachments = get_posts($args);
                      if ($griffith_attachments) {
                        	foreach ($griffith_attachments as $griffith_attachment) {
				?>
					<li><?php echo wp_get_attachment_image($griffith_attachment->ID, 'full', $griffith_img_attr); ?></li>
				<?php
							}
					  }
				?>
				</ul>
			</div>
            <?php
					}
				}
        	?>
      	</div>
      	<div class="banner-title center-items-hv">
        	<div class="wrapper">
				<?php if (is_user_logged_in()) { ?>
          		<div class="post-col-12">
            		<?php get_template_part('template-parts/component/default-content'); ?>
            		<a href="<?php echo esc_url(home_url('/guest-documents/')) ?>" class="btn btn-banner"><i class="fas fa-folder"></i>Guest Documents</a>
          		</div>
				<?php } else { ?>
          		<div class="post-col-12">
            		<?php get_template_part('template-parts/component/default-content'); ?>
            		<a href="<?php echo esc_url(home_url('/login/')) ?>" class="btn btn-banner"><i class="fas fa-user-lock"></i>Member Login</a>
          		</div>
				<?php } ?>
        	</div>
      	</div>
      	<div class="arrow-right"></div>
      	<div class="arrow-left"></div>
	</div>
</section>
<?php } ?>