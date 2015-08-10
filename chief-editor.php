<?php
/*
Plugin Name: Chief Editor
Plugin URI: http://www.termel.fr
Description: Manage all posts, comments and authors accross the network. The Chief Editor toolbox.
Version: 3.7.2
Author: Termel
Author URI: http://www.maxizone.fr
License: A "Slug" license name e.g. GPL2
*/
if ( ! defined( 'ABSPATH' ) ) { 
    exit; // Exit if accessed directly
}

// Initialize Settings
include_once(sprintf("%s/admin/chief-editor-admin.php", dirname(__FILE__)));
$ChiefEditorSettings = new ChiefEditorSettings();

//SCRIPTS
function chief_editor_scripts(){
  
  wp_register_script( 'jqueryui-js', plugins_url( '/libs/jqueryui/1.11.4/jquery-ui.min.js', __FILE__ ));
  wp_enqueue_script('jqueryui-js');
  wp_enqueue_style( 'jquery-ui-css', plugins_url( '/libs/jqueryui/1.11.4/jquery-ui.css', __FILE__ ));
  
  wp_register_script( 'chief-editor-js', plugins_url( '/js/chief-editor.js', __FILE__ ));
  wp_enqueue_script('chief-editor-js');
  wp_enqueue_style( 'chief-editor-css', plugins_url( '/css/chief-editor.css', __FILE__ ));
  
  wp_register_script( 'sorttable-js', plugins_url( '/js/sorttable.js', __FILE__ ));
  wp_enqueue_script('sorttable-js');
  
  wp_register_script( 'Chart-js', plugins_url( '/js/ChartNew.js', __FILE__ ));
  wp_enqueue_script('Chart-js');
  
  wp_register_script( 'chief-editor-graph-js', plugins_url( '/js/chief-editor-graph.js', __FILE__ ));
  wp_enqueue_script('chief-editor-graph-js');
  
}


add_action('admin_enqueue_scripts','chief_editor_scripts');
add_action( 'init', 'chief_editor_load_lang' );
// Register style sheet.
//add_action( 'wp_enqueue_scripts', 'register_plugin_styles' );
/********************************************************/
/* FUNCTIONS
********************************************************/

function chief_editor_load_lang() {
  $plugin_name =  'chief-editor';
  $relative_path = dirname( plugin_basename( __FILE__ ) ) . '/languages' ;
  //echo $relative_path . '<br/>';
  if (load_plugin_textdomain( 'chief-editor', false, $relative_path)) {
	//echo 'SUCCESS::loading lang file in :'.$relative_path;
  }
  else {
	//echo 'ERROR::loading lang file';
  }
}

?>