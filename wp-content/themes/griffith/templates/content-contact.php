<?php 
	/*
		Template Name: Contact
	*/
	get_header(); 
?>
<section class="page-content page-contact">
	<div class="wrapper">
		<div class="page-banner-img">
			<div class="post-col-12">
				<iframe src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d22631.216866372066!2d-80.9262115608968!3d44.843926890746125!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x4d2ca4b884553cef%3A0xeaaaf0afab4c7fed!2sGriffith%20Island!5e0!3m2!1sen!2sca!4v1656080897957!5m2!1sen!2sca" width="100%" height="600" style="border:0;" allowfullscreen="" loading="lazy" referrerpolicy="no-referrer-when-downgrade"></iframe>
			</div>
		</div>
		<div class="page-desc contact-desc clearfix">
			<div class="post-col-4">
			<?php 
				the_title('<h2>', '</h2>');
				get_template_part('template-parts/component/default-content');
			?>
			</div>
			<div class="post-col-8">
				<div class="contact-form">
					<?php echo do_shortcode('[contact-form-7 id="39"]'); ?>
				</div>
			</div>
		</div>
	</div>
</section>
<?php get_footer(); ?>