<?php
/**
 *  Admin Extensions for Leaflet Map DSGVO
 *
 * @package Extensions for Leaflet Map DSGVO
 **/

// Direktzugriff auf diese Datei verhindern.
defined( 'ABSPATH' ) || die();

// Add menu page
function leafext_dsgvo_add_page() {
	$leafext_plugin_name = basename( __DIR__ );
	// Add Submenu
	$leafext_admin_page = add_submenu_page(
		'leaflet-map',
		__( 'Leaflet Map Options GDPR', 'extensions-leaflet-map-dsgvo' ),
		__( 'Leaflet Map GDPR', 'extensions-leaflet-map-dsgvo' ),
		'manage_options',
		$leafext_plugin_name,
		'leafext_dsgvo_do_page'
	);
}
add_action( 'admin_menu', 'leafext_dsgvo_add_page', 100 );

function leafext_dsgvo_init() {
	add_settings_section( 'leafext_dsgvo', '', 'leafext_dsgvo_help', 'leafext_settings_dsgvo' );
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
	// var_dump($field);
	$options  = leafext_dsgvo_params();
	$option   = leafext_array_find2( $field, $options );
	$settings = leafext_dsgvo_settings();
	// var_dump($settings,$option);
	$setting = $settings[ $field ];
	// if ( $option['desc'] != "" ) echo '<p>'.$option['desc'].'</p>';
	if ( is_plugin_active( 'theme-translation-for-polylang/polylang-theme-translation.php' ) ) {
		$readonly = ' readonly ';
	} else {
		$readonly = '';
	}
	switch ( $field ) {
		case 'text':
			echo '<textarea ' . $readonly . ' name="leafext_dsgvo[text]" type="textarea" cols="80" rows="5">';
			echo esc_textarea( $setting );
			echo '</textarea>';
			break;
		case 'mapurl':
			echo '<input type="url" size="80" name="leafext_dsgvo[mapurl]" value="' . esc_url( $setting ) .
			'" />';
			break;
		case 'cookie':
			echo '<input type="number" size="5" min="1" max="365" name="leafext_dsgvo[cookie]" value=' . $setting . '> ';
			esc_html_e( 'days', 'extensions-leaflet-map-dsgvo' );
			break;
		case 'count':
			echo '<input type="radio" name="leafext_dsgvo[count]" value="1" ';
			echo boolval( $setting ) ? 'checked' : '';
			echo '> ';
			esc_html_e( 'each map', 'extensions-leaflet-map-dsgvo' );
			echo ' &nbsp;&nbsp; ';
			echo '<input type="radio" name="leafext_dsgvo[count]" value="0" ';
			echo ! boolval( $setting ) ? 'checked' : '';
			echo '> ';
			esc_html_e( 'only first', 'extensions-leaflet-map-dsgvo' );
			echo ' ';
			break;
		case 'okay':
			echo '<input type="text" ' . $readonly . ' size="10" name="leafext_dsgvo[okay]" value="' . esc_textarea( $setting ) . '" />';
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
		if ( $options['cookie'] == '0' || $options['cookie'] == '' ) {
			$options['cookie'] = '365';
		}
		$options['text']   = wp_kses_normalize_entities( $options['text'] );
		$options['mapurl'] = sanitize_text_field( $options['mapurl'] );
		$options['count']  = $options['count'];
		$options['okay']   = wp_kses_normalize_entities( $options['okay'] );
		$change            = array();
		foreach ( $options as $key => $value ) {
			if ( $value != $defaults[ $key ] ) {
				$change[ $key ] = $value;
			}
		}
		// var_dump($options,$defaults,$change); wp_die();
		return $change;
	}
	if ( isset( $_POST['delete'] ) ) {
		delete_option( 'leafext_dsgvo' );
	}
	return false;
}

// Erklaerung / Hilfe
function leafext_dsgvo_help() {
	// Call globals
	global $wp_filesystem;
	// Initiate
	WP_Filesystem();
	$local_file = LEAFEXT_DSGVO_PLUGIN_DIR . '/readme.md';
	$text       = '';
	if ( $wp_filesystem->exists( $local_file ) ) {
		$text = $wp_filesystem->get_contents( $local_file );
		// [Extensions for Leaflet Map](https://de.wordpress.org/plugins/extensions-leaflet-map/)
		$suchmuster = array(
			'/\[(.+)\]\((.+)\)/i',
			'/(### )(.*)/',
			'/(## )(.*)/',
			'/(# )(.*)/',
			'/  /',
		);
		$ersetzung  = array(
			'<a href="${2}">${1}</a>',
			'<h3>${2}</h3>',
			'<h2>${2}</h2>',
			'<h1>${2}</h1>',
			'<br>',
		);
		$text       = preg_replace( $suchmuster, $ersetzung, $text );
		// https://wp-mix.com/allowed-html-tags-wp_kses/
		$allowed_tags = wp_kses_allowed_html( 'post' );
		echo '<div style="width:80%">' . wp_kses( $text, $allowed_tags ) . '</div>';
		echo '<h3>';
		esc_html_e( 'Settings', 'extensions-leaflet-map-dsgvo' );
		echo '</h3>';
		echo '<p>';
		esc_html_e( 'Test it in a private browser window.', 'extensions-leaflet-map-dsgvo' );
	} else {
		echo 'Error';
	}

	if ( is_plugin_active( 'polylang/polylang.php' ) ) {
		echo '<h3>Polylang</h3>';
		$ttfb = '<a href="https://wordpress.org/plugins/theme-translation-for-polylang/">Theme and plugin translation for Polylang (TTfP)</a> ';
		if ( is_plugin_active( 'theme-translation-for-polylang/polylang-theme-translation.php' ) ) {
			echo '<ul><li>';
			echo $ttfb . ' ';
			$ttfb = true;
			esc_html_e( 'is active.', 'extensions-leaflet-map-dsgvo' );
			echo '</li><li>';
			esc_html_e( 'Go to', 'extensions-leaflet-map-dsgvo' );
			echo ' <a href="' . admin_url( 'admin.php' ) . '?page=mlang_import_export_strings">';
			esc_html_e( 'Settings', 'extensions-leaflet-map-dsgvo' );
			echo '</a>, ';
				esc_html_e( 'enable', 'extensions-leaflet-map-dsgvo' );
			echo ' <code>leafext-dsgvo</code> ';
			esc_html_e( 'and', 'extensions-leaflet-map-dsgvo' );
			echo ' <a href="https://leafext.info/b/wp-admin/admin.php?page=mlang_strings&s&group=TTfP%3A+leafext-dsgvo&paged=1">';
			esc_html_e( 'fill in your text', 'extensions-leaflet-map-dsgvo' );
			echo '</a>!';
			echo '</li></ul>';
		} else {
			printf(
				__( 'If you wish to translate these strings in %s use', 'extensions-leaflet-map-dsgvo' ),
				' <a href="https://wordpress.org/plugins/polylang/">Polylang</a> '
			);
			echo ' ' . $ttfb . '.';
			$ttfb = false;
		}
	}
}

// Draw the menu page itself
function leafext_dsgvo_do_page() {
	echo '<form method="post" action="options.php">';
	settings_fields( 'leafext_settings_dsgvo' );
	wp_nonce_field( 'leafext_dsgvo', 'leafext_dsgvo_nonce' );
	do_settings_sections( 'leafext_settings_dsgvo' );
	submit_button();
	submit_button( __( 'Reset', 'extensions-leaflet-map-dsgvo' ), 'delete', 'delete', false );
	echo '</form>';
}
