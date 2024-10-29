<?php

$args = array();

if ( isset( $_GET['paged'] ) ) {
	$args['paged'] = $_GET['paged'];
}
if ( isset( $_GET['source'] ) ) {
	if ( $_GET['source'] != 'all' ) {
		$args['source_id'] = $_GET['source'];
	}
} 

if ( isset( $_GET['status'] ) ) {
	$args['status'] = $_GET['status'];
}

$items = AWE_Update::query( $args );

$awe_sources = AWE_Sources::get_sources();

print '<script>';
print 'var nonce = "'.wp_create_nonce('awe').'";';
print 'var awe_sources = '.json_encode( $awe_sources ).';';
print '</script>';

?>

<script type="text/javascript" charset="utf-8">
	jQuery(function($) {

		$(document).endlessScroll({
			bottomPixels: 500,
			fireDelay: 100,
			callback: function(i) {
				var data = {
					<?php if ( isset( $_GET['source'] ) ) print 'source: "'.$_GET['source'].'",'; ?>
					<?php if ( isset( $_GET['status'] ) ) print 'status: "'.$_GET['status'].'",'; ?>
					action: 'reader_paged',
					paged: i+1
				};

				jQuery.post(ajaxurl, data, function(response) {
					$('#awe-updates-wrapper').append($(response));
				});

			}
		});
	});
</script>

<?php 

print '<div class="wrap">';
print '<div class="icon32" id="icon-options-awe"><br></div>';
print '<h2>'.__("Reader", 'awe').'</h2>';

print self::messages();

print '<div class="awe-new-source">';
print '<p>Enter the website URL</p>';
print '<form action="'.admin_url('admin.php?page=awesources').'" method="POST">';
print '<input type="hidden" name="new-source" value="true"></input>';
print '<input type="text" name="new-source-url" class="awe-new-source-url" value=""></input>';
print '<input type="submit" value="Add Source" class="button-primary awe-new-source-submit"></input>';
print '</form>';
print '</div>';

print '<div class="tablenav top">';
print '<div class="alignleft actions">';
print '<div id="awe-refresh-manual" type="submit" name="" id="post-query-submit" class="button-secondary"><span>Refresh</span></div>';
print '</div>';




?>
	<form action="" method="get">
	<input type="hidden" name="page" value="awe">
	<div class="alignleft actions">
	<select name="source">
		<?php 
		$source_options_html = '';
		foreach ( $awe_sources as $source_id => $source ) {
			$selected = '';
			if ( isset( $_GET['source'] ) && $_GET['source'] == $source_id ) {
				$selected = ' selected="selected"';
			} 
			$source_options_html .= '<option value="'.$source_id.'" '.$selected.'>'.$source['title'].'</option>';
		}

		$selected = '';
		if ( !isset( $_GET['source'] ) ) {
			$selected = ' selected="selected"';
			$source = false;
		} else if ( $_GET['source'] == 'all' ) {
			$source = false;
		} else {
			$source = $_GET['source'];
		}
		print '<option value="all" '.$selected.'>Show all sources</option>';
		print $source_options_html;

		?>
	</select>

	<input type="submit" value="Filter" class="button-secondary" id="post-query-submit" name="">
	</form>
	</div>
<?php
print '</div>';

print '<ul class="subsubsub">';

if ( isset($_GET['source']) ) {
	$source_url = '&source='.$_GET['source'];
} else {
	$source_url = '';
}

if ( !isset($_REQUEST['status']) ) {
	$current = ' class="current"';
} else {
	$current = '';
}

print '<li class="all"><a href="admin.php?page=awe'.$source_url.'"'.$current.'>All <span class="count">('.AWE_update::total($source).')</span></a> |</li>';

if ( isset($_GET['status']) && $_GET['status'] == 'publish' ) {
	$current = ' class="current"';
} else {
	$current = '';
}
print '<li class="publish"><a href="admin.php?page=awe'.$source_url.'&status=publish"'.$current.'>Reblogs <span class="count">('.AWE_Update::total_published($source).')</span></a></li>';

print '</ul>';

if ( count( $items ) > 0 ) {

	print '<div id="awe-updates-wrapper">';

	foreach ( $items as $item ) {
		print AWE_Template::render( 'reader_item.php', array( 'item' => $item, 'awe_sources' => $awe_sources ));
	}

	print '</div>';

} else {
	print '<p>Nothing to read yet.</p>';
}

print '</div>';

?>
