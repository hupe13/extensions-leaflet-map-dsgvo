<?php
/**
 * Plugin Name:       DSGVO snippet for Leaflet Map and its Extensions Github
 * Description:       DSGVO/GDPR snippet for Leaflet Map and its Extensions
 * Plugin URI:        https://leafext.de/en/
 * GitHub Plugin URI: https://github.com/hupe13/extensions-leaflet-map-dsgvo
 * Primary Branch:    main
 * Version:           250125
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

require_once LEAFEXT_DSGVO_PLUGIN_DIR . 'php/leaflet-map.php';
require_once LEAFEXT_DSGVO_PLUGIN_DIR . 'php/shortcode.php';

// WP < 6.5 or Github
global $wp_version;
function leafext_dsgvo_require() {
	if ( is_admin() ) {
		if ( ! defined( 'LEAFEXT_PLUGIN_FILE' ) ) {
			if ( ( is_multisite() && ! is_main_site() ) || ! is_multisite() ) {
				function leafext_require_leaflet_map_extensions() {
					echo '<div class="notice notice-error" ><p> ';
					printf(
						/* translators: %s is a link. */
						esc_html__( 'Please install and activate %1$sExtensions for Leaflet Map%2$s before using DSGVO snippet for Leaflet Map and its Extensions.', 'extensions-leaflet-map-dsgvo' ),
						'<a href="https://wordpress.org/plugins/extensions-leaflet-map/">',
						'</a>'
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
