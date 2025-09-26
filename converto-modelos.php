<?php
/**
 * Plugin Name: Converto Modelos
 * Description: Biblioteca de templates (páginas e seções) para Elementor usada pelo Converto.
 * Author: Ricardo Maia
 * Version: 1.3.1
 */

if ( ! defined( 'ABSPATH' ) ) exit;

define( 'CONVERTO_MODELOS_PATH', plugin_dir_path( __FILE__ ) );
define( 'CONVERTO_MODELOS_URL', plugin_dir_url( __FILE__ ) );

require_once CONVERTO_MODELOS_PATH . 'includes/classCpt.php';
require_once CONVERTO_MODELOS_PATH . 'includes/classLibrary.php';
require_once CONVERTO_MODELOS_PATH . 'includes/classAjax.php';
require_once CONVERTO_MODELOS_PATH . 'includes/classRest.php';
require_once CONVERTO_MODELOS_PATH . 'includes/classExport.php';
require_once CONVERTO_MODELOS_PATH . 'includes/classPreview.php';


add_action( 'init', function() {
    (new ConvertoCpt())->register();
}, 0 );

add_action( 'rest_api_init', function() {
    (new ConvertoRest())->registerRoutes();
} );

add_action( 'elementor/ajax/register_actions', function( $ajax ) {
    (new ConvertoAjax())->registerAjaxActions( $ajax );
} );

add_action( 'plugins_loaded', function() {
    (new ConvertoExport())->boot();
    (new ConvertoPreview())->boot();
} );

add_action('rest_api_init', function() {
    add_filter('rest_pre_serve_request', function($value) {
        header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
        header("Pragma: no-cache");
        return $value;
    });
});

register_activation_hook( __FILE__, function() {
    (new ConvertoPreview())->registerRewriteRules();
    flush_rewrite_rules();
} );

register_deactivation_hook( __FILE__, function() {
    flush_rewrite_rules();
} );