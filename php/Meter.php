<?php
/**
 * Rate limit meter.
 *
 * @package   Cedaro\WPRESTCop
 * @since     1.0.0
 * @copyright Copyright (c) 2015 Cedaro, LLC
 * @license   GPL-2.0+
 */

namespace Cedaro\WPRESTCop;

/**
 * Meter class.
 *
 * @package Cedaro\WPRESTCop
 * @since   1.0.0
 */
class Meter implements \jsonSerializable {
	/**
	 * Unique meter identifier.
	 *
	 * @since 1.0.0
	 * @var string|int
	 */
	protected $id;

	/**
	 * Number of requests allowed per interval.
	 *
	 * @since 1.0.0
	 * @var int
	 */
	protected $limit;

	/**
	 * Seconds per interval.
	 *
	 * @since 1.0.0
	 * @var int
	 */
	protected $interval;

	/**
	 * Number of remaining ticks allowed in the interval.
	 *
	 * @since 1.0.0
	 * @var int
	 */
	protected $remaining;

	/**
	 * The interval start time.
	 *
	 * @since 1.0.0
	 * @var int
	 */
	protected $start;

	/**
	 * Constructor method.
	 *
	 * @since 1.0.0
	 *
	 * @param string|int $id       Unique key to identify the current client.
	 * @param int        $limit    Number of ticks allowed per interval.
	 * @param int        $interval Seconds per interval.
	 */
	public function __construct( $id, $limit, $interval ) {
		$this->id        = $id;
		$this->interval  = $interval;
		$this->limit     = $limit;
		$this->remaining = $this->limit;
		$this->start     = time();
	}

	/**
	 * Retrieve the meter identifier.
	 *
	 * @since 1.0.0
	 *
	 * @return string
	 */
	public function get_id() {
		return $this->id;
	}

	/**
	 * Retrieve rate limit headers.
	 *
	 * @since 1.0.0
	 *
	 * @return array
	 */
	public function get_headers() {
		$headers = [
			'X-RateLimit-Limit'     => $this->get_limit(),
			'X-RateLimit-Remaining' => $this->get_remaining(),
			'X-RateLimit-Reset'     => $this->get_reset(),
		];

		if ( $this->is_limit_exceeded() ) {
			$headers['Retry-After']           = $this->get_reset();
			$headers['X-RateLimit-Remaining'] = 0;
		}

		return $headers;
	}

	/**
	 * Retrieve the number of ticks allowed in the interval.
	 *
	 * @since 1.0.0
	 *
	 * @return int
	 */
	public function get_limit() {
		return $this->limit;
	}

	/**
	 * Whether the limit has been exceeded.
	 *
	 * @since 1.0.0
	 *
	 * @return bool
	 */
	public function is_limit_exceeded() {
		return 0 > $this->get_remaining();
	}

	/**
	 * Retrieve the number of ticks allowed before the limit is reached.
	 *
	 * @since 1.0.0
	 *
	 * @return int
	 */
	public function get_remaining() {
		return $this->remaining;
	}

	/**
	 * Retrieve the number of seconds until the meter resets.
	 *
	 * @since 1.0.0
	 *
	 * @return int
	 */
	public function get_reset() {
		return $this->start + $this->interval - time();
	}

	/**
	 * Update the counter.
	 *
	 * @since 1.0.0
	 *
	 * @param  int $tick The number of hits.
	 * @return $this
	 */
	public function tick( $tick = 1 ) {
		$this->remaining -= $tick;
		return $this;
	}

	/**
	 * Convert the meter to an array.
	 *
	 * @since 1.0.0
	 *
	 * @return array
	 */
	public function to_array() {
		return [
			'limit'     => $this->get_limit(),
			'remaining' => $this->is_limit_exceeded() ? 0 : $this->get_remaining(),
			'reset'     => $this->get_reset(),
		];
	}

	/**
	 * Serialize for JSON.
	 *
	 * @since 1.0.0
	 *
	 * @return array
	 */
	public function jsonSerialize() {
		return $this->to_array();
	}
}
