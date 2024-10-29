<?php 

$migrated = false;

if ( isset( $_REQUEST['task'] ) && $_REQUEST['task'] == 'migrate_database' ) {
	$migrated = AWE::db_migrate_data();
}

print '<div class="wrap">';
print '<div class="icon32" id="icon-options-awe"><br></div>';
print '<h2>'.__("Reader", 'awe').'</h2>';

if ( $migrated ) {
	global $wpdb;

	print '<p style="font-size:48px;line-height:55px;"><span style="color:green;">Database Updated!</span> It is recommended to <strong>manually</strong> remove old data by removing all posts where the post_type equals "update" from the '.$wpdb->posts.' table, and to remove the option "awe_sources" <a href="">Continue</a></p>';

} else { 

?>

<p style="font-size:48px;line-height:55px;">The database needs to be updated. There is a potential of data loss, <a href="https://codex.wordpress.org/Backing_Up_Your_Database#Using_Straight_MySQL_Commands">please make a backup of your database</a>.</p>

<form method="POST">
<input type="hidden" name="task" value="migrate_database"/>
<input type="submit" value="Update Database"/>
</form>

<?php

}

print '</div>';

?>
