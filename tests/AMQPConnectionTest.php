<?php
namespace NeedleProject\LaravelRabbitMq;

use PhpAmqpLib\Channel\AMQPChannel;
use PhpAmqpLib\Connection\AbstractConnection;
use PhpAmqpLib\Connection\AMQPStreamConnection;
use Tests\NeedleProject\LaravelRabbitMq\Stubs\ConnectionDetailsStub;
use PHPUnit\Framework\TestCase;

class AMQPConnectionTest extends TestCase
{
    public function testCreateWithEmptyDetails()
    {
        $connection = ConnectionDetailsStub::createConnection('foo', []);
        $details = $connection->getConnectionDetails();

        $this->assertEquals('127.0.0.1', $details['hostname']);
        $this->assertEquals(5672, $details['port']);
        $this->assertEquals('guest', $details['username']);
        $this->assertEquals('guest', $details['password']);
        $this->assertEquals('/', $details['vhost']);
        $this->assertEquals(true, $details['lazy']);
        $this->assertEquals(3, $details['read_write_timeout']);
        $this->assertEquals(3, $details['connect_timeout']);
        $this->assertEquals(0, $details['heartbeat']);
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
        $this->assertEquals(1, $details['port']);
        $this->assertEquals('bar', $details['username']);
        $this->assertEquals('baz', $details['password']);
        $this->assertEquals('ahost', $details['vhost']);
        $this->assertEquals(false, $details['lazy']);
        $this->assertEquals(99, $details['read_write_timeout']);
        $this->assertEquals(98, $details['connect_timeout']);
        $this->assertEquals(97, $details['heartbeat']);
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

    public function testConnectionGetChannel()
    {
        $channelMock = $this->createMock(AMQPChannel::class);
        $connectionMock = $this->createMock(AbstractConnection::class);

        $connectionMock->expects($this->once())
            ->method('channel')
            ->willReturn($channelMock);

        $amqpConnection = new class('foo', [], $connectionMock) extends AMQPConnection {
            /**
             * @var AMQPStreamConnection
             */
            private $mock;

            /**
             *  constructor.
             *
             * @param string $aliasName
             * @param array $connectionDetails
             * @param AMQPStreamConnection $mock
             */
            public function __construct($aliasName, array $connectionDetails = [], $mock = null)
            {
                $this->mock = $mock;
                parent::__construct($aliasName, $connectionDetails);
            }

            /**
             * @return AbstractConnection
             */
            protected function getConnection(): AbstractConnection
            {
                return $this->mock;
            }
        };

        $this->assertEquals($channelMock, $amqpConnection->getChannel());
    }

    public function testAliasName()
    {
        $amqpConnection = new AMQPConnection('foo', []);
        $this->assertEquals($amqpConnection->getAliasName(), 'foo');
    }

    public function testLazyConnection()
    {
        $tester = $this;
        $abstractConnectionMock = $this->createMock(AbstractConnection::class);

        new class('foo', ['lazy' => false], $tester, $abstractConnectionMock)  extends AMQPConnection {
            private $tester;

            private $abstractConnectionMock;

            /**
             *  constructor.
             *
             * @param string $aliasName
             * @param array $connectionDetails
             * @param null $tester
             */
            public function __construct($aliasName, array $connectionDetails = [], $tester = null, $abstractConnectionMock = null)
            {
                $this->tester = $tester;
                $this->abstractConnectionMock = $abstractConnectionMock;
                parent::__construct($aliasName, $connectionDetails);
            }

            /**
             * @return AbstractConnection
             */
            protected function getConnection(): AbstractConnection
            {
                $this->tester->assertTrue(true);
                return $this->abstractConnectionMock;
            }
        };
    }

    public function testReconnect()
    {
        $channelMock = $this->createMock(AMQPChannel::class);
        $channelMock->expects($this->once())
            ->method('close')
            ->willReturn(null);

        $connectionMock = $this->createMock(AbstractConnection::class);

        $connectionMock->expects($this->once())
            ->method('channel')
            ->willReturn($channelMock);
        $connectionMock->expects($this->once())
            ->method('reconnect')
            ->willReturn(null);

        $amqpConnection = new class('foo', [], $connectionMock)  extends AMQPConnection {
            /**
             * @var AbstractConnection
             */
            private $mock;

            /**
             *  constructor.
             *
             * @param string $aliasName
             * @param array $connectionDetails
             * @param AbstractConnection $mock
             */
            public function __construct($aliasName, array $connectionDetails = [], $mock = null)
            {
                $this->mock = $mock;
                parent::__construct($aliasName, $connectionDetails);
            }

            /**
             * @return AbstractConnection
             */
            protected function getConnection(): AbstractConnection
            {
                return $this->mock;
            }
        };

        $amqpConnection->reconnect();
    }
}
