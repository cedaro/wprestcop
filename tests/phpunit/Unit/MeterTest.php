<?php

namespace Cedaro\WPRESTCop\Test;

use Cedaro\WPRESTCop\Meter;


class MeterTest extends \PHPUnit_Framework_TestCase {
	public function setUp() {
		parent::setUp();
	}

	public function test_constructor_args() {
		$meter = new Meter( 1, 10, 300 );
		$this->assertSame( 1, $meter->get_id() );

		$data = $meter->to_array();
		$this->assertSame( $data['limit'], 10 );
		$this->assertSame( $data['remaining'], 10 );
		$this->assertSame( $data['reset'], 300 );
	}

	public function test_limit() {
		$meter = new Meter( 1, 10, 300 );

		$this->assertSame( 10, $meter->get_limit() );
		$this->assertSame( 10, $meter->get_remaining() );
		$this->assertFalse( $meter->is_limit_exceeded() );
	}

	public function test_tick() {
		$meter = new Meter( 1, 10, 300 );

		$meter->tick();
		$this->assertSame( 9, $meter->get_remaining() );

		$meter->tick( 2 );
		$this->assertSame( 7, $meter->get_remaining() );

		$meter->tick( 8 );
		$this->assertSame( -1, $meter->get_remaining() );

		$this->assertTrue( $meter->is_limit_exceeded() );
	}

	public function test_get_headers() {
		$meter = new Meter( 1, 10, 300 );
		$meter->tick( 11 );

		$headers = $meter->get_headers();

		$this->assertEquals( $headers['X-RateLimit-Limit'], 10 );
		$this->assertEquals( $headers['X-RateLimit-Remaining'], 0 );
		$this->assertEquals( $headers['X-RateLimit-Reset'], 300 );
		$this->assertEquals( $headers['Retry-After'], 300 );
	}
}
