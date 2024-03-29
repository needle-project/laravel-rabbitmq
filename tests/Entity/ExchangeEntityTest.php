<?php
namespace NeedleProject\LaravelRabbitMq\Entity;

use PhpAmqpLib\Channel\AbstractChannel;
use PhpAmqpLib\Exception\AMQPProtocolChannelException;
use PhpAmqpLib\Message\AMQPMessage;
use PHPUnit\Framework\TestCase;
use NeedleProject\LaravelRabbitMq\AMQPConnection;
use PhpAmqpLib\Channel\AMQPChannel;
use Tests\NeedleProject\LaravelRabbitMq\Stubs\ExchangeEntityDetailsStub;
use PhpAmqpLib\Exception\AMQPChannelClosedException;

class ExchangeEntityTest extends TestCase
{
    public function testCreate()
    {
        $amqpConnection = $this->createMock(AMQPConnection::class);
        $exchange = ExchangeEntity::createExchange($amqpConnection, 'foo', []);

        $this->assertInstanceOf(ExchangeEntity::class, $exchange);
        $this->assertEquals('foo', $exchange->getAliasName());
    }

    public function testCreateWithDefaultAttributes()
    {
        $amqpConnection = $this->createMock(AMQPConnection::class);

        $exchange = ExchangeEntityDetailsStub::createExchange($amqpConnection, 'foo', []);
        $this->assertEquals(
            [
                'exchange_type'                => 'topic',
                'passive'                      => false,
                'durable'                      => false,
                'auto_delete'                  => false,
                'internal'                     => false,
                'nowait'                       => false,
                'auto_create'                  => false,
                'throw_exception_on_redeclare' => true,
                'throw_exception_on_bind_fail' => true,
                'arguments'                    => [],
                'ticket'                       => null
            ],
            $exchange->getAttributes()
        );
    }

    public function testCreateExchangeByChannel()
    {
        $amqpConnection = $this->createMock(AMQPConnection::class);
        $channelMock = $this->createMock(AMQPChannel::class);

        $amqpConnection->expects($this->once())
            ->method('getChannel')
            ->willReturn($channelMock);

        $channelMock->expects($this->once())
            ->method('exchange_declare')
            ->with(
                'exchange.name.on.rabbit',
                'an-exchange-type',
                'passive-value',
                'durable-value',
                'auto_delete-value',
                'internal-value',
                'nowait-value'
            )
            ->willReturn(null);

        $exchange = ExchangeEntity::createExchange($amqpConnection, 'foo', [
            'name'          => 'exchange.name.on.rabbit',
            'exchange_type' => 'an-exchange-type',
            'passive'       => 'passive-value',
            'durable'       => 'durable-value',
            'auto_delete'   => 'auto_delete-value',
            'internal'      => 'internal-value',
            'nowait'        => 'nowait-value',
        ]);
        $exchange->create();
    }

    public function testDeleteExchange()
    {
        $amqpConnection = $this->createMock(AMQPConnection::class);
        $channelMock = $this->createMock(AMQPChannel::class);

        $amqpConnection->expects($this->once())
            ->method('getChannel')
            ->willReturn($channelMock);

        $channelMock->expects($this->once())
            ->method('exchange_delete')
            ->with(
                'exchange.name.on.rabbit'
            )
            ->willReturn(null);

        $exchange = ExchangeEntity::createExchange(
            $amqpConnection,
            'foo',
            [
                'name' => 'exchange.name.on.rabbit'
            ]
        );
        $exchange->delete();
    }

