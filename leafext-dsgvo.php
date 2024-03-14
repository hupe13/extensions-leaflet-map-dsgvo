<?php
/**
 * Plugin Name:       DSGVO Snippet for Extensions for Leaflet Map
 * Description:       DSGVO Snippet for Extensions for Leaflet Map
 * Plugin URI:        https://github.com/hupe13/extensions-leaflet-map-dsgvo
 * GitHub Plugin URI: https://github.com/hupe13/extensions-leaflet-map-dsgvo
 * Primary Branch:    main
 * Version:           240314
 * Requires PHP:      7.4
 * Author:            hupe13
 * Author URI:        https://leafext.de/en/
 * Text Domain:       extensions-leaflet-map-dsgvo
 * Domain Path:       /lang/
 *
 * @package Extensions for Leaflet Map DSGVO
 **/

// Direktzugriff auf diese Datei verhindern.
defined( 'ABSPATH' ) || die();

define( 'LEAFEXT_DSGVO_PLUGIN_FILE', __FILE__ ); // /pfad/wp-content/plugins/extensions-leaflet-map-dsgvo/leafext-dsgvo.php
define( 'LEAFEXT_DSGVO_PLUGIN_DIR', plugin_dir_path( __FILE__ ) ); // /pfad/wp-content/plugins/plugin/
define( 'LEAFEXT_DSGVO_PLUGIN_NAME', basename( LEAFEXT_DSGVO_PLUGIN_DIR ) ); // extensions-leaflet-map-dsgvo
define( 'LEAFEXT_DSGVO_PLUGIN_URL', WP_PLUGIN_URL . '/' . LEAFEXT_DSGVO_PLUGIN_NAME ); // https://url/wp-content/plugins/plugin/

// for translating a plugin
function leafext_dsgvo_textdomain() {
	if ( get_locale() === 'de_DE' ) {
		load_plugin_textdomain( 'extensions-leaflet-map-dsgvo', false, '/' . LEAFEXT_DSGVO_PLUGIN_NAME . '/lang/' );
	}
}
add_action( 'init', 'leafext_dsgvo_textdomain' );

// Add settings to plugin page
function leafext_add_action_dsgvo_links( $actions ) {
	$actions[] = '<a href="' . admin_url( 'admin.php' ) . '?page=' . LEAFEXT_DSGVO_PLUGIN_NAME . '">' . esc_html__( 'Settings' ) . '</a>';
	return $actions;
}
add_filter( 'plugin_action_links_' . plugin_basename( __FILE__ ), 'leafext_add_action_dsgvo_links' );

function leafext_dsgvo_params() {
	$params       = array();
		$params[] = array(
			'param'   => 'text',
			'desc'    => __( 'Text', 'extensions-leaflet-map-dsgvo' ),
			'default' => __( 'When using the maps, content is loaded from third-party servers. If you agree to this, a cookie will be set and this notice will be hidden. If not, no maps will be displayed.', 'extensions-leaflet-map-dsgvo' ),
		);
		$params[] = array(
			'param'   => 'okay',
			'desc'    => __( 'Submit Button', 'extensions-leaflet-map-dsgvo' ),
			'default' => __( 'Okay', 'extensions-leaflet-map-dsgvo' ),
		);
		$params[] = array(
			'param'   => 'mapurl',
			'desc'    => __( 'URL to Background Image', 'extensions-leaflet-map-dsgvo' ),
			'default' => LEAFEXT_DSGVO_PLUGIN_URL . '/map.png',
		);
		$params[] = array(
			'param'   => 'cookie',
			'desc'    => __( 'Cookie Lifetime', 'extensions-leaflet-map-dsgvo' ),
			'default' => '365',
		);
		$params[] = array(
			'param'   => 'count',
			'desc'    => __( 'Should the text be displayed on each map of the page or only on the first map?', 'extensions-leaflet-map-dsgvo' ),
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

	$server  = map_deep( wp_unslash( $_SERVER ), 'sanitize_text_field' );
	$request = map_deep( wp_unslash( $_REQUEST ), 'sanitize_text_field' );

	if ( $server['REQUEST_METHOD'] === 'POST' && ! empty( $_POST['leafext_button'] ) ) {
		if ( ! wp_verify_nonce( $request['leafext_dsgvo_okay'], 'leafext_dsgvo' ) ) {
			wp_die( 'invalid', 404 );
		}

		$settings = leafext_dsgvo_settings();
		if ( $_POST['leafext_button'] === $settings['okay'] ) {
			// https://www.php.net/manual/en/function.setcookie.php#125242
			$arr_cookie_options = array(
				'expires'  => time() + 3600 * 24 * $settings['cookie'],
				'path'     => '/',
				'domain'   => $server['HTTP_HOST'],
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

if ( strpos( implode( ' ', get_option( 'active_plugins', array() ) ), '/extensions-leaflet-map.php' ) !== false ) {
	function leafext_query_cookie( $output, $tag ) {
		$text = leafext_should_interpret_shortcode( $tag, array() );
		if ( $text !== '' ) {
			return $output;
		} else {
			global $leafext_cookie;
			if (
				is_admin()
				|| is_user_logged_in()
				|| ( 'leaflet-map' !== $tag && 'sgpx' !== $tag )
				|| isset( $_COOKIE['leafext'] )
				|| $leafext_cookie
			) {
				return $output;
			}
			$form     = '<form action="" method="post">';
			$form     = $form . wp_nonce_field( 'leafext_dsgvo', 'leafext_dsgvo_okay' );
			$settings = leafext_dsgvo_settings();
			$form     = $form . $settings['text'];
			$form     = $form .
			'<p class="submit" style="display:flex; justify-content: center; align-items: center;">
			<input type="submit" value="' . $settings['okay'] . '" name="leafext_button" /></p>
			</form>';

			global $leafext_okay;
			// var_dump($leafext_okay);
			if ( ! isset( $leafext_okay ) ) {
				$leafext_okay = true;
				wp_dequeue_style( 'leaflet_stylesheet' );
				wp_dequeue_script( 'wp_leaflet_map' );
				wp_deregister_style( 'leaflet_stylesheet' );
				wp_deregister_script( 'wp_leaflet_map' );
				$text = $form;
			} else {
				$count = filter_var( $settings['count'], FILTER_VALIDATE_BOOLEAN );
				if ( $count ) {
					$text = $form;
				} else {
					$text = '';
				}
			}

			// bei sgpx leaflet wird es 2x aufgerufen.
			if ( $tag === 'sgpx' ) {
				$output = do_shortcode( '[leaflet-map]' );
				$text   = $form;
			}

			preg_match( '/linear-gradient|WPLeafletMap" style="height:1px;/', $output, $matches );

			if ( count( $matches ) === 0 || $tag === 'sgpx' ) {
				preg_match( '/style="[^"]+"/', $output, $matches );
				if ( count( $matches ) === 0 ) {
					$matches[0] = ' style=".';
				}
				$output = '<div data-nosnippet ' . substr( $matches[0], 0, -1 ) .
				';background: linear-gradient(rgba(255, 255, 255, 0.7), rgba(255, 255, 255, 0.7)), ' .
				'url(' . $settings['mapurl'] . '); background-position: center; ' .
				'border: gray 2px solid; display:flex; justify-content: center; ' .
				'align-items: center;"><div style="width: 70%;">' . $text . '</div></div>';
			}
			return $output;
		}
	}
	add_filter( 'do_shortcode_tag', 'leafext_query_cookie', 10, 2 );
}

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
