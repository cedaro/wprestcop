<?php
/**
 * Meter manager.
 *
 * @package   Cedaro\WPRESTCop
 * @since     1.0.0
 * @copyright Copyright (c) 2015 Cedaro, LLC
 * @license   GPL-2.0+
 */

namespace Cedaro\WPRESTCop;

/**
 * Meter manager class.
 *
 * @package Cedaro\WPRESTCop
 * @since   1.0.0
 */
class MeterMaid {
	/**
	 * Make or retrieve an existing meter from cache by id.
	 *
	 * @since 1.0.0
	 *
	 * @param string|int $id       Unique key to identify the current client.
	 * @param int        $limit    Number of requests allowed per interval.
	 * @param int        $interval Seconds per interval.
	 * @return Meter
	 */
	public static function make( $id, $limit, $interval ) {
		$meter = wp_cache_get( self::get_cache_key( $id ), 'wprestcop' );
		if ( false === $meter ) {
			$meter = new Meter( $id, $limit, $interval );
		}

		return $meter;
	}

	/**
	 * Save a meter.
	 *
	 * @since 1.0.0
	 *
	 * @param Meter $meter Meter instance.
	 */
	public static function save( Meter $meter ) {
		wp_cache_set(
			self::get_cache_key( $meter->get_id() ),
			$meter,
			'wprestcop',
			$meter->get_reset()
		);
	}

	/**
	 * Retrieve a key to use for storage.
	 *
	 * @since 1.0.0
	 *
	 * @param string $id Meter identifier.
	 * @return string
	 */
	protected static function get_cache_key( $id ) {
		$prefix = 'wprestcop:meter:';
		return $prefix . $id;
	}
}
