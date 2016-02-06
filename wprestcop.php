<?php
/**
 * WP REST Cop
 *
 * @package   Cedaro\WPRESTCop
 * @copyright Copyright (c) 2015 Cedaro, LLC
 * @license   GPL-2.0+
 *
 * @wordpress-plugin
 * Plugin Name: WP REST Cop
 * Plugin URI:  https://github.com/cedaro/wprestcop
 * Description: Manage access to the WP REST API.
 * Version:     1.0.0
 * Author:      Cedaro
 * Author URI:  http://www.cedaro.com/
 * License:     GPL-2.0+
 * License URI: http://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: wprestcop
 * Domain Path: /languages
 */

use Cedaro\WPRESTCop\IPRules;
use Cedaro\WPRESTCop\Plugin;

include( __DIR__ . '/php/AbstractPlugin.php' );
include( __DIR__ . '/php/Plugin.php' );
include( __DIR__ . '/php/IPRulesInterface.php' );
include( __DIR__ . '/php/IPRules.php' );
include( __DIR__ . '/php/Meter.php' );
include( __DIR__ . '/php/MeterMaid.php' );

/**
 * Retrieve the main plugin instance.
 *
 * @since 1.0.0
 *
 * @return \Cedaro\WPRESTCop\Plugin
 */
function wprestcop() {
	static $instance;
	if ( null === $instance ) {
		$instance = new Plugin( new IPRules() );
	}
	return $instance;
}

$wprestcop = wprestcop()
	->set_basename( plugin_basename( __FILE__ ) )
	->set_directory( plugin_dir_path( __FILE__ ) )
	->set_file( __FILE__ )
	->set_slug( 'wprestcop' )
	->set_url( plugin_dir_url( __FILE__ ) );

/**
 * Register WP CLI commands.
 */
if ( defined( 'WP_CLI' ) && WP_CLI ) {
	include( __DIR__ . '/php/CLI.php' );
	$wprestcop->initialize_ip_rules();
	WP_CLI::add_command( 'restcop', '\Cedaro\WPRESTCop\CLI' );
}

/**
 * Localize the plugin.
 *
 * @since 1.0.0
 */
add_action( 'plugins_loaded', function() use ( $wprestcop ) {
	$plugin_rel_path = dirname( $wprestcop->get_basename() ) . '/languages';
	load_plugin_textdomain( $wprestcop->get_slug(), false, $plugin_rel_path );
} );

/**
 * Load the plugin when the REST API is initialized.
 *
 * @since 1.0.0
 */
add_action( 'rest_api_init', function() use ( $wprestcop ) {
	$wprestcop->load();
}, 5 );
