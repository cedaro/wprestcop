<?php
/**
 * WP REST Cop plugin.
 *
 * @package   Cedaro\WPRESTCop
 * @copyright Copyright (c) 2015, Cedaro, LLC
 * @license   GPL-2.0+
 * @since     1.0.0
 */

namespace Cedaro\WPRESTCop;

use WP_Error;
use WP_REST_Request;
use WP_REST_Response;
use WP_REST_Server;

/**
 * Main plugin class.
 *
 * @package Cedaro\WPRESTCop
 * @since   1.0.0
 */
class Plugin extends AbstractPlugin {
	/**
	 * Number of requests allowed per interval.
	 *
	 * @since 1.0.0
	 * @var integer
	 */
	protected $limit;

	/**
	 * Seconds per interval.
	 *
	 * @since 1.0.0
	 * @var integer
	 */
	protected $interval;

	/**
	 * IP address rules.
	 *
	 * @since 1.0.0
	 * @var \Cedaro\WPRESTCop\IPRulesInterface
	 */
	protected $ip_rules;

	/**
	 * Constructor method.
	 *
	 * @param \Cedaro\WPRESTCop\IPRulesInterface $ip_rules IP address rules.
	 */
	public function __construct( IPRulesInterface $ip_rules ) {
		$this->set_ip_rules( $ip_rules );
	}

	/**
	 * Load the plugin.
	 *
	 * @since 1.0.0
	 *
	 * @return $this
	 */
	public function load() {
		$settings = get_option( 'wprestcop_settings' );

		if ( false === $settings ) {
			$settings = [ 'limit' => 500, 'interval' => 3600 ];
			update_option( 'wprestcop_settings', $settings );
		}

		$this
			->set_limit( $settings['limit'] )
			->set_interval( $settings['interval'] );

		add_action( 'rest_api_init',              [ $this, 'initialize_ip_rules' ] );
		add_filter( 'rest_authentication_errors', [ $this, 'check_ip_rules' ] );
		add_filter( 'rest_pre_dispatch',          [ $this, 'throttle_request' ], 10, 3 );
		add_filter( 'rest_dispatch_request',      [ $this, 'check_route_ip_rules' ], 10, 2 );

		do_action( 'wprestcop_plugin_loaded', $this );

		return $this;
	}

	/**
	 * Initialize IP rules from options.
	 *
	 * These will usually be set with WP CLI.
	 *
	 * @since 1.0.0
	 *
	 * @return $this
	 */
	public function initialize_ip_rules() {
		$allow = get_option( 'wprestcop_allowed_ips' );
		if ( false === $allow ) {
			update_option( 'wprestcop_allowed_ips', [] );
		}

		$deny = get_option( 'wprestcop_denied_ips' );
		if ( false === $deny ) {
			update_option( 'wprestcop_denied_ips', [] );
		}

		$this->get_ip_rules()->allow( $allow );
		$this->get_ip_rules()->deny( $deny );
		return $this;
	}

	/**
	 * Retrieve an identifier for the current client.
	 *
	 * If a user is logged in, their user ID will be used, otherwise, defaults
	 * to the current client's IP address.
	 *
	 * @since 1.0.0
	 *
	 * @return string|int
	 */
	public function get_client_id() {
		$key = $this->get_ip_address();

		if ( is_user_logged_in() ) {
			$key = get_current_user_id();
		}

		return apply_filters( 'wprestcop_current_user_key', $key );
	}

	/**
	 * Retrieve the current client's IP address.
	 *
	 * @since 1.0.0
	 *
	 * @return string
	 */
	public function get_ip_address() {
		return $_SERVER['REMOTE_ADDR'];
	}

	/**
	 * Retreive IP rules.
	 *
	 * @since 1.0.0
	 *
	 * @return \Cedaro\WPRESTCop\IPRulesInterface
	 */
	public function get_ip_rules() {
		return $this->ip_rules;
	}

