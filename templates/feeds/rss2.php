<?php
/**
 * RSS2 Feed Template for displaying RSS2 Posts feed.
 */
header('Content-Type: ' . feed_content_type('rss-http') . '; charset=' . get_option('blog_charset'), true);
$more = 1;

echo '<?xml version="1.0" encoding="'.get_option('blog_charset').'"?'.'>'; ?>

<rss version="2.0"
	xmlns:content="http://purl.org/rss/1.0/modules/content/"
	xmlns:wfw="http://wellformedweb.org/CommentAPI/"
	xmlns:dc="http://purl.org/dc/elements/1.1/"
	xmlns:atom="http://www.w3.org/2005/Atom"
	xmlns:sy="http://purl.org/rss/1.0/modules/syndication/"
	xmlns:slash="http://purl.org/rss/1.0/modules/slash/"
	<?php do_action('rss2_ns'); ?>
>

<channel>
	<title><?php bloginfo_rss('name'); print ' | Reader'; ?></title>
	<atom:link href="<?php self_link(); ?>" rel="self" type="application/rss+xml" />
	<link><?php bloginfo_rss('url') ?></link>
	<description><?php bloginfo_rss("description") ?></description>
	<lastBuildDate><?php echo mysql2date('D, d M Y H:i:s +0000', get_lastpostmodified('GMT'), false); ?></lastBuildDate>
	<language><?php echo get_option('rss_language'); ?></language>
	<sy:updatePeriod><?php echo apply_filters( 'rss_update_period', 'hourly' ); ?></sy:updatePeriod>
	<sy:updateFrequency><?php echo apply_filters( 'rss_update_frequency', '1' ); ?></sy:updateFrequency>
	<?php do_action('rss2_head'); ?>
	<?php foreach( $items as $item ) { ?>
	<item>
		<title><?php print AWE_Template::feed_title( $item['title'] ); ?></title>
		<link><?php print $item['permalink'] ?></link>
		<comments></comments>
		<pubDate><?php print mysql2date('D, d M Y H:i:s +0000', $item['date'], false); ?></pubDate>
		<dc:creator><?php print $item['author']; ?></dc:creator>
		<?php //the_category_rss('rss2') ?>

		<guid isPermaLink="false"><?php print $item['guid']; ?></guid>
<?php if ( false ) : ?>
		<description><![CDATA[]]></description>
<?php else : ?>
	<?php if ( strlen( $item['body'] ) > 0 ) : ?>
		<content:encoded><![CDATA[<?php print AWE_Template::feed_body( $item['body'], 'rss2' ); ?>]]></content:encoded>
	<?php else : ?>
		<content:encoded><![CDATA[]]></content:encoded>
	<?php endif; ?>
<?php endif; ?>
		<wfw:commentRss><?php ?></wfw:commentRss>
		<slash:comments><?php ?></slash:comments>
<?php //rss_enclosure(); ?>
	<?php //do_action('rss2_item'); ?>
	</item>
	<?php } ?>
</channel>
</rss>
