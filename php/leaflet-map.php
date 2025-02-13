<?php
/**
 * Functions DSGVO for leaflet-map
 *
 * @package DSGVO snippet for Leaflet Map and its Extensions
 **/

// Direktzugriff auf diese Datei verhindern.
defined( 'ABSPATH' ) || die();

function leafext_dsgvo_params() {
	$params   = array();
	$params[] = array(
		'param'   => 'text',
		'desc'    => __( 'Text', 'dsgvo-leaflet-map' ),
		'default' => __( 'When using the maps, content is loaded from third-party servers. If you agree to this, a cookie will be set and this notice will be hidden. If not, no maps will be displayed.', 'dsgvo-leaflet-map' ),
	);
	$params[] = array(
		'param'   => 'okay',
		'desc'    => __( 'Submit Button', 'dsgvo-leaflet-map' ),
		'default' => __( 'Okay', 'dsgvo-leaflet-map' ),
	);
	$params[] = array(
		'param'   => 'mapurl',
		'desc'    => __( 'URL to background image. Leave it blank for no image.', 'dsgvo-leaflet-map' ),
		'default' => LEAFEXT_DSGVO_PLUGIN_URL . 'pict/map.png',
	);
	$params[] = array(
		'param'   => 'color',
		'desc'    => __( 'Color of the background', 'dsgvo-leaflet-map' ),
		'default' => 'rgba(255,255,255,0.7)',
		// 'default' => '#ffffffb3',
	);
	$params[] = array(
		'param'   => 'cookie',
		'desc'    => __( 'Cookie Lifetime', 'dsgvo-leaflet-map' ),
		'default' => '365',
	);
	$params[] = array(
		'param'   => 'count',
		'desc'    => __( 'Should the text be displayed on each map of the page or only on the first map?', 'dsgvo-leaflet-map' ),
		'default' => false,
	);
	return $params;
}

function leafext_dsgvo_settings() {
	$defaults = array();
	$params   = leafext_dsgvo_params();
	foreach ( $params as $param ) {
		$defaults[ $param['param'] ] = $param['default'];
	}
	$options = shortcode_atts( $defaults, get_option( 'leafext_dsgvo' ) );
	return $options;
}

function leafext_setcookie() {
	global $leafext_cookie;
	$leafext_cookie = false;

	if ( isset( $_SERVER['REQUEST_METHOD'] ) && sanitize_text_field( wp_unslash( $_SERVER['REQUEST_METHOD'] ) ) === 'POST' && ! empty( $_POST['leafext_button'] ) ) {
		if ( isset( $_REQUEST['leafext_dsgvo_okay'] ) && ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_REQUEST['leafext_dsgvo_okay'] ) ), 'leafext_dsgvo' ) ) {
			wp_die( 'invalid', 404 );
		}
		$settings = leafext_dsgvo_settings();
		// https://www.php.net/manual/en/function.setcookie.php#125242
		$arr_cookie_options = array(
			'expires'  => time() + 3600 * 24 * $settings['cookie'],
			'path'     => '/',
			'domain'   => wp_parse_url( get_site_url(), PHP_URL_HOST ),
			'secure'   => true,
			'httponly' => true,
			'samesite' => 'Strict', // None || Lax  || Strict
		);
		setcookie( 'leafext', 1, $arr_cookie_options );
		$leafext_cookie = true;
	}
}
add_action( 'init', 'leafext_setcookie' );

