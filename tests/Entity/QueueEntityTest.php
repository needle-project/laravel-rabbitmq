<?php
namespace NeedleProject\LaravelRabbitMq\Entity;

use NeedleProject\LaravelRabbitMq\AMQPConnection;
use PhpAmqpLib\Channel\AMQPChannel;
use PhpAmqpLib\Message\AMQPMessage;
use PHPUnit\Framework\TestCase;
use Tests\NeedleProject\LaravelRabbitMq\Stubs\QueueEntityDetailsStub;

class QueueEntityTest extends TestCase
{
    public function testCreate()
    {
        $amqpConnection = $this->getMockBuilder(AMQPConnection::class)
            ->disableOriginalConstructor()
            ->getMock();

        $queue = QueueEntity::createQueue($amqpConnection, 'foo', []);

        $this->assertInstanceOf(QueueEntity::class, $queue);
        $this->assertEquals('foo', $queue->getAliasName());
    }

    public function testCreateWithDefaultAttributes()
    {
        $amqpConnection = $this->getMockBuilder(AMQPConnection::class)
            ->disableOriginalConstructor()
            ->getMock();

        $queue = QueueEntityDetailsStub::createQueue($amqpConnection, 'foo', []);

        $this->assertEquals(
            [
                'passive'   => false,
                'durable'   => false,
                'exclusive' => false,
                'auto_delete' => false,
                'internal'  => false,
                'nowait'    => false,
            ],
            $queue->getAttributes()
        );
    }

    public function testPrefetch()
    {
        $amqpConnection = $this->getMockBuilder(AMQPConnection::class)
            ->disableOriginalConstructor()
            ->getMock();

        $queue = QueueEntityDetailsStub::createQueue($amqpConnection, 'foo', []);
        $queue->setPrefetchCount(5);
        $this->assertEquals(5, $queue->prefetchCount());
    }

    public function testMessageProcessor()
    {
        $amqpConnection = $this->getMockBuilder(AMQPConnection::class)
            ->disableOriginalConstructor()
            ->getMock();

        $queue = QueueEntityDetailsStub::createQueue($amqpConnection, 'foo', []);
        $queue->setMessageProcessor(self::class);
        $this->assertEquals(self::class, $queue->messageProcessor());
    }

    public function testCreateQueueByChannel()
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
            ->method('queue_declare')
            ->with(
                'queue.name.on.rabbit',
                'passive-value',
                'durable-value',
                'exclusive-value',
                'auto_delete-value',
                'internal-value',
                'nowait-value'
            )
            ->willReturn(null);

        $queue = QueueEntity::createQueue($amqpConnection, 'foo', [
            'name' => 'queue.name.on.rabbit',
            'passive'   => 'passive-value',
            'durable'   => 'durable-value',
            'exclusive' => 'exclusive-value',
            'auto_delete' => 'auto_delete-value',
            'internal'  => 'internal-value',
            'nowait'    => 'nowait-value',
        ]);
        $queue->create();
    }

    public function testDeleteQueue()
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
            ->method('queue_delete')
            ->with(
                'queue.name.on.rabbit'
            )
            ->willReturn(null);

        $queue = QueueEntity::createQueue(
            $amqpConnection,
            'foo',
            [
                'name' => 'queue.name.on.rabbit'
            ]
        );
        $queue->delete();
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
                ['queue.name.on.rabbit', 'first.exchange', 'a'],
                ['queue.name.on.rabbit', 'second.exchange', 'b']
            )
            ->willReturn(null);

        $queue = QueueEntity::createQueue(
            $amqpConnection,
            'foo',
            [
                'name' => 'queue.name.on.rabbit',
                'bind' => [
                    ['exchange' => 'first.exchange', 'routing_key' => 'a'],
                    ['exchange' => 'second.exchange', 'routing_key' => 'b'],
                ]
            ]
        );
        $queue->bind();
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
                '',
                'queue.name.on.rabbit',
                true
            )
            ->willReturn(null);

        $queue = QueueEntity::createQueue(
            $amqpConnection,
            'foo',
            [
                'name' => 'queue.name.on.rabbit'
            ]
        );
        $queue->publish('a');
    }
}
