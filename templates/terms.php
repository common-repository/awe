<?php 
	print AWEBook_Template::render( 'awebook', 'global/header.php', array( 'robots' => false ) ); 
?>

<?php

	global $terms;

	if ( array_key_exists( 'category', $terms ) ) {
		print '<ul class="awe-terms">';
		$out = array();
		$total = count( $terms['category'] );
		$count = 1;
		foreach ( $terms['category'] as $term ) {
			if ( $total > $count ) {
				$seperator = '<span class="awe-term-seperator"></span>';
			} else {
				$seperator = '';
			}
			global $wp_query;
			$post_types = $wp_query->query_vars['taxonomy_post_types'];
			$url = get_bloginfo('url') . '/' . AWEBook_Rewrite::uri( 'reader-category', array( 'post_type' => $post_types, 'category_name' => $term->slug ) );
			print '<li> <a href="' . $url . '"> ' . $term->name . '</a><span class="awe-term-count">('.$term->{'COUNT(*)'}.')</span>  '.$seperator.'</li>';
			$count++;
		}
		print '</ul>';
	}


	if ( array_key_exists( 'post_tag', $terms ) ) {
		print '<ul class="awe-terms">';
		$out = array();
		$total = count( $terms['post_tag'] );
		$count = 1;
		foreach ( $terms['post_tag'] as $term ) {
			if ( $total > $count ) {
				$seperator = '<span class="awe-term-seperator"></span>';
			} else {
				$seperator = '';
			}
			global $wp_query;
			$post_types = $wp_query->query_vars['taxonomy_post_types'];
			$url = get_bloginfo('url') . '/' . AWEBook_Rewrite::uri( 'reader-tag', array( 'post_type' => $post_types, 'post_tag' => $term->slug ) );
			print '<li> <a href="' . $url . '"> ' . $term->name . '</a><span class="awe-term-count">('.$term->{'COUNT(*)'}.')</span>  '.$seperator.'</li>';
			$count++;
		}
		print '</ul>';
	}

	if ( array_key_exists( 'year', $terms ) ) {
		print '<ul class="awe-terms">';
		$out = array();
		$total = count( $terms['year'] );
		$count = 1;
		foreach ( $terms['year'] as $term ) {
			if ( $total > $count ) {
				$seperator = '<span class="awe-term-seperator"></span>';
			} else {
				$seperator = '';
			}
			global $wp_query;
			$post_types = $wp_query->query_vars['taxonomy_post_types'];
			$url = get_bloginfo('url') . '/' . AWEBook_Rewrite::uri( 'reader-year', array( 'post_type' => $post_types, 'year' => $term->slug ) );
			print '<li> <a href="' . $url . '"> ' . $term->name . '</a><span class="awe-term-count">('.$term->count.')</span>  '.$seperator.'</li>';
			$count++;
		}
		print '</ul>';
	}

	if ( array_key_exists( 'month', $terms ) ) {
		print '<ul class="awe-terms">';
		$out = array();
		$year_total = count( $terms['month'] );
		$year_count = 1;
		foreach ( $terms['month'] as $year => $months ) {
			if ( $year_total > $year_count ) {
				$seperator = '<span class="awe-term-seperator"></span>';
			} else {
				$seperator = '';
			}
			global $wp_query;
			$post_types = $wp_query->query_vars['taxonomy_post_types'];
			$url = get_bloginfo('url') . '/' . AWEBook_Rewrite::uri( 'reader-year', array( 'post_type' => $post_types, 'year' => $year ) );
			print '<li> <a href="' . $url . '"> ' . $year . '</a>:';
			print '<ul>';
			$month_total = count( $months );
			$month_count = 1;
			foreach( $months as $term ) {
				if ( $month_total > $month_count ) {
					$seperator = '<span class="awe-term-seperator"></span>';
				} else {
					$seperator = '';
				}
				$url = get_bloginfo('url') . '/' . AWEBook_Rewrite::uri( 'reader-month', array( 'post_type' => $post_types, 'year' => $year, 'monthnum' => $term->slug ) );
				print '<li><a href="'.$url.'">' . $term->name . '</a><span class="awe-term-count">('.$term->count.')</span>  '.$seperator.'</li>';
				$month_count++;
			}
			print '</ul>';
			print '</li>';
			$year_count++;
		}
		print '</ul>';
	}

?>

<?php 
	print AWEBook_Template::render( 'awebook', 'global/footer.php', array() ); 
?>
