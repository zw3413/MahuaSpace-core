<?php

class WP_MANGA_AJAX {

	public function __construct() {

		//delete_zip
		add_action('wp_ajax_wp-manga-delete-zip', array( $this, 'wp_manga_delete_zip' ) );
		add_action('wp_ajax_nopriv_wp-manga-delete-zip', array( $this, 'wp_manga_delete_zip' ) );

		//save page setting in first install page
		add_action( 'wp_ajax_wp_manga_first_install_page_save', array( $this, 'wp_manga_first_install_page_save') );

		//save post type setting in first install page
		add_action( 'wp_ajax_wp_manga_first_install_post_save', array( $this, 'wp_manga_first_install_post_save') );

		add_action( 'wp_ajax_wp_manga_skip_first_install', array( $this, 'wp_manga_skip_first_install' ) );

		add_action( 'wp_ajax_wp_manga_archive_loadmore', array( $this, 'wp_manga_archive_loadmore' ) );

		add_action( 'wp_ajax_wp_manga_clean_temp_folder', array( $this, 'wp_manga_clean_temp_folder' ) );

		add_action( 'wp_ajax_replace_blogspot_url', array( $this, 'replace_blogspot_url' ) );

		add_action( 'wp_ajax_wp-manga-duplicate-server', array( $this, 'wp_manga_duplicate_server' ) );
		
		add_action( 'wp_ajax_wp-manga-remove-storage', array( $this, 'wp_manga_remove_storage' ) );

		require_once( WP_MANGA_DIR . '/inc/ajax/backend.php' );
		require_once( WP_MANGA_DIR . '/inc/ajax/frontend.php' );
		require_once( WP_MANGA_DIR . '/inc/ajax/upload.php' );

	}

	function replace_blogspot_url(){

		global $wp_manga_google_upload;
		$all_manga_dirs = glob( WP_MANGA_JSON_DIR . '/*' );

		foreach( $all_manga_dirs as $dir ){

			$manga_json = $dir . '/manga.json';

			if( !file_exists( $manga_json ) ){
				continue;
			}

			$manga_json_data = file_get_contents( $manga_json );
			$manga_data = json_decode( $manga_json_data, true );

			if( empty( $manga_data['chapters'] ) ){
				continue;
			}

			foreach( $manga_data['chapters'] as $chapter_id => $chapter ){

				if( !empty( $chapter['storage']['picasa'] ) ){

					foreach( $chapter['storage']['picasa']['page'] as $page_num => $page ){
						$manga_data['chapters'][$chapter_id]['storage']['picasa']['page'][$page_num]['src'] = $wp_manga_google_upload->blogspot_url_filter( $page['src'] );
					}
				}
			}

			$fp = fopen( $manga_json , 'w');
			fwrite( $fp, json_encode( $manga_data ) );
			fclose( $fp );

		}

		update_option('_wp_manga_is_blogspot_replaced', true);
		wp_send_json_success();

	}

	function wp_manga_clean_temp_folder(){

		$post_id = isset( $_POST['postID'] ) ? $_POST['postID'] : '';

		if( empty( $post_id ) ){
			wp_send_json_error( __('Missing Post ID', WP_MANGA_TEXTDOMAIN ) );
		}

		global $wp_manga, $wp_manga_storage;
		$uniqid = $wp_manga->get_uniqid( $post_id );
		$paths_to_clean = get_transient( 'path_to_clean_' . $uniqid );

		if( $paths_to_clean ){
			foreach( $paths_to_clean as $path ){
				$wp_manga_storage->local_remove_storage( $path );
			}
		}

		delete_transient( 'path_to_clean_' . $uniqid );

		wp_send_json_success();

	}

