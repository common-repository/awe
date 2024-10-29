<?php 
	print AWEBook_Template::render( 'awebook', 'global/header.php', array( 'robots' => false ) ); 
?>

<?php
	foreach ( $posts as $post ) {
		global $post;
		print AWEBook_Template::render( 'awebook', 'posts/update.php', array( 'post' => $post ) ); 
	}
?>

<?php 
	print AWEBook_Template::render( 'awebook', 'global/footer.php', array() ); 
?>