<?php

if($type != '') {?>
<div class="post-content_item">
	<div class="summary-heading">
		<h5>
			<?php echo esc_html__( 'Type', WP_MANGA_TEXTDOMAIN ); ?>
		</h5>
	</div>
	<div class="summary-content">
		<?php echo wp_kses_post( $type ); ?>
	</div>
</div>
<?php } ?>