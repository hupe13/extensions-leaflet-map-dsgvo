<?php
/**
 *  Admin DSGVO snippet for Leaflet Map and its Extensions
 *
 * @package DSGVO snippet for Leaflet Map and its Extensions
 **/

// Direktzugriff auf diese Datei verhindern.
defined( 'ABSPATH' ) || die();

if ( leafext_plugin_active( 'extensions-leaflet-map' ) ) {
	// Add menu page
	function leafext_dsgvo_add_page() {
		// Add Submenu
		$leafext_admin_page = add_submenu_page(
			'leaflet-map',
			'Leaflet Map ' . __( 'Options GDPR', 'dsgvo-leaflet-map' ),
			'Leaflet Map ' . __( 'GDPR', 'dsgvo-leaflet-map' ),
			'manage_options',
			LEAFEXT_DSGVO_PLUGIN_NAME,
			'leafext_dsgvo_do_page'
		);
	}
	add_action( 'admin_menu', 'leafext_dsgvo_add_page', 100 );

	function leafext_dsgvo_init() {
		add_settings_section( 'leafext_dsgvo', '', '', 'leafext_settings_dsgvo' );
		$fields = leafext_dsgvo_params();
		foreach ( $fields as $field ) {
			add_settings_field(
				'leafext_dsgvo[' . $field['param'] . ']',
				$field['desc'],
				'leafext_dsgvo_form',
				'leafext_settings_dsgvo',
				'leafext_dsgvo',
				$field['param'],
			);
		}
		// https://stackoverflow.com/a/77545721
		$leafext_dsgvo = get_option( 'leafext_dsgvo' );
		if ( $leafext_dsgvo === false ) {
			add_option( 'leafext_dsgvo', '' );
		}
		register_setting( 'leafext_settings_dsgvo', 'leafext_dsgvo', 'leafext_validate_dsgvo' );
	}
	add_action( 'admin_init', 'leafext_dsgvo_init' );

	function leafext_dsgvo_form( $field ) {
		$options  = leafext_dsgvo_params();
		$option   = leafext_array_find3( $field, $options );
		$settings = leafext_dsgvo_settings();
		$setting  = $settings[ $field ];
		if ( leafext_plugin_active( 'polylang-theme-translation' ) ) {
			$ttfp = ' readonly ';
		} else {
			$ttfp = '';
		}
		switch ( $field ) {
			case 'text':
				echo '<textarea ' . esc_attr( $ttfp ) . ' name="leafext_dsgvo[text]" type="textarea" cols="80" rows="5">';
				echo wp_kses_post( $setting );
				echo '</textarea>';
				break;
			case 'mapurl':
				echo '<input type="url" size="80" name="leafext_dsgvo[mapurl]" value="' . esc_url( $setting ) . '" />';
				break;
			case 'color':
				leafext_dsgvo_colors( $option['default'], $setting );
				break;
			case 'cookie':
				echo '<input type="number" size="5" min="1" max="365" name="leafext_dsgvo[cookie]" value="' . absint( $setting ) . '"> ';
				esc_html_e( 'days', 'dsgvo-leaflet-map' );
				break;
			case 'count':
				echo '<input type="radio" name="leafext_dsgvo[count]" value="1" ';
				echo boolval( $setting ) ? 'checked' : '';
				echo '> ';
				esc_html_e( 'each map', 'dsgvo-leaflet-map' );
				echo ' &nbsp;&nbsp; ';
				echo '<input type="radio" name="leafext_dsgvo[count]" value="0" ';
				echo ! boolval( $setting ) ? 'checked' : '';
				echo '> ';
				esc_html_e( 'only first', 'dsgvo-leaflet-map' );
				echo ' ';
				break;
			case 'okay':
				echo '<input type="text" ' . esc_attr( $ttfp ) . ' size="10" name="leafext_dsgvo[okay]" value="' . esc_attr( $setting ) . '" />';
				break;
			default:
				wp_die( 'error' );
		}
	}

	// Sanitize and validate input. Accepts an array, return a sanitized array.
	function leafext_validate_dsgvo( $options ) {
		check_admin_referer( 'leafext_dsgvo', 'leafext_dsgvo_nonce' );
		if ( isset( $_POST['submit'] ) ) {
			$defaults = array();
			$params   = leafext_dsgvo_params();
			foreach ( $params as $param ) {
				$defaults[ $param['param'] ] = $param['default'];
			}
			if ( isset( $options['cookie'] ) && ( $options['cookie'] === '0' || $options['cookie'] === '' ) ) {
				$options['cookie'] = absint( $defaults['cookie'] );
			}
			if ( isset( $options['text'] ) ) {
				$options['text'] = wp_kses_post( $options['text'] );
			}
			if ( isset( $options['mapurl'] ) ) {
				$options['mapurl'] = sanitize_url( $options['mapurl'] );
			}
			if ( isset( $options['color'] ) ) {
				$options['color'] = sanitize_text_field( $options['color'] );
			}
			if ( isset( $options['count'] ) ) {
				$options['count'] = absint( $options['count'] );
			}
			if ( isset( $options['okay'] ) ) {
				$options['okay'] = sanitize_text_field( $options['okay'] );
			}
			$change = array();
			foreach ( $options as $key => $value ) {
				if ( $value !== $defaults[ $key ] ) {
					$change[ $key ] = $value;
				}
			}
			return $change;
		}
		if ( isset( $_POST['delete'] ) ) {
			delete_option( 'leafext_dsgvo' );
		}
		return false;
	}

	// Erklaerung / Hilfe
	function leafext_dsgvo_help() {
		$text = '<h3>' .
		__( 'GDPR (DSGVO) snippet for Leaflet Map and its Extensions', 'dsgvo-leaflet-map' )
		. '</h3>';
		echo wp_kses_post( $text );
	}

	function leafext_dsgvo_help_what() {
		$text = '<h3>' . __( 'Function of the plugin', 'dsgvo-leaflet-map' ) . '</h3>';
		$text = $text . '<p>' . sprintf(
		/* translators: %1$s is leaflet-map, %2$s is the cookie name */
			__( 'The plugin prevents the shortcode %1$s from being executed. If the user agrees, the cookie %2$s is set and %1$s is executed.', 'dsgvo-leaflet-map' ),
			'<code>&#091;leaflet-map]</code>',
			'<code>leafext</code>'
		) . '</p>';
		$text = $text . '<p>' .
		sprintf(
			/* translators: %s are hrefs. */
			__(
				'An example is %1$shere%2$s',
				'dsgvo-leaflet-map'
			),
			'<a href="https://leafext.de/extra/dsgvo-example/">',
			'</a>'
		);
		$text = $text . '.</p>';
		echo wp_kses_post( $text );
	}

	function leafext_dsgvo_ttfp_help() {
		if ( leafext_plugin_active( 'polylang' ) ) {
			echo '<h3>Polylang</h3>';
			$ttfp = '<a href="' . esc_url( 'https://wordpress.org/plugins/theme-translation-for-polylang/' ) . '">Theme and plugin translation for Polylang (TTfP)</a> ';
			if ( leafext_plugin_active( 'polylang-theme-translation' ) ) {
				echo '<ul><li>';
				echo wp_kses_post( $ttfp ) . ' ';
				$ttfp = true;
				esc_html_e( 'is active.', 'dsgvo-leaflet-map' );
				echo '</li><li>';
				esc_html_e( 'Go to', 'dsgvo-leaflet-map' );
				echo ' <a href="' . esc_url( admin_url( 'admin.php' ) . '?page=mlang_import_export_strings' ) . '">';
				esc_html_e( 'Settings', 'dsgvo-leaflet-map' );
				echo '</a>, ';
				esc_html_e( 'enable', 'dsgvo-leaflet-map' );
				echo ' <code>' . esc_html( LEAFEXT_DSGVO_PLUGIN_NAME ) . '</code> ';
				esc_html_e( 'and', 'dsgvo-leaflet-map' );
				echo ' <a href="' . esc_url( admin_url( 'admin.php' ) . '?page=mlang_strings&s&group=TTfP%3A+' . LEAFEXT_DSGVO_PLUGIN_NAME ) . '">';
				esc_html_e( 'fill in your text', 'dsgvo-leaflet-map' );
				echo '</a>!';
				echo '</li></ul>';
			} else {
				printf(
				/* translators: %s is a link. */
					esc_html__( 'If you wish to translate these strings in %s use', 'dsgvo-leaflet-map' ),
					' <a href="' . esc_url( 'https://wordpress.org/plugins/polylang/' ) . '">Polylang</a> '
				);
				echo ' ' . wp_kses_post( $ttfp ) . '.';
				$ttfp = false;
			}
		}
	}

	// Draw the menu page itself
	function leafext_dsgvo_do_page() {
		leafext_dsgvo_help();
		if ( function_exists( 'leafext_dsgvo_update_admin' ) ) {
			leafext_dsgvo_update_admin();
		}
		leafext_dsgvo_help_what();
		echo '<h3>';
		esc_html_e( 'Settings', 'dsgvo-leaflet-map' );
		echo '</h3>';
		echo '<p>';
		esc_html_e( 'Test it in a private browser window.', 'dsgvo-leaflet-map' );
		echo '</p>';
		leafext_dsgvo_ttfp_help();
		echo '<form method="post" action="options.php">';
		settings_fields( 'leafext_settings_dsgvo' );
		wp_nonce_field( 'leafext_dsgvo', 'leafext_dsgvo_nonce' );
		do_settings_sections( 'leafext_settings_dsgvo' );
		if ( current_user_can( 'manage_options' ) ) {
			submit_button();
			submit_button( __( 'Reset', 'dsgvo-leaflet-map' ), 'delete', 'delete', false );
		}
		echo '</form>';
		leafext_dsgvo_short_code_help();
	}

	// Suche bestimmten Wert in array im admin interface
	function leafext_array_find3( $needle, $haystack ) {
		foreach ( $haystack as $item ) {
			if ( $item['param'] === $needle ) {
				return $item;
			}
		}
	}

	// Baue Abfrage Farben
	function leafext_dsgvo_colors( $defcolor, $value ) {
		wp_enqueue_style( 'wp-color-picker' );
		wp_enqueue_script( 'wp-color-picker' );
		wp_enqueue_script(
			'wp-color-picker-alpha',
			plugins_url( '/js/wp-color-picker-alpha.min.js', __FILE__ ),
			array( 'wp-color-picker' ),
			LEAFEXT_DSGVO_PLUGIN_VERSION,
			true
		);
		wp_enqueue_script(
			'leafext-picker',
			plugins_url( '/js/colorpicker.js', __FILE__ ),
			array( 'wp-color-picker-alpha', 'wp-color-picker' ),
			LEAFEXT_DSGVO_PLUGIN_VERSION,
			true
		);

		echo '<input type="text" class="color-picker" id="leafext_dsgvo_color" name="leafext_dsgvo[color]" data-alpha-enabled="true" data-default-color="'
		. esc_attr( $defcolor ) . '" value="' . esc_attr( $value ) . '">';
	}
}
