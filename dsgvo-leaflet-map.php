<?php
/**
 * Plugin Name:       DSGVO snippet for Leaflet Map and its Extensions Github
 * Description:       DSGVO/GDPR snippet for Leaflet Map and its Extensions
 * Plugin URI:        https://leafext.de/en/
 * Version:           250218
 * Requires PHP:      7.4
 * Requires Plugins:  leaflet-map, extensions-leaflet-map
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

if ( ! function_exists( 'leafext_plugin_active' ) ) {
	function leafext_plugin_active( $plugin ) {
		if ( ! ( strpos( implode( ' ', get_option( 'active_plugins', array() ) ), '/' . $plugin . '.php' ) === false &&
			strpos( implode( ' ', array_keys( get_site_option( 'active_sitewide_plugins', array() ) ) ), '/' . $plugin . '.php' ) === false ) ) {
			return true;
		} else {
			return false;
		}
	}
}

if ( leafext_plugin_active( 'extensions-leaflet-map' ) ) {
	// Add settings to plugin page
	function leafext_add_action_dsgvo_links( $actions ) {
		$actions[] = '<a href="' . esc_url( admin_url( 'admin.php' ) . '?page=' . LEAFEXT_DSGVO_PLUGIN_NAME ) . '">' . esc_html__( 'Settings', 'dsgvo-leaflet-map' ) . '</a>';
		return $actions;
	}
	add_filter( 'plugin_action_links_' . plugin_basename( __FILE__ ), 'leafext_add_action_dsgvo_links' );

	require_once LEAFEXT_DSGVO_PLUGIN_DIR . 'php/leaflet-map.php';
	require_once LEAFEXT_DSGVO_PLUGIN_DIR . 'php/shortcode.php';
}

if ( is_admin() ) {
	include_once LEAFEXT_DSGVO_PLUGIN_DIR . 'admin.php';
}

// WP < 6.5
global $wp_version;
if ( version_compare( $wp_version, '6.5', '<' ) ) {
	function leafext_dsgvo_require() {
		if ( ! leafext_plugin_active( 'extensions-leaflet-map' ) ) {
			if ( ( is_multisite() && ! is_main_site() ) || ! is_multisite() ) {
				function leafext_require_leaflet_map_extensions() {
					echo '<div class="notice notice-error" ><p> ';
					printf(
						/* translators: %s is a link. */
						esc_html__( 'Please install and activate %1$sExtensions for Leaflet Map%2$s before using DSGVO snippet for Leaflet Map and its Extensions.', 'dsgvo-leaflet-map' ),
						'<a href="https://wordpress.org/plugins/extensions-leaflet-map/">',
						'</a>'
					);
					echo '</p></div>';
				}
				add_action( 'admin_notices', 'leafext_require_leaflet_map_extensions' );
			}
		}
	}
	add_action( 'plugins_loaded', 'leafext_dsgvo_require' );
}

// Github
if ( is_admin() ) {
	require_once LEAFEXT_DSGVO_PLUGIN_DIR . 'github-backend-dsgvo.php';
}
