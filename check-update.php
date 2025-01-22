<?php
/**
 *  Updates for DSGVO snippet for Leaflet Map and its Extensions Github Version
 *
 * @package Leaflet Map DSGVO
 **/

// Direktzugriff auf diese Datei verhindern.
defined( 'ABSPATH' ) || die();

// define some globals
global $leafext_dsgvo_main_active;
global $leafext_update_token;
global $leafext_github_denied;

$main_site_id = get_main_site_id();

if ( is_multisite() ) {
	$setting = get_blog_option( $main_site_id, 'leafext_updating', array( 'token' => '' ) );
} else {
	$setting = get_option( 'leafext_updating', array( 'token' => '' ) );
}

if ( $setting && isset( $setting['token'] ) && $setting['token'] !== '' ) {
	$leafext_update_token = $setting['token'];
} else {
	$leafext_update_token = '';
}

if ( is_multisite() ) {
	switch_to_blog( $main_site_id );
	$leafext_github_denied = get_transient( 'leafext_github_403' );
	restore_current_blog();
} else {
	$leafext_github_denied = get_transient( 'leafext_github_403' );
}

if ( is_multisite() ) {
	// Makes sure the plugin is defined before trying to use it
	if ( ! function_exists( 'is_plugin_active_for_network' ) ) {
		require_once ABSPATH . '/wp-admin/includes/plugin.php';
	}
	if ( is_plugin_active_for_network( LEAFEXT_DSGVO_PLUGIN_NAME . '/dsgvo-leaflet-map.php' ) ) {
		$leafext_dsgvo_main_active = true;
	} else {
		switch_to_blog( $main_site_id );
		if ( is_plugin_active( LEAFEXT_DSGVO_PLUGIN_NAME . '/dsgvo-leaflet-map.php' ) ) {
			$leafext_dsgvo_main_active = true;
		} else {
			$leafext_dsgvo_main_active = false;
		}
		restore_current_blog();
	}
} else {
	include_once ABSPATH . 'wp-admin/includes/plugin.php';
	if ( is_plugin_active( LEAFEXT_DSGVO_PLUGIN_NAME . '/dsgvo-leaflet-map.php' ) ) {
		$leafext_dsgvo_main_active = true;
	} else {
		$leafext_dsgvo_main_active = false;
	}
}
// end globals

function leafext_dsgvo_meta_links( $links, $file ) {
	global $leafext_dsgvo_main_active;
	global $leafext_update_token;
	global $leafext_github_denied;

	if ( ! ( is_main_site() && ( false === $leafext_github_denied || $leafext_update_token !== '' ) ) || ! is_main_site() ) {
		if ( strpos( LEAFEXT_DSGVO_PLUGIN_FILE, $file ) !== false ) {
			$local  = get_file_data(
				LEAFEXT_DSGVO_PLUGIN_DIR . '/dsgvo-leaflet-map.php',
				array(
					'Version' => 'Version',
				)
			);
			$remote = get_file_data(
				'https://raw.githubusercontent.com/hupe13/extensions-leaflet-map-dsgvo/main/dsgvo-leaflet-map.php',
				array( 'Version' => 'Version' )
			);
			// var_dump( $local, $remote );

			if ( $local['Version'] < $remote['Version'] ) {
				$links[] = '<a href="' . get_site_url() . '/wp-admin/admin.php?page=' . LEAFEXT_DSGVO_PLUGIN_NAME . '">' .
				'<span class="update-message notice inline notice-warning notice-alt">' .
				esc_html__( 'New version available.', 'extensions-leaflet-map-dsgvo' ) .
				'</span>' .
				'</a>';
			}
		}
	}
	return $links;
}
add_filter( 'plugin_row_meta', 'leafext_dsgvo_meta_links', 10, 2 );

// Init settings fuer update
if ( ! function_exists( 'leafext_updating_init' ) ) {
	function leafext_updating_init() {
		add_settings_section( 'updating_settings', '', '', 'leafext_settings_updating' );
		add_settings_field( 'leafext_updating', esc_html__( 'Github token', 'extensions-leaflet-map-dsgvo' ), 'leafext_form_updating', 'leafext_settings_updating', 'updating_settings' );
		register_setting( 'leafext_settings_updating', 'leafext_updating', 'leafext_validate_updating' );
	}
}
add_action( 'admin_init', 'leafext_updating_init' );

// Baue Abfrage der Params
if ( ! function_exists( 'leafext_form_updating' ) ) {
	function leafext_form_updating() {
		$setting = get_option( 'leafext_updating', array( 'token' => '' ) );
		if ( ! current_user_can( 'manage_options' ) ) {
			$disabled = ' disabled ';
		} else {
			$disabled = '';
		}
		// var_dump($setting);
		//phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- string not changeable
		echo '<input ' . $disabled . ' type="text" size="30" name="leafext_updating[token]" value="' . $setting['token'] . '" />';
	}
}

