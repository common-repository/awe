<?php
/**
 * Atom Feed Template for displaying Atom Posts feed.
 */
header('Content-Type: ' . feed_content_type('atom') . '; charset=' . get_option('blog_charset'), true);
echo '<?xml version="1.0" encoding="'.get_option('blog_charset').'"?'.'>'; ?>
<feed
  xmlns="http://www.w3.org/2005/Atom"
  xmlns:thr="http://purl.org/syndication/thread/1.0"
  xml:lang="<?php echo get_option('rss_language'); ?>"
  xml:base="<?php bloginfo_rss('url') ?>/wp-atom.php"
  <?php do_action('atom_ns'); ?>
 >
	<title type="text"><?php bloginfo_rss('name'); print ' | Reader'; ?></title>
	<subtitle type="text"><?php bloginfo_rss("description") ?></subtitle>

	<updated><?php echo mysql2date('Y-m-d\TH:i:s\Z', get_lastpostmodified('GMT'), false); ?></updated>

	<link rel="alternate" type="text/html" href="<?php bloginfo_rss('url') ?>" />
	<id><?php bloginfo('atom_url'); ?></id>
	<link rel="self" type="application/atom+xml" href="<?php self_link(); ?>" />

	<?php do_action('atom_head'); ?>
	<?php foreach( $items as $item ) { ?>
	<entry>
		<author>
			<name><?php print $item['author']; ?></name>
		</author>
		<title type="<?php html_type_rss(); ?>"><![CDATA[<?php print AWE_Template::feed_title( $item['title'] ); ?>]]></title>
		<link rel="alternate" type="text/html" href="<?php the_permalink_rss() ?>" />
		<id><?php print $item['guid']; ?></id>
		<updated><?php print mysql2date('D, d M Y H:i:s +0000', $item['date'], false); ?></updated>
		<published><?php echo mysql2date('Y-m-d\TH:i:s\Z', $item['date'], true); ?></published>
		<content type="<?php html_type_rss(); ?>" xml:base="<?php the_permalink_rss() ?>"><![CDATA[<?php print AWE_Template::feed_body( $item['body'], 'atom' ); ?>]]></content>
		<link rel="replies" type="text/html" href="" thr:count=""/>
		<link rel="replies" type="application/atom+xml" href="" thr:count=""/>
		<thr:total></thr:total>
	</entry>
	<?php } ?>
</feed>
