<?php
/**
 * Plugin command line interface.
 *
 * @package   Cedaro\WPRESTCop
 * @since     1.0.0
 * @copyright Copyright (c) 2015 Cedaro, LLC
 * @license   GPL-2.0+
 */

namespace Cedaro\WPRESTCop;

use WP_CLI;

/**
 * Manage access to the REST API.
 *
 * @package Cedaro\WPRESTCop
 * @since   1.0.0
 */
class CLI {
	/**
	 * Grant IP addresses access to the REST API.
	 *
	 * ## OPTIONS
	 *
	 * <ip>...
	 * : One or more IP addresses.
	 *
	 * [--delete]
	 * : Delete rules for the IP addresses.
	 *
	 * @synopsis <ip>... [--delete]
	 */
	public function allow( $args, $assoc_args ) {
		$this->update_option( 'wprestcop_allowed_ips', $args, isset( $assoc_args['delete'] ) );
	}

	/**
	 * Deny IP addresses access to the REST API.
	 *
	 * ## OPTIONS
	 *
	 * <ip>...
	 * : One or more IP addresses.
	 *
	 * [--delete]
	 * : Delete rules for the IP addresses.
	 *
	 * @synopsis <ip>... [--delete]
	 */
	public function deny( $args, $assoc_args ) {
		$this->update_option( 'wprestcop_denied_ips', $args, isset( $assoc_args['delete'] ) );
	}

	/**
	 * Check the status of an IP address.
	 *
	 * ## OPTIONS
	 *
	 * <ip>
	 * : An IP address.
	 *
	 * @synopsis <ip>
	 */
	public function check( $args, $assoc_args ) {
		if ( wprestcop()->get_ip_rules()->check( $args[0] ) ) {
			WP_CLI::success( sprintf( esc_html__( '%s is allowed to access the REST API.', 'wprestcop' ), $args[0] ) );
		} else {
			WP_CLI::error( sprintf( esc_html__( '%s is blocked from accessing the REST API.', 'wprestcop' ), $args[0] ) );
		}
	}

	/**
	 * View IP address rules.
	 */
	public function status( $args, $assoc_args ) {
		$items = [];

		$labels = [
			esc_html__( 'IP Address', 'wprestcop' ),
			esc_html__( 'Action', 'wprestcop' ),
			esc_html__( 'Source', 'wprestcop' ),
		];

		$option      = get_option( 'wprestcop_allowed_ips', [] );
		$action_l10n = esc_html( 'ALLOW', 'wprestcop' );
		foreach ( wprestcop()->get_ip_rules()->get_allowed() as $ip ) {
			$source = 'code';
			if ( in_array( $ip, $option ) ) {
				$source = 'option';
			}

			$items[] = array_combine( $labels, [ $ip, $action_l10n, $source ] );
		}

		$option      = get_option( 'wprestcop_denied_ips', [] );
		$action_l10n = esc_html__( 'DENY', 'wprestcop' );
		foreach ( wprestcop()->get_ip_rules()->get_denied() as $ip ) {
			$source = 'code';
			if ( in_array( $ip, $option ) ) {
				$source = 'option';
			}

			$items[] = array_combine( $labels, [ $ip, $action_l10n, $source ] );
		}

		$format_args = [];
		$formatter = new WP_CLI\Formatter( $format_args, $labels );
		$formatter->display_items( $items );
	}

	/**
	 * Set a plugin setting.
	 *
	 * ## OPTIONS
	 *
	 * <key>
	 * : The name of the setting to update (limit or interval).
	 *
	 * <value>
	 * : The new value.
	 *
	 * @synopsis <key> <value>
	 */
	public function set( $args, $assoc_args ) {
		$settings = get_option( 'wprestcop_settings' );

		if ( in_array( $args[0], [ 'interval', 'limit' ] ) ) {
			$settings[ $args[0] ] = intval( $args[1] );
		} else {
			WP_CLI::error( sprintf( esc_html__( '%s is not a valid setting.', 'wprestcop' ), $args[0] ) );
		}

		update_option( 'wprestcop_settings', $settings );
		WP_CLI::success( sprintf( esc_html__( 'Updated %s setting to %s.', 'wprestcop' ), $args[0], $args[1] ) );
	}

	/**
	 * Helper function to update an array option.
	 *
	 * @param string  $key    Option name.
	 * @param array   $args   Option values.
	 * @param boolean $delete Optional. Whether the passed values should be deleted from existing values. Default is to merge them.
	 */
	protected function update_option( $key, $args, $delete = false ) {
		$ips = get_option( $key, [] );

		if ( $delete ) {
			$ips = array_diff( $ips, (array) $args );
		} else {
			$ips = array_merge( $ips, (array) $args );
		}

		update_option( $key, array_unique( array_filter( $ips ) ) );
	}
}
