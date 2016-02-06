<?php

namespace Cedaro\WPRESTCop\Test;

use Cedaro\WPRESTCop\IPRules;


class IPRulesTest extends \PHPUnit_Framework_TestCase {
	public function setUp() {
		parent::setUp();
	}

	public function test_implements_iprules_interface() {
		$rules = new IPRules();

		$this->assertInstanceOf( '\Cedaro\WPRESTCop\IPRulesInterface', $rules );
	}

	public function test_constructor_args() {
		$allowed = [ '127.0.0.1' ];
		$denied = [ '192.168.50.4' ];

		$rules = new IPRules( [
			'allow' => $allowed,
			'deny'  => $denied,
		] );

		$this->assertEquals( $allowed, $rules->get_allowed() );
		$this->assertEquals( $denied, $rules->get_denied() );
	}

	public function test_allow() {
		$ip = '127.0.0.1';
		$rules = new IPRules();

		$this->assertFalse( $rules->is_allowed( $ip ) );
		$rules->allow( $ip );
		$this->assertEquals( [ $ip ], $rules->get_allowed() );
		$this->assertTrue( $rules->is_allowed( $ip ) );
	}

	public function test_allow_with_array() {
		$ips = [ '127.0.0.1', '192.168.50.4' ];
		$rules = new IPRules();
		$rules->allow( $ips );

		$this->assertEquals( $ips, $rules->get_allowed() );
		$this->assertTrue( $rules->is_allowed( '127.0.0.1' ) );
		$this->assertTrue( $rules->is_allowed( '192.168.50.4' ) );
	}

	public function test_deny() {
		$ip = '127.0.0.1';
		$rules = new IPRules();

		$this->assertFalse( $rules->is_denied( $ip ) );
		$rules->deny( $ip );
		$this->assertEquals( [ $ip ], $rules->get_denied() );
		$this->assertTrue( $rules->is_denied( $ip ) );
	}

	public function test_deny_with_array() {
		$ips = [ '127.0.0.1', '192.168.50.4' ];
		$rules = new IPRules();
		$rules->deny( $ips );

		$this->assertEquals( $ips, $rules->get_denied() );
		$this->assertTrue( $rules->is_denied( '127.0.0.1' ) );
		$this->assertTrue( $rules->is_denied( '192.168.50.4' ) );
	}

	public function test_blacklist() {
		$rules = new IPRules();
		$deny = '192.168.50.4';

		$this->assertFalse( $rules->deny( $deny )->check( $deny ) );
		$this->assertTrue( $rules->check( '127.0.0.1' ) );
	}

	public function test_whitelist() {
		$rules = new IPRules();
		$allow = '127.0.0.1';

		$this->assertTrue( $rules->allow( $allow )->check( $allow ) );
		$this->assertFalse( $rules->check( '192.168.50.4' ) );
	}
}
