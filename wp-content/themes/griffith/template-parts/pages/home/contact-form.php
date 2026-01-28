<?php $griffith_cform_bg = get_stylesheet_directory_uri() . "/assets/img/cform-bg.jpg"; ?>
<div class="contact-form" data-bg="<?php echo esc_url($griffith_cform_bg); ?>">
	<div class="overlay">&nbsp;</div>
	<div class="wrapper clearfix">
		<div class="post-col-12 textcenter">
			<h2>Contact the Club</h2>
			<div class="width-80p pphone-width-100p center-div post-bottom-3em">
				<p>Contact the Griffith Island administrative team for information about the weather, runway & dock conditions, and any other questions you may have by calling (519) 534-5999 or sending us an email.</p>
			</div>
			<div class="width-80p pphone-width-100p center-div">
				<?php echo do_shortcode('[contact-form-7 id="39"]'); ?>
			</div>
		</div>
	</div>
</div>