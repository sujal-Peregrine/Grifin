<div class="post-col-<?php echo $postcolumn; ?> phone-width-50p pphone-width-100p phone-post-bottom">
	<div class="resources-list">
		<div class="club-icon">
			<i class="<?php echo esc_html($post->griffith_resources_icon); ?> fa-3x"></i>
		</div>
		<?php
			the_title('<h4>', '</h4>'); 
			echo wpautop(esc_html($post->griffith_resources_excerpt));
		?>
		<a href="<?php echo esc_url($post->griffith_resources_link); ?>" class="btn btn-small"><?php echo esc_html($post->griffith_resources_button); ?></a>
	</div>
</div>