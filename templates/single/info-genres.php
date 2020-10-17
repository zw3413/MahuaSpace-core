<?php

if($genres != '') {?>
<div class="post-content_item">
	<div class="summary-heading">
		<h5>
			<?php echo esc_html__( 'Genre(s)', WP_MANGA_TEXTDOMAIN ); ?>
		</h5>
	</div>
	<div class="summary-content">
		<div class="genres-content">
			<?php echo wp_kses_post( $genres ); ?>
		</div>
	</div>
</div>

<?php }