<?php
// Direktzugriff auf diese Datei verhindern:
defined( 'ABSPATH' ) or die();

// Add menu page
function leafext_dsgvo_add_page() {
	$leafext_plugin_name = basename(dirname(  __FILE__  ));
	//Add Submenu
	$leafext_admin_page = add_submenu_page(
		'leaflet-map',
		'Extensions for Leaflet Map Options DSGVO',
		'Extensions for Leaflet Map DSGVO',
		'manage_options',
		$leafext_plugin_name,
		'leafext_dsgvo_do_page'
	);
}
add_action('admin_menu', 'leafext_dsgvo_add_page', 100);

function leafext_dsgvo_init(){
	// Create Setting
	$section_group = 'leafext_dsgvo';
 	$section_name = 'leafext_dsgvo';
 	$validate = 'leafext_validate_dsgvo';
	register_setting( $section_group, $section_name, $validate );

	// Create section of Page
	$settings_section = 'leafext_dsgvo_main';
	$page = $section_group;
	add_settings_section(
		$settings_section,
		'DSGVO',
		'leafext_dsgvo_help',
		$page
	);

	// Add fields to that section
	add_settings_field(
		$section_name,
		'Text',
		'leafext_dsgvo_form',
		$page,
		$settings_section
	);
}
add_action( 'admin_init', 'leafext_dsgvo_init' );

function leafext_dsgvo_form() {
	$setting = leafext_okay();
	echo '<textarea name="leafext_dsgvo" type="textarea" cols="80" rows="5">';
	echo $setting;
	echo '</textarea>';
}

// Sanitize and validate input. Accepts an array, return a sanitized array.
function leafext_validate_dsgvo($options) {
	if (isset($_POST['submit'])) {
		$dsgvo_text = wp_kses_normalize_entities ( $options );
		return $dsgvo_text;
	}
	if (isset($_POST['delete'])) delete_option('leafext_dsgvo');
	return false;
}

// Erklaerung / Hilfe
function leafext_dsgvo_help() {
	echo '<p>'.
	"Laut DSGVO muss der Nutzer aktiv zustimmen,
	wenn Inhalte von Drittservern geladen werden sollen.
	Das Wordpress-Plugin Extensions for Leaflet Map
	lädt Inhalte von den definierten Tile-Servern sowie unpkg.com.
	Dieses kleine Snippet holt die Zustimmung des Nutzers zum Laden der Karten ein.
	Du kannst hier den Text anpassen und es auf eigene Verantwortung verwenden.
	".
	'</p>';
}

// Draw the menu page itself
function leafext_dsgvo_do_page (){
	echo '<form method="post" action="options.php">';
	settings_fields('leafext_dsgvo');
	do_settings_sections( 'leafext_dsgvo' );
	submit_button();
	submit_button( __( 'Reset', 'extensions-leaflet-map' ), 'delete', 'delete', false);
	echo '</form>';
}
