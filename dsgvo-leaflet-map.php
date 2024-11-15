<?php
/**
 * Plugin Name:       DSGVO snippet for Leaflet Map and its Extensions Github
 * Description:       DSGVO/GDPR snippet for Leaflet Map and its Extensions
 * Plugin URI:        https://leafext.de/en/
 * GitHub Plugin URI: https://github.com/hupe13/extensions-leaflet-map-dsgvo
 * Primary Branch:    main
 * Version:           241115
 * Requires PHP:      7.4
 * Author:            hupe13
 * Author URI:        https://leafext.de/en/
 * License:           GPL v2 or later
 *
 * @package DSGVO snippet for Leaflet Map and its Extensions
 **/

// Direktzugriff auf diese Datei verhindern.
defined( 'ABSPATH' ) || die();

define( 'LEAFEXT_DSGVO_PLUGIN_FILE', __FILE__ ); // /pfad/wp-content/plugins/dsgvo-leaflet-map/dsgvo-leaflet-map.php
define( 'LEAFEXT_DSGVO_PLUGIN_DIR', plugin_dir_path( __FILE__ ) ); // /pfad/wp-content/plugins/dsgvo-leaflet-map/
define( 'LEAFEXT_DSGVO_PLUGIN_NAME', basename( LEAFEXT_DSGVO_PLUGIN_DIR ) ); // dsgvo-leaflet-map
define( 'LEAFEXT_DSGVO_PLUGIN_URL', plugin_dir_url( __FILE__ ) ); // https://url/wp-content/plugins/dsgvo-leaflet-map/

if ( ! function_exists( 'get_plugin_data' ) ) {
	require_once ABSPATH . 'wp-admin/includes/plugin.php';
}
// string $plugin_file, bool $markup = true, bool $translate = true
$plugin_data = get_plugin_data( __FILE__, true, false );
define( 'LEAFEXT_DSGVO_PLUGIN_VERSION', $plugin_data['Version'] );

// for translating a plugin
function leafext_dsgvo_textdomain() {
	if ( get_locale() === 'de_DE' ) {
		load_plugin_textdomain( 'extensions-leaflet-map-dsgvo', false, LEAFEXT_DSGVO_PLUGIN_NAME . '/lang/' );
		load_plugin_textdomain( 'dsgvo-leaflet-map', false, LEAFEXT_DSGVO_PLUGIN_NAME . '/lang/' );
	}
}
add_action( 'plugins_loaded', 'leafext_dsgvo_textdomain' );

// Add settings to plugin page
function leafext_add_action_dsgvo_links( $actions ) {
	$actions[] = '<a href="' . esc_url( admin_url( 'admin.php' ) . '?page=' . LEAFEXT_DSGVO_PLUGIN_NAME ) . '">' . esc_html__( 'Settings', 'dsgvo-leaflet-map' ) . '</a>';
	return $actions;
}
add_filter( 'plugin_action_links_' . plugin_basename( __FILE__ ), 'leafext_add_action_dsgvo_links' );

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
		if ( $_POST['leafext_button'] === $settings['okay'] ) {
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
}
add_action( 'init', 'leafext_setcookie' );

if ( function_exists( 'leafext_should_interpret_shortcode' ) ) {
	function leafext_query_cookie( $output, $tag ) {
		$text = leafext_should_interpret_shortcode( $tag, array() );
		if ( $text !== '' ) {
			return $output;
		} else {
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
			$formtext       = $settings['text'];
			$formend_safe   = '<p class="submit" style="display:flex; justify-content: center; align-items: center;">
		<input type="submit" value="' . esc_attr( $settings['okay'] ) . '" name="leafext_button" /></p>
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
				if ( defined( 'WPGPXMAPS_CURRENT_VERSION' ) && $sgpxoptions['sgpx'] === '0' ) {
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
			return $output;
		}
	}
	add_filter( 'do_shortcode_tag', 'leafext_query_cookie', 10, 2 );
}

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

// WP < 6.5 or Github
global $wp_version;
function leafext_dsgvo_require() {
	if ( is_admin() ) {
		if ( ! defined( 'LEAFEXT_PLUGIN_FILE' ) ) {
			if ( ( is_multisite() && ! is_main_site() ) || ! is_multisite() ) {
				function leafext_require_leaflet_map_extensions() {
					echo '<div class="notice notice-error" ><p> ';
					printf(
						/* translators: %s are plugin names. */
						esc_html__( 'Please install and activate %1$s before using %2$s.', 'extensions-leaflet-map-dsgvo' ),
						'<a href="https://wordpress.org/plugins/extensions-leaflet-map/">Extensions for Leaflet Map</a>',
						'DSGVO snippet for Leaflet Map and its Extensions'
					);
					echo '</p></div>';
				}
				add_action( 'admin_notices', 'leafext_require_leaflet_map_extensions' );
			}
		}
	}
}
add_action( 'plugins_loaded', 'leafext_dsgvo_require' );

// Updates
use YahnisElsts\PluginUpdateChecker\v5\PucFactory;
if ( is_admin() ) {
	include_once LEAFEXT_DSGVO_PLUGIN_DIR . 'admin.php';
	include_once LEAFEXT_DSGVO_PLUGIN_DIR . 'check-update.php';

	if ( is_main_site() ) {
		require_once LEAFEXT_DSGVO_PLUGIN_DIR . '/pkg/plugin-update-checker/plugin-update-checker.php';

		global $leafext_update_token;
		global $leafext_github_denied;

		if ( false === $leafext_github_denied || $leafext_update_token !== '' ) {
			$my_update_checker = PucFactory::buildUpdateChecker(
				'https://github.com/hupe13/extensions-leaflet-map-dsgvo/',
				__FILE__,
				LEAFEXT_DSGVO_PLUGIN_NAME
			);

			// Set the branch that contains the stable release.
			$my_update_checker->setBranch( 'main' );

			if ( $leafext_update_token !== '' ) {
				// Optional: If you're using a private repository, specify the access token like this:
				$my_update_checker->setAuthentication( $leafext_update_token );
			}

			function leafext_dsgvo_puc_error( $error, $response = null, $url = null, $slug = null ) {
				if ( isset( $slug ) && $slug !== LEAFEXT_DSGVO_PLUGIN_NAME ) {
					return;
				}
				if ( wp_remote_retrieve_response_code( $response ) === 403 ) {
					// var_dump( 'Permission denied' );
					set_transient( 'leafext_github_403', true, DAY_IN_SECONDS );
				}
			}
			add_action( 'puc_api_error', 'leafext_dsgvo_puc_error', 10, 4 );
		}
	}
}
