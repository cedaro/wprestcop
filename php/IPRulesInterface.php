<?php
/**
 * Access rules interface.
 *
 * @package   Cedaro\WPRESTCop
 * @since     1.0.0
 * @copyright Copyright (c) 2015 Cedaro, LLC
 * @license   GPL-2.0+
 */

namespace Cedaro\WPRESTCop;

/**
 * Access rules interface.
 *
 * @package Cedaro\WPRESTCop
 * @since   1.0.0
 */
interface IPRulesInterface {
	/**
	 * Whitelist one or more clients.
	 *
	 * @since 1.0.0
	 *
	 * @param  string $id Client identifier(s).
	 * @return $this
	 */
	public function allow( $id );

	/**
	 * Whether client ID passes allowed and denied checks.
	 *
	 * @since 1.0.0
	 *
	 * @param  string $id Client identifier to test.
	 * @return boolean
	 */
	public function check( $id );

	/**
	 * Blacklist one or more clients.
	 *
	 * @since 1.0.0
	 * @param  string $id Client identifier(s).
	 * @return $this
	 */
	public function deny( $id );

	/**
	 * Retrieve allowed clients.
	 *
	 * @since 1.0.0
	 *
	 * @return array
	 */
	public function get_allowed();

	/**
	 * Retrieve denied clients.
	 *
	 * @since 1.0.0
	 *
	 * @return array
	 */
	public function get_denied();

	/**
	 * Whether a client is is allowed.
	 *
	 * @since 1.0.0
	 *
	 * @param  string $id Client identifier string.
	 * @return boolean
	 */
	public function is_allowed( $id );

	/**
	 * Whether a client is denied.
	 *
	 * @since 1.0.0
	 *
	 * @param  string $id Client identifier string.
	 * @return boolean
	 */
	public function is_denied( $id );
}