	function wp_manga_archive_loadmore(){

		$manga_args = isset( $_POST['manga_args'] ) ? $_POST['manga_args'] : '';
		$template = $_POST['template'];

		if( empty( $manga_args ) ){
			wp_send_json_error();
		}

		global $wp_manga, $wp_manga_template;

		$manga_args['paged'] += 1;
		$manga_query = $wp_manga->mangabooth_manga_query( $manga_args );

		if( $manga_query->have_posts() ) {

			$wp_manga->wp_manga_query_vars_js( $manga_args );

			$index = 0;

			set_query_var( 'wp_manga_posts_per_page', $manga_query->post_count );
			set_query_var( 'wp_manga_paged', $manga_args['paged'] );

			while( $manga_query->have_posts() ){
				$index++;
				set_query_var( 'wp_manga_post_index', $index );

				$manga_query->the_post();
				$wp_manga_template->load_template( 'content/content', $template );
			}

			$args = $manga_query->query;
			$args['max_num_pages'] = $manga_query->max_num_pages;

			$wp_manga->wp_manga_query_vars_js( $args, true );

			die();

		}

		wp_reset_postdata();

		die(0);

	}

	function wp_manga_delete_zip(){

		$zip_dir = isset( $_POST['zipDir'] ) ? $_POST['zipDir'] : '';

		if( !empty( $zip_dir ) ){
			unlink( $zip_dir );
		}

	}

    function wp_manga_duplicate_server(){

        $post_id          = isset( $_POST['postID'] ) ? $_POST['postID'] : '';
        $chapter_id       = isset( $_POST['chapterID'] ) ? $_POST['chapterID'] : '';
        $duplicate_server = isset( $_POST['duplicateServer'] ) ? $_POST['duplicateServer'] : '';

        if( empty( $post_id ) || empty( $chapter_id ) || empty( $duplicate_server ) ) {
            wp_send_json_error();
        }

        global $wp_manga_storage;
        $response = $wp_manga_storage->duplicate_server( $post_id, $chapter_id, $duplicate_server );

        if( $response !== false ) {
            wp_send_json_success( $response );
        }
    }
	
	function wp_manga_remove_storage(){
		$post_id          = isset( $_POST['postID'] ) ? $_POST['postID'] : '';
        $chapter_id       = isset( $_POST['chapterID'] ) ? $_POST['chapterID'] : '';
        $storage = isset( $_POST['storage'] ) ? $_POST['storage'] : '';

        if( empty( $post_id ) || empty( $chapter_id ) || empty( $storage ) ) {
            wp_send_json_error();
        }

        global $wp_manga_storage;
        $response = $wp_manga_storage->remove_storage( $post_id, $chapter_id, $storage );

        if( $response !== false ) {
            wp_send_json_success( $response );
        }
	}

	function wp_manga_first_install_page_save(){

		$manga_archive_page = isset( $_POST['manga_archive_page'] ) ? $_POST['manga_archive_page'] : 0;
		$user_page = isset( $_POST['user_page'] ) ? $_POST['user_page'] : 0;

		if( $manga_archive_page == 0 && $user_page == 0 ) {
			return false;
		}

		$settings = get_option( 'wp_manga_settings' , array() );
		$settings['manga_archive_page'] = $manga_archive_page;
		$settings['user_page'] = $user_page;

		$resp = update_option( 'wp_manga_settings', $settings );

		wp_send_json_success( $resp );

	}

	function wp_manga_first_install_post_save(){

		$manga_slug = isset( $_POST['manga_slug'] ) ? $_POST['manga_slug'] : 'manga';

		if( $manga_slug == 'manga' ) {
			return false;
		}

		$settings = get_option( 'wp_manga_settings' , array() );
		$settings['manga_slug'] = urldecode( sanitize_title( $manga_slug ) );
		update_option( 'wp_manga_settings', $settings );

		$args = get_post_type_object( 'wp-manga' );
		$args->rewrite['slug'] = $manga_slug;
		register_post_type( $args->name, $args );
		flush_rewrite_rules();

		wp_send_json_success();

	}

	function wp_manga_skip_first_install(){

		$resp = update_option( 'wp_manga_notice', true );
		wp_send_json_success( $resp );

	}

}
$GLOBALS['wp_manga_ajax'] = new WP_MANGA_AJAX();
