<?php

	print '<div class="awe-update" data-id="' . $item['guid'] . '">';
	print '<div class="awe-update-source">';

	print '<div class="awe-update-source-meta">';

	if ( isset($item['author']) && strlen( $item['author'] ) > 0 ) {
		print '<span class="awe-update-source-author">'.$item['author'].' via </span>';
	}	

	print '<a class="awe-update-source-meta-title" href="' . $item['source']['url'] . '">' . $item['source']['title'] . '</a> ';
	print ' <span class="awe-update-source-meta-date"> - '.date('M j, Y H:i:s',  strtotime( $item['date'] )  ).'  </span>';

	print '<div class="awe-update-actions">';
	if ( current_user_can( 'publish_posts' ) ) {
		$selected = '';
		if ( $item['status'] == 'publish' ) {
			$selected = ' selected';
		}
		print '<div data-id="' . $item['guid'] . '" class="awe-update-publish '.$selected.'"><div class="awe-update-publish-button"></div>' . __("Reblog", 'awe') . '</div>';
	} 
	print '</div>';



	print '</div>';

	print '</div>';
	print '<div class="awe-update-body">';
	print '<h2 class="awe-update-title">';
	print '<a href="' . $item['permalink'] . '">' . $item['title'] . '</a>';
	print '</h2>';

	// Filter body to remove JavaScript and other stuff that could be harmful
	print wp_kses_post( $item['body'] );

	print '</div>';
	print '</div>';

?>