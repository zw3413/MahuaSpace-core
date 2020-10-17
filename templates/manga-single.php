<?php

	/** Single template of Manga **/

	get_header();

	use App\Madara;

	$wp_manga           = madara_get_global_wp_manga();
	$wp_manga_functions = madara_get_global_wp_manga_functions();
	$thumb_size         = array( 193, 278 );
	$post_id            = get_the_ID();

	$madara_single_sidebar      = madara_get_theme_sidebar_setting();
	$madara_breadcrumb          = Madara::getOption( 'manga_single_breadcrumb', 'on' );
	$manga_profile_background   = madara_output_background_options( 'manga_profile_background' );
	$manga_single_summary       = Madara::getOption( 'manga_single_summary', 'on' );

	$wp_manga_settings = get_option( 'wp_manga_settings' );
	$related_manga     = isset( $wp_manga_settings['related_manga'] ) ? $wp_manga_settings['related_manga'] : null;
?>


<?php do_action( 'before_manga_single' ); ?>
<div <?php post_class();?>>
<div class="profile-manga" style="<?php echo esc_attr( $manga_profile_background != '' ? $manga_profile_background : 'background-image: url(' . get_parent_theme_file_uri( '/images/bg-search.jpg' ) . ');' ); ?>">
    <div class="container">
        <div class="row">
            <div class="col-12 col-sm-12 col-md-12">
				<?php
					if ( $madara_breadcrumb == 'on' ) {
						get_template_part( 'madara-core/manga', 'breadcrumb' );
					}
				?>
                <div class="post-title">
                    <h1>
						<?php madara_manga_title_badges_html( $post_id, 1 ); ?>

						<?php echo esc_html( get_the_title() ); ?>
                    </h1>
                </div>
                <div class="tab-summary <?php echo has_post_thumbnail() ? '' : esc_attr( 'no-thumb' ); ?>">

					<?php if ( has_post_thumbnail() ) { ?>
                        <div class="summary_image">
                            <a href="<?php echo get_the_permalink(); ?>">
								<?php echo madara_thumbnail( $thumb_size ); ?>
                            </a>
                        </div>
					<?php } ?>
                    <div class="summary_content_wrap">
                        <div class="summary_content">
                            <div class="post-content">
								<?php get_template_part( 'html/ajax-loading/ball-pulse' ); ?>
                                
								<?php do_action('wp-manga-manga-properties', $post_id);?>
								
								<?php do_action('wp-manga-after-manga-properties', $post_id);?>
                            </div>
                            <div class="post-status">
							
								<?php do_action('wp-manga-manga-status', $post_id);?>

                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="c-page-content style-1">
    <div class="content-area">
        <div class="container">
            <div class="row <?php echo esc_attr( $madara_single_sidebar == 'left' ? 'sidebar-left' : '' ) ?>">
                <div class="main-col <?php echo esc_attr( $madara_single_sidebar !== 'full' && ( is_active_sidebar( 'manga_single_sidebar' ) || is_active_sidebar( 'main_sidebar' ) ) ? ' col-md-8 col-sm-8' : 'col-md-12 col-sm-12 sidebar-hidden' ) ?>">
                    <!-- container & no-sidebar-->
                    <div class="main-col-inner">
                        <div class="c-page">
                            <!-- <div class="c-page__inner"> -->
                            <div class="c-page__content">

								<?php if ( get_the_content() != '' ) { ?>

                                    <div class="c-blog__heading style-2 font-heading">

                                        <h4>
                                            <i class="<?php madara_default_heading_icon(); ?>"></i>
											<?php echo esc_attr__( 'Summary', 'madara' ); ?>
                                        </h4>
                                    </div>

                                    <div class="description-summary">

                                        <div class="summary__content <?php echo( esc_attr($manga_single_summary == 'on' ? 'show-more' : '' )); ?>">
											<?php the_content(); ?>
                                        </div>

										<?php if ( $manga_single_summary == 'on' ) { ?>
                                            <div class="c-content-readmore">
                                                <span class="btn btn-link content-readmore">
                                                    <?php echo esc_html__( 'Show more  ', 'madara' ); ?>
                                                </span>
                                            </div>
										<?php } ?>

                                    </div>

								<?php } ?>
								
								<?php do_action('wp-manga-chapter-listing', $post_id); ?>
                            </div>
                            <!-- </div> -->
                        </div>
						<?php edit_post_link(esc_html__('Edit This Manga', 'madara'));?>
						
                        <!-- comments-area -->
						<?php do_action( 'wp_manga_discussion' ); ?>
                        <!-- END comments-area -->

						<?php

							if ( $related_manga == 1 ) {
								get_template_part( '/madara-core/manga', 'related' );
							}

							if ( class_exists( 'WP_Manga' ) ) {
								$GLOBALS['wp_manga']->wp_manga_get_tags();
							}
						?>

                    </div>
                </div>

				<?php
					if ( $madara_single_sidebar != 'full' && ( is_active_sidebar( 'main_sidebar' ) || is_active_sidebar( 'manga_single_sidebar' ) ) ) {
						?>
                        <div class="sidebar-col col-md-4 col-sm-4">
							<?php get_sidebar(); ?>
                        </div>
					<?php }
				?>

            </div>
        </div>
    </div>
</div>

<?php do_action( 'after_manga_single' ); ?>
</div>
<?php get_footer();