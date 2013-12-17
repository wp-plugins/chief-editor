<?php
/*
Plugin Name: Chief Editor
Plugin URI: http://www.termel.fr
Description: Manage all drafts accross the network
Version: 1.0
Author: Max UNGER
Author URI: http://www.termel.fr
License: A "Slug" license name e.g. GPL2
*/
// Initialize Settings
include_once(sprintf("%s/admin/chief-editor-admin.php", dirname(__FILE__)));
$ChiefEditorSettings = new ChiefEditorSettings();
//SETUP
function chief_editor_install(){
    //Do some installation work
}
register_activation_hook(__FILE__,'chief_editor_install'); 
//SCRIPTS
function chief_editor_scripts(){
    //wp_register_script('super_plugin_script',plugin_dir_url( __FILE__ ).'js/super-plugin.js');
    //wp_enqueue_script('super_plugin_script');
}
add_action('wp_enqueue_scripts','chief_editor_scripts');
//HOOKS
add_action('init','chief_editor_init');
/********************************************************/
/* FUNCTIONS
********************************************************/
function chief_editor_init(){
    //do work
    run_sub_process();
}
function run_sub_process(){
    //more work
}
?>