    public function testBind()
    {
        $amqpConnection = $this->createMock(AMQPConnection::class);
        $channelMock = $this->createMock(AMQPChannel::class);

        $amqpConnection->expects($this->exactly(2))
            ->method('getChannel')
            ->willReturn($channelMock);

        $matcher = $this->exactly(2);
        $matchingArgumentsOnFirstCall = ['first.queue', 'exchange.name.on.rabbit', 'a'];
        $matchingArgumentsOnSecondCall = ['second.queue', 'exchange.name.on.rabbit', 'b'];
        $channelMock->expects($matcher)
            ->method('queue_bind')
            ->willReturnCallback(
                function (string $queue, string $exchange, string $routingKey) use ($matcher, $matchingArgumentsOnFirstCall, $matchingArgumentsOnSecondCall) {
                    match ($matcher->numberOfInvocations()) {
                        1 =>  $this->assertEquals($matchingArgumentsOnFirstCall, [$queue, $exchange, $routingKey]),
                        2 =>  $this->assertEquals($matchingArgumentsOnSecondCall, [$queue, $exchange, $routingKey]),
                    };
                });

        $exchange = ExchangeEntity::createExchange(
            $amqpConnection,
            'foo',
            [
                'name' => 'exchange.name.on.rabbit',
                'bind' => [
                    ['queue' => 'first.queue', 'routing_key' => 'a'],
                    ['queue' => 'second.queue', 'routing_key' => 'b'],
                ]
            ]
        );
        $exchange->bind();
    }

    public function testEmptyBind()
    {
        $amqpConnection = $this->createMock(AMQPConnection::class);

        $amqpConnection->expects($this->never())
            ->method('getChannel')
            ->willReturn(null);

        $exchange = ExchangeEntity::createExchange(
            $amqpConnection,
            'foo',
            [
                'name' => 'exchange.name.on.rabbit'
            ]
        );
        $exchange->bind();
    }

    public function testPublish()
    {
        $amqpConnection = $this->createMock(AMQPConnection::class);
        $channelMock = $this->createMock(AMQPChannel::class);

        $amqpConnection->expects($this->once())
            ->method('getChannel')
            ->willReturn($channelMock);

        $channelMock->expects($this->once())
            ->method('basic_publish')
            ->with(
                new AMQPMessage('a'),
                'exchange.name.on.rabbit',
                '',
                true
            )
            ->willReturn(null);

        $exchange = ExchangeEntity::createExchange(
            $amqpConnection,
            'foo',
            [
                'name' => 'exchange.name.on.rabbit'
            ]
        );
        $exchange->publish('a');
    }

    public function testPublishWithRoutingKey()
    {
        $amqpConnection = $this->createMock(AMQPConnection::class);
        $channelMock = $this->createMock(AMQPChannel::class);

        $amqpConnection->expects($this->once())
            ->method('getChannel')
            ->willReturn($channelMock);

        $channelMock->expects($this->once())
            ->method('basic_publish')
            ->with(
                new AMQPMessage('a'),
                'exchange.name.on.rabbit',
                'a-routing-key',
                true
            )
            ->willReturn(null);

        $exchange = ExchangeEntity::createExchange(
            $amqpConnection,
            'foo',
            [
                'name' => 'exchange.name.on.rabbit'
            ]
        );
        $exchange->publish('a', 'a-routing-key');
    }

    public function testCreateWithExceptionSuppressed()
    {
        $amqpConnection = $this->createMock(AMQPConnection::class);
        $channelMock = $this->createMock(AMQPChannel::class);

        $amqpConnection->expects($this->once())
            ->method('getChannel')
            ->willReturn($channelMock);

        $channelMock->expects($this->once())
            ->method('exchange_declare')
            ->willThrowException(
                new AMQPProtocolChannelException(406, 'Foo', [50,20])
            );

        $amqpConnection->expects($this->once())
            ->method('reconnect')
            ->willReturn(null);

        $exchange = ExchangeEntity::createExchange($amqpConnection, 'foo', [
            'name'                         => 'exchange.name.on.rabbit',
            'exchange_type'                => 'an-exchange-type',
            'throw_exception_on_redeclare' => false,
        ]);
        $exchange->create();
    }

