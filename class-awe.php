<?php
/**
 * AWE Plugin API
 *
 * Includes all of our functions that hook into WordPress for our plugin's functionality.
 *
 * @package WordPress
 * @subpackage AWE Plugin
 */

class AWE {

	protected static $templates;
	protected static $templates_by_hash;
	public static $minify;
	public static $cache;
	public static $template_basepath;
	public static $db_version = "1.0";

	/**
	 * Array walk that expects a pass-by-reference
	 */
	static function array_walk(array &$array, $callback, &$userdata = null) {
	    foreach($array as $k => $v) {
	        $array[$k] = $callback( $v, $k, $userdata );
	    }
	}

	static public function is_migrated(){
		$awe_sources = get_option('awe_sources');
		if ( $awe_sources ) {
			$migrated_version = get_option('awe_database_migrated');
			if ( $migrated_version == self::$db_version ) {
				return true;
			} else {
				return false;
			}
		} else {
		  return true;
		}
	}

	static public function reader_table_index(){
		global $wpdb;
		return $wpdb->prefix . "status_date";
	}

	static public function reader_table(){
		global $wpdb;
		return $wpdb->prefix . "reader_posts";
	}

	static public function relationships_table(){
		global $wpdb;
		return $wpdb->prefix . "reader_source_relationships";
	}

	static public function sources_table(){
		global $wpdb;
		return $wpdb->prefix . "reader_sources";
	}

	/**
	 * Templates multi-dimentional directory listing to hashed list.
	 *
	 * Will take a multi-dimentional directory listing array returned from scandir_r, and store it as a 
	 * single-dimentional array based upon it's second value that is a hash.
	 *
	 * @param array $list Multi-dimenional array from scandir_r.
	 */
	static public function templates_to_hash( &$list ) {
		foreach ( $list as $key => $value ) {
			if ( gettype($key) == 'string' ) {
				self::templates_to_hash( $list[$key] );
			} else {
				self::$templates_by_hash[$value[1]] = array( $value[0], $value[2] );
			}
		}
	}

	/**
	 * Flushes rewrite rules in WordPress
	 */
	static public function flush_rules() {
	    global $wp_rewrite;
	    $wp_rewrite->flush_rules();
	}


	/**
	 * Logs errors 
	 */
	static public function error_log( $error = false ) {
		$errors = get_option( 'awe_errors', array() );
		if ( $error ) {
			$errors[] = $error;
			update_option( 'awe_errors', $errors );
		}
		return $errors;
	}

	/**
	 * Logs messages
	 */
	static public function log( $message = false ) {
		$messages = get_option( 'awe_messages', array() );
		if ( $message ) {
			$messages[] = $message;
			update_option( 'awe_messages', $messages );
		}
		return $messages;
	}

	/**
	 * Returns top-level pages as an array with values to be used as terms for the menu display
	 */
	static public function get_pages_as_terms( ) {

		global $wpdb;

		$query = "SELECT post_name, post_title FROM $wpdb->posts WHERE post_type = 'page' AND post_parent = 0 AND post_status = 'publish'";
		$arcresults = $wpdb->get_results($query);

		$value = array();
		foreach ( $arcresults as $arcresult ) {
			$item = new stdClass();
			$item->slug = $arcresult->post_name;
			$item->name = $arcresult->post_title;
			$item->count = false;
			$value[] = $item;
		}

		return $value;
	}

