<?php

include_once(ABSPATH . WPINC . '/feed.php');
include_once(ABSPATH . WPINC . '/class-simplepie.php');

class AWE_Sources {

	public static $sources;

	public static function initialize() {

		add_filter( 'wp_feed_cache_transient_lifetime', create_function( '$a', 'return 1800;' ) );

		self::$sources = self::get_sources();

		if ( !self::$sources ) {
			self::$sources = array();
		}

	}

	public static function get_sources_percentages( $sort = 'alpha' ){
		global $wpdb;
		$sources = self::get_sources();
		$counts = array();
		$total = 0;
		foreach( $sources as $source ) {
			$sql = 'SELECT COUNT(p.guid) FROM ( SELECT guid FROM '.AWE::relationships_table().' WHERE source_ID="'.$source['slug'].'" ) r LEFT JOIN '.AWE::reader_table().' p ON p.guid = r.guid WHERE p.post_status="publish";';
			$count = $wpdb->get_var( $sql );
			if ( $count ) {
				$counts[$source['slug']] = (int) $count;
				$total = $total + (int) $count;
			}
		}

		$percentages = array();

		foreach ( $counts as $id => $count ) {
			$p = $sources[ $id ];
			$p['percentage'] = $count / $total * 100;
			$percentages[ $id ] = $p;
		}

		if ( $sort == 'percentage' ) {

			$p = array();
			foreach ($percentages as $key => $row){
				$p[$key] = $row['percentage'];
			}

			array_multisort( $p, SORT_ASC, $percentages);

		}

		return $percentages;
		
	}

	public static function get_sources() {
		global $wpdb;

		$sql = 'SELECT source_ID, source_title, source_url FROM '.AWE::sources_table().' ORDER BY source_title ASC;';
		$results = $wpdb->get_results( $sql );

		if ( $results ) {
			foreach( $results as $source ) {
				$sources[ $source->source_ID ] = array( 
					'slug' => $source->source_ID, 
					'title' => $source->source_title,
					'url' => $source->source_url
				);
			}

			return $sources;
		}

		return array();

	}

	public static function update_sources( $sources ) {

		global $wpdb;

		$sql = 'INSERT INTO '.AWE::sources_table().' (source_ID, source_title, source_url) VALUES ';

		$sql_values = array();

		foreach( $sources as $source ) {
			$sql_values[] = $wpdb->prepare("(%s,%s,%s)", $source['slug'], stripslashes($source['title']), $source['url']);
		}

		$sql .= implode( ",", $sql_values ). 'ON DUPLICATE KEY UPDATE source_title=VALUES(source_title),source_url=VALUES(source_url);';

		$rows_affected = $wpdb->query( $sql );

	}

	public static function add_source( $url ) {

		if ( empty( $url ) ) {
			AWE::error_log( __( "Couldn't add feed: No feed URL supplied", 'awe' ) );
			return false;
		}

		if ( !preg_match( '#https|http|feed#', $url ) ) {
			if ( strpos( $url, '://' ) ) {
				AWE::error_log( __( "Couldn't add feed: Unsupported protocol", 'awe' ) );
				return false;
			}
			$url = 'http://' . $url;
		}

		$feed_info = AWE_Update::fetch_feed( $url );
		$feed_error = $feed_info->error();
		$feed_url = $feed_info->subscribe_url();

		if ( !empty( $feed_error ) ) {
			AWE::error_log( __( "Couldn't add feed: ".$url." is not a valid URL or the server could not be accessed. Additionally, no feeds could be found by autodiscovery. ".$feed_error ) );
			return false;
		}

		$slug = sha1( $feed_url );

		$sources = self::get_sources();
		if ( array_key_exists( $slug, $sources ) ) {
			AWE::error_log( __( "Couldn't add feed: you have already added that feed" ) );
			return false;
		}

		$user = get_current_user_id();

		$title = $feed_info->get_title();

		$sources[ $slug ] = array(
			'slug' => $slug,
			'url' => $feed_url,
			'title' => $title,
		);

		self::update_sources( $sources );

		AWE::log( __( 'Feed "'.$title.'" has been added' ) );

		return true;
	}

}

?>