    public function testCreateWithExceptionNotSuppressed()
    {
        $amqpConnection = $this->createMock(AMQPConnection::class);
        $channelMock = $this->createMock(AMQPChannel::class);

        $amqpConnection->expects($this->once())
            ->method('getChannel')
            ->willReturn($channelMock);

        $channelMock->expects($this->once())
            ->method('exchange_declare')
            ->willThrowException(
                new AMQPProtocolChannelException(406, 'Foo', [50,20])
            );

        $amqpConnection->expects($this->never())
            ->method('reconnect')
            ->willReturn(null);

        $exchange = ExchangeEntity::createExchange($amqpConnection, 'foo', [
            'name'                         => 'exchange.name.on.rabbit',
            'exchange_type'                => 'an-exchange-type',
            'throw_exception_on_redeclare' => true
        ]);

        $this->expectException(AMQPProtocolChannelException::class);
        $exchange->create();
    }

    public function testPublishWithAutoCreate()
    {
        $amqpConnection = $this->createMock(AMQPConnection::class);
        $channelMock = $this->createMock(AMQPChannel::class);

        $amqpConnection->expects($this->exactly(3))
            ->method('getChannel')
            ->willReturn($channelMock);

        $channelMock->expects($this->once())
            ->method('exchange_declare')
            ->willReturn(null);

        $channelMock->expects($this->once())
            ->method('queue_bind')
            ->willReturn(null);

        $channelMock->expects($this->once())
            ->method('basic_publish')
            ->with(
                new AMQPMessage('a'),
                'exchange.name.on.rabbit',
                '',
                true
            )
            ->willReturn(null);

        $exchange = ExchangeEntity::createExchange(
            $amqpConnection,
            'foo',
            [
                'name' => 'exchange.name.on.rabbit',
                'auto_create' => true,
                'bind' => [['queue' => 'foo', 'routing_key' => '*']]
            ]
        );
        $exchange->publish('a');
    }


    public function testPublishRetry()
    {
        $amqpConnection = $this->createMock(AMQPConnection::class);
        $channelMock = $this->createMock(AMQPChannel::class);

        $amqpConnection->expects($this->atLeastOnce())
            ->method('getChannel')
            ->willReturn($channelMock);

        $amqpConnection->expects($this->atLeastOnce())
            ->method('reconnect')
            ->willReturn($channelMock);

        $retries = 0;
        $channelMock->expects($this->exactly(2))
            ->method('basic_publish')
            ->will($this->returnCallback(function ($args) use (&$retries) {
                if (0 === $retries) {
                    $retries++;
                    throw new AMQPChannelClosedException("Channel is Closed");
                }
                return null;
            }));

        $queue = ExchangeEntity::createExchange(
            $amqpConnection,
            'foo',
            [
                'name' => 'exchange.name.on.rabbit',
                'auto_create' => true,
                'bind' => [['queue' => 'foo', 'routing_key' => '*']]
            ]
        );
        $queue->publish('a');
        $this->assertEquals(1, $retries);
    }

    public function testPublishMaxRetry()
    {
        $amqpConnection = $this->createMock(AMQPConnection::class);
        $channelMock = $this->createMock(AMQPChannel::class);

        $amqpConnection->expects($this->atLeastOnce())
            ->method('getChannel')
            ->willReturn($channelMock);

        $amqpConnection->expects($this->atLeastOnce())
            ->method('reconnect')
            ->willReturn($channelMock);

        $channelMock->expects($this->exactly(3))
            ->method('basic_publish')
            ->will($this->throwException(new AMQPChannelClosedException("Channel is Closed")));

        $queue = ExchangeEntity::createExchange(
            $amqpConnection,
            'foo',
            [
                'name' => 'exchange.name.on.rabbit',
                'auto_create' => true,
                'bind' => [['queue' => 'foo', 'routing_key' => '*']]
            ]
        );
        $this->expectException(AMQPChannelClosedException::class);
        $queue->publish('a');
    }
}