// Sanitize and validate input. Accepts an array, return a sanitized array.
if ( ! function_exists( 'leafext_validate_updating' ) ) {
	function leafext_validate_updating( $input ) {
		if ( ! empty( $_POST ) && check_admin_referer( 'leafext_updating', 'leafext_updating_nonce' ) ) {
			if ( isset( $_POST['submit'] ) ) {
				return $input;
			}
			if ( isset( $_POST['delete'] ) ) {
				delete_option( 'leafext_updating' );
			}
			return false;
		}
	}
}

function leafext_dsgvo_update_admin() {
	global $leafext_dsgvo_main_active;
	global $leafext_update_token;
	global $leafext_github_denied;

	// var_dump( $leafext_dsgvo_main_active, $leafext_update_token, $leafext_github_denied );

	echo '<h3>' . esc_html__( 'Github token to receive updates in WordPress way', 'extensions-leaflet-map-dsgvo' ) . '</h3>';
	if ( is_main_site() ) {
		if ( $leafext_update_token === '' ) {
			// var_dump($leafext_github_denied);
			if ( false !== $leafext_github_denied ) {
				echo sprintf(
					/* translators: %s is a link. */
					esc_html__( 'You need a %1$sGithub token%2$s to receive updates successfully.', 'extensions-leaflet-map-dsgvo' ),
					'<a href="https://docs.github.com/en/authentication/keeping-your-account-and-data-secure/managing-your-personal-access-tokens">',
					'</a>'
				) . '<br>';
			} else {
				echo sprintf(
					/* translators: %s is a link. */
					esc_html__( 'Maybe you need a %1$sGithub token%2$s to receive updates successfully.', 'extensions-leaflet-map-dsgvo' ),
					'<a href="https://docs.github.com/en/authentication/keeping-your-account-and-data-secure/managing-your-personal-access-tokens">',
					'</a>'
				) . '<br>';
			}
		}
		echo '<form method="post" action="options.php">';
		settings_fields( 'leafext_settings_updating' );
		do_settings_sections( 'leafext_settings_updating' );
		if ( current_user_can( 'manage_options' ) ) {
			wp_nonce_field( 'leafext_updating', 'leafext_updating_nonce' );
			submit_button();
			submit_button( esc_html__( 'Reset', 'extensions-leaflet-map-dsgvo' ), 'delete', 'delete', false );
		}
		echo '</form>';
	} else {
		$main_site_id  = get_main_site_id();
		$main_site_url = get_site_url( $main_site_id );
		if ( $leafext_dsgvo_main_active ) {
			$main_active = ' ';
		} else {
			$main_active = ', ' . esc_html__( 'activate the plugin there', 'extensions-leaflet-map-dsgvo' );
		}

		if ( $leafext_dsgvo_main_active && ( false === $leafext_github_denied || $leafext_update_token !== '' ) ) {
			echo esc_html__( 'You receive updates in WordPress way.', 'extensions-leaflet-map-dsgvo' );
		} else {
			printf(
				/* translators: %s is a link. */
				esc_html__(
					'If you want to receive updates in WordPress way, go to the %1$smain site dashboard%2$s%3$s and set a Github token if necessary.',
					'extensions-leaflet-map-dsgvo'
				),
				'<a href="' . $main_site_url . '/wp-admin/plugins.php">', //phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- string not changeable
				'</a>',
				$main_active //phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- esc_html__ used
			);
		}
	}
}

if ( ! function_exists( 'leafext_updating_help' ) ) {
	function leafext_updating_help() {
		echo '<h3>' . esc_html__( 'Github token to receive updates in WordPress way', 'extensions-leaflet-map-dsgvo' ) . '</h3>';
		$setting = get_option( 'leafext_updating', array( 'token' => '' ) );
		if ( $setting && isset( $setting['token'] ) && $setting['token'] !== '' ) {
			$token = $setting['token'];
		} else {
			$token = '';
		}
		if ( $token === '' ) {
			$perm_denied = get_transient( 'leafext_github_403' );
			// var_dump($perm_denied);
			if ( false !== $perm_denied ) {
				echo sprintf(
					/* translators: %s is a link. */
					esc_html__( 'You need a %1$sGithub token%2$s to receive updates successfully.', 'extensions-leaflet-map-dsgvo' ),
					'<a href="https://docs.github.com/en/authentication/keeping-your-account-and-data-secure/managing-your-personal-access-tokens">',
					'</a>'
				) . '<br>';
			} else {
				echo sprintf(
					/* translators: %s is a link. */
					esc_html__( 'Maybe you need a %1$sGithub token%2$s to receive updates successfully.', 'extensions-leaflet-map-dsgvo' ),
					'<a href="https://docs.github.com/en/authentication/keeping-your-account-and-data-secure/managing-your-personal-access-tokens">',
					'</a>'
				) . '<br>';
			}
		}
	}
}
