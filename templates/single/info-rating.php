<div class="post-rating">
	<?php
		$wp_manga_functions->manga_rating_display( $manga_id, true );
	?>
</div>

<div class="post-content_item">
	<div class="summary-heading">
		<h5><?php echo esc_attr__( 'Rating', 'madara' ); ?></h5>
	</div>
	<div class="summary-content vote-details" vocab="https://schema.org/" typeof="AggregateRating">
		<span property="itemReviewed" typeof="Book"><span class="rate-title" property="name" title="<?php echo esc_attr(get_the_title($manga_id));?>"><?php echo esc_html(get_the_title($manga_id));?></span></span><?php echo sprintf(wp_kses(__('<span> <span> Average <span property="ratingValue" id="averagerate"> %s</span> / <span property="bestRating">5</span> </span> </span> out of <span property="ratingCount" id="countrate">%s</span>', 'madara'), array('span' => array('rel'=>1,'typeof'=>1,'property'=>1,'id'=>1))), $rate, $vote ? $vote : 0);?>
	</div>
</div>