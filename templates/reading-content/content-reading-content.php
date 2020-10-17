<?php
	/** Manga Reading Content - Text & Video type **/

?>

<?php

	$wp_manga     = madara_get_global_wp_manga();
	$post_id      = get_the_ID();
	
	$reading_chapter = madara_permalink_reading_chapter();
	if(!$reading_chapter){
		get_template_part( 404 );
		exit();
	}
	$name         = $reading_chapter['chapter_slug'];
	$chapter_type = get_post_meta( get_the_ID(), '_wp_manga_chapter_type', true );

	if ( ! $reading_chapter ) {
		return;
	}

	$chapter_content = new WP_Query( array(
		'post_parent' => $reading_chapter['chapter_id'],
		'post_type'   => 'chapter_text_content'
	) );
	
	$server = '';

	if ( $chapter_content->have_posts() ) {

		$post = $chapter_content->the_post();

		setup_postdata( $post );

		?>

		<?php if ( $chapter_type == 'text' ) { ?>

			<?php do_action( 'madara_before_text_chapter_content' ); ?>

            <div class="text-left">
				<?php the_content(); ?>
            </div>

			<?php do_action( 'madara_after_text_chapter_content' ); ?>

		<?php } elseif ( $chapter_type == 'video' ) { ?>

			<?php do_action( 'madara_before_video_chapter_content' ); ?>

            <div class="chapter-video-frame">
				<?php $wp_manga->chapter_video_content($post, $server); ?>
            </div>

			<?php do_action( 'madara_after_video_chapter_content' ); ?>

		<?php } ?>

		<?php

	}

	wp_reset_postdata();