	/**
	 * Used to get terms used in a specific post_type
	 */
	static public function get_terms_by_post_type( $taxonomy, $post_types ) {

		global $wpdb;

		if ( in_array( $taxonomy, array( 'year', 'month', 'day' ) ) ) {

			// sql query posts
			$where = apply_filters('getarchives_where', 
				"WHERE post_type IN('".join( "', '", $post_types )."') AND post_status = 'publish'");

			$join = apply_filters('getarchives_join', "");

			$limit = ' LIMIT 100';

			// archives 
			switch ( $taxonomy ) {
			case 'year':
				$value = array();
				$query = "SELECT YEAR(post_date) AS `year`, count(ID) as posts FROM $wpdb->posts $join $where GROUP BY YEAR(post_date) ORDER BY post_date DESC $limit";
				$qkey = md5($query);
				$cache = wp_cache_get( 'awe_taxonomy' , 'year' );
				if ( !isset( $cache[ $qkey ] ) ) {
					$arcresults = $wpdb->get_results($query);
					$cache[ $qkey ] = $arcresults;
					wp_cache_set( 'awe_taxonomy', $cache, 'year' );
				} else {
					$arcresults = $cache[ $qkey ];
				}
				if ($arcresults) {
					$value = array();
					foreach ( $arcresults as $arcresult ) {
						$item = new stdClass();
						$item->slug = $arcresult->year;
						$item->name = $arcresult->year;
						$item->count = $arcresult->posts;
						$value[] = $item;
					}
				} else {
					$value = false;
				}
				break;
			case 'month':
				$value = array();
				$query = "SELECT YEAR(post_date) AS `year`, MONTH(post_date) AS `month`, count(ID) AS posts FROM $wpdb->posts $join $where GROUP BY YEAR(post_date), MONTH(post_date) ORDER BY post_date DESC $limit";
				$qkey = md5($query);
				$cache = wp_cache_get( 'awe_taxonomy' , 'month' );
				if ( !isset( $cache[ $qkey ] ) ) {
					$arcresults = $wpdb->get_results($query);
					$cache[ $qkey ] = $arcresults;
					wp_cache_set( 'awe_taxonomy', $cache, 'month' );
				} else {
					$arcresults = $cache[ $qkey ];
				}
				if ($arcresults) {
					$value = array();
					global $month;
					foreach ( $arcresults as $arcresult ) {
						$item = new stdClass();
						$item->slug = $arcresult->month;
						$item->name = $month[zeroise($arcresult->month, 2)];
						$item->count = $arcresult->posts;
						$value[$arcresult->year][] = $item;
					}
				} else {
					$value = false;
				}
				break;
			case 'day':
				$value = array();
				break;
			default:
				$value = false;
			}
			return $value;

		} else {
			$query = $wpdb->prepare( "SELECT t.*, COUNT(*) from $wpdb->terms AS t INNER JOIN $wpdb->term_taxonomy AS tt ON t.term_id = tt.term_id INNER JOIN $wpdb->term_relationships AS r ON r.term_taxonomy_id = tt.term_taxonomy_id INNER JOIN $wpdb->posts AS p ON p.ID = r.object_id WHERE p.post_type IN('".join( "', '", $post_types )."') AND tt.taxonomy IN('".$taxonomy."') AND p.post_status = 'publish' GROUP BY t.term_id ORDER BY t.name ASC");

			$results = $wpdb->get_results( $query );

			return $results;
		}
		
	}

	static public function db_migrate_data() {

		global $wpdb;

		$sources = get_option( 'awe_sources' );

		$sql_values = array();

		foreach ( $sources as $source ) {
			$sql_values[] = $wpdb->prepare( "(%s,%s,%s)", $source['slug'], $source['title'], $source['url'] );
		}

		// Copy sources

		$sql = 'INSERT IGNORE '.self::sources_table().' (source_ID,source_title,source_url) VALUES '.implode(',', $sql_values);

		$sources_status = $wpdb->query( $sql );

		// Copy posts-sources relationships

		$sql = 'INSERT IGNORE '.self::relationships_table().' (guid,source_ID) SELECT p.guid,s.meta_value FROM '.$wpdb->posts.' p LEFT JOIN '.$wpdb->postmeta.' s ON p.ID = s.post_id AND s.meta_key="source" WHERE p.post_type="update"';

		$relationships_status = $wpdb->query( $sql );

		// Copy posts

		$sql = 'INSERT IGNORE '.self::reader_table().' (guid,post_title,post_body,post_date,post_permalink,post_status) SELECT p.guid,p.post_title,p.post_content,p.post_date,m.meta_value,p.post_status FROM '.$wpdb->posts.' p LEFT JOIN '.$wpdb->postmeta.' m ON p.ID = m.post_id AND m.meta_key="source_permalink" WHERE p.post_type="update" AND p.post_status IN ("pending", "publish")';

		$updates_status = $wpdb->query( $sql );

		// Migrated old data status

		if ( get_option( 'awe_database_migrated' ) != self::$db_version ) {
		    update_option( 'awe_database_migrated', self::$db_version );
		} else {
		    add_option( 'awe_database_migrated', self::$db_version, ' ', 'no' );
		}

		return true;

	}

	static public function db_install() {

		global $wpdb;

		require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );

