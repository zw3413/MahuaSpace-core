<?php

class WP_MANGA_AJAX_UPLOAD{

    public function __construct(){

        // upload manga in backend
		add_action('wp_ajax_wp-manga-upload-chapter', array( $this,'wp_manga_upload_chapter' ) );

        //upload manga (multi chapters)
        add_action('wp_ajax_wp-manga-upload', array( $this, 'wp_manga_upload' ) );

        add_action( 'wp_ajax_wp_manga_create_content_chapter', array( $this, 'wp_manga_create_content_chapter' ) );

        add_action( 'wp_ajax_manga_import_album_chapter', array( $this, 'import_album_chapter' ) );

    }

    function import_album_chapter(){

        if( empty( $_POST['post'] ) || empty( $_POST['name'] ) ){
            wp_send_json_error([
                'message' => esc_html__( 'Missing Information', WP_MANGA_TEXTDOMAIN )
            ]);
        }

        $_POST = stripslashes_deep( $_POST );

        global $wp_manga_storage, $wp_manga_functions;
		
		$post_id = $_POST['post'];
		$name    = $_POST['name'];
		$volume  = isset( $_POST['volume'] ) ? $_POST['volume']: '0';
		

        $slugified_name = $wp_manga_storage->slugify( $name );

		
        $chapter_args = array(
			'post_id'             => $post_id,
			'volume_id'           => $volume,
			'chapter_name'        => $name,
			'chapter_name_extend' => $_POST['nameExtend'],
			'chapter_slug'        => $slugified_name,
            'storage_in_use'      => $_POST['storage']
		);
		
		if( !isset( $_POST['c_overwrite'] ) ){ //if doesn't have c_overwrite means chapter upload isn't verified yet
			//then start to verify chapter name
			$data_uniq_chapter = $wp_manga_functions->check_unique_chapter( $name, $volume, $post_id );

			if( $data_uniq_chapter && !isset( $_POST['overwrite'] ) && isset( $data_uniq_chapter['output'] ) ) {
				wp_send_json_error( array(
					'error'   => 'chapter_existed',
					'message' => __( 'This Chapter name is already existed, please choose if you want to over write or create a new one', WP_MANGA_TEXTDOMAIN ),
					'output'  => $data_uniq_chapter['output'],
				) );
			}
		}

        switch( $_POST['storage'] ){
            case 'picasa' :
                global $wp_manga_google_upload;
                $data = $wp_manga_google_upload->get_album_images( $_POST['album'] );
                break;
            case 'imgur' :
                global $wp_manga_imgur_upload;
                $data = $wp_manga_imgur_upload->get_album_images( $_POST['album'] );
                break;
            case 'amazon' :
                global $wp_manga_amazon_upload;
                $data = $wp_manga_amazon_upload->get_folder_images( $_POST['album'] );
                break;
            case 'flickr' :
                global $wp_manga_flickr_upload;
                $data = $wp_manga_flickr_upload->get_album_images( $_POST['album'] );
                break;
            default :
				// support custom storage
				if(class_exists('wp_manga_storage_' . $_POST['storage'] )){
					$class = 'wp_manga_storage_' . $_POST['storage'];
					$uploader = $class::get_instance();
					$data = $uploader->get_album_images( $_POST['album'] );
				} else {
					wp_send_json_error([
						'message' => esc_html__( 'Invalid storage', WP_MANGA_TEXTDOMAIN )
					]);
				}
        }

        if( is_wp_error( $data ) ){
            wp_send_json_error([
                'message' => $data->get_error_message()
            ]);
        }elseif( empty( $data ) ){
            wp_send_json_error([
                'message' => esc_html__( 'Cannot import album to Chapter', WP_MANGA_TEXTDOMAIN )
            ]);
        }

        $result = array(
			'host' => '',
			'file' => $data
		);

        $chapter_id = $wp_manga_storage->create_chapter( $chapter_args, $result, $_POST['storage'], false );

        wp_send_json_success( array( 'chapter_id' => $chapter_id ) );

    }

