<?php

if($authors != '') {?>
<div class="post-content_item">
	<div class="summary-heading">
		<h5>
			<?php echo esc_html__( 'Author(s)', WP_MANGA_TEXTDOMAIN ); ?>
		</h5>
	</div>
	<div class="summary-content">
		<div class="author-content">
			<?php echo wp_kses_post( $authors ); ?>
		</div>
	</div>
</div>
<?php }?>