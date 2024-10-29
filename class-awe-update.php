<?php

class AWE_Update {

	/**
	 * Initialize with WordPress
	 */
    public static function initialize() {

		add_filter( 'login_redirect', 'AWE_Update::login_redirect', 10, 3 );
		add_action( 'wp_ajax_awe_update_publish', 'AWE_Update::publish' );
		add_action( 'wp_ajax_awe_update_unpublish', 'AWE_Update::unpublish' );
		add_action( 'wp_ajax_awe_update_trash', 'AWE_Update::trash' );

    }

	public static function total( $source_id = false ){
		global $wpdb;
		if ( $source_id ) {
			$count = $wpdb->get_var( 'SELECT COUNT(p.guid) FROM ( SELECT guid FROM '.AWE::relationships_table().$wpdb->prepare(' WHERE source_ID = %s', $source_id).' ) r LEFT JOIN '.AWE::reader_table().' p ON p.guid = r.guid;');

		} else {
			$count = $wpdb->get_var( 'SELECT COUNT(guid) FROM '.AWE::reader_table().';' );
		}
		if ( $count ) {
			return $count;
		} else {
			return 0;
		}
	}

	public static function total_published( $source_id = false ){
		global $wpdb;
		if ( $source_id ) {
			$count = $wpdb->get_var( 'SELECT COUNT(p.guid) FROM ( SELECT guid FROM '.AWE::relationships_table().$wpdb->prepare(' WHERE source_ID = %s', $source_id).' ) r LEFT JOIN '.AWE::reader_table().' p ON p.guid = r.guid WHERE  p.post_status="publish";');

		} else {
			$count = $wpdb->get_var( 'SELECT COUNT(guid) FROM '.AWE::reader_table().' WHERE post_status="publish";' );

		}

		if ( $count ) {
			return $count;
		} else {
			return 0;
		}
	}

	public static function query( $args ){
		global $wpdb;

		$posts_per_page = 10;
		
		if ( isset( $args['posts_per_page'] ) ) {
			$posts_per_page = $args['posts_per_page'];
		}

		if ( isset( $args['paged'] ) ) {
			$offset = $posts_per_page * $args['paged'];
			$offset_sql .= ' OFFSET '.$offset;
		} else {
			$offset_sql = '';
		}


		if ( isset( $args['source_id'] ) ) {

//			$where = $wpdb->prepare(' WHERE r.source_ID = %s', $args['source_id'] );

			$where = '';

			if ( isset( $args['status'] ) ) {
				$where = $wpdb->prepare(' WHERE p.post_status = %s', $args['status'] );
			}

			$sql = 'SELECT p.guid, p.post_title, p.post_body, p.post_author, p.post_date, p.post_permalink, p.post_status, s.source_title, s.source_url, s.source_site_url, r.source_ID FROM ( SELECT guid, source_ID FROM '.AWE::relationships_table().$wpdb->prepare(' WHERE source_ID = %s', $args['source_id'] ).' ) r LEFT JOIN '.AWE::sources_table().' s ON r.source_ID =  s.source_ID LEFT JOIN '.AWE::reader_table().' p ON p.guid = r.guid'.$where.' ORDER BY p.post_date DESC LIMIT '.$posts_per_page.$offset_sql;


		} else {

			$where = '';

			if ( isset( $args['status'] ) ) {
				$where = $wpdb->prepare(' WHERE p.post_status = %s', $args['status'] );
			}

			$sql = 'SELECT res.guid, res.post_title, res.post_body, res.post_author, res.post_date, res.post_permalink, res.post_status, s.source_title, s.source_url, s.source_site_url, s.source_ID FROM ( SELECT p.guid, p.post_title, p.post_body, p.post_author, p.post_date, p.post_permalink, p.post_status FROM '.AWE::reader_table().' p '.$where.' ORDER BY p.post_date DESC LIMIT '.$posts_per_page.$offset_sql.' ) res LEFT JOIN '.AWE::relationships_table().' r ON res.guid = r.guid LEFT JOIN '.AWE::sources_table().' s ON s.source_ID = r.source_ID';

		}

		$sql .= ';';

		$results = $wpdb->get_results( $sql );

		$updates = array();
		foreach ( $results as $post ) {
			$updates[] = array(
				'guid' => $post->guid,
				'title' => $post->post_title,
				'body' => $post->post_body,
				'author' => $post->post_author,
				'date' => $post->post_date,
				'permalink' => $post->post_permalink,
				'status' => $post->post_status,
				'source' => array( 
					'id' => $post->source_ID,
					'title' => $post->source_title,
					'url' => $post->source_url,
					'site_url' => $post->source_site_url
				)
			);
		}
		return $updates;
	}

	/**
	 * Redirect to the pending page
	 */
	public static function login_redirect( $redirect_to, $requested_redirect_to, $user ) {
	    if( isset( $user->user_login ) ) {
			$url = admin_url( 'admin.php?page=awe' );
			wp_redirect( $url );
			die();
		} 
	}

	/**
	 * AJAX Publish Callback
	 */
	public static function publish() {
		global $wpdb;
		$id = $_POST['id'];
		$response = array( 'id' => $id, 'status' => false );
		if ( $id ) {
	        $rows = $wpdb->update( AWE::reader_table(), array( 'post_status' => 'publish' ), array( 'guid' => $id ) );
			if ( $rows ) {
				$response = array( 'id' => $id, 'status' => true );
			}
		} 
		print json_encode( $response );
		die();
	}

	/**
	 * AJAX Unpublish Callback
	 */
	public static function unpublish() {
		global $wpdb;
		$id = $_POST['id'];
		$response = array( 'id' => $id, 'status' => false );
		if ( $id ) {
	        $rows = $wpdb->update( AWE::reader_table(), array( 'post_status' => 'pending' ), array( 'guid' => $id ) );
			if ( $rows ) {
				$response = array( 'id' => $id, 'status' => true );
			}
		} 
		print json_encode( $response );
		die();
	}

	public static function fetch_feed( $url ) {
		$feed = fetch_feed( $url );

		if ( is_wp_error( $feed ) ) {

			$error_string = $feed->get_error_message();

			AWE::error_log( 'Warning: Error while reading from "'. $url .'": ' . $error_string );

		    $request = new WP_Http;
		    $result = $request->request( $url );

			$bad_chrs = array(
				chr(0), //null
				chr(1), //start of heading
				chr(2), //start of text
				chr(3), //end of text
				chr(4), //end of transmission
				chr(5), //enquiry
				chr(6), //acknowledge
				chr(7), //bell
				chr(8), //backspace
				chr(10), //linefeed
				chr(11), //vertical tabulation
				chr(12), //form feed
				chr(13), //carriage return
				chr(14), //shift out
				chr(15), //shift in
				chr(16), //data link escape
				chr(17), //device control
				chr(18), //device control two
				chr(19), //device control three
				chr(20), //device controll four
				chr(21), //negative acknowledge
				chr(22), //synchronous idle
				chr(23), //end of transmission block
				chr(24), //cancel
				chr(25), //end of medium
				chr(26), //substitute
				chr(27), //escape
				chr(28), //file separator
				chr(29), //group separator
				chr(30), //record separator
				chr(31), //unit separator
				chr(127), //delete
			);

			$body = str_replace( $bad_chrs, "", $result['body'] );

			$feed = new SimplePie();
			$feed->set_raw_data( $body );
			$feed->init();
			$feed->handle_content_type();
		}

		return $feed;
	}

}

?>
