<?php

class AWE_Template {

	/**
	 * Generates an HTML template using a namespace passed to it.
	 *
	 * Will generate HTML for a url routing rule to be used to change
	 * the paramaters of the rule.
	 *
	 * @uses AWE::$template_basepath
	 * @param string The relative path to the template file.
	 * @param array Associative array with variables used to generate the page. 
	 * @return string The html output
	 */
	public static function render( $filepath, $namespace ) {

		extract( $namespace );

		ob_start();

		if ( file_exists( AWE::$template_basepath . '/' . $filepath ) ) {
			include ( AWE::$template_basepath . '/' . $filepath );
		} else {
			throw Exception('Template file not found');
		}

		$html = ob_get_contents();

		if( AWE::$minify ) {
			$html = preg_replace('/\s+/', ' ', $html);
		}

		ob_end_clean();

		return $html;
	}

	public static function feed_title( $title ) {
        return apply_filters( 'the_title_rss', $title );
	}

	public static function feed_body( $body, $feed_type ) {
		$output = apply_filters( 'the_content', wp_kses_post( $body ) );
		$content = str_replace( ']]>', ']]&gt;', $output );
		return apply_filters('the_content_feed', $output, $feed_type);
	}

}

?>