function leafext_query_cookie( $output, $tag ) {
	if ( ( is_singular() || is_archive() || is_home() || is_front_page() ) && ! current_user_can( 'edit_post', get_the_ID() ) ) {
		global $leafext_cookie;
		if (
			is_admin()
			// || is_user_logged_in()
			|| ( 'leaflet-map' !== $tag && 'sgpx' !== $tag )
			|| isset( $_COOKIE['leafext'] )
			|| $leafext_cookie
			) {
			return $output;
		}
		wp_enqueue_style(
			'leafext-dsgvo-css',
			plugins_url( 'css/leafext-dsgvo.css', LEAFEXT_DSGVO_PLUGIN_FILE ),
			array(),
			LEAFEXT_DSGVO_PLUGIN_VERSION
		);
		$formbegin_safe = '<form action="" method="post">';
		$formbegin_safe = $formbegin_safe . wp_nonce_field( 'leafext_dsgvo', 'leafext_dsgvo_okay' );
		$settings       = leafext_dsgvo_settings();
		$formtext       = '<p style="display:flex; justify-content: center; align-items: center;">' . $settings['text'] . '</p>';
		$formend_safe   = '<p class="submit" style="display:flex; justify-content: center; align-items: center;">
		<input type="submit" aria-label="Submit ' . esc_attr( $settings['okay'] ) . '" value="' . esc_attr( $settings['okay'] ) . '" name="leafext_button" /></p>
		</form>';

		global $leafext_okay;
		if ( ! isset( $leafext_okay ) ) {
			$leafext_okay = true;
			wp_dequeue_style( 'leaflet_stylesheet' );
			wp_dequeue_script( 'wp_leaflet_map' );
			wp_deregister_style( 'leaflet_stylesheet' );
			wp_deregister_script( 'wp_leaflet_map' );
			$form = true;
		} else {
			$count = filter_var( $settings['count'], FILTER_VALIDATE_BOOLEAN );
			if ( $count ) {
				$form = true;
			} else {
				$form = false;
			}
		}

		$sgpxoptions = leafext_sgpx_settings();

		if ( $tag === 'sgpx' ) {
			if ( leafext_plugin_active( 'wp-gpx-maps' ) && $sgpxoptions['sgpx'] === '0' ) {
				return $output;
			}

			if ( $sgpxoptions['sgpx'] === '1' ) {
				$pos    = strpos( $output, '"></div><script>' );
				$output = substr( $output, 0, $pos );
				$output = str_replace( 'leaflet-map WPLeafletMap', 'leafext-dsgvo', $output );
				$output = $output .
				' background: linear-gradient(' . esc_attr( $settings['color'] ) . ',' . esc_attr( $settings['color'] ) . '), url(' . esc_url( $settings['mapurl'] ) . ');" ><div style="width: 70%;">'
				. ( $form ? $formbegin_safe . wp_kses_post( $formtext ) . $formend_safe : '' ) . '</div></div>';
			} else {
				// search width and height
				$search = 'style="width:';
				$pos    = strpos( $output, $search );
				if ( $pos === false ) {
					$search = 'style="height:';
					$pos    = strpos( $output, $search );
				}
				$style  = substr( $output, $pos - strlen( $output ) );
				$search = 'position:relative';
				$pos    = strpos( $style, $search );
				if ( $pos === false ) {
					$search = '"></div>';
					$pos    = strpos( $style, $search );
				}
				$style  = substr( $style, 0, $pos );
				$output = '<div class="leafext-dsgvo" ' . $style .
				' background: linear-gradient(' . esc_attr( $settings['color'] ) . ',' . esc_attr( $settings['color'] ) . '), url(' . esc_url( $settings['mapurl'] ) . ');" ><div style="width: 70%;">'
				. ( $form ? $formbegin_safe . wp_kses_post( $formtext ) . $formend_safe : '' ) . '</div></div>';
			}
		} else {
			global $post;
			if ( ! has_shortcode( $post->post_content, 'sgpx' ) ) {
				$pos    = strpos( $output, '"></div><script>' );
				$output = substr( $output, 0, $pos );
				$output = str_replace( 'leaflet-map WPLeafletMap', 'leafext-dsgvo', $output );
				$output = $output .
				' background: linear-gradient(' . esc_attr( $settings['color'] ) . ',' . esc_attr( $settings['color'] ) . '), url(' . esc_url( $settings['mapurl'] ) . ');" ><div style="width: 70%;">'
				. ( $form ? $formbegin_safe . wp_kses_post( $formtext ) . $formend_safe : '' ) . '</div></div>';
			}
		}
	}
	return $output;
}
add_filter( 'do_shortcode_tag', 'leafext_query_cookie', 10, 2 );

function leafext_dequeue_missing() {
	leafext_dequeue_recursive( 'wp_leaflet_map' );
}

function leafext_dequeue_recursive( $dep ) {
	global $wp_scripts;
	foreach ( $wp_scripts->queue as $handle ) {
		$obj = $wp_scripts->registered [ $handle ];
		if ( is_array( $obj->deps ) ) {
			if ( in_array( $dep, $obj->deps, true ) ) {
				wp_dequeue_script( $handle );
				foreach ( $obj->deps as $deeper ) {
					if ( $deeper !== 'wp_leaflet_map' ) {
						leafext_dequeue_recursive( $deeper );
					}
				}
			}
		}
	}
}
add_action( 'wp_footer', 'leafext_dequeue_missing', 1000 );
