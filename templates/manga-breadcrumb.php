<?php
	/*
	*  Manga Breadcrumb
	*/

    global $wp_query, $wp_manga_functions, $wp_manga, $wp_manga_chapter, $wp_manga_setting;
	$object   = $wp_query->queried_object;

    $obj_title = $object->post_title;
    $obj_url   = get_the_permalink( $object->ID );
	
	$breadcrumb_all_manga_link = $wp_manga_setting->get_manga_option( 'breadcrumb_all_manga_link', true );
	$breadcrumb_first_genre_link = $wp_manga_setting->get_manga_option( 'breadcrumb_first_genre_link', true );
?>

    <div class="c-breadcrumb-wrapper">
        <div class="c-breadcrumb">
            <ol class="breadcrumb">
                <li>
                    <a href="<?php echo esc_url( home_url() ); ?>">
                        <?php esc_html_e( 'Home', WP_MANGA_TEXTDOMAIN ); ?>
                    </a>
                </li>
				<?php if($breadcrumb_all_manga_link){?>
                <li>
                    <a href="<?php echo esc_url( $wp_manga_functions->get_manga_archive_link() ); ?>">
                        <?php esc_html_e( 'All Mangas', WP_MANGA_TEXTDOMAIN ); ?>
                    </a>
                </li>
				<?php } ?>
                <?php
				
				if($breadcrumb_first_genre_link) {
					
					$middle = $wp_manga->wp_manga_breadcrumb_middle( $object );

					if ( ! empty( $middle ) ) {
						$middle = array_reverse( $middle );

						foreach ( $middle as $name => $link ) { ?>
							<li>
								<a href="<?php echo esc_url( $link ); ?>">
									<?php echo esc_html( $name ); ?>
								</a>
							</li>
						<?php }
					}
				
				}
                ?>

                <?php if ( $object !== null && ( ( $breadcrumb_all_manga_link && ! $wp_manga_functions->is_manga_archive() ) || ! $breadcrumb_all_manga_link ) ) { ?>
                    <li>
                        <a href="<?php echo esc_url( $obj_url ); ?>">
                            <?php echo esc_html( $obj_title ); ?>
                        </a>
                    </li>
                <?php } ?>

                <?php if ( $wp_manga_functions->is_manga_reading_page() ) {
                    $this_chapter = madara_permalink_reading_chapter();

					if ( $this_chapter ) {
						$chapter_slug = $this_chapter['chapter_slug'];
						
                        $chapter_db = $this_chapter;

                        $c_name   = isset( $chapter_db['chapter_name'] ) ? $chapter_db['chapter_name'] : '';
                        $c_extend = $wp_manga_functions->filter_extend_name( $chapter_db['chapter_name_extend'] );

                        if ( isset( $c_name ) ) {
                            ?>
                            <li class="active">
                                <?php echo esc_html( $c_name . $c_extend ); ?>
                            </li>
                            <?php
                        }
                    }
                } ?>

            </ol>
        </div>

        <?php if ( $wp_manga_functions->is_manga_reading_page() ) { ?>
            <div class="action-icon">
                <ul class="action_list_icon list-inline">
                    <li>
                        <?php echo $wp_manga_functions->create_bookmark_link(); ?>
                    </li>
                </ul>
            </div>
        <?php } ?>
    </div>
