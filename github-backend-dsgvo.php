<?php
/**
 * Backend Menus
 *
 * @package Update Management for Leaflet Map and its Extensions Github
 **/

// Direktzugriff auf diese Datei verhindern.
defined( 'ABSPATH' ) || die();

// for translating a plugin
function leafext_dsgvo_textdomain() {
	if ( get_locale() === 'de_DE' ) {
		load_plugin_textdomain( 'extensions-leaflet-map-dsgvo', false, LEAFEXT_DSGVO_PLUGIN_NAME . '/lang/' );
		load_plugin_textdomain( 'dsgvo-leaflet-map', false, LEAFEXT_DSGVO_PLUGIN_NAME . '/lang/' );
	}
}
add_action( 'plugins_loaded', 'leafext_dsgvo_textdomain' );

// Repos on Github
if ( ! leafext_plugin_active( 'leafext-update-github' ) ) {
	function leafext_dsgvo_goto_main_site() {
		// if ( ! leafext_plugin_active ( 'extensions-leaflet-map' ) ) {
			echo '<h3>' . esc_html__( 'Updates in WordPress way', 'extensions-leaflet-map-dsgvo' ) . '</h3>';
			printf(
				/* translators: %s is a link. */
				esc_html__(
					'If you want to receive updates in WordPress way, go to the %1$smain site dashboard%2$s and activate %3$s here or install and activate %4$s.',
					'extensions-leaflet-map-dsgvo'
				),
				'<a href="' . esc_url( get_site_url( get_main_site_id() ) ) . '/wp-admin/plugins.php">',
				'</a>',
				'<a href="https://github.com/hupe13/extensions-leaflet-map-github">Extensions for Leaflet Map Github Version</a>',
				'<a href="https://github.com/hupe13/leafext-update-github">Manage Updates of Leaflet Map Extensions and DSGVO Github Versions</a>'
			);
		// }
	}
}
