<?php
/*
Plugin Name: AWE
Version: 0.6.7
Description: A feed reader with reblogging features. Includes a recent reblog widget with a link to subscribe to the reblog feed. 
Author: Braydon Fuller
Author URI: http://braydon.com/
Text Domain: awe
License: GPL2
*/

add_theme_support( 'post-thumbnails' );

include_once ( 'class-awe-update.php' );
include_once ( 'class-awe-sources.php' );
include_once ( 'class-awe-template.php' );
include_once ( 'class-awe.php' );

// Activation
register_activation_hook( __FILE__, 'AWE::db_install' );
//register_activation_hook( __FILE__, 'AWE::db_install_data' );

add_action( 'register_navigation', 'awe_register_ravigation' );
add_action( 'register_templates', 'awe_register_templates' );

function awe_register_templates(){
	if ( function_exists( 'register_templates' ) ) {
		register_templates( 'awe', dirname( __FILE__ ).'/templates' );
	}
}

function awe_register_ravigation(){
	if ( function_exists( 'register_navigation' ) ) {

		register_navigation( array(
			'title' => 'Reader',
			'id' => 'reader',
			'path' => 'reader/?$',
			'menu' => true,
			'weight' => 4,
			'request' => array(
				'post_type' => 'update',
				'numberposts' => 10
			),
			'template_set' => 'awe',
			'template' => 'reader.php',
			)
		);

		register_navigation(array(
			'title' => 'Feeds',
			'id' => 'reader-feed',
			'parent' => 'reader',
			'path' => 'reader/feed/?$',
			'menu' => true,
			'weight' => 4,
			'request' => array(
				'post_type' => 'update',
				'numberposts' => 20,
				'feed' => 'rss2'
			),
			'template_set' => 'awe',
			'template' => 'feeds/rss2.php',
			)
		);

		register_navigation(array(
			'title' => 'RSS',
			'id' => 'reader-rss',
			'parent' => 'reader-feed',
			'path' => 'reader/feed/rss/?$',
			'menu' => true,
			'weight' => 3,
			'request' => array(
				'post_type' => 'update',
				'numberposts' => 20,
				'feed' => 'rss'
			),
			'template_set' => 'awe',
			'template' => 'feeds/rss.php',
			)
		);

		register_navigation(array(
			'title' => 'RDF',
			'id' => 'reader-rdf',
			'parent' => 'reader-feed',
			'path' => 'reader/feed/rdf/?$',
			'menu' => true,
			'weight' => 2,
			'request' => array(
				'post_type' => 'update',
				'numberposts' => 20,
				'feed' => 'rdf'
			),
			'template_set' => 'awe',
			'template' => 'feeds/rdf.php',
			)
		);

		register_navigation(array(
			'title' => 'Atom',
			'id' => 'reader-atom',
			'parent' => 'reader-feed',
			'path' => 'reader/feed/atom/?$',
			'menu' => true,
			'weight' => 1,
			'request' => array(
				'post_type' => 'update',
				'numberposts' => 20,
				'feed' => 'atom'
			),
			'template_set' => 'awe',
			'template' => 'feeds/atom.php',
			)
		);

		register_navigation(array(
			'title' => 'Archives',
			'id' => 'reader-archives',
			'parent' => 'reader',
			'path' => 'reader/archive/?$',
			'menu' => true,
			'weight' => 4,
			'request' => array(
				'post_type' => 'update',
				'taxonomy_slugs' => 'month',
				'taxonomy_post_types' => 'update'
			),
			'template_set' => 'awe',
			'template' => 'terms.php',
			)
		);

		register_navigation(array(
			'title' => 'Year',
			'id' => 'reader-year',
			'parent' => 'archives',
			'path' => 'reader/%year%/?$',
			'menu' => true,
			'weight' => 0,
			'request' => array(
				'post_type' => 'update',
				'year' => '%year%',
			),
			'template_set' => 'awe',
			'template' => 'taxonomy.php',
			)
		);

		register_navigation(array(
			'title' => 'Month',
			'id' => 'reader-month',
			'parent' => 'reader-year',
			'path' => 'reader/%year%/%monthnum%/?$',
			'menu' => true,
			'weight' => 0,
			'request' => array(
				'post_type' => 'update',
				'year' => '%year%',
				'monthnum' => '%monthnum%'
			),
			'template_set' => 'awe',
			'template' => 'taxonomy.php',
			)
		);

		register_navigation(array(
			'title' => 'Reblog',
			'id' => 'update',
			'parent' => 'reader-month',
			'path' => 'news/%year%/%monthnum%/%postname%/?$',
			'menu' => true,
			'weight' => 0,
			'request' => array(
				'post_type' => 'update',
				'year' => '%year%',
				'monthnum' => '%monthnum%',
				'name' => '%postname%'
			),
			'template_set' => 'awe',
			'template' => 'reader.php',
			)
		);

	}
}

AWE_Sources::initialize();
AWE::initialize();

add_action( 'init', 'awe_init' );

function awe_init() {
	// Setup sails untie the cleats
	AWE_Update::initialize();
}

?>
