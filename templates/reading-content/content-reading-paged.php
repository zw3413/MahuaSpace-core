<?php

	/** Manga Reading Content - paged Style **/

	global $wp_manga, $wp_manga_chapter, $wp_manga_functions;

	$post_id  = get_the_ID();
	$reading_chapter = madara_permalink_reading_chapter();
	if(!$reading_chapter){
		return;
	}
	$name     = $reading_chapter['chapter_slug'];
	$paged    = isset( $_GET[$wp_manga->manga_paged_var] ) ? $_GET[$wp_manga->manga_paged_var] : 1;
	$style    = isset( $_GET['style'] ) ? $_GET['style'] : 'paged';

	$chapter      = $wp_manga_functions->get_single_chapter( $post_id, $reading_chapter['chapter_id'] );
	$in_use       = $chapter['storage']['inUse'];

	$alt_host = isset( $_GET['host'] ) ? $_GET['host'] : null;
	if ( $alt_host ) {
		$in_use = $alt_host;
	}

	$host = $chapter['storage'][ $in_use ]['host'];
	$link = $chapter['storage'][ $in_use ]['page'][ $paged ]['src'];
	$src  = $host . $link;
	
	$src  = apply_filters('wp_manga_chapter_image_url', $host . $link, $host, $link, $post_id, $name);
	?>
	<img id="image-<?php echo esc_attr( $paged ); ?>" src="<?php echo esc_url( $src ); ?>" class ="wp-manga-chapter-img">
