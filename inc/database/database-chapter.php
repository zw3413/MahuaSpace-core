<?php

	class WP_DB_CHAPTER extends WP_MANGA_DATABASE {

		public function __construct() {

			parent::__construct();

			$this->table = $this->get_wpdb()->prefix . 'manga_chapters';

			add_action( 'manga_chapter_inserted', array( $this, 'update_manga_latest_meta' ), 10, 2 );

		}

		function insert_chapter( $args ) {

			//post_id require, volume id, chapter name, chapter extend name, chapter slug

			if ( empty( $args['post_id'] ) ) {
				return false;
			}

			if( empty( $args['volume_id'] ) ){
				$args['volume_id'] = 0;
			}

			//check if chapter slug is unique
			$search = $this->get_chapter_by_slug( $args['post_id'], $args['chapter_slug'] );

			if( $search ){
				global $wp_manga_functions;
				$args['chapter_slug'] = $wp_manga_functions->unique_slug( $args['post_id'], $args['chapter_name'] );
			}

			$args['date']     = current_time( 'mysql' );
			$args['date_gmt'] = current_time( 'mysql', true );
			
			$insertData = apply_filters( 'wp_manga_chapter_insert_args', $args );

			$chapter_id = $this->insert( $this->table, $insertData );

			do_action( 'manga_chapter_inserted', $chapter_id, $args );

			return $chapter_id;

		}

		/**
		 *
		 * $limit - int - limit number of rows returned
		 **/
		function get_chapters( $args, $search = false, $orderby = '', $order = '', $limit = 0 ) {
			
			$conditions = array();
			foreach ( $args as $name => $value ) {
				if( $name == 'orderby' || $name == 'order' ){
					continue;
				}
				
				$value = addslashes( $value );
				$conditions[] = "$name = '$value'";
			}

			if ( $search ) {
				$conditions[] = "chapter_name LIKE '%$search%' OR chapter_name_extend LIKE '%$search%'";
			}
			$conditions = apply_filters( 'manga_get_chapters_conditions', $conditions, $args );

			$where = implode( ' AND ', $conditions );
			
			$results = $this->get( $this->table, $where, $orderby, $order, $limit ? "LIMIT 0, $limit" : '');

			return apply_filters( 'manga_get_chapters_results', $results, $args, $where, $orderby, $order, $limit );

		}

		function get_latest_chapters( $post_id, $q, $num = 0, $all_meta = 0, $orderby = 'name', $order = 'desc' ) {
			
			$chapters = $this->get_chapters( array(
				'post_id' => $post_id
			), $q, $orderby, $order, $num );
			
		
			if ( $chapters && $all_meta == 0 && $num > 0) {
				return array_slice( $chapters, 0, $num );
			}
			
			return $chapters;

		}

		function delete_chapter( $args ) {
			// delete chapter content if it is novel
			$chapter_id = isset($args['chapter_id']) ? $args['chapter_id'] : 0;
			
			if($chapter_id){
				$ar = array(
						'post_parent' => $chapter_id,
						'post_type'   => 'chapter_text_content'
					);
					
				$the_query = new WP_Query( $ar );
				if ($the_query->have_posts()) {
					while ( $the_query->have_posts() ) :
						$the_query->the_post();
						wp_delete_post(get_the_ID());
					endwhile;
				}
				wp_reset_postdata();

				$resp = $this->delete( $this->table, $args );

				return $resp;
			} elseif( isset( $args['post_id'] ) ){
                // delete chapter content, if any (novel, video chapter)
				$this->get_wpdb()->query(
                    $this->get_wpdb()->prepare(
                        "DELETE P FROM {$this->get_wpdb()->prefix}posts as P
                        JOIN {$this->get_wpdb()->prefix}manga_chapters as C
                        ON P.post_parent = C.chapter_id
                        WHERE C.post_id = %d AND P.post_type = 'chapter_text_content'",
                        $args['post_id']
                    )
                );
				
				return $this->delete( $this->table, $args );
            }
			
			return false;
		}

		/**
		 * $where_args - array('post_id', 'chapter_id')
		 **/
		function update_chapter( $update, $where_args ) {

			if( ! isset( $update['chapter_slug'] ) && isset( $where_args['post_id'] ) && isset( $where_args['chapter_id'] ) ){
				// Get unique slug
				global $wp_manga_functions;
				$update['chapter_slug'] = $wp_manga_functions->unique_slug( $where_args['post_id'], $update['chapter_name'], $where_args['chapter_id'] );
			}

			return $this->update( $this->table, $update, $where_args );

		}

		function get_manga_chapters( $post_id ) {
			return $this->get_chapters( array(
				'post_id' => $post_id
			) );
		}

		function get_chapter_by_id( $post_id = null, $chapter_id ) {

			$args = array(
				'chapter_id' => $chapter_id
			);

			if( $post_id ){
				$args['post_id'] = $post_id;
			}

			$chapter = $this->get_chapters( $args );

			if ( isset( $chapter[0] ) ) {
				return $chapter[0];
			}

			return false;
		}

		function get_chapter_volume( $post_id, $chapter_id ) {

			$chapter = $this->get_chapter_by_id( $post_id, $chapter_id );

			if ( $chapter == false ) {
				return false;
			}

			if ( $chapter['volume_id'] == 0 ) {
				return false;
			}

			$volume = $GLOBALS['wp_manga_volume']->get_volumes( array(
				'post_id'   => $post_id,
				'volume_id' => $chapter['volume_id']
			) );

			if ( isset( $volume[0] ) ) {
				return $volume[0];
			}

			return false;
		}

		function get_chapter_by_slug( $post_id, $chapter_slug ) {

			$chapters = $this->get_chapters( array(
				'post_id'      => $post_id,
				'chapter_slug' => $chapter_slug
			) );
			if ( isset( $chapters[0] ) ) {
				return $chapters[0];
			}

			return false;

		}

		function get_chapter_id_by_slug( $post_id, $chapter_slug ) {

			$chapter = $this->get_chapter_by_slug( $post_id, $chapter_slug );

			if ( $chapter ) {
				return $chapter['chapter_id'];
			}

			return false;

		}

		function get_chapter_slug_by_id( $post_id, $chapter_id ) {

			$chapter = $this->get_chapter_by_id( $post_id, $chapter_id );

			if ( $chapter ) {
				return $chapter['chapter_slug'];
			}

			return false;
		}

		function get_chapter_info( $post_id, $chapter_id ) {

			$chapter = $this->get_chapter_by_id( $post_id, $chapter_id );

			if ( $chapter ) {

				$chapter['volume'] = $this->get_chapter_volume( $post_id, $chapter_id );

				if ( $chapter['volume'] == false ) {
					unset( $chapter['volume'] );
				}
				
				// populate Chapter Args into coresponding properties
				// @since 1.6.1.5
				$args = isset($chapter['chapter_metas']) ? unserialize($chapter['chapter_metas']) : array();
				
				foreach($args as $key => $value){
					$chapter[$key] = $value;
				}
			}

			return $chapter;

		}
		
		/**
		 * Get Chapter meta
		 *
		 * $chapter - String (ID) or Mixed Object (Chapter obj)
		 * $meta - string - Name of meta
		 *
		 * @return $args (array) if $meta is empty, or mixed value if $meta name is passed in
		 *
		 * since 1.6.1.5
		 **/
		function get_chapter_meta( $chapter, $meta = ''){
			if(is_numeric($chapter)){
				$chapter = $this->get_chapter_by_id( null, $chapter );
			}
			
			if ( $chapter ) {
				$args = isset($chapter['chapter_metas']) ? $chapter['chapter_metas'] : '';
				
				if($args){
					$args = unserialize($args);
					
					if($meta && isset($args[$meta])){
						return $args[$meta];
					}
					
					return $args;
				}
			}

			return false;
		}

		function update_manga_latest_meta( $chapter_id, $args ){

			if( !empty( $args['post_id'] ) ){
				global $wp_manga_functions;
				$wp_manga_functions->update_latest_meta( $args['post_id'] );
			}

		}

	}

	$GLOBALS['wp_manga_chapter'] = new WP_DB_CHAPTER();
