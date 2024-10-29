<?php

print '<script>';
print 'var nonce = "'.wp_create_nonce('awe').'";';
print '</script>';

print '<div class="wrap">';
print '<div class="icon32" id="icon-options-awe"><br></div>';
print '<h2>'.__("Sources", 'awe').'</h2>';

if ( isset( $_REQUEST['new-source'] ) ) {
	AWE_Sources::add_source( $_REQUEST['new-source-url'] );
}

print self::messages();

?>

<div class="awe-new-source">
<p>Enter the website URL</p>
<form action="" method="POST">
<input type="hidden" name="new-source" value="true"></input>
<input type="text" name="new-source-url" class="awe-new-source-url" value=""></input>
<input type="submit" value="Add Source" class="button-primary awe-new-source-submit"></input>
</form>
</div>

<?php


print '<form method="POST" action="">';

function render_source_input( $source ) {

	$html = '<tr class="awe-sources-item">
			<td><input type="hidden" value="'.esc_attr($source['slug']).'" class="awe-sources-list-slug-field"><input type="text" value="'.esc_attr($source['title']).'" class="awe-sources-list-title-field"></td>
			<td><input type="text" value="'.esc_attr($source['url']).'" class="awe-sources-list-url-field"></td>
		   </tr>';

	return $html;
}

print '<script>';
//print 'var source_template = '.json_encode( render_source_input() ).';';
print 'var nonce = "'.wp_create_nonce('awe').'";';
print '</script>';


print '<table id="awe-sources-list" class="wp-list-table widefat fixed bookmarks">';
print '<thead>';
print '<tr><th class="awe-sources-list-title">Title</th><th class="awe-sources-list-url">Feed URL</th></tr>';
print '</thead>';

foreach( AWE_Sources::get_sources() as $source ) {
	print render_source_input( $source );
}

print '</table>';

print '<p>';
print '<input id="submit" type="submit" value="Save Changes">';
print '</p>';

print '</form>';


print '</div>';

?>
