<?php
/**
 * Plugin Name: Templify Import
 * Description: Import Templates through Templify import
 * Version: 1.0.0
 * Author: Templify Inner WP
 * License: GPLv2 or later
 * Text Domain: templify-import-templates
 *
 * @package Templify Import Templates
 */

// Block direct access to the main plugin file.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'TEMPLIFY_IMPORT_TEMPLATES_PATH', plugin_dir_path( __FILE__ ) );
define( 'TEMPLIFY_IMPORT_TEMPLATES_URL', trailingslashit( plugin_dir_url( __FILE__ ) ) );
define( 'TEMPLIFY_IMPORT_TEMPLATES_VERSION', '1.0.0' );

// Enqueue scripts and styles
function templify_import_enqueue_scripts() {
    // Enqueue jQuery
    wp_enqueue_script('jquery');
    
    // Enqueue plugin scripts
    wp_enqueue_style('templify-import-style', plugins_url('assets/css/style.css', __FILE__));
    wp_enqueue_script('templify-import-script', plugins_url('assets/js/script.js', __FILE__), array('jquery'), TEMPLIFY_IMPORT_TEMPLATES_VERSION, true);
}
add_action('wp_enqueue_scripts', 'templify_import_enqueue_scripts');

// Activation hook
function templify_import_activate() {
    // Activation code here
}
register_activation_hook(__FILE__, 'templify_import_activate');

// Deactivation hook
function templify_import_deactivate() {
    // Deactivation code here
}
register_deactivation_hook(__FILE__, 'templify_import_deactivate');


require_once plugin_dir_path( __FILE__ ) . 'vendor/vendor-prefixed/autoload.php';
require_once plugin_dir_path( __FILE__ ) . 'vendor/autoload.php';
require_once plugin_dir_path( __FILE__ ) . 'class-templify-import-templates.php';
