<?php
/**
 * Functions
 *
 * @package DSGVO snippet for Leaflet Map and its Extensions
 */

// Direktzugriff auf diese Datei verhindern.
defined( 'ABSPATH' ) || die();

function leafext_should_interpret_shortcode_dsgvo( $shortcode, $atts ) {
	global $button;
	// var_dump($button);
	$server     = map_deep( wp_unslash( $_SERVER ), 'sanitize_text_field' );
	$scriptname = $server['SCRIPT_NAME'];
	if ( is_singular() || is_archive() || is_home() || is_front_page() || leafext_backend() ) {
		if ( strpos( $scriptname, '/wp-admin/post.php' ) === false ) {
			// return 'should interpret '.$shortcode;
			return '';
		} elseif ( ! isset( $button ) ) {
				$button = true;
				echo '<input type="button" value="' . esc_html__( 'Click to interpret shortcodes.', 'extensions-leaflet-map' ) . '" onclick="window.location.reload()">';
		}
	}
	$text = '[' . $shortcode . ' ';
	if ( is_array( $atts ) ) {
		foreach ( $atts as $key => $item ) {
			if ( is_int( $key ) ) {
				$text = $text . "$item ";
			} else {
				$text = $text . "$key=$item ";
			}
		}
	}
	$text = $text . ']';
	return $text;
}
