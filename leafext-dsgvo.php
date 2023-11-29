<?php
/**
* Plugin Name: DSGVO Snippet for Extensions for Leaflet Map
* Description: DSGVO Snippet for Extensions for Leaflet Map
* Plugin URI:  https://github.com/hupe13/extensions-leaflet-map-dsgvo
* GitHub Plugin URI: https://github.com/hupe13/extensions-leaflet-map-dsgvo
* Primary Branch: main
* Version:     231129
* Author:      hupe13
* Text Domain: extensions-leaflet-map-dsgvo
* Domain Path: /lang/
**/

// Direktzugriff auf diese Datei verhindern:
defined( 'ABSPATH' ) or die();

define('LEAFEXT_DSGVO_PLUGIN_DIR', plugin_dir_path(__FILE__)); // /pfad/wp-content/plugins/plugin/
define('LEAFEXT_DSGVO_PLUGIN_URL', WP_PLUGIN_URL . '/' . basename (LEAFEXT_DSGVO_PLUGIN_DIR)); // https://url/wp-content/plugins/plugin/

// for translating a plugin
function leafext_dsgvo_textdomain() {
  if (get_locale() == 'de_DE') {
    load_plugin_textdomain('extensions-leaflet-map-dsgvo', false, basename(plugin_dir_path( __FILE__ )) . '/lang/');
  }
}
add_action( 'init', 'leafext_dsgvo_textdomain' );

// Add settings to plugin page
function leafext_add_action_dsgvo_links ( $actions ) {
  $actions[] = '<a href="'. esc_url( get_admin_url(null, 'admin.php?page='. dirname( plugin_basename( __FILE__ ) ) ) ) .'">'. esc_html__( "Settings").'</a>';
  return $actions;
}
add_filter( 'plugin_action_links_' . plugin_basename(__FILE__), 'leafext_add_action_dsgvo_links' );

function leafext_dsgvo_params() {
  $params = array(
    array(
      'param' => 'text',
      'desc' => __('Text','extensions-leaflet-map-dsgvo'),
      'default' => __('When using the maps, content is loaded from third-party servers. If you agree to this, a cookie will be set and this notice will be hidden. If not, no maps will be displayed.','extensions-leaflet-map-dsgvo'),
    ),
    array(
      'param' => 'okay',
      'desc' => __('Submit Button','extensions-leaflet-map-dsgvo'),
      'default' =>  __("Okay",'extensions-leaflet-map-dsgvo'),
    ),
    array(
      'param' => 'mapurl',
      'desc' => __('URL to Background Image','extensions-leaflet-map-dsgvo'),
      'default' => LEAFEXT_DSGVO_PLUGIN_URL.'/map.png',
    ),
    array(
      'param' => 'cookie',
      'desc' => __('Cookie Lifetime','extensions-leaflet-map-dsgvo'),
      'default' => "365",
    ),
    array(
      'param' => 'count',
      'desc' =>   __('Should the text be displayed on each map of the page or only on the first map?','extensions-leaflet-map-dsgvo'),
      'default' => false,
    ),
  );
  return $params;
}

function leafext_dsgvo_settings() {
  $defaults=array();
  $params = leafext_dsgvo_params();
  foreach($params as $param) {
    $defaults[$param['param']] = $param['default'];
  }
  $options = shortcode_atts($defaults, get_option('leafext_dsgvo'));
  return $options;
}

function leafext_setcookie() {
  global $leafext_cookie;
  $leafext_cookie = false;
  $request_method = strtolower($_SERVER['REQUEST_METHOD']);
  if ( $request_method == 'post' && !empty($_POST["leafext_button"])) {
    if(!wp_verify_nonce($_REQUEST['leafext_dsgvo_okay'], 'leafext_dsgvo')){
      wp_die('invalid', 404);
    }

    $settings = leafext_dsgvo_settings();
    if ( $_POST["leafext_button"] == $settings['okay'] ) {
      setcookie ("leafext", 1, time()+3600*24*$settings['cookie'], "/", $_SERVER['HTTP_HOST'], true, true);
      $leafext_cookie = true;
    }
  }
}
add_action( 'init', 'leafext_setcookie' );

function leafext_query_cookie( $output, $tag ) {
  $text = leafext_should_interpret_shortcode($tag,array());
  if ( $text != "" ) {
    //return $text;
    return $output;
  } else {
    global $leafext_cookie;
    if (
      is_admin()
      || is_user_logged_in()
      || ('leaflet-map' !== $tag && 'sgpx' !== $tag)
      || isset($_COOKIE["leafext"])
      || $leafext_cookie
    ) {
      return $output;
    }
    //
    $form = '<form action="" method="post">';
    $form = $form.wp_nonce_field( 'leafext_dsgvo', 'leafext_dsgvo_okay' );
    $settings = leafext_dsgvo_settings();
    $form = $form.$settings['text'];
    $form = $form.
    '<p class="submit" style="display:flex; justify-content: center; align-items: center;">
    <input type="submit" value="'.$settings['okay'].'" name="leafext_button" /></p>
    </form>';

    global $leafext_okay;
    //var_dump($leafext_okay);
    if ( !isset($leafext_okay)) {
      $leafext_okay = true;
      wp_dequeue_style('leaflet_stylesheet');
      wp_dequeue_script('wp_leaflet_map');
      wp_deregister_style('leaflet_stylesheet');
      wp_deregister_script('wp_leaflet_map');
      $text = $form;
    } else {
      $count = filter_var($settings['count'],FILTER_VALIDATE_BOOLEAN);
      if ($count) {
        $text = $form;
      } else {
        $text = "";
      }
    }
    //!isset($leafext_okay) end

    // bei sgpx leaflet wird es 2x aufgerufen.
    if ($tag == "sgpx") {
      $output = do_shortcode('[leaflet-map]');
      $text = $form;
    }

    preg_match('/linear-gradient|WPLeafletMap" style="height:1px;/', $output, $matches);

    if (count($matches) == 0 || $tag == "sgpx") {
      preg_match('/style="[^"]+"/', $output, $matches);
      if (count($matches) == 0) $matches[0] = ' style=".';
      $output = '<div data-nosnippet '.substr($matches[0], 0, -1).
      ';background: linear-gradient(rgba(255, 255, 255, 0.7), rgba(255, 255, 255, 0.7)), '.
      'url('.$settings['mapurl'].'); background-position: center; '.
      'border: gray 2px solid; display:flex; justify-content: center; '.
      'align-items: center;"><div style="width: 70%;">'.$text.'</div></div>';
    }
    return $output;
  }
}
add_filter('do_shortcode_tag', 'leafext_query_cookie', 10, 2);

if (is_admin()) {
  include_once LEAFEXT_DSGVO_PLUGIN_DIR . 'admin.php';
}