		$table = self::sources_table();
		$sql = "CREATE TABLE $table (
			source_ID VARCHAR(40),
			source_title TEXT NOT NULL,
			source_url VARCHAR(255) NOT NULL,
			source_updated DATETIME NULL,
			source_description TEXT NULL,
			source_site_url VARCHAR(255) NULL,
			source_status VARCHAR(20) DEFAULT 'new' NOT NULL,
			source_errors LONGTEXT NULL,
			PRIMARY KEY  (source_ID)
		);";

		$table = self::reader_table();
		$sql .= "CREATE TABLE $table (
			guid VARCHAR(255),
			post_title TEXT NOT NULL,
			post_body LONGTEXT NOT NULL,
			post_author TEXT NULL,
			post_date DATETIME NOT NULL,
			post_permalink VARCHAR(255) NOT NULL,
			post_status VARCHAR(20) DEFAULT 'pending' NOT NULL,
			PRIMARY KEY  (guid),
			KEY post_status (post_status),
			KEY post_date (post_date)
		);";

		$table = self::relationships_table();
		$sql .= "CREATE TABLE $table (
			source_ID VARCHAR(40) NOT NULL,
			guid VARCHAR(255) NOT NULL,
			PRIMARY KEY  (source_ID,guid)
		);";

		dbDelta( $sql );

		add_option( "awe_db_version", self::$db_version );
	}

	/**
	 * HTTP Redirection
	 */
	static public function redirect( $uri, $code=301 ) {
	    // Specific URL
	    $location = null;
	    if (substr($uri,0,4)=='http') {
	        $location = $uri;
	    } else {
	        $location = get_bloginfo('url');
	        // Special Trick, // starts at webserver root / starts at app root
	        if (substr($uri,0,2) == '//') {
	            $location .= '/' . ltrim($uri,'/');
	        } elseif (substr($uri,0,1) == '/') {
	            $location .= '/' . ltrim($uri,'/');
	        }
	    }

        switch ($code) {
        case 301:
            // Convert to GET
            header("301 Moved Permanently HTTP/1.1",true,$code);
            break;
        case 302:
            // Conform re-POST
            header("302 Found HTTP/1.1",true,$code);
            break;
        case 303:
            // dont cache, always use GET
            header("303 See Other HTTP/1.1",true,$code);
            break;
        case 304:
            // use cache
            header("304 Not Modified HTTP/1.1",true,$code);
            break;
        case 305:
            header("305 Use Proxy HTTP/1.1",true,$code);
            break;
        case 306:
            header("306 Not Used HTTP/1.1",true,$code);
            break;
        case 307:
            header("307 Temporary Redirect HTTP/1.1",true,$code);
            break;
        }
        //header('Cache-Control: no-store, no-cache, must-revalidate, post-check=0, pre-check=0');
        header("Location: $location");

	    exit(0);
	}

	/**
	 * Runs all of our hooks, filters, actions into WordPress
	 *
	 * @uses AWE::scandir_r()
	 * @uses AWE::templates_to_hash()
	 */
	static function initialize() {

		self::$minify = false;
		self::$template_basepath = dirname( __FILE__ ).'/templates';
		self::$templates = self::scandir_r( self::$template_basepath );
		self::templates_to_hash( self::$templates );

		// Allow video and audio tags 

		global $allowedposttags;

		$allowedposttags["audio"] = array(
			"poster" => array(),
			"controls" => array(),
			"height" => array(),
			"width" => array()
		);
		$allowedposttags["video"] = array(
			"poster" => array(),
			"controls" => array(),
			"height" => array(),
			"width" => array()
		);
		$allowedposttags["source"] = array(
			"src" => array(),
			"type" => array()
		);

		// Localization
		load_plugin_textdomain('awe', false, dirname( plugin_basename( __FILE__ ) ) . '/languages/' );

		// Avoid confusion of features by turning off a few administration tabs.
		add_action('admin_menu', 'AWE::modify_admin_menus');

		// Flush rewrite rules
		add_action('init', 'AWE::flush_rules');

		// Add our new rewrite rules based on our configuration.
		add_action('generate_rewrite_rules', 'AWE::generate_rewrite_rules');

		// Parse the request
		add_action('parse_request', 'AWE::parse_request');

		// Parse the query
		add_action('parse_query', 'AWE::parse_query');

		// Generate our http response
		add_action('template_redirect', 'AWE::response', 0);

		// Update hooks into our URI rewriting structure
		add_filter('query_vars', 'AWE::add_query_vars' );

		// Add a widget to broadcast reblogs
		wp_register_sidebar_widget( 
			'awe-updates',
			'Recent Reblogs',
			'AWE::widget_display',
			array(
				'description' => 'Shows latest reblogged posts'
			) 
		);

		// Add a widget for popular reblogs sources
		wp_register_sidebar_widget( 
			'awe-updates-sources',
			'Reblog Sources',
			'AWE::widget_sources_display',
			array(
				'description' => 'Shows piechart of most popular reblog sources'
			) 
		);

		// Add widget headers
		add_action('widgets_init', 'AWE::widgets_init');

		// AJAX
		add_action('wp_ajax_read_template_file', 'AWE::ajax_read_template_file');
		add_action('wp_ajax_save_template_file', 'AWE::ajax_save_template_file');
		add_action('wp_ajax_new_template_file', 'AWE::ajax_new_template_file');
		add_action('wp_ajax_delete_template_file', 'AWE::ajax_delete_template_file');
		add_action('wp_ajax_template_options', 'AWE::ajax_template_options');
		add_action('wp_ajax_save_rules', 'AWE::ajax_save_rules');
		add_action('wp_ajax_save_sources', 'AWE::ajax_save_sources');
		add_action('wp_ajax_reader_paged', 'AWE::ajax_reader_paged');
		add_action('wp_ajax_request_source', 'AWE::ajax_request_source');
		add_action('wp_ajax_save_updates', 'AWE::ajax_save_updates');
	}

	static function ajax_save_updates(){

		global $wpdb;

		$updates = $_REQUEST['updates'];
		$source_id = $_REQUEST['source_id'];

		$sql = 'INSERT INTO '.AWE::reader_table().' ( guid, post_title, post_body, post_author, post_date, post_permalink ) VALUES ';

		$sql_values = array();

		$sql_rel = 'INSERT IGNORE '.AWE::relationships_table().' (guid,source_ID) VALUES ';

		$sql_rel_values = array();

		foreach ( $updates as $item ) {

			$body = stripslashes( $item['description'] );

			$title = stripslashes( $item['title'] );

			$author = stripslashes( $item['author'] );

			// We keep the original GUID
			$guid = $item['id'];

			if ( strpos( $guid, 'http://' ) !== 0 &&
				strpos( $guid, 'https://' ) !== 0
				) {
				// TODO: avoid duplicates.... We previously made the GUID WordPressized
				$old_guid = apply_filters('post_guid', $item['link'] );
			}

			$sql_values[] = $wpdb->prepare("(%s,%s,%s,%s,%s,%s)", $guid, $title, $body, $author, $item['updated'], $item['link'] );

			$sql_rel_values[] = $wpdb->prepare("(%s,%s)", $guid, $source_id );

		}

		$sql .= implode(',', $sql_values).' ON DUPLICATE KEY UPDATE post_title=VALUES(post_title),post_body=VALUES(post_body),post_date=VALUES(post_date),post_permalink=VALUES(post_permalink);';

		$sql_rel .= implode(',', $sql_rel_values).';';

		$rows_affected = $wpdb->query( $sql );
		$rows_rel_affected = $wpdb->query( $sql_rel );

		exit(0);
	}

	static function ajax_request_source(){
		$sources = AWE_Sources::get_sources();
		if ( isset( $_REQUEST['source_id'] ) ) {
			$id = $_REQUEST['source_id'];
			if ( array_key_exists( $id, $sources ) ) {

				print wp_remote_retrieve_body( wp_remote_get( $sources[$id]['url'] ) );

			} else {
				print json_encode( array( 
					'status' => false, 
					'message' => __("No source available with that id", 'awe') 
					)
				);
			}
		} else {
			print json_encode( array( 
				'status' => false, 
				'message' => __("No source id specified", 'awe') 
				)
			);
		}
		exit();
	}

	static function ajax_reader_paged(){

		if ( isset( $_REQUEST['source'] ) ) {
			$args['source_id'] = $_REQUEST['source'];
		}
	
		if ( isset( $_REQUEST['paged'] ) ) {
			$args['paged'] = $_REQUEST['paged'];
		}

		if ( isset( $_REQUEST['status'] ) ) {
			$args['status'] = $_REQUEST['status'];
		}

		$items = AWE_Update::query( $args );

		foreach ( $items as $item ) {
			print AWE_Template::render( 'reader_item.php', array( 'item' => $item ) );
		}		

		exit(0);
		
	}

	static function widgets_init(){
	    if ( is_active_widget( false, false, 'awe-updates', true ) ) {
			wp_enqueue_style( 'recent-reblogs', plugins_url( '/static/awe/recent-reblogs.css', __FILE__ ) );
	    }
	}

	static public function feed_url( $type = 'rss2' ){
		global $wp_rewrite;
		if ( $wp_rewrite->permalink_structure == '' ) {
			$url = get_bloginfo('url').'/?awefeed='.$type;
		} else {
			if ( $type = 'rss2' ) {
				$url = get_bloginfo('url').'/update/';
			} else {
				$url = get_bloginfo('url').'/update/'.$type.'/';
			}
		}
		return $url;
	}

	static function widget_display(){
		print '<div class="widget-container widget_recent_reblogs" id="recent-reblogs">';
		print '<h3 class="widget-title">Recent Reblogs</h3>';
		print '<div class="widget-awe-follow"><a href="'.AWE::feed_url().'">Follow</a></div>';

		$args = array( 
			'posts_per_page' => get_option('posts_per_page'),
			'status' => 'publish'
		);
		$items = AWE_Update::query( $args );
		$sources = AWE_Sources::get_sources();
		print '<ul>';
		foreach ( $items as $item ) {
			print '<li><a href="'.$item['permalink'].'" title="'.$item['title'].'"><em>'.$item['source']['title'] .'</em> : '. $item['title'].'</a></li>';
		}
		print '</ul>';
		print '<div class="widget-generator-awe"><p><a href="http://wordpress.org/extend/plugins/awe/">Proudly powered by AWE</a></p></div>';
		print '</div>';
	}

	static function widget_sources_display(){
		wp_enqueue_script('jquery');
		wp_enqueue_script('highcharts', plugins_url('/static/highcharts/highcharts.js', __FILE__));
		wp_enqueue_script('highcharts', plugins_url('/static/highcharts/modules/exporting.js', __FILE__));
		
		wp_enqueue_script('awe-sources-widget', plugins_url('/static/awe/widget-sources.js', __FILE__));
		wp_enqueue_style('awe-sources-widget', plugins_url('/static/awe/widget-sources.css', __FILE__));


		$stats = AWE_Sources::get_sources_percentages();

		wp_localize_script('awe-sources-widget', 'sources_percentages', $stats);

		print '<div class="widget-container widget_recent_reblogs" id="recent-reblogs">';
		print '<h3 class="widget-title">Reblog Sources</h3>';
		$sources = AWE_Sources::get_sources();
		print '<div id="awe-widget-sources"></div>';
		print '</div>';
	}

	/**
	 * Additional action callback for the WordPress parse_request for dealing with 404 not found.
	 */
	static function parse_request( &$request ) {

		if ( isset( $request->query_vars['url'] ) ) {
			AWE::redirect( $request->query_vars['url'] );
			exit();
		}

		if( ( $request->matched_rule === NULL || $request->matched_rule == '' ) && strpos( $request->request, 'wp-admin' ) != 0 ) {
			header( "Status: 404 Not Found" );

			print '<h1>404 Not Found</h1>';
			exit();
		}
	    return $request;
	}

	/**
	 * Additional action callback for the WordPress parse_query to translate query variables.
	 */
	static function parse_query( &$query ) {

		// add ability to query for multiple post types from the query_string
		global $wp;
		preg_match('/post_type=([a-zA-Z\-\_\+]+)\&/', $wp->matched_query, $matches );
		if ( isset( $matches[1] ) ) { 
			if ( strstr($matches[1], '+') ) {
				$post_types = explode('+', $matches[1] );
				$query->set( 'post_type', $post_types );
			}
		}

		if ( isset( $query->query_vars['numberposts'] ) ){
			$query->set( 'posts_per_page', $query->query_vars['numberposts'] );
		};

		if ( isset( $query->query_vars['taxonomy_slugs'] ) && isset( $query->query_vars['taxonomy_post_types'] ) ) {

			global $terms;

			$slug = $query->query_vars['taxonomy_slugs'];
			$post_types = explode( '+', $query->query_vars['taxonomy_post_types'] );

			$terms[$slug] = AWE::get_terms_by_post_type( $slug, $post_types );

		}

		return $query;
	}

	/**
	 * Additional action callback for the WordPress to add additional query_vars.
	 */
	static function add_query_vars( $query_vars ) {
		// Add our template query variable for WP_Query
		$query_vars[] = 'awefeed';
		$query_vars[] = 'numberposts';
		$query_vars[] = 'can_be_empty';
		$query_vars[] = 'post_status';
		$query_vars[] = 'url';
		$query_vars[] = 'taxonomy_slugs';
		$query_vars[] = 'taxonomy_post_types';
		return $query_vars;
	}

	/**
	 * Addional action callback to add and remove menus from the WordPress admin.
	 */
	static function modify_admin_menus() {

		// Add our replacement menu
		$awereader = add_menu_page(__('Reader', 'awe'), __('Reader', 'awe'), 'publish_posts', 'awe', 'AWE::reader_page', plugins_url( '/static/awe/awe_icon_16.png', __FILE__  ), 0 );

		$awesources = add_submenu_page('awe', __('Sources', 'awe'), __('Sources', 'awe'), 'publish_posts', 'awesources', 'AWE::sources_page', '', 60);

		// Custom Admin
		add_action( 'admin_print_styles', 'AWE::admin_general_styles' );
		add_action( 'admin_print_styles-'.$awesources, 'AWE::admin_styles' );
		add_action( 'admin_print_styles-'.$awereader, 'AWE::admin_styles' );
		add_action( 'admin_print_scripts-'.$awesources, 'AWE::admin_headers_sources' );
		add_action( 'admin_print_scripts-'.$awereader, 'AWE::admin_headers_reader' );

	} 

	/**
	 * Callback to add custom stylesheets to WordPress.
	 */
	static function admin_styles() {
		wp_enqueue_style( 'awecss', plugins_url( '/static/awe/options-awe.css', __FILE__ ) );

		$reader_style = get_option( 'awe_reader_style', 'opensans' );

		wp_enqueue_style( 'awecss-'.$reader_style.'', plugins_url( '/static/awe/options-awe-'.$reader_style.'.css', __FILE__ ) );
	}

	/**
	 * Callback to add custom stylesheets to WordPress.
	 */
	static function admin_general_styles() {
		wp_enqueue_style( 'awe-repost', plugins_url( '/static/awe/awe-repost.css', __FILE__ ) );
	}

	/**
	 * Callback to add custom scripts to WordPress, specifically for the routing options page.
	 */
	static function admin_headers_routing() {
		wp_enqueue_script('jquery-ui-awecustom', 
			plugins_url( '/static/jquery/jquery-ui-1.8.11.custom.min.js', __FILE__) );
		wp_enqueue_script('jquery-ui-nestedsortable', 
			plugins_url( '/static/jquery/jquery.ui.nestedSortable.js', __FILE__) );
		wp_enqueue_script('awe-routing', 
			plugins_url( '/static/awe/options-awe-routing.js', __FILE__) );
	}

	/**
	 * Callback to add custom scripts to WordPress, specifically for the templates options page.
	 */
	static function admin_headers_templates() {
		wp_enqueue_script('ace', 
			plugins_url( '/static/ace/src/ace.js', __FILE__) );
		wp_enqueue_script('ace-php', 
			plugins_url( '/static/ace/src/mode-php.js', __FILE__) );
		wp_enqueue_script('ace-emacs', 
			plugins_url( '/static/ace/src/keybinding-emacs.js', __FILE__) );
		wp_enqueue_script('awe-templates', 
			plugins_url( '/static/awe/options-awe-templates.js', __FILE__) );
	}

	/**
	 * Callback to add custom scripts to WordPress, specifically for sources options page.
	 */
	static function admin_headers_sources() {
		wp_enqueue_script('awe-sources', 
			plugins_url( '/static/awe/options-awe-sources.js', __FILE__) );
	}

	/**
	 * Callback to add custom scripts to WordPress, specifically for sources options page.
	 */
	static function admin_headers_reader() {
		wp_enqueue_script('moment', 
			plugins_url( '/static/moment/moment.min.js', __FILE__) );
		wp_enqueue_script('awe-sources', 
			plugins_url( '/static/awe/options-awe-reader.js', __FILE__) );
		wp_enqueue_script('endless-scroll',	
			plugins_url( '/static/jquery/jquery.endless-scroll.js', __FILE__) );
	}

	/**
	 * Generates HTML messages for our options pages.
	 *
	 */
	static public function messages() {

		$html = '';

		$html .= '<div id="messages">';
		foreach ( AWE::error_log() as $error ) {
			$html .= '<div class="error below-h2"><p>'.$error.'</p></div>';
		}
		delete_option( 'awe_errors' );

		foreach ( AWE::log() as $message ) {
			$html .= '<div class="updated below-h2"><p>'.$message.'</p></div>';
		}
		delete_option( 'awe_messages' );
		$html .= '</div>';

		return $html;
	}

	/**
	 * Generate multi-dimentional array of directories and files
	 *
	 * This will make a multi-dimentional array with files and directories from the
	 * given filename. Such as this: array( [0] => array( <rel_path>, <sha1_of_abs_path> ,
	 * <abs_path> ), 'directory' => array( [0] => array( <rel_path> ...
	 *
	 * @uses AWE::scandir_r()
	 *
	 * @param string $directory The permalink structure.
	 * @return array Multi-dimentional array with three string values at the endpoints.
	 */
	private static function scandir_r( $directory ) {

		$return = array();

		if ( is_dir( $directory ) ) {
			foreach ( scandir( $directory ) as $file ) {
				if ( $file == '.' || $file == '..') 
					continue;
				if (is_dir($directory.'/'.$file)) {
					$return[$file] = self::scandir_r($directory.'/'.$file);
				} else {
					$filepathinfo = pathinfo($file);
					if ( isset( $filepathinfo['extension'] ) ) {
						if ( $filepathinfo['extension'] == 'php' || $filepathinfo['extension'] == 'js' || $filepathinfo['extension'] == 'css' ) {
							$p = $directory.'/'.$file;
							$file_path = str_replace( self::$template_basepath, '', $p );
							$return[] = array($file_path, sha1($p), $p);
						}
					}
				}
			}
		} else {
			$return[] = $directory;
		}
		return $return;
	}

	/**
	 * Generates HTML options tags with our templates, with our current template selected.
	 *
	 * @uses AWE::listdir_r()
	 *
	 * @param array $dirs Multi-dimentional array return from scandir_r.
	 * @param string $selected The relative path to the selected template file.	
	 * @return sting HTML options tags	
	 */
	static public function listdir_r( $dirs, $selected = null){
		$html = '';
		foreach( $dirs as $key => $value ){
			if ( gettype( $key ) == 'string' ){
				$html .= self::listdir_r( $dirs[$key], $selected );
			} else {
				if( $value[0] == $selected ){
					$sel = ' selected';
				} else {
					$sel = '';
				}
				$html .= '<option value="'.$value[1].'"'.$sel.'>'.$value[0].'</option>';
			}
		}
		return $html;
	}

	/**
	 * Validates multidimentional array from save_routes
	 *
	 * Will take a tree of url rules data and validate each of the values to make sure
	 * that no two rules use the same request or path and are not blank.
	 *
	 * @param array $a Multi-dimentional array from the routing page output.
	 * @return array Status and messages.
	 */
	static public function validate_rules( &$a , $key, &$return ) {

		// Check path not blank
		if ( $a['path'] == '' ) {
			$return['messages'][] =  '<strong>'.$a['title'] . ':</strong> ' .__("<em>URL Path</em> can't be blank.", 'awe');

		// Check for leading forward slash
		} else if ( strpos($a['path'], '/') === 0 ) {
			$return['messages'][] =  '<strong>'.$a['title'] . ':</strong> ' .__("<em>URL Path</em> can't have a leading forward slash ( / ).", 'awe');
			
		// Check for terminator
		} else if ( strpos($a['path'], '$') != strlen($a['path'])-1 ) {
			$return['messages'][] =  '<strong>'.$a['title'] . ':</strong> ' .__("<em>URL Path</em> needs a terminating dollar sign ( $ ).", 'awe');
			
		}

		// Check for duplicate path
		if ( in_array( $a['path'], $return['paths'] ) ) {
			$return['messages'][] =  '<strong>'.$a['title'] . ':</strong> ' .__('Duplicate <em>URL Path</em> found in another rule.', 'awe');
		}

		// Check request not blank
		if ( $a['request'] == '' ) {
			$return['messages'][] =  '<strong>'.$a['title'] . ':</strong> ' .__("<em>Request Posts</em> can't be blank.", 'awe');
		}

		// Check for duplicate request
		if ( in_array( $a['request'], $return['requests'] ) ) {
			$return['messages'][] =  '<strong>'.$a['title'] . ':</strong> ' .__('Duplicate <em>Request Posts</em> found in another rule.', 'awe');
		}

		$return['paths'][] = $a['path'];
		$return['requests'][] = $a['request'];

		if ( isset( $a['children'] ) ) {
			self::array_walk( $a['children'], 'AWE::validate_rules', $return );
		}

	}


	/**
	 * Validates there are no duplicate values in an array of 
	 * associative arrays with the same keys.
	 *
	 * @param array $a array of associative arrays
	 * @return boolean
	 */
	static public function validate_sources_duplicates( $sources ) {

		$urls = array();

		foreach ( $sources as $source ) {
			$urls[] = $source['url'];
		}

		if ( count( $urls ) > count( array_unique( $urls ) ) ) {
			return true;
		}

		return false;
	}


	/**
	 * Validates single dimentional array from save_sources
	 *
	 * Will take an array and verify that each value is valid. The slug,
	 * title, or url doesn't already exist. The slug has valid characters,
	 * and the url is an reachable xml feed source.
	 *
	 * @param array $a array from the sources page output.
	 * @return array Status and messages.
	 */
	static public function validate_sources( &$a , $key, &$return ) {

		// Check slug, title or url are not blank
		if ( $a['title'] == '' || $a['slug'] == '' || $a['url'] == '' ) {
			$return['messages'][] =  __("All fields must filled.", 'awe');
		}

		if ( !preg_match( "/^[A-Za-z0-9-_]+$/", $a['slug'] ) ){
			$return['messages'][] = '<strong>Slug:<strong> '.__('"'.$a['slug'].'" is an invalid slug. A slug is used in a URL and can have upper and lowercase characters a to z, numerals, dashes, and underscores.', 'awe');
		}

	}

	/**
	 * AJAX security check.
	 *
	 * Will verify nonce values and check that the current user has permissions to edit options.
	 * If there are any problems it will print back JSON.
	 *
	 * @param object $post WordPress post
	 */
	static private function security_check($post){

		$nonce=$post['nonce'];

		if (! wp_verify_nonce($nonce, 'awe') ) {
			print json_encode( array( 
				'status' => false, 
				'message' => __("Security check fail.", 'awe') 
				)
			);
			exit();
		}

		if (! current_user_can( 'manage_options' ) ) {
			print json_encode( array( 
				'status' => false, 
				'message' => __("You don't have permission to edit url rules", 'awe')
				) 
			);
			exit();			
		} 

	}


	/**
	 * AJAX save sources list.
	 *
	 * Will verify and save routing rule values, and print back JSON.
	 * 
	 * @uses AWE::security_check()
	 * @uses AWE::scandir_r()	
	 * @uses AWE::indent_json()	
	 *
	 */
	static public function ajax_save_sources(){

		self::security_check($_POST);

		$sources = $_POST['sources'];

		$savetime = date_i18n( 'G:i:s' );

		if ( self::validate_sources_duplicates( $sources ) ) {

			print json_encode( array( 
				'status' => false,
				'message' => __("Duplicate values found. Time:", 'awe') . ' ' .$savetime,
				'messages' => array()
				) 
			);
			exit();
			
		}

		$validator = array( 'sources' => $sources, 'messages' => array() );

		self::array_walk( $sources , 'AWE::validate_sources', $validator );

		if ( count( $validator['messages'] ) > 0 ) {

			print json_encode( array( 
				'status' => false,
				'message' => __("Failed to save at:", 'awe') . ' ' .$savetime,
				'messages' => $validator['messages']
				) 
			);
			exit();

		} else {

			AWE_Sources::update_sources( $sources );
    
			print json_encode( array( 
				'status' => true,
				'message' => __("Sources Saved at: ", 'awe') . $savetime
				) 
			);
			exit();
		}
	}

	/**
	 * Callback to print out options page for templates.
	 */
	static public function templates_page(){

		include ( 'options-awe-templates.php' );

	}

	/**
	 * Callback to print out options page for templates.
	 */
	static public function reader_page(){

		if ( !AWE::is_migrated() ) {
			include ( 'options-awe-migrate.php' );
		} else {
			include ( 'options-awe-reader.php' );
		}

	}

	/**
	 * Callback to print out options page for routing.
	 */
	static public function sources_page(){

		if ( !AWE::is_migrated() ) {
			include ( 'options-awe-migrate.php' );
		} else {
			include ( 'options-awe-sources.php' );
		}

	}

	/**
	 * Shortcut to include template file
	 *
	 * Will include a file from the template directory.
	 *
	 */
	static public function include_template( $filepath ) {
		if ( file_exists( self::$template_basepath . '/' . $filepath ) ) {
			include ( self::$template_basepath . '/' . $filepath );
		} else {
			print 'Error: Template file not found.';
			//throw Exception('Template file not found');
		}
	}

	/**
	 * Callback to replace the standard WordPress rewrite rules.
	 *
	 * @uses AWE_Rewrite::generate_rules()
	 *
	 */
	static function generate_rewrite_rules() {

		global $wp_rewrite;

		$rules = array(
			'update/?$' => 'index.php?awefeed=rss2',
			'update/atom/?$' => 'index.php?awefeed=atom',
			'update/rdf/?$' => 'index.php?awefeed=rdf',
			'update/rss/?$' => 'index.php?awefeed=rss'
		);

		$wp_rewrite->rules = array_merge($rules, $wp_rewrite->rules);

	}  

	/**
	 * Callback to print our HTTP response.
	 *
	 * @uses AWE::$template_basepath
	 */
	static function response(){
		global $wp_query;
		global $wp;
		global $posts;

		if ( isset( $wp_query->query_vars['awefeed'] ) ){

			$args = array(
				'status' => 'publish',
				'posts_per_page' => 50
			);

			$items = AWE_Update::query( $args );

			$template_base = $wp_query->query_vars['awefeed'];
			ob_start();
			include ( self::$template_basepath.'/feeds/'.$template_base.'.php' );
			$html = ob_get_contents();
			ob_end_clean();

			print $html;

			exit();
		}
	}

	/**
	 * Indents a flat JSON string to make it more human-readable.
	 *
	 * @param string $json The original JSON string to process.
	 * @return string Indented version of the original JSON string.
	 */
	static public function indent_json( $json ) {

		$result = '';
		$pos = 0;
		$str_len = strlen($json);
		$indent_str = '  ';
		$new_line = "\n";
		$prev_char = '';
		$out_of_quotes = true;

		for ( $i=0; $i<=$str_len; $i++ ) {

			// Grab the next character in the string.
			$char = substr($json, $i, 1);

			// Are we inside a quoted string?
			if ( $char == '"' && $prev_char != '\\' ) {
				$out_of_quotes = !$out_of_quotes;
        
				// If this character is the end of an element, 
				// output a new line and indent the next line.
			} else if ( ( $char == '}' || $char == ']' ) && $out_of_quotes ) {
				$result .= $new_line;
				$pos --;
				for ( $j=0; $j<$pos; $j++ ) {
					$result .= $indent_str;
				}
			}
        
			// Add the character to the result string.
			$result .= $char;

			// If the last character was the beginning of an element, 
			// output a new line and indent the next line.
			if ( ( $char == ',' || $char == '{' || $char == '[' ) && $out_of_quotes ) {
				$result .= $new_line;
				if ( $char == '{' || $char == '[' ) {
					$pos ++;
				}
            
				for ( $j = 0; $j < $pos; $j++ ) {
					$result .= $indent_str;
				}
			}
        
			$prev_char = $char;
		}
		return $result;
	}
}

?>
