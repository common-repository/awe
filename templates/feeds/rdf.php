<?php
/**
 * RSS 1 RDF Feed Template for displaying RSS 1 Posts feed.
 */
header('Content-Type: ' . feed_content_type('rdf') . '; charset=' . get_option('blog_charset'), true);
$more = 1;

echo '<?xml version="1.0" encoding="'.get_option('blog_charset').'"?'.'>'; ?>
<rdf:RDF
	xmlns="http://purl.org/rss/1.0/"
	xmlns:rdf="http://www.w3.org/1999/02/22-rdf-syntax-ns#"
	xmlns:dc="http://purl.org/dc/elements/1.1/"
	xmlns:sy="http://purl.org/rss/1.0/modules/syndication/"
	xmlns:admin="http://webns.net/mvcb/"
	xmlns:content="http://purl.org/rss/1.0/modules/content/"
	<?php do_action('rdf_ns'); ?>
>
<channel rdf:about="<?php bloginfo_rss("url") ?>">
	<title><?php bloginfo_rss('name'); print ' | Reader'; ?></title>
	<link><?php bloginfo_rss('url') ?></link>
	<description><?php bloginfo_rss('description') ?></description>
	<dc:date><?php echo mysql2date('Y-m-d\TH:i:s\Z', get_lastpostmodified('GMT'), false); ?></dc:date>
	<sy:updatePeriod><?php echo apply_filters( 'rss_update_period', 'hourly' ); ?></sy:updatePeriod>
	<sy:updateFrequency><?php echo apply_filters( 'rss_update_frequency', '1' ); ?></sy:updateFrequency>
	<sy:updateBase>2000-01-01T12:00+00:00</sy:updateBase>
	<?php do_action('rdf_header'); ?>
	<items>
		<rdf:Seq>
		<?php foreach ( $items as $item ) { ?>
			<rdf:li rdf:resource="<?php print $item['permalink']; ?>"/>
		<?php } ?>
		</rdf:Seq>
	</items>
</channel>
<?php foreach ( $items as $item ) { ?>
<item rdf:about="<?php print $item['permalink']; ?>">
	<title><?php print AWE_Template::feed_title( $item['title'] ); ?></title>
	<link><?php print $item['permalink']; ?></link>
	 <dc:date><?php echo mysql2date('Y-m-d\TH:i:s\Z', $item['date'], false); ?></dc:date>
	<dc:creator><?php print $item['author']; ?></dc:creator>
	<?php //the_category_rss('rdf') ?>
<?php if ( false ) : ?>
	<description><?php the_excerpt_rss() ?></description>
<?php else : ?>
	<?php //<description> the_excerpt_rss() </description>?>
	<content:encoded><![CDATA[<?php print AWE_Template::feed_body( $item['body'], 'rdf' ); ?>]]></content:encoded>
<?php endif; ?>
	<?php //do_action('rdf_item'); ?>
</item>
<?php } ?>
</rdf:RDF>

