<?php
namespace NeedleProject\LaravelRabbitMq\Connection;

use Tests\NeedleProject\LaravelRabbitMq\Stubs\ConnectionDetailsStub;
use PHPUnit\Framework\TestCase;

class AMQPConnectionTest extends TestCase
{
    public function testCreateWithEmptyDetails()
    {
        $connection = ConnectionDetailsStub::createConnection('foo', []);
        $details = $connection->getConnectionDetails();

        $this->assertEquals('127.0.0.1',$details['hostname']);
        $this->assertEquals(5672,       $details['port']);
        $this->assertEquals('guest',    $details['username']);
        $this->assertEquals('guest',    $details['password']);
        $this->assertEquals('/',        $details['vhost']);
        $this->assertEquals(true,       $details['lazy']);
        $this->assertEquals(8,          $details['read_write_timeout']);
        $this->assertEquals(10,         $details['connect_timeout']);
        $this->assertEquals(4,          $details['heartbeat']);
    }

    public function testCreateWithAllDetails()
    {
        $connection = ConnectionDetailsStub::createConnection(
            'foo',
            [
                'hostname' => 'foo',
                'port'     => 1,
                'username' => 'bar',
                'password' => 'baz',
                'vhost'    => 'ahost',
                'lazy'     => false,
                'read_write_timeout' => 99,
                'connect_timeout' => 98,
                'heartbeat'       => 97,
            ]
        );

        $details = $connection->getConnectionDetails();

        $this->assertEquals('foo', $details['hostname']);
        $this->assertEquals(1,     $details['port']);
        $this->assertEquals('bar', $details['username']);
        $this->assertEquals('baz', $details['password']);
        $this->assertEquals('ahost',$details['vhost']);
        $this->assertEquals(false,  $details['lazy']);
        $this->assertEquals(99,     $details['read_write_timeout']);
        $this->assertEquals(98,     $details['connect_timeout']);
        $this->assertEquals(97,     $details['heartbeat']);
    }

    public function testCreateWithInvalidArgumentsDetails()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage("Cannot create connection foo, received unknown arguments: foo, bar!");
        ConnectionDetailsStub::createConnection(
            'foo',
            [
                'foo' => 'bar',
                'bar' => 'baz'
            ]
        );
    }
}
