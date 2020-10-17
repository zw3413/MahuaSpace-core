<?php
	/** Manga Reading Content - List Style **/

?>

<?php

	global $wp_manga, $wp_manga_chapter, $wp_manga_functions;
	$post_id  = get_the_ID();
	$reading_chapter = madara_permalink_reading_chapter();
	if(!$reading_chapter){
		return;
	}
	$name = $reading_chapter['chapter_slug'];
	$paged    = isset( $_GET[$wp_manga->manga_paged_var] ) ? $_GET[$wp_manga->manga_paged_var] : 1;
	$style    = isset( $_GET['style'] ) ? $_GET['style'] : 'paged';

	if ( $name !== '' ) {
		$reading_chapter = $wp_manga_chapter->get_chapter_by_slug( get_the_ID(), $name );

		if( !$reading_chapter ) {
			return;
		}

		$chapter      = $wp_manga_functions->get_single_chapter( $post_id, $reading_chapter['chapter_id'] );
		$in_use       = $chapter['storage']['inUse'];
		$alt_host     = isset( $_GET['host'] ) ? $_GET['host'] : null;
		if ( $alt_host ) {
			$in_use = $alt_host;
		}
	}
?>

<?php
	foreach ( $chapter['storage'][ $in_use ]['page'] as $page => $link ) {
		$host = $chapter['storage'][ $in_use ]['host'];
		
		$src  = apply_filters('wp_manga_chapter_image_url', $host . $link['src'], $host, $link['src'], $post_id, $name);
		?>
        <div class="page-break">
            <img id="image-<?php echo esc_attr( $page ); ?>" src="<?php echo esc_url( $src ); ?>" class ="wp-manga-chapter-img">
        </div>
	<?php }
