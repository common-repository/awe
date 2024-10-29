<?php 

	global $posts;
	global $wp_query;

	if ( $wp_query->query_vars['post_type'] == 'update' ) {
		$robots = false;
	} else {
		$robots = true;
	}	

	print AWEBook_Template::render( 'awebook', 'global/header.php', array( 'robots' => $robots ) ); 
?>

<?php
	print '<h1 class="awe-taxonomy-title">';
	if ( is_category() ) {
		printf( __( 'Category: %s', 'awe' ), '<span>' . single_cat_title( '', false ) . '</span>' );
	} elseif ( is_tag() ) {
		printf( __( 'Tag: %s', 'awe' ), '<span>' . single_tag_title( '', false ) . '</span>' );
	} elseif ( is_day() ) {
		printf( __( 'Daily Archives: %s', 'awe' ), '<span>' . get_the_date() . '</span>' ); 
	} elseif ( is_month() ) {
		printf( __( 'Monthly Archives: %s', 'awe' ), '<span>' . get_the_date( 'F Y' ) . '</span>' );
	} elseif ( is_year() ) {
		printf( __( 'Yearly Archives: %s', 'awe' ), '<span>' . get_the_date( 'Y' ) . '</span>' );
	} else {
		_e( 'Archives', 'awe' );
	}
	print '</h1>';

	foreach ( $posts as $post ) {
		global $post;
		if ( $post->post_type == 'update' ) {
			print AWEBook_Template::render( 'awebook', 'posts/update.php', array( 'post' => $post, 'view' => 'summary' ) );
		} else if ( $post->post_type == 'post' ) {
			print AWEBook_Template::render( 'awebook', 'posts/post.php', array( 'post' => $post, 'view' => 'summary' ) );
		}
	}

?>

<?php 
	print AWEBook_Template::render( 'awebook', 'global/footer.php', array() ); 
?>
