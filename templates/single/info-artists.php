<?php

if($artists != '') {?>
<div class="post-content_item">
	<div class="summary-heading">
		<h5>
			<?php echo esc_html__( 'Artist(s)', WP_MANGA_TEXTDOMAIN ); ?>
		</h5>
	</div>
	<div class="summary-content">
		<div class="artist-content">
			<?php echo wp_kses_post( $artists ); ?>
		</div>
	</div>
</div>

<?php } ?>