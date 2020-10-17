<?php

add_action('wp-manga-manga-properties', 'wp_manga_single_manga_info_user_rate', 10, 1);

if(!function_exists('wp_manga_single_manga_info_user_rate')){
	function wp_manga_single_manga_info_user_rate( $manga_id ) {
		$wp_manga_settings = get_option( 'wp_manga_settings' );
		$user_rate = isset( $wp_manga_settings['user_rating'] ) ? $wp_manga_settings['user_rating'] : 1;
		
		if($user_rate){
			global $wp_manga_functions;
			$rate        = $wp_manga_functions->get_total_review( $manga_id );
			$vote        = $wp_manga_functions->get_total_vote( $manga_id );
			
			global $wp_manga_template;
			include $wp_manga_template->load_template('single/info','rating', false);
		}
	}
}

add_action('wp-manga-manga-properties', 'wp_manga_single_manga_info_rank', 12, 1);

if(!function_exists('wp_manga_single_manga_info_rank')){
	function wp_manga_single_manga_info_rank( $manga_id ) {
		global $wp_manga_functions;
		$rank              = $wp_manga_functions->get_manga_rank( $manga_id );
		$views             = $wp_manga_functions->get_manga_monthly_views( $manga_id );
		
		global $wp_manga_template;
		include $wp_manga_template->load_template('single/info','rank', false);
	}
}

add_action('wp-manga-manga-properties', 'wp_manga_single_manga_info_afternativename', 14, 1);

if(!function_exists('wp_manga_single_manga_info_afternativename')){
	function wp_manga_single_manga_info_afternativename( $manga_id ) {
		global $wp_manga_functions;
		$alternative = $wp_manga_functions->get_manga_alternative( $manga_id );
		
		global $wp_manga_template;
		include $wp_manga_template->load_template('single/info','alternative-name', false);
	}
}

add_action('wp-manga-manga-properties', 'wp_manga_single_manga_info_authors', 16, 1);

if(!function_exists('wp_manga_single_manga_info_authors')){
	function wp_manga_single_manga_info_authors( $manga_id ) {
		global $wp_manga_functions;
		$authors     = $wp_manga_functions->get_manga_authors( $manga_id );
		
		global $wp_manga_template;
		include $wp_manga_template->load_template('single/info','authors', false);
	}
}

add_action('wp-manga-manga-properties', 'wp_manga_single_manga_info_artists', 18, 1);

if(!function_exists('wp_manga_single_manga_info_artists')){
	function wp_manga_single_manga_info_artists( $manga_id ) {
		global $wp_manga_functions;
		$artists     = $wp_manga_functions->get_manga_artists( $manga_id );
		
		global $wp_manga_template;
		include $wp_manga_template->load_template('single/info','artists', false);
	}
}

add_action('wp-manga-manga-properties', 'wp_manga_single_manga_info_genres', 20, 1);

if(!function_exists('wp_manga_single_manga_info_genres')){
	function wp_manga_single_manga_info_genres( $manga_id ) {
		global $wp_manga_functions;
		$genres     = $wp_manga_functions->get_manga_genres( $manga_id );
		
		global $wp_manga_template;
		include $wp_manga_template->load_template('single/info','genres', false);
	}
}

add_action('wp-manga-manga-properties', 'wp_manga_single_manga_info_type', 22, 1);

if(!function_exists('wp_manga_single_manga_info_type')){
	function wp_manga_single_manga_info_type( $manga_id ) {
		global $wp_manga_functions;
		$type = $wp_manga_functions->get_manga_type( $manga_id );
		
		global $wp_manga_template;
		include $wp_manga_template->load_template('single/info','type', false);
	}
}

add_action('wp-manga-manga-status', 'wp_manga_single_manga_info_release', 10, 1);

if(!function_exists('wp_manga_single_manga_info_release')){
	function wp_manga_single_manga_info_release( $manga_id ) {
		global $wp_manga_functions;
		
		$release = $wp_manga_functions->get_manga_release( $manga_id );
		
		global $wp_manga_template;
		include $wp_manga_template->load_template('single/info','release', false);
	}
}

add_action('wp-manga-manga-status', 'wp_manga_single_manga_info_status', 12, 1);

if(!function_exists('wp_manga_single_manga_info_status')){
	function wp_manga_single_manga_info_status( $manga_id ) {
		global $wp_manga_functions;
		
		$status = $wp_manga_functions->get_manga_status( $manga_id );
		
		global $wp_manga_template;
		include $wp_manga_template->load_template('single/info','status', false);
	}
}

add_action('wp-manga-manga-status', 'wp_manga_single_manga_user_buttons', 12, 1);

if(!function_exists('wp_manga_single_manga_user_buttons')){
	function wp_manga_single_manga_user_buttons( $manga_id ) {
		global $wp_manga_functions;
		
		$wp_manga_settings = get_option( 'wp_manga_settings' );
		$manga_comment = isset( $wp_manga_settings['enable_comment'] ) ? $wp_manga_settings['enable_comment'] : 1;
		$user_bookmark = isset( $wp_manga_settings['user_bookmark'] ) ? $wp_manga_settings['user_bookmark'] : 1;
		
		global $wp_manga_template;
		include $wp_manga_template->load_template('single/user-actions','', false);
	}
}

add_action('wp-manga-chapter-listing', 'wp_manga_single_manga_info_chapters');
if(!function_exists('wp_manga_single_manga_info_chapters')){
	function wp_manga_single_manga_info_chapters( $manga_id ) {
		?>
		<div id="manga-chapters-holder" data-id="<?php echo esc_attr($manga_id);?>"><i class="fas fa-spinner fa-spin fa-3x"></i></div>
		<?php
		/**
		global $wp_manga_functions, $wp_manga_database;
		
		$sort_option = $wp_manga_database->get_sort_setting();
		
		$manga = $wp_manga_functions->get_all_chapters( $manga_id, $sort_option['sort'] );
		
		$current_read_chapter = 0;
		if ( is_user_logged_in() ) {
			$user_id = get_current_user_id();
			$history = madara_get_current_reading_chapter($user_id, $manga_id);
			if($history){
				$current_read_chapter = $history['c'];
			}
		}
		
		global $wp_manga_template;
		include $wp_manga_template->load_template('single/info','chapters', false);
		**/
	}
}