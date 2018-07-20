<?php
namespace NeedleProject\LaravelRabbitMq\Entity;

use PhpAmqpLib\Message\AMQPMessage;
use PHPUnit\Framework\TestCase;
use NeedleProject\LaravelRabbitMq\AMQPConnection;
use PhpAmqpLib\Channel\AMQPChannel;
use Tests\NeedleProject\LaravelRabbitMq\Stubs\ExchangeEntityDetailsStub;

class ExchangeEntityTest extends TestCase
{
    public function testCreate()
    {
        $amqpConnection = $this->getMockBuilder(AMQPConnection::class)
            ->disableOriginalConstructor()
            ->getMock();

        $exchange = ExchangeEntity::createExchange($amqpConnection, 'foo', []);

        $this->assertInstanceOf(ExchangeEntity::class, $exchange);
        $this->assertEquals('foo', $exchange->getAliasName());
    }

    public function testCreateWithDefaultAttributes()
    {
        $amqpConnection = $this->getMockBuilder(AMQPConnection::class)
            ->disableOriginalConstructor()
            ->getMock();

        $exchange = ExchangeEntityDetailsStub::createExchange($amqpConnection, 'foo', []);
        $this->assertEquals(
            [
                'exchange_type' => 'topic',
                'passive'       => false,
                'durable'       => false,
                'auto_delete'   => false,
                'internal'      => false,
                'nowait'        => false
            ],
            $exchange->getAttributes()
        );
    }

    public function testCreateExchangeByChannel()
    {
        $amqpConnection = $this->getMockBuilder(AMQPConnection::class)
            ->disableOriginalConstructor()
            ->getMock();

        $channelMock = $this->getMockBuilder(AMQPChannel::class)
            ->disableOriginalConstructor()
            ->getMock();

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
            'name' => 'exchange.name.on.rabbit',
            'exchange_type' => 'an-exchange-type',
            'passive'   => 'passive-value',
            'durable'   => 'durable-value',
            'auto_delete' => 'auto_delete-value',
            'internal'  => 'internal-value',
            'nowait'    => 'nowait-value',
        ]);
        $exchange->create();
    }

    public function testDeleteExchange()
    {
        $amqpConnection = $this->getMockBuilder(AMQPConnection::class)
            ->disableOriginalConstructor()
            ->getMock();

        $channelMock = $this->getMockBuilder(AMQPChannel::class)
            ->disableOriginalConstructor()
            ->getMock();

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
            'foo', [
                'name' => 'exchange.name.on.rabbit'
            ]
        );
        $exchange->delete();
    }

    public function testBind()
    {
        $amqpConnection = $this->getMockBuilder(AMQPConnection::class)
            ->disableOriginalConstructor()
            ->getMock();

        $channelMock = $this->getMockBuilder(AMQPChannel::class)
            ->disableOriginalConstructor()
            ->getMock();

        $amqpConnection->expects($this->exactly(2))
            ->method('getChannel')
            ->willReturn($channelMock);

        $channelMock->expects($this->exactly(2))
            ->method('queue_bind')
            ->withConsecutive(
                ['first.queue', 'exchange.name.on.rabbit', 'a'],
                ['second.queue', 'exchange.name.on.rabbit', 'b']
            )
            ->willReturn(null);

        $exchange = ExchangeEntity::createExchange(
            $amqpConnection,
            'foo', [
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
        $amqpConnection = $this->getMockBuilder(AMQPConnection::class)
            ->disableOriginalConstructor()
            ->getMock();

        $amqpConnection->expects($this->never())
            ->method('getChannel')
            ->willReturn(null);

        $exchange = ExchangeEntity::createExchange(
            $amqpConnection,
            'foo', [
                'name' => 'exchange.name.on.rabbit'
            ]
        );
        $exchange->bind();
    }

    public function testPublish()
    {
        $amqpConnection = $this->getMockBuilder(AMQPConnection::class)
            ->disableOriginalConstructor()
            ->getMock();

        $channelMock = $this->getMockBuilder(AMQPChannel::class)
            ->disableOriginalConstructor()
            ->getMock();

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
            'foo', [
                'name' => 'exchange.name.on.rabbit'
            ]
        );
        $exchange->publish('a');
    }

    public function testPublishWithRoutingKey()
    {
        $amqpConnection = $this->getMockBuilder(AMQPConnection::class)
            ->disableOriginalConstructor()
            ->getMock();

        $channelMock = $this->getMockBuilder(AMQPChannel::class)
            ->disableOriginalConstructor()
            ->getMock();

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
            'foo', [
                'name' => 'exchange.name.on.rabbit'
            ]
        );
        $exchange->publish('a', 'a-routing-key');
    }
}
