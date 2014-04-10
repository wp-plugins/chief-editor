<?php
/*
Plugin Name: Chief Editor
Plugin URI: http://www.termel.fr
Description: Manage all drafts, pending and scheduled posts and comments accross the network
Version: 2.3
Author: Max UNGER
Author URI: http://www.maxizone.fr
License: A "Slug" license name e.g. GPL2
*/
// Initialize Settings
include_once(sprintf("%s/admin/chief-editor-admin.php", dirname(__FILE__)));
$ChiefEditorSettings = new ChiefEditorSettings();

//SETUP
/*
function chief_editor_install(){
    //Do some installation work
}
register_activation_hook(__FILE__,'chief_editor_install'); 
*/
//SCRIPTS
function chief_editor_scripts(){
	//echo 'Loading Chief Editor scripts...';
	// enqueue the jquery ui datepicker library from your plugin:	
    wp_enqueue_script('jquery-ui-datepicker');
	global $wp_scripts;
 
	// get registered script object for jquery-ui
   	$ui = $wp_scripts->query('jquery-ui-core');
   	// tell WordPress to load the Smoothness theme from Google CDN
   	$protocol = is_ssl() ? 'https' : 'http';
   	$url = "$protocol://ajax.googleapis.com/ajax/libs/jqueryui/{$ui->ver}/themes/smoothness/jquery-ui.min.css";
   	wp_enqueue_style('jquery-ui-smoothness', $url, false, null);
	wp_register_script( 'chief-editor-js', plugins_url( '/js/chief-editor.js', __FILE__ ));	
	wp_enqueue_script('chief-editor-js');
  
  wp_register_script( 'sorttable-js', plugins_url( '/js/sorttable.js', __FILE__ ));	
	wp_enqueue_script('sorttable-js');
  
	//echo '...done';
}
//add_action('wp_enqueue_scripts','chief_editor_scripts');
add_action('admin_enqueue_scripts','chief_editor_scripts');

//HOOKS
//add_action('init','chief_editor_init');
/********************************************************/
/* FUNCTIONS
********************************************************/
function chief_editor_init(){
    //do work
    chief_editor_init_path();
}
function chief_editor_init_path(){
    //more work
 
}
?>