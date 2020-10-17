<?php

add_filter('wpseo_sitemaps_providers', 'madara_chapter_sitemap_provider');

function madara_chapter_sitemap_provider( $providers ){
	require WP_MANGA_DIR . '/inc/yoast/madara_wpseo_sitemap_provider.php';
	
	$madara_sitemap = new Madara_Sitemap_Provider();
	
	array_push( $providers, $madara_sitemap);
	
	return $providers;
}

add_action('init', 'madara_add_chapter_feed');
if(!function_exists('madara_add_chapter_feed')){
	function madara_add_chapter_feed(){
		add_feed('manga-chapters', 'madara_build_chapter_feed');
	}
}

if(!function_exists('madara_build_chapter_feed')){
	function madara_build_chapter_feed(){
		global $wp_manga_template;
		
		$wp_manga_template->load_template( 'manga', 'feed', true );
	}
}

// support Photon API
add_action('init', 'wp_manga_init_photon_url');
function wp_manga_init_photon_url(){
	if(class_exists( 'Jetpack' ) && method_exists( 'Jetpack', 'get_active_modules' ) && in_array( 'photon', Jetpack::get_active_modules() )){
		add_filter('wp_manga_chapter_image_url', 'wp_manga_chapter_image_url_photon', 10, 5);
	}
}

if(!function_exists('wp_manga_chapter_image_url_photon')){
	function wp_manga_chapter_image_url_photon($url, $host, $relative_url, $manga_id, $chapter_slug){
		return jetpack_photon_url($url);
	}
}

add_action('wp_manga_before_chapter_content', 'wp_manga_before_chapter_content_warning_text', 10, 2);
if(!function_exists('wp_manga_before_chapter_content_warning_text')){
	function wp_manga_before_chapter_content_warning_text($chapter_slug, $manga_id){
		global $wp_manga_functions;
		$chapter = $wp_manga_functions->get_chapter_by_slug( $manga_id, $chapter_slug );
		if($chapter && isset( $chapter['chapter_warning'] ) && $chapter['chapter_warning'] != ''){
			echo '<div class="chapter-warning alert alert-warning">';
			echo $chapter['chapter_warning'];
			echo '</div>';
		}
	}
}

add_filter('madara_user_settings_tab_array_compare', 'madara_user_settings_tabs');
function madara_user_settings_tabs( $tabs ){
	// get mangas by author
	global $mymangas;
	
	$my_mangas_args = array('author' => get_current_user_id(), 'orderby' => 'name', 'order' => 'ASC');
	
	$my_mangas_args = apply_filters('madara_my_manga_list_args', $my_mangas_args);
	$mymangas = madara_manga_query($my_mangas_args);
	
	if(!$mymangas->have_posts() & isset($tabs['my-mangas'])){
		unset($tabs['my-mangas']);
	}
	
	$wp_manga_settings = get_option( 'wp_manga_settings' );
	$user_bookmark = isset($wp_manga_settings['user_bookmark']) ? $wp_manga_settings['user_bookmark'] : 1;
	
	if(!$user_bookmark && isset($tabs['bookmark'])) {
		unset($tabs['bookmark']);
	}
	
	return $tabs;
}

include 'upload/googlephotos.php';
include 'helper.php';

