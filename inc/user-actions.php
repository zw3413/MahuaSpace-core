<?php

	class WP_MANGA_USER_ACTION {

		public $user_actions;

		public function __construct() {

			add_shortcode( 'manga-user-page', array( $this, 'wp_manga_user_page' ) );

			add_action( 'admin_bar_menu', array( $this, 'add_admin_bar_manga_settings' ), 10, 1 );

			add_action( 'wp', array( $this, 'wp_manga_user_page_redirect' ) );

			add_action( 'manga_chapter_inserted', array( $this, 'update_user_new_chapters' ), 10, 2 );

			add_action( 'wp', array( $this, 'send_users_notifications' ) );

			add_action( 'manga_history', array( $this, 'remove_unread_chapter' ), 10, 2 );

			add_action( 'wp_footer', array( $this, 'get_user_player_id' ) );

		}

		function wp_manga_user_page() {

			global $wp_manga_template;

			$content = '';
			ob_start();
			$wp_manga_template->load_template( 'user/settings', '', true );
			$content = ob_get_contents();
			ob_end_clean();
			

			return $content;

		}

		function wp_manga_user_page_redirect() {

			global $wp_manga_setting;

			$user_page = $wp_manga_setting->get_manga_option( 'user_page' );

			if ( $user_page == get_the_ID() && ! is_user_logged_in() ) {
				wp_safe_redirect( home_url( '/' ), 302 );
			}

		}

		function wp_manga_user_section() {

			global $wp_manga_functions;

			$html = '<div class="c-modal_item">';

			if ( is_user_logged_in() ) {

				$html .= $this->get_user_section();

			} else {

				$html .= '<a href="javascript:void(0)" data-toggle="modal" data-target="#form-login" class="btn-active-modal">' . esc_html__( 'Sign in', WP_MANGA_TEXTDOMAIN ) . '</a>';
				$html .= '<a href="javascript:void(0)" data-toggle="modal" data-target="#form-sign-up" class="btn-active-modal">' . esc_html__( 'Sign up', WP_MANGA_TEXTDOMAIN ) . '</a>';

			}

			$html .= '</div>';

			return $html;

		}

		function get_user_section( $size = 50, $echo = false ) {

			global $wp_manga_setting, $wp;

			if ( ! is_user_logged_in() ) {
				return false;
			}

			$html = '';

			$current_user = wp_get_current_user();
			$link = $this->get_user_setting_page_url();

			$user_menu = '';

			$html = '<div class="c-user_item">';

			$html .= '<span>' . esc_html__( 'Hi, ', WP_MANGA_TEXTDOMAIN ) . $current_user->display_name . '</span>';
			$html .= '<div class="c-user_avatar">';
			$html .= '<div class="c-user_avatar-image">';
			$html .= get_avatar( $current_user->ID, $size );

			$notify_num = $this->get_user_notify_num();

			if ( ! empty( $notify_num ) ) {
				$html .= '<div class="c-user_notify">' . $notify_num . '</div>';
			}
			
			$html .= '</div>';

			$html .= '<ul class="c-user_menu">';
			
			$user_menu .= apply_filters('wp_manga_user_menu_before_items', '');
			
			if ( ! empty( $notify_num ) && ! empty( $link ) ) {
				$user_menu .= '<li><a href="' . esc_url( "{$link}?tab=bookmark" ) . '">' . esc_html__( 'New Chapter', WP_MANGA_TEXTDOMAIN ) . ' (' . $notify_num . ')</a></li>';
			}

			//get menu items
			if ( $menu_items = $this->get_user_menu_items() ) {
				$user_menu .= implode( '', $menu_items );
			}
			if ( ! empty( $link ) ) {
				$user_menu .= '
					<li>
						<a href="' . esc_url( $link ) . '">' . esc_html__( 'User Settings', WP_MANGA_TEXTDOMAIN ) . '</a>
					</li>';
			}
			
			$user_menu .= apply_filters('wp_manga_user_menu_after_items', '');

			$user_menu .= '
                <li>
                    <a href="' . wp_logout_url( home_url( $wp->request ) ) . '">' . esc_html__( 'Logout', WP_MANGA_TEXTDOMAIN ) . '</a>
                </li>
            ';

			$user_menu = apply_filters( 'madara_user_menu_profile', $user_menu );

			$html .= $user_menu . '</ul>';
			$html .= '</div>';
			$html .= '</div>';

			if($echo) {
				echo apply_filters( 'madara_user_profile', $html, $size );
			} else {
				return apply_filters( 'madara_user_profile', $html, $size );
			}

		}

		function get_user_menu_items() {
			if ( has_nav_menu( 'user_menu' ) ) {

				$output = '';
				ob_start();
				wp_nav_menu( array( 'theme_location' => 'user_menu' ) );
				$output = ob_get_contents();
				ob_end_clean();

				preg_match_all( '/<li(.+)<\/li>/', $output, $matches );

				if ( ! empty( $matches[0] ) ) {
					return apply_filters('wp_manga_user_menu_items', $matches[0]);
				}

			}

			return false;

		}

		function get_user_setting_page_url() {

			global $wp_manga_setting;

			$user_page = $wp_manga_setting->get_manga_option( 'user_page', null );

			if(function_exists('pll_get_post')){
				return get_the_permalink( pll_get_post($user_page) );
			}
			else {
				return get_the_permalink( $user_page );
			}
		}

		function get_user_tab_url( $tab ) {

			$user_page_url = $this->get_user_setting_page_url();

			if ( empty( $user_page_url ) ) {
				return false;
			}

			return add_query_arg( [ 'tab' => $tab ], $user_page_url );

		}

		function add_admin_bar_manga_settings( $bar ) {

			global $wp_manga_setting;

			$user_page = $wp_manga_setting->get_manga_option( 'user_page' );

			if ( $user_page ) {
				$link = get_the_permalink( $user_page );
				$bar->add_node( array(
					'id'     => 'wp-manga-settings',
					'title'  => __( 'Manga Settings', WP_MANGA_TEXTDOMAIN ),
					'href'   => $link,
					'parent' => 'user-actions',
				) );
			}
		}

		function user_save_settings() {

			global $wp_manga_setting;

			if ( ! isset( $_POST['_wpnonce'] ) || ! wp_verify_nonce( esc_html( $_POST['_wpnonce'] ), '_wp_manga_save_user_settings' ) ) {
				return false;
			}

			$current_user = get_current_user_id();

			$reading_style = isset( $_POST['reading-style'] ) ? $_POST['reading-style'] : 'paged';

			update_user_meta( $current_user, '_manga_reading_style', $reading_style );
			wp_safe_redirect( get_the_permalink( $wp_manga_setting->get_manga_option( 'user_page' ) ) . '#setting' );
			exit;

		}

		function get_user_notify_num( $user_id = null, $post_id = null ) {

			if ( empty( $user_id ) ) {
				$user_id = get_current_user_id();
			}

			if ( empty( $user_id ) || ! is_user_logged_in() ) {
				return '';
			}

			$user_bookmarks = get_user_meta( $user_id, '_wp_manga_bookmark', true );

			if ( empty( $user_bookmarks ) ) {
				return '';
			}

			// If this is getting notify of specific manga
			if ( $post_id ) {
				$manga = $this->get_manga_bookmark( $post_id, $user_bookmarks );

				if ( empty( $manga ) || empty( $manga['unread_c'] ) ) {
					return '';
				}

				return count( isset($manga['unread_c']) ? $manga['unread_c'] : array() );

			} else {
				$col = 'unread_c';
				$mangas = array_map(function($element) use($col ){return $element[$col ];}, $user_bookmarks);

				$total = 0;
				foreach ( $mangas as $manga ) {
					$total += is_array( $manga ) ? count( $manga ) : 0;
				}

				return $total;

			}
		}

		function get_manga_bookmark_index( $post_id, $bookmarks ) {

			if ( ! is_array( $bookmarks ) ) {
				return false;
			}
			$col = 'id';
			$ids = array_map(function($element) use($col ){return $element[$col ];}, $bookmarks);

			if ( ! is_array( $ids ) ) {
				return false;
			}

			return // Find index of bookmark that user bookmarked this manga
				$index = array_search( $post_id, $ids );
		}

		// Get Manga from user bookmark array
		function get_manga_bookmark( $post_id, $bookmarks ) {

			$index = $this->get_manga_bookmark_index( $post_id, $bookmarks );

			if ( $index === false ) {
				return $index;
			}

			return isset($bookmarks[ $index ]) ? $bookmarks[ $index ] : null;

		}

		function update_user_new_chapters( $chapter_id, $chapter_args ) {

			$is_notify_enable  = $this->is_new_chapter_notify_enable();
			$is_webpush_enable = $this->is_webpush_enable();

			if ( empty( $is_notify_enable ) ) {
				return;
			}

			$post_id = $chapter_args['post_id'];

			// Get the latest chapter
			$users_bookmarked = get_post_meta( $post_id, '_wp_user_bookmarked', true );

			if ( empty( $users_bookmarked ) ) {
				return;
			}

			$cur_notifications = get_transient( '_wp_manga_chapter_notifications' );
			
			global $wp_manga_setting;
			$max_bookmark_count = $wp_manga_setting->get_manga_option( 'user_bookmark_max', 30 );

			foreach ( $users_bookmarked as $user_id ) {

				// All manga that user bookmarked
				$user_bookmark = get_user_meta( $user_id, '_wp_manga_bookmark', true );
				
				// only store $max_bookmark_count latest item in users_bookmarked;
				if(count($user_bookmark) > $max_bookmark_count){
					$user_bookmark = array_slice($user_bookmark, count($user_bookmark) - $max_bookmark_count);
				}
				
				$index = $this->get_manga_bookmark_index( $post_id, $user_bookmark );

				if ( $index === false || empty( $user_bookmark[ $index ] ) ) {
					continue;
				}				

				// This bookmark
				if ( ! is_array( $user_bookmark[ $index ]['unread_c'] ) ) {
					$user_bookmark[ $index ]['unread_c'] = array( $chapter_id );
				} else {
					$user_bookmark[ $index ]['unread_c'][] = $chapter_id;
				}
				
				if(count($user_bookmark[ $index ]['unread_c']) > 20){
				    // reduce number of unread chapters, so the $user_bookmark is not big
					$temp = array_slice($user_bookmark[$index]['unread_c'], count($user_bookmark[ $index ]['unread_c']) - 20);
					$user_bookmark[ $index ]['unread_c'] = $temp;
				}

				$resp = update_user_meta( $user_id, '_wp_manga_bookmark', $user_bookmark );

				if ( $resp && $is_webpush_enable ) {
					if(empty($cur_notifications)) {
						
						$cur_notifications = array();
						
					}
					
					if(!isset($cur_notifications[ $post_id ])) {
						
						$cur_notifications[ $post_id ] = array();
						
					}
					
					if(!isset($cur_notifications[ $post_id ][ $chapter_id ])) {
						
						$cur_notifications[ $post_id ][ $chapter_id ] = array();
						
					}
					
					$cur_notifications[ $post_id ][ $chapter_id ][] = $user_id;
				}

			}

			if ( $is_webpush_enable ) {
				set_transient( '_wp_manga_chapter_notifications', $cur_notifications );
			}

		}

		function send_users_notifications() {

			if ( ! $this->is_onesignal_active() ) {
				return;
			}

			global $wp_manga_functions;

			$notifications = get_transient( '_wp_manga_chapter_notifications' );

			if ( empty( $notifications ) && ! is_array( $notifications ) ) {
				return;
			}

			// Send 10 notifications each request
			$index = 1;

			foreach ( $notifications as $post_id => $chapters ) {

				// If this is manga chapter type then check if it's uploaded completed
				$completed_chapters = $wp_manga_functions->get_chapter( $post_id, true );

				foreach ( $chapters as $chapter_id => $users ) {

					if ( $index == 11 ) {
						break;
					}

					// Check if chapter is upload completed, or else skip
					if ( ! isset( $completed_chapters[ $chapter_id ] ) ) {
						continue;
					}

					$users_player_ids = array();

					foreach ( $users as $user ) {

						// an user could have more than one player ids
						$player_ids = get_user_meta( $user, '_onesignal_player_id', true );

						if ( ! empty( $player_ids ) ) {
							$users_player_ids = array_merge( $users_player_ids, $player_ids );
						}
					}

					if ( ! empty( $users_player_ids ) ) {
						$resp = $this->send_notification( $post_id, $chapter_id, $users_player_ids );

						if ( is_wp_error( $resp ) ) {
							error_log( $resp->get_error_message() );
						} else {
							unset( $notifications[ $post_id ][ $chapter_id ] );
						}

						$index ++;
					}

				}
			}

			return set_transient( '_wp_manga_chapter_notifications', $notifications );

		}

		function send_notification( $post_id, $chapter_id, $player_ids ) {

			if ( ! $this->is_onesignal_active() ) {
				return new WP_Error( 'OneSignal', 'OneSignal is not activated' );
			}

			global $wp_manga_setting, $wp_manga_chapter, $wp_manga_functions;

			// Prepare content
			$content = $wp_manga_setting->get_manga_option( 'webpush_noti_content', '%manga% has new chapter %chapter%!' );

			$manga_name   = get_the_title( $post_id );
			$chapter      = $wp_manga_chapter->get_chapter_by_id( $post_id, $chapter_id );
			$chapter_name = $wp_manga_functions->parse_chapter_full_name( $chapter );

			if ( empty( $chapter ) ) {
				return new WP_Error( '404', esc_html__('Chapter not found', WP_MANGA_TEXTDOMAIN) );
			}

			$content = str_replace( '%manga%', $manga_name, $content );
			$content = str_replace( '%chapter%', $chapter_name, $content );

			if ( empty( $player_ids ) || ! is_array( $player_ids ) ) {
				return false;
			}

			// Content
			$notif_content = OneSignalUtils::decode_entities( $content );

			// Url
			$page_style = apply_filters('wp_manga_default_notification_chapter_reading_style', 'paged');
			$url = $wp_manga_functions->build_chapter_url( $post_id, $chapter['chapter_slug'], $page_style );

			// Title
			$os_settings = OneSignal::get_onesignal_settings();
			$site_title  = $os_settings['notification_title'] != "" ? $os_settings['notification_title'] : get_bloginfo( 'name' );

			$site_title = OneSignalUtils::decode_entities( $site_title );

			$fields = array(
				'app_id'             => $os_settings['app_id'],
				'headings'           => array( "en" => $site_title ),
				'include_player_ids' => $player_ids,
				'isAnyWeb'           => true,
				'url'                => $url,
				'contents'           => array( "en" => $notif_content )
			);

			$send_to_mobile = $os_settings['send_to_mobile_platforms'];
			if ( $send_to_mobile == true ) {
				$fields['isIos']     = true;
				$fields['isAndroid'] = true;
			}

			if ( ! empty( $os_settings['utm_additional_url_params'] ) ) {
				$fields['url'] .= '?' . $os_settings['utm_additional_url_params'];
			}

			if ( has_post_thumbnail( $post_id ) ) {

				$post_thumbnail_id = get_post_thumbnail_id( $post_id );

				// Higher resolution (2x retina, + a little more) for the notification small icon
				$thumb_sized = wp_get_attachment_image_src( $post_thumbnail_id, array( 192, 192 ), true );

				// Much higher resolution for the notification large image
				$large_sized = wp_get_attachment_image_src( $post_thumbnail_id, 'large', true );

				$use_ft_image_icon = $os_settings['showNotificationIconFromPostThumbnail'] == "1";

				$use_ft_image_image = $os_settings['showNotificationImageFromPostThumbnail'] == "1";

				// get the icon image from wordpress if it exists
				if ( $use_ft_image_icon ) {
					$thumb_image = $thumb_sized[0];

					// set the icon image for both chrome and firefox-1
					$fields['chrome_web_icon'] = $thumb_image;
					$fields['firefox_icon']    = $thumb_image;
				}

				if ( $use_ft_image_image ) {
					$fields['chrome_web_image'] = $large_sized[0];
				}
			}

			$ch = curl_init();

			$api_url = "https://onesignal.com/api/v1/notifications";

			$onesignal_auth_key = $os_settings['app_rest_api_key'];

			curl_setopt( $ch, CURLOPT_URL, $api_url );
			curl_setopt( $ch, CURLOPT_HTTPHEADER, array(
				'Content-Type: application/json',
				'Authorization: Basic ' . $onesignal_auth_key
			) );
			curl_setopt( $ch, CURLOPT_RETURNTRANSFER, true );
			curl_setopt( $ch, CURLOPT_HEADER, false );
			curl_setopt( $ch, CURLOPT_POST, true );
			curl_setopt( $ch, CURLOPT_POSTFIELDS, json_encode( $fields ) );
			curl_setopt( $ch, CURLOPT_SSL_VERIFYPEER, false );

			$response = curl_exec( $ch );

			$curl_http_code = curl_getinfo( $ch, CURLINFO_HTTP_CODE );

			if ( $curl_http_code != 200 ) {

				if ( $curl_http_code != 0 ) {

					set_transient( 'onesignal_transient_error', '<div class="error notice onesignal-error-notice">
					<p><strong>OneSignal Push:</strong><em> There was a ' . $curl_http_code . ' error sending your notification:</em></p>
					<pre>' . print_r( $response, true ) . '</pre>
					</div>', 86400 );

				} else {

					// A 0 HTTP status code means the connection couldn't be established
					set_transient( 'onesignal_transient_error', '<div class="error notice onesignal-error-notice">
					<p><strong>OneSignal Push:</strong><em> There was an error establishing a network connection. Please make sure outgoing network connections from cURL are allowed.</em></p>
					</div>', 86400 );

				}

			} else {

				$parsed_response = json_decode( $response, true );

				if ( ! empty( $parsed_response ) ) {
					// API can send a 200 OK even if the notification failed to send
					$recipient_count   = $parsed_response['recipients'];
					$sent_or_scheduled = array_key_exists( 'send_after', $fields ) ? 'scheduled' : 'sent';

					$show_send_status_msg = $os_settings['show_notification_send_status_message'] == "1";

					if ( $show_send_status_msg ) {
						if ( $recipient_count != 0 ) {
							set_transient( 'onesignal_transient_success', '<div class="updated notice notice-success is-dismissible">
							<p><strong>OneSignal Push:</strong><em> Successfully ' . $sent_or_scheduled . ' a notification to ' . $parsed_response['recipients'] . ' recipients.</em></p>
							</div>', 86400 );
						} else {
							set_transient( 'onesignal_transient_success', '<div class="updated notice notice-success is-dismissible">
							<p><strong>OneSignal Push:</strong><em> A notification was ' . $sent_or_scheduled . ', but there were no recipients.</em></p>
							</div>', 86400 );
						}
					}
				}
			}

			curl_close( $ch );

			OneSignal_Admin::update_last_sent_timestamp();

			return $response;

		}

		function is_new_chapter_notify_enable() {

			global $wp_manga_setting;

			return $wp_manga_setting->get_manga_option( 'new_chap_notify', false );

		}

		function is_webpush_enable() {

			global $wp_manga_setting;

			return $wp_manga_setting->get_manga_option( 'webpush_noti', false );

		}

		function is_onesignal_active() {

			if ( ! class_exists( 'OneSignal_Admin' ) || ! class_exists( 'OneSignalUtils' ) ) {
				return false;
			}

			$os_settings = OneSignal::get_onesignal_settings();

			if ( empty( $os_settings['app_id'] ) || empty( $os_settings['app_rest_api_key'] ) ) {
				return false;
			}

			return true;

		}

		function remove_unread_chapter( $user_id, $history ) {

			if ( empty( $user_id ) || empty( $history ) ) {
				return;
			}

			$user_bookmarks = get_user_meta( $user_id, '_wp_manga_bookmark', true );

			$index = $this->get_manga_bookmark_index( $history['id'], $user_bookmarks );

			if ( $index === false || empty( $user_bookmarks[ $index ] ) ) {
				return;
			}

			if ( ! empty( $user_bookmarks[ $index ]['unread_c'] ) && is_array( $user_bookmarks[ $index ]['unread_c'] ) ) {
				foreach ( $user_bookmarks[ $index ]['unread_c'] as $c_index => $chap ) {
					if ( $chap == $history['c'] ) {
						unset( $user_bookmarks[ $index ]['unread_c'][ $c_index ] );
					}
				}
			}

			update_user_meta( $user_id, '_wp_manga_bookmark', $user_bookmarks );

		}

		function get_user_player_id() {

			if ( is_user_logged_in() && $this->is_onesignal_active() ) {

				$user_id    = get_current_user_id();
				$player_ids = get_user_meta( $user_id, '_onesignal_player_id', true );

				?>
                <script type="text/javascript">
					OneSignal.push(function () {
						OneSignal.getUserId(function (userId) {

							var curUserIds = <?php echo json_encode( $player_ids ); ?>;

							if ('' != userId && curUserIds.indexOf(userId) == -1) {
								jQuery.ajax({
									method: "POST",
									url: "<?php echo esc_url( admin_url( 'admin-ajax.php' ) ); ?>",
									data: {
										action: 'save_user_player_id',
										userID: '<?php echo $user_id; ?>',
										playerID: userId,
									},
								});
							}
						});
					});
                </script>
				<?php
			}

		}

	}

	$GLOBALS['wp_manga_user_actions'] = new WP_MANGA_USER_ACTION();