	/**
	 * Set the IP rules.
	 *
	 * @since 1.0.0
	 *
	 * @param  \Cedaro\WPRESTCop\IPRulesInterface $ip_rules Instance of IP rules.
	 * @return $this
	 */
	public function set_ip_rules( $ip_rules ) {
		$this->ip_rules = $ip_rules;
		return $this;
	}

	/**
	 * Retrieve the global rate limit.
	 *
	 * @since 1.0.0
	 *
	 * @return int
	 */
	public function get_rate_limit() {
		return $this->limit;
	}

	/**
	 * Set the number of requests allowed per interval.
	 *
	 * @since 1.0.0
	 *
	 * @param  int $limit Number of requests.
	 * @return $this
	 */
	public function set_limit( $limit ) {
		$this->limit = intval( $limit );
		return $this;
	}

	/**
	 * Retrieve the global rate limit interval.
	 *
	 * @since 1.0.0
	 *
	 * @return int
	 */
	public function get_interval() {
		return $this->interval;
	}

	/**
	 * Set the number of seconds per interval.
	 *
	 * @since 1.0.0
	 *
	 * @param  int $interval Seconds per interval.
	 * @return $this
	 */
	public function set_interval( $interval ) {
		$this->interval = intval( $interval );
		return $this;
	}

	/**
	 * Global request handler.
	 *
	 * @since 1.0.0
	 *
	 * @param  mixed            $response Response.
	 * @param  \WP_REST_Server  $server   Server instance.
	 * @param  \WP_REST_Request $request  Request used to generate the response.
	 * @return \WP_REST_Response|WP_Error
	 */
	public function throttle_request( $response, WP_REST_Server $server, WP_REST_Request $request ) {
		// Bail if the interval is -1.
		if ( -1 === $this->get_interval() ) {
			return $response;
		}

		$meter = MeterMaid::make(
			$this->get_client_id(),
			$this->get_rate_limit(),
			$this->get_interval()
		);

		// Don't throttle HEAD requests to let clients check details.
		if ( 'HEAD' !== $request->get_method() ) {
			$meter->tick();
		}

		$server->send_headers( $meter->get_headers() );

		if ( $meter->is_limit_exceeded() ) {
			$data = [
				'code'    => 'rate_limit_exceeded',
				'message' => esc_html__( 'Too many requests.', 'wprestcop' ),
				'data'    => array_merge(
					$meter->to_array(),
					[ 'status' => 429 ]
				),
			];

			$response = new WP_REST_Response( $data, 429 );
		}

		MeterMaid::save( $meter );

		return $response;
	}

	/**
	 * Check global IP address settings.
	 *
	 * @since 1.0.0
	 *
	 * @param  WP_Error|null|boolean $error WP_Error if authentication error, null if
	 *                                      authentication method wasn't used, true if
	 *                                      authentication succeeded.
	 * @return null|WP_Error
	 */
	public function check_ip_rules( $error ) {
		if ( ! $this->get_ip_rules()->check( $this->get_ip_address() ) ) {
			$error = $this->get_forbidden_error();
		}

		return $error;
	}

	/**
	 * Check route-level settings for IP addresses.
	 *
	 * @since 1.0.0
	 *
	 * @param mixed            $response Existing response.
	 * @param \WP_REST_Request $request  Request used to generate the response.
	 * @return mixed
	 */
	public function check_route_ip_rules( $response, WP_REST_Request $request ) {
		$settings = [];
		if ( ! empty( $request->get_attributes()['ips'] ) ) {
			$settings = $request->get_attributes()['ips'];
		}

		if ( $settings instanceof IPRulesInterface ) {
			$rules = $settings;
		} else {
			$rules = new IPRules( $settings );
		}

		if ( ! $rules->check( $this->get_ip_address() ) ) {
			$response = $this->get_forbidden_error();
		}

		return $response;
	}

	/**
	 * Retrieve the default forbidden error.
	 *
	 * @since 1.0.0
	 *
	 * @return WP_Error
	 */
	protected function get_forbidden_error() {
		return new WP_Error(
			'rest_forbidden',
			esc_html( "You don't have permission to do this.", 'wprestcop' ),
			[ 'status' => 403 ]
		);
	}
}