add_filter('comment_moderation_subject', 'madara_comment_moderation_subject', 10, 2);
function madara_comment_moderation_subject($subject, $comment_id){
	if($chapter_id = get_comment_meta( $comment_id, 'chapter_id', true)){
		global $wp_manga_chapter;
		$chapter = $wp_manga_chapter->get_chapter_by_id(null, $chapter_id);
		
		if($chapter){
			$subject .= ' - ' . $chapter['chapter_name'];
		}
	}
	
	return $subject;
}
add_filter('comment_moderation_text', 'madara_comment_moderation_text', 10, 2);
function madara_comment_moderation_text($notify_message, $comment_id){
	if($chapter_id = get_comment_meta( $comment_id, 'chapter_id', true)){
		global $wp_manga_chapter;
		$chapter = $wp_manga_chapter->get_chapter_by_id(null, $chapter_id);
		
		if($chapter){
			$comment = get_comment( $comment_id );
			global $wp_manga_functions;
			
			if(isset($wp_manga_functions)){
				$post    = get_post( $comment->comment_post_ID );
				$chapter_url    = $wp_manga_functions->build_chapter_url( $comment->comment_post_ID, $chapter );
				$comment_author_domain = @gethostbyaddr( $comment->comment_author_IP );
				global $wpdb;
				$comments_waiting      = $wpdb->get_var( "SELECT count(comment_ID) FROM $wpdb->comments WHERE comment_approved = '0'" );

				$comment_content = wp_specialchars_decode( $comment->comment_content );

				/* translators: %s: post title */
				$notify_message  = sprintf( __( 'A new comment on the chapter "%s" of "%s" is waiting for your approval', 'madara' ), $chapter['chapter_name'],  $post->post_title ) . "\r\n";
				$notify_message .= $chapter_url . "\r\n\r\n";
				/* translators: 1: comment author's name, 2: comment author's IP address, 3: comment author's hostname */
				$notify_message .= sprintf( __( 'Author: %1$s (IP address: %2$s, %3$s)' ), $comment->comment_author, $comment->comment_author_IP, $comment_author_domain ) . "\r\n";
				/* translators: %s: comment author email */
				$notify_message .= sprintf( __( 'Email: %s' ), $comment->comment_author_email ) . "\r\n";
				/* translators: %s: trackback/pingback/comment author URL */
				$notify_message .= sprintf( __( 'URL: %s' ), $comment->comment_author_url ) . "\r\n";
				/* translators: %s: comment text */
				$notify_message .= sprintf( __( 'Comment: %s' ), "\r\n" . $comment_content ) . "\r\n\r\n";

				/* translators: Comment moderation. %s: Comment action URL */
				$notify_message .= sprintf( __( 'Approve it: %s' ), admin_url( "comment.php?action=approve&c={$comment_id}#wpbody-content" ) ) . "\r\n";

				if ( EMPTY_TRASH_DAYS ) {
					/* translators: Comment moderation. %s: Comment action URL */
					$notify_message .= sprintf( __( 'Trash it: %s' ), admin_url( "comment.php?action=trash&c={$comment_id}#wpbody-content" ) ) . "\r\n";
				} else {
					/* translators: Comment moderation. %s: Comment action URL */
					$notify_message .= sprintf( __( 'Delete it: %s' ), admin_url( "comment.php?action=delete&c={$comment_id}#wpbody-content" ) ) . "\r\n";
				}

				/* translators: Comment moderation. %s: Comment action URL */
				$notify_message .= sprintf( __( 'Spam it: %s' ), admin_url( "comment.php?action=spam&c={$comment_id}#wpbody-content" ) ) . "\r\n";

				/* translators: Comment moderation. %s: Number of comments awaiting approval */
				$notify_message .= sprintf(
					_n(
						'Currently %s comment is waiting for approval. Please visit the moderation panel:',
						'Currently %s comments are waiting for approval. Please visit the moderation panel:',
						$comments_waiting
					),
					number_format_i18n( $comments_waiting )
				) . "\r\n";
				$notify_message .= admin_url( 'edit-comments.php?comment_status=moderated#wpbody-content' ) . "\r\n";
			}
		}
	}
	
	return $notify_message;
}

	add_filter( 'comment_form_defaults', function( $fields ) {
		$fields['must_log_in'] = sprintf( 
			__( '<p class="must-log-in">
					 You must <a href="%s" data-toggle="modal" data-target="#form-sign-up">Register</a> or 
					 <a href="%s" data-toggle="modal" data-target="#form-login">Login</a> to post a comment.</p>' 
			, WP_MANGA_TEXTDOMAIN),
			wp_registration_url(),
			wp_login_url( apply_filters( 'the_permalink', get_permalink() ) )   
		);
		return $fields;
	});
	
	add_filter('madara_manga_ranking_views', function( $text, $manga_id, $rank, $views ){
		
		$show_all_time_views = \App\Madara::getOption('manga_rank_views', 'monthly') == 'monthly' ? false : true;
		
		if($show_all_time_views){
			$views = wp_manga_number_format_short(get_post_meta( $manga_id, '_wp_manga_views', true ));
			
			$text = sprintf( _n( ' %1s, it has %2s view', ' %1s, it has %2s views', $views, WP_MANGA_TEXTDOMAIN ), $rank, $views );
		}
		
		return $text;
	}, 10, 4);