    function wp_manga_create_content_chapter(){

		global $wp_manga_text_type, $wp_manga_functions;

		if( empty( $_POST['postID'] ) ){
			wp_send_json_error( array( 'message' => esc_html__('Missing Post ID', 'madara') ) );
		}

		$_POST = stripslashes_deep( $_POST );

		$chapter_args = array(
			'post_id'             => isset( $_POST['postID'] ) ? $_POST['postID'] : '',
			'chapter_name'        => isset( $_POST['chapterName'] ) ? $_POST['chapterName'] : '',
			'chapter_name_extend' => isset( $_POST['chapterNameExtend'] ) ? $_POST['chapterNameExtend'] : '',
			'chapter_index' => isset( $_POST['chapterIndex'] ) ? $_POST['chapterIndex'] : '',
			'volume_id'           => isset( $_POST['chapterVolume'] ) ? $_POST['chapterVolume'] : '',
			'chapter_content'     => isset( $_POST['chapterContent'] ) ? $_POST['chapterContent'] : ''
		);

		$chapter_id = $wp_manga_text_type->insert_chapter( $chapter_args );

		if( $chapter_id ){
            if( is_wp_error( $chapter_id ) ){
                return wp_send_json_error( array( 'message' => $chapter_id->get_error_message() ) );
            }else{
    			wp_send_json_success( array( 'message' => esc_html__('Chapter is created successfully!', WP_MANGA_TEXTDOMAIN), 'chapter_id' => $chapter_id ) );
            }

		}

	}

    function wp_manga_upload(){

        if( $_FILES || (isset($_POST['directlink']) && $_POST['directlink'] != ''))			{

            global $wp_manga, $wp_manga_storage, $wp_manga_functions, $wp_manga_chapter, $wp_manga_volume;

            $storage = isset( $_POST['storage'] ) ? $_POST['storage'] : '';
            $post_id = isset( $_POST['postID'] ) ? $_POST['postID'] : '';
            $volume = ( isset( $_POST['volume'] ) && $_POST['volume'] !== 'none' ) ? $_POST['volume'] : 0;
						
            $manga_zip = $_FILES ? $_FILES[ key( $_FILES ) ] : array('tmp_name' => ABSPATH . $_POST['directlink']);
			
			if(isset($manga_zip['error']) && $manga_zip['error'] != 0){
				error_log('Upload error code: ' . $manga_zip['error']);
				
				$str_message = esc_html__('Upload error. Please try again', WP_MANGA_TEXTDOMAIN);
				if($manga_zip['error'] == 1){
					$str_message = esc_html__('Please check your maximum upload file size', WP_MANGA_TEXTDOMAIN);
				}
				
				return wp_send_json_error( $str_message );
			} else {
				// Check if zip file contains invalid file
				$validation = MADARA_ZIP_VALIDATION::get_zip_structure( $manga_zip['tmp_name'] );

				do_action( 'multi_chapters_upload_validation', $validation );

				if( is_wp_error( $validation ) ){
					return wp_send_json_error( $validation->get_error_message() );
				}elseif(
					isset( $validation['chapter_type'] )
					&& $validation['chapter_type'] == 'content'
				){
					return wp_send_json_error( esc_html__( 'Invalid Zip file for Manga Chapter upload. This should be zip file for Video or Text Chapter upload.', WP_MANGA_TEXTDOMAIN ) );
				}elseif(
					isset( $validation['zip_type'] )
					&& $validation['zip_type'] == 'single_chapter'
				){
					return wp_send_json_error( esc_html__( 'Invalid Zip file for Multi Chapters upload. This is Single Chapter upload zip file', WP_MANGA_TEXTDOMAIN ) );
				}elseif( empty( $validation['zip_type'] ) || empty( $validation['chapter_type'] ) ){
					return wp_send_json_error( esc_html__( 'Unsupported Zip File for Multi Chapters upload', WP_MANGA_TEXTDOMAIN ) );
				}

				if( empty( $post_id ) ) {
					return wp_send_json_error( esc_html__('Missing post ID', WP_MANGA_TEXTDOMAIN ) );
				}

				//if storage is blogspot
				if( $storage == 'picasa' && isset( $_POST['picasa_album'] ) ){
					update_option( 'google_latest_album', $_POST['picasa_album'] );
				}
				
				do_action('wp_manga_before_storage_upload', $post_id, $manga_zip, $storage);

				$response = $wp_manga_storage->manga_upload( $post_id, $manga_zip, $storage, $volume );

				if( !empty( $response['success'] ) ){
					return wp_send_json_success( $response['message'] );
				}else{
					return wp_send_json_error( $response['message'] );
				}
			}

        }else{
            return wp_send_json_error( esc_html__('Please choose zip file to upload', WP_MANGA_TEXTDOMAIN ) );
        }

        return wp_send_json_error( esc_html__('Something wrong happened, please try again later', WP_MANGA_TEXTDOMAIN ) );
    }

    function wp_manga_upload_chapter() {

        global $wp_manga, $wp_manga_storage, $wp_manga_functions, $wp_manga_chapter;

        if( !$_FILES && (!isset($_POST['directlink']) || $_POST['directlink'] == '') ) {
            wp_send_json_error( array(
		      'message' => 'Upload fail! Please provide direct link or upload a file'
            ) );
        }

		$_POST = stripslashes_deep( $_POST );

		$post_id = $_POST['post'];
		$name    = $_POST['name'];
		$volume  = isset( $_POST['volume'] ) ? $_POST['volume']: '0';

		if( !isset( $_POST['c_overwrite'] ) ){ //if doesn't have c_overwrite means chapter upload isn't verified yet

			//then start to verify chapter name
			$data_uniq_chapter = $wp_manga_functions->check_unique_chapter( $name, $volume, $post_id );

			if( $data_uniq_chapter && !isset( $_POST['overwrite'] ) && isset( $data_uniq_chapter['output'] ) ) {
				wp_send_json_error( array(
					'error'   => 'chapter_existed',
					'message' => __( 'This Chapter name is already existed, please choose if you want to over write or create a new one', WP_MANGA_TEXTDOMAIN ),
					'output'  => $data_uniq_chapter['output'],
				) );
			}

		}

		$nameExtend = isset( $_POST['nameExtend'] ) ? $_POST['nameExtend'] : '';
		$chapter_index = isset( $_POST['chapterIndex'] ) ? $_POST['chapterIndex'] : '';
		$storage    = $_POST['storage'];
		$chapter    = $_FILES ? $_FILES[ key( $_FILES ) ] : array('tmp_name' => ABSPATH . $_POST['directlink']);
		
		$overwrite  = isset( $_POST['overwrite'] ) ? $_POST['overwrite'] : false;

		//if storage is blogspot
		if( $storage == 'picasa' && isset( $_POST['picasa_album'] ) ){
			update_option( 'google_latest_album', $_POST['picasa_album'] );
		}

        //start extracting
        $uniqid = $wp_manga->get_uniqid( $post_id );

		if( ( isset( $_POST['overwrite'] ) && $_POST['overwrite'] == false ) || isset( $data_uniq_chapter['overwrite'] ) ) { //if not overwrite
			$slugified_name = $data_uniq_chapter['c_uniq_slug'];
		}elseif( isset( $_POST['overwrite'] ) && $_POST['overwrite'] == 'true' && isset( $_POST['c_overwrite'] ) ){ //if overwrite chapter
			$c_overwrite = $wp_manga_chapter->get_chapter_by_id( $post_id, $_POST['c_overwrite'] );
			$wp_manga_storage->local_delete_chapter_files( $post_id, $_POST['c_overwrite'] );
			$slugified_name = $c_overwrite['chapter_slug'];
		}else{
			$slugified_name = $wp_manga_storage->slugify( $name );
		}

		$physical_chapter_slug = $wp_manga_storage->get_uniq_dir_slug( $name );

        if( $storage == 'local' ) {
            $extract = WP_MANGA_DATA_DIR . $uniqid . '/' . $physical_chapter_slug;
            $extract_uri = WP_MANGA_DATA_URL;
        }else{
            $extract = WP_MANGA_EXTRACT_DIR . $uniqid . '/' . $physical_chapter_slug;
            $extract_uri = WP_MANGA_EXTRACT_URL . $uniqid . '/' . $physical_chapter_slug;
        }

        // Check if zip file contains invalid file
        $validation = MADARA_ZIP_VALIDATION::get_zip_structure( $chapter['tmp_name'] );

        do_action( 'single_chapter_upload_validation', $validation );

        if( is_wp_error( $validation ) ){
            return wp_send_json_error( $validation->get_error_message() );
        }elseif(
            isset( $validation['chapter_type'] )
            && $validation['chapter_type'] == 'content'
        ){
            return wp_send_json_error( __( 'Invalid Zip file for Manga Chapter upload. This should be zip file for Video or Text Chapter upload.', WP_MANGA_TEXTDOMAIN ) );
        }elseif(
            isset( $validation['zip_type'] )
            && in_array(
                $validation['zip_type'],
                array(
                    'multi_chapters_no_volume',
                    'multi_chapters_with_volumes'
                )
            )
        ){
            return wp_send_json_error( __( 'Invalid Zip file for Single Chapter upload. This is Multi Chapters Upload zip file', WP_MANGA_TEXTDOMAIN ) );
        }elseif( empty( $validation['zip_type'] ) || empty( $validation['chapter_type'] ) ){
            return wp_send_json_error( __( 'Unsupported Zip File for Single Chapter upload', WP_MANGA_TEXTDOMAIN ) );
        }

        $chapter_zip = new ZipArchive();
		if( $chapter_zip->open( $chapter['tmp_name'] ) ) {
            $chapter_zip->extractTo( $extract );
            $chapter_zip->close();
        }
		
		// if there is a folder inside $extract (.zip file contains single chapter folder), we move all images inside it to upper folder
		$scandir_lv1 = glob( $extract . '/*' );
		foreach($scandir_lv1 as $dir){
			if ( basename( $dir ) === '__MACOSX' ) {
				continue;
			}
			if ( is_dir( $dir ) ) {
				$files = glob( $dir . '/*' );
				foreach($files as $file){
					rename($dir . '/' . basename($file), $extract . '/' . basename($file));
				}
			}
		}

		do_action( 'wp_manga_upload_after_extract', $post_id, $slugified_name, $extract, $storage );

		$chapter_args = array(
			'post_id'             => $post_id,
			'volume_id'           => $volume,
			'chapter_name'        => $name,
			'chapter_name_extend' => $nameExtend,
			'chapter_index' => $chapter_index,
			'chapter_slug'        => $slugified_name,
		);

        //upload chapter
        $chapter_id = $wp_manga_storage->wp_manga_upload_single_chapter( $chapter_args, $extract, $extract_uri, $storage, $overwrite );

		do_action( 'wp_manga_upload_completed', $chapter_id, $post_id, $extract, $extract_uri, $storage );

		if( $storage !== 'local' ){
			$wp_manga_storage->local_remove_storage( $extract );
		}

        if( !empty( $chapter_id ) ) {
            if( is_wp_error( $chapter_id ) ){

                wp_send_json_error( array(
                    'error' => true,
                    'message' => $chapter_id->get_error_message()
                ) );

            }else if( isset( $chapter_id['error'] ) ) {

                wp_send_json_error( $chapter_id );

			}else{
				wp_send_json_success( array( 'chapter_id' => $chapter_id ) );
			}
        }else{
            wp_send_json_error( esc_html__('Upload failed. Please try again later', WP_MANGA_TEXTDOMAIN ) );
        }

	    exit;
	}

}

new WP_MANGA_AJAX_UPLOAD();
