<?php

	class WP_MANGA_DATABASE {

		public $wpdb;

		public function __construct() {

			global $wpdb;
			$this->wpdb = $wpdb;

			register_activation_hook( WP_MANGA_FILE, array( $this, 'wp_manga_create_db' ) );

		}

		function get_wpdb(){
			if( empty( $this->wpdb ) ){
				global $wpdb;
				$this->wpdb = $wpdb;
			}

			return $this->wpdb;
		}

		function wp_manga_create_db() {

			$volume_table = apply_filters( 'manga_volumes_table_columns', array(
				'volume_id'   => 'bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY',
				'post_id'     => 'bigint(20) UNSIGNED NOT NULL',
				'volume_name' => 'text NOT NULL',
				'date'        => 'datetime DEFAULT "0000-00-00 00:00:00" NOT NULL',
				'date_gmt'    => 'datetime DEFAULT "0000-00-00 00:00:00" NOT NULL',
			) );

			$this->create_table( 'manga_volumes', $args );

			$args = array(
				'chapter_id'          => 'bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY',
				'post_id'             => 'bigint(20) UNSIGNED NOT NULL',
				'volume_id'           => 'bigint(20) UNSIGNED NULL',
				'chapter_name'        => 'text NOT NULL',
				'chapter_name_extend' => 'text NOT NULL',
				'chapter_slug'        => 'text NOT NULL',
				'storage_in_use'      => 'varchar(20) UNSIGNED NULL',
				'date datetime'       => 'DEFAULT "0000-00-00 00:00:00" NOT NULL',
				'date_gmt datetime'   => 'DEFAULT "0000-00-00 00:00:00" NOT NULL',
			);

			$this->create_table( 'manga_chapters', $args );

			$args = array(
				'data_id'    => 'bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY',
				'chapter_id' => 'bigint(20) UNSIGNED NOT NULL',
				'storage'    => 'varchar(20) NOT NULL',
				'data'       => 'text NOT NULL',
			);

			$this->create_table( 'manga_chapters_data', $args );

		}

		function create_table( $name, $args ) {

			if ( ! is_array( $args ) || empty( $args ) ) {
				return false;
			}

			$charset_collate = $this->wpdb->get_charset_collate();
			$table_name      = $this->wpdb->prefix . $name;

			if( $this->table_exists( $table_name ) ){
				foreach( $args as $column => $data ){
					if( ! $this->column_exists( $column ) ){
						$this->alter_add_column( $table_name, $column, $data );
					}
				}
			}else{
				$query_args = array();

				foreach( $args as $column => $data ){
					$query_args[] = "{$column} {$data}";
				}

				$sql = "CREATE TABLE $table_name (
		            " . implode( ', ', $query_args ) . "
		        ) $charset_collate;";

				$this->maybe_create_table( $table_name, $sql );
			}
		}

		function maybe_create_table( $table_name, $create_ddl ) {

		    global $wpdb;

			if( $this->table_exists( $table_name ) ){
				return true;
			}

		    // Didn't find it try to create it..
		    $wpdb->query($create_ddl);

		    // We cannot directly tell that whether this succeeded!
			if( $this->table_exists( $table_name ) ){
				return true;
			}

		    return false;
		}

		function insert( $table, $args ) {

			// foreach( $args as $key => $value ){
			// 	$args[ $key ] = addslashes( $value );
			// }

			$this->wpdb
			     ->insert( $table, $args );

			if ( isset( $this->wpdb->insert_id ) ) {
				return $this->wpdb->insert_id;
			}

			return false;

		}

		function get( $table, $where, $orderBy, $order ) {

			$sort_setting = $this->get_sort_setting();

			$sort_by    = $sort_setting['sortBy'];
			$sort_order = $sort_setting['sort'];

			if( !empty( $orderBy ) ){
				$sort_by = $orderBy;
				$sort_order = !empty( $order ) ? $order : 'desc';
			}

			if( $sort_by == 'date' ){
				$sql = "
							SELECT SQL_CACHE *
							FROM $table
						";

				if( !empty( $where ) ){
					$sql .= "WHERE $where";
				}

				$sql .= "
							ORDER BY $sort_by $sort_order
						";
			}else{
				$sql = "
							SELECT SQL_CACHE *
							FROM $table
						";

				if( !empty( $where ) ){
					$sql .= "WHERE $where";
				}
			}

			$results = $this
			->get_wpdb()
			->get_results( $sql, 'ARRAY_A' );

			if( $results && $sort_by == 'name' ){

				if( strpos( $table, 'chapters' ) !== false ){
					$column = 'chapter_name';
				}elseif( strpos( $table, 'volumes' ) !== false ){
					$column = 'volume_name';
				}

				if( isset( $column ) ){

					//bring column name to be key of results array
					
					$names = array_map(function($element) use($column ){return $element[$column ];}, $results);

					natcasesort( $names );

					//put appropiate values to sorted position
					$output_results = array();
					foreach( $names as $key => $name ){
						$output_results[] = $results[ $key ];
					}

					if( !empty( $sort_order ) && $sort_order == 'desc' ){
						$results = array_reverse( $output_results );
					}else{
						$results = $output_results;
					}

				}
			}

			return $results;

		}

		function update( $table, $data, $where ) {

			return $this->wpdb
			            ->update( $table, $data, $where );

		}

		function delete( $table, $where ) {

			return $this->wpdb
			            ->delete( $table, $where );

		}

		function table_exists( $table_name ){

			$query = $this->wpdb->prepare( "SHOW TABLES LIKE %s", $wpdb->esc_like( $table_name ) );

		    if ( $this->wpdb->get_var( $query ) == $table_name ) {
		        return true;
		    }

			return false;

		}

		function column_exists( $table_name, $column_name ){

			$query = $this->wpdb->prepare(
				"SELECT *
				FROM INFORMATION_SCHEMA.COLUMNS
				WHERE TABLE_NAME = %s
				AND COLUMN_NAME = %s",
				$table_name,
				$column_name
			);

		    return !empty( $this->wpdb->query( $query ) );

		}

		function alter_add_column( $table_name, $column_name, $column_data ){
			$query = $this->wpdb->prepare(
				"ALTER TABLE %s
				ADD COLUMN %s %s",
				$table_name,
				$column_name,
				$column_data
			);

			return !empty( $this->wpdb->query( $query ) );

		}

		function get_sort_setting(){

			//get sort option
			if( class_exists( 'App\Madara' ) ){
				$sort_option = App\Madara::getOption('manga_chapters_order', 'name_desc');
			}else{
				$sort_option = 'name_desc';
			}

			if( in_array( $sort_option, array( 'name_desc', 'name_asc' ) ) ){
				$sort_option = array(
					'sortBy' => 'name',
					'sort'   => $sort_option == 'name_desc' ? 'desc' : 'asc'
				);
			}else{
				$sort_option = array(
					'sortBy' => 'date',
					'sort'   => $sort_option == 'date_desc' ? 'desc' : 'asc',
				);
			}

			return $sort_option;

		}
	}

	$GLOBALS['wp_manga_database'] = new WP_MANGA_DATABASE();
