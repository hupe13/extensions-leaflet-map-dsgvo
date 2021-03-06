<?php
/**
 * Plugin Name: DSGVO Snippet for Extensions for Leaflet Map
 * Description: DSGVO Snippet for Extensions for Leaflet Map
 * Plugin URI:  https://github.com/hupe13/extensions-leaflet-map-dsgvo
 * GitHub Plugin URI: https://github.com/hupe13/extensions-leaflet-map-dsgvo
 * Primary Branch: main
 * Version:     220720
 * Author:      hupe13
**/

// Direktzugriff auf diese Datei verhindern:
defined( 'ABSPATH' ) or die();

define('LEAFEXT_DSGVO_PLUGIN_DIR', plugin_dir_path(__FILE__)); // /pfad/wp-content/plugins/plugin/
define('LEAFEXT_DSGVO_PLUGIN_URL', WP_PLUGIN_URL . '/' . basename (LEAFEXT_DSGVO_PLUGIN_DIR)); // https://url/wp-content/plugins/plugin/

// Add settings to plugin page
function leafext_add_action_dsgvo_links ( $actions ) {
  $actions[] = '<a href="'. esc_url( get_admin_url(null, 'admin.php?page='. dirname( plugin_basename( __FILE__ ) ) ) ) .'">'. esc_html__( "Settings").'</a>';
  return $actions;
}
add_filter( 'plugin_action_links_' . plugin_basename(__FILE__), 'leafext_add_action_dsgvo_links' );

// Passe diesen Text im Admin Interface an.
function leafext_okay() {
  $defaulttext =
    'Bei der Verwendung der Karten werden Inhalte von Drittservern geladen. '.
    'Wenn Du dem zustimmst, wird ein Cookie gesetzt und dieser Hinweis ausgeblendet. '.
    'Wenn nicht, werden Dir keine Karten angezeigt.';
  $options = get_option( 'leafext_dsgvo' );
  if ( ! $options ) return $defaulttext;
  if ( ! is_array ($options) ) return $options;
  if ( $options['text'] == "" ) return $defaulttext;
  return $options['text'];
}

function leafext_empty(){
  //
}

function leafext_setcookie() {
  global $leafext_cookie;
  $leafext_cookie = false;
  $request_method = strtolower($_SERVER['REQUEST_METHOD']);
  if ( $request_method == 'post' && !empty($_POST["leafext_button"])) {
    //array(1) { ["leafext_button"]=> string(4) "Okay" }
    if ( $_POST["leafext_button"] == "Okay" ) {
      $cookie = "365";
      $options = get_option( 'leafext_dsgvo' );
      if ( is_array ($options) && isset($options['cookie']) ) $cookie = $options['cookie'];
      setcookie ("leafext", 1, time()+3600*24*$cookie, "/", $_SERVER['HTTP_HOST'], true, true);
      $leafext_cookie = true;
    }
  }
}
add_action( 'init', 'leafext_setcookie' );

function leafext_query_cookie( $output, $tag ) {
  global $leafext_cookie;
  if ( is_admin()
    || is_user_logged_in()
    || ('leaflet-map' !== $tag
      && 'sgpx' !== $tag)
    || isset($_COOKIE["leafext"])
    || $leafext_cookie
  ) {
    return $output;
  }
  //
  global $leafext_okay;
  if (!isset($leafext_okay)) {
    $leafext_okay = true;
    global $shortcode_tags;
    $shortcodes = $shortcode_tags;
    $leafext = array (
      'cluster',
      'elevation',
      'elevation-track',
      'elevation-tracks',
      'fullscreen',
      'gestures',
      'hidemarkers',
      'hover',
      'layerswitch',
      'markerClusterGroup',
      'multielevation',
      'placementstrategies',
      'sgpx',
      'zoomhomemap',
    );
    foreach ($shortcodes as $shortcode => $value) {
      if ( $shortcode !== 'leaflet-map' ) {
        if ( strpos($shortcode, "leaflet") !== false || in_array($shortcode,$leafext) ) {
          //$text=$text.var_dump($shortcode);
          remove_shortcode( $shortcode);
          add_shortcode($shortcode,'leafext_empty');
        }
      }
    }
    $text = '<form action="" method="post">';
    $text = $text.leafext_okay();
    $text = $text.
      '<p class="submit" style="display:flex; justify-content: center; align-items: center;">
      <input type="submit" value="Okay" name="leafext_button" /></p>
      </form>';
  } else {
    $text = "";
  }
  //!isset($leafext_okay) end
  preg_match('/style="[^"]+"/', $output, $matches);
  if (count($matches) == 0) $matches[0] = ' style=".';
  $options = get_option( 'leafext_dsgvo');
  if (! $options) {
    $image=LEAFEXT_DSGVO_PLUGIN_URL.'/map.png';
  } else if ( !is_array($options)) {
    $image = $options;
  } else if ( $options['mapurl'] != "") {
    $image = $options['mapurl'];
  } else {
    $image = LEAFEXT_DSGVO_PLUGIN_URL.'/map.png';
  }
  $output = '<div data-nosnippet '.substr($matches[0], 0, -1).
    ';background: linear-gradient(rgba(255, 255, 255, 0.7), rgba(255, 255, 255, 0.7)), '.
    'url('.$image.'); background-position: center; '.
    'border: gray 2px solid; display:flex; justify-content: center; '.
    'align-items: center;"><div style="width: 70%;">'.$text.'</div></div>';
  return $output;
}
add_filter('do_shortcode_tag', 'leafext_query_cookie', 10, 2);

if (is_admin()) {
  include_once LEAFEXT_DSGVO_PLUGIN_DIR . 'admin.php';
}
