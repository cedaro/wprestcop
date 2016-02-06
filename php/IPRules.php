<?php
/**
 * IP address rules.
 *
 * @package   Cedaro\WPRESTCop
 * @since     1.0.0
 * @copyright Copyright (c) 2015 Cedaro, LLC
 * @license   GPL-2.0+
 */

namespace Cedaro\WPRESTCop;

/**
 * IP address rules class.
 *
 * @package Cedaro\WPRESTCop
 * @since   1.0.0
 */
class IPRules implements IPRulesInterface {
	/**
	 * Whitelisted IP addresses.
	 *
	 * @since 1.0.0
	 * @var array
	 */
	protected $allow = [];

	/**
	 * Blacklisted IP addresses.
	 *
	 * @since 1.0.0
	 * @var array
	 */
	protected $deny = [];

	/**
	 * Constructor method.
	 *
	 * @since 1.0.0
	 *
	 * @param array $rules Array of rules.
	 */
	public function __construct( $rules = [] ) {
		if ( isset( $rules['allow'] ) ) {
			$this->allow( $rules['allow'] );
		}

		if ( isset( $rules['deny'] ) ) {
			$this->deny( $rules['deny'] );
		}
	}

	/**
	 * Whitelist one or more IP addresses.
	 *
	 * @since 1.0.0
	 *
	 * @param  string $ip IP address(es).
	 * @return $this
	 */
	public function allow( $ip ) {
		$this->allow = array_filter( array_merge( $this->allow, (array) $ip ) );
		return $this;
	}

	/**
	 * Whether an IP address passes allowed and denied checks.
	 *
	 * @since 1.0.0
	 *
	 * @param  string $ip IP address to test.
	 * @return boolean
	 */
	public function check( $ip ) {
		$is_allowed = true;

		if ( ! empty( $this->allow ) && ! $this->is_allowed( $ip ) ) {
			$is_allowed = false;
		} elseif ( ! empty( $this->deny ) && $this->is_denied( $ip ) ) {
			$is_allowed = false;
		}

		return $is_allowed;
	}

	/**
	 * Blacklist one or more IP addresses.
	 *
	 * @since 1.0.0
	 * @param  string $ip IP address(es).
	 * @return $this
	 */
	public function deny( $ip ) {
		$this->deny = array_filter( array_merge( $this->deny, (array) $ip ) );
		return $this;
	}

	/**
	 * Retrieve allowed IP addresses.
	 *
	 * @since 1.0.0
	 *
	 * @return array
	 */
	public function get_allowed() {
		return $this->allow;
	}

	/**
	 * Retrieve denied IP addresses.
	 *
	 * @since 1.0.0
	 *
	 * @return array
	 */
	public function get_denied() {
		return $this->deny;
	}

	/**
	 * Whether an IP address is allowed.
	 *
	 * @since 1.0.0
	 *
	 * @param  string $ip IP address to test.
	 * @return boolean
	 */
	public function is_allowed( $ip ) {
		return in_array( $ip, $this->allow );
	}

	/**
	 * Whether an IP address is denied.
	 *
	 * @since 1.0.0
	 *
	 * @param  string $ip IP address to test.
	 * @return boolean
	 */
	public function is_denied( $ip ) {
		return in_array( $ip, $this->deny );
	}
}
