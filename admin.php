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
		'',
		'leafext_dsgvo_help',
		$page
	);

	// Add fields to that section
	add_settings_field(
		$section_name,
		'Text',
		'leafext_dsgvo_form',
		$page,
		$settings_section,
	);

	add_settings_field(
		"leafext_dsgvo_mapurl",
		'Map URL',
		'leafext_dsgvo_form_mapurl',
		$page,
		$settings_section,
	);

	add_settings_field(
		"leafext_dsgvo_cookie",
		'Cookie Lifetime',
		'leafext_dsgvo_form_cookie',
		$page,
		$settings_section,
	);

	add_settings_field(
		"leafext_dsgvo_count",
		__('Should the text be displayed on each map of the page or only on the first map?','extensions-leaflet-map-dsgvo'),
		'leafext_dsgvo_form_count',
		$page,
		$settings_section,
	);

	add_settings_field(
		"leafext_dsgvo_okay",
		__('Okay','extensions-leaflet-map-dsgvo'),
		'leafext_dsgvo_form_okay',
		$page,
		$settings_section,
	);
}
add_action( 'admin_init', 'leafext_dsgvo_init' );

function leafext_dsgvo_form() {
	$setting = leafext_okay();
	echo '<textarea name="leafext_dsgvo[text]" type="textarea" cols="80" rows="5">';
	echo $setting;
	echo '</textarea>';
}

function leafext_dsgvo_form_mapurl() {
	$image="";
  $options = get_option( 'leafext_dsgvo' );
  if ( is_array ($options) && $options['mapurl'] != "" ) $image=$options['mapurl'];
	echo '<input type="url" size="80" name="leafext_dsgvo[mapurl]" value="'.$image.'" /><p>'.__('URL to Background Image','extensions-leaflet-map-dsgvo').'</p>';
}

function leafext_dsgvo_form_cookie() {
	$cookie = "365";
  $options = get_option( 'leafext_dsgvo' );
  if ( is_array ($options) && isset($options['cookie']) ) $cookie = $options['cookie'];
	echo '<input type="number" size="3" max="365" name="leafext_dsgvo[cookie]" placeholder="'.$cookie.'">';
}

function leafext_dsgvo_form_count() {
  $options = get_option( 'leafext_dsgvo' );
	$count = ( is_array ($options) && isset($options['count']) ) ? filter_var($options['count'],FILTER_VALIDATE_BOOLEAN) : false;
	echo '<input type="radio" name="leafext_dsgvo[count]" value="1" ';
	echo $count ? 'checked' : '' ;
	echo '> '.__('each map','extensions-leaflet-map-dsgvo').' &nbsp;&nbsp; ';
	echo '<input type="radio" name="leafext_dsgvo[count]" value="0" ';
	echo (!$count) ? 'checked' : '' ;
	echo '> '.__('only first','extensions-leaflet-map-dsgvo').' ';
}

function leafext_dsgvo_form_okay() {
	$options = get_option( 'leafext_dsgvo' );
	$okay="Okay";
	if ( is_array ($options) && isset($options['okay']) ) $okay = $options['okay'];
	echo '<input type="text" size="10" placeholder="'.$okay.'" name="leafext_dsgvo[okay]" value="'.esc_attr($okay).'" />';
}

// Sanitize and validate input. Accepts an array, return a sanitized array.
function leafext_validate_dsgvo($options) {
	if (isset($_POST['submit'])) {
		//var_dump($options); wp_die();
		if ($options['cookie'] == "0" || $options['cookie'] == "" ) $options['cookie'] = "365";
		$options['text'] = wp_kses_normalize_entities ( $options['text'] );
		$options['mapurl'] = sanitize_text_field ( $options['mapurl'] );
		$options['count'] = $options['count'];
		$options['okay'] = wp_kses_normalize_entities ( $options['okay'] );
		return $options;
	}
	if (isset($_POST['delete'])) delete_option('leafext_dsgvo');
	return false;
}

// Erklaerung / Hilfe
function leafext_dsgvo_help() {
	$text = file_get_contents( LEAFEXT_DSGVO_PLUGIN_DIR . "/readme.md" );
	//[Extensions for Leaflet Map](https://de.wordpress.org/plugins/extensions-leaflet-map/)
	$suchmuster = array(
		'/\[(.+)\]\((.+)\)/i',
		'/(### )(.*)/',
		'/(## )(.*)/',
		'/(# )(.*)/',
		'/  /',
	);
	$ersetzung = array(
		'<a href="${2}">${1}</a>',
		'<h3>${2}</h3>',
		'<h2>${2}</h2>',
		'<h1>${2}</h1>',
		'<br>',
	);
 	$text = preg_replace($suchmuster, $ersetzung, $text);
	echo '<div style="width:80%">'.$text.'</div>';
	echo '<h3>'.__('Settings','extensions-leaflet-map-dsgvo').'</h3>';
	echo '<p>'.__('Test it in a private browser window.','extensions-leaflet-map-dsgvo');
}

// Draw the menu page itself
function leafext_dsgvo_do_page (){
	echo '<form method="post" action="options.php">';
	settings_fields('leafext_dsgvo');
	do_settings_sections( 'leafext_dsgvo' );
	submit_button();
	submit_button( __( 'Reset', 'extensions-leaflet-map-dsgvo' ), 'delete', 'delete', false);
	echo '</form>';
}
