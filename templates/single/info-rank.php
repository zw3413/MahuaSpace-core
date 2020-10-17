<div class="post-content_item">
	<div class="summary-heading">
		<h5>
			<?php echo esc_html__( 'Rank', WP_MANGA_TEXTDOMAIN ); ?>
		</h5>
	</div>
	<div class="summary-content">
		<?php 
		
		if(method_exists($wp_manga_functions, 'print_ranking_views')){
			$wp_manga_functions->print_ranking_views( $manga_id );
		} else {
			?>
			<?php echo sprintf( _n( ' %1s, it has %2s monthly view', ' %1s, it has %2s monthly views', $views, WP_MANGA_TEXTDOMAIN ), $rank, $views ); ?>
			<?php
		}
		
		 ?>
	</div>
</div>