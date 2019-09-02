<?php
namespace NeedleProject\LaravelRabbitMq\Entity;

use NeedleProject\LaravelRabbitMq\AMQPConnection;
use NeedleProject\LaravelRabbitMq\Processor\MessageProcessorInterface;
use PhpAmqpLib\Channel\AMQPChannel;
use PhpAmqpLib\Exception\AMQPChannelClosedException;
use PhpAmqpLib\Exception\AMQPProtocolChannelException;
use PhpAmqpLib\Message\AMQPMessage;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
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
                'passive'                      => false,
                'durable'                      => false,
                'exclusive'                    => false,
                'auto_delete'                  => false,
                'internal'                     => false,
                'nowait'                       => false,
                'auto_create'                  => false,
                'throw_exception_on_redeclare' => true,
                'throw_exception_on_bind_fail' => true
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

    public function testCreateQueueWithExceptionSuppressing()
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
            ->willThrowException(
                new AMQPProtocolChannelException(406, 'Foo', [50,20])
            );

        $queue = QueueEntity::createQueue($amqpConnection, 'foo', [
            'name' => 'queue.name.on.rabbit',
            'throw_exception_on_redeclare' => false,
        ]);
        $queue->create();
    }

    public function testCreateQueueWithoutExceptionSuppressing()
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
            ->willThrowException(
                new AMQPProtocolChannelException(406, 'Foo', [50,20])
            );

        $queue = QueueEntity::createQueue($amqpConnection, 'foo', [
            'name' => 'queue.name.on.rabbit',
            'throw_exception_on_redeclare' => true,
        ]);
        $this->expectException(AMQPProtocolChannelException::class);
        $queue->create();
    }

    public function testBindException()
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
            ->method('queue_bind')
            ->willThrowException(
                new AMQPProtocolChannelException(406, 'Foo', [50,20])
            );

        $queue = QueueEntity::createQueue($amqpConnection, 'foo', [
            'name' => 'queue.name.on.rabbit',
            'bind' => [
                ['exchange' => 'foo.bar', 'routing_key' => '*']
            ]
        ]);
        $this->expectException(AMQPProtocolChannelException::class);
        $queue->bind();
    }

    public function testEmptyBind()
    {
        $amqpConnection = $this->getMockBuilder(AMQPConnection::class)
            ->disableOriginalConstructor()
            ->getMock();

        $amqpConnection->expects($this->never())
            ->method('getChannel')
            ->willReturn(null);

        $queue = QueueEntity::createQueue($amqpConnection, 'foo', [
            'name' => 'queue.name.on.rabbit',
            'bind' => []
        ]);
        $queue->bind();
    }

    public function testPublishWithAutoCreate()
    {
        $amqpConnection = $this->getMockBuilder(AMQPConnection::class)
            ->disableOriginalConstructor()
            ->getMock();

        $channelMock = $this->getMockBuilder(AMQPChannel::class)
            ->disableOriginalConstructor()
            ->getMock();

        $amqpConnection->expects($this->exactly(3))
            ->method('getChannel')
            ->willReturn($channelMock);

        $channelMock->expects($this->once())
            ->method('queue_declare')
            ->willReturn(null);

        $channelMock->expects($this->once())
            ->method('queue_bind')
            ->willReturn(null);

        $channelMock->expects($this->once())
            ->method('basic_publish')
            ->willReturn(null);

        $queue = QueueEntity::createQueue(
            $amqpConnection,
            'foo',
            [
                'name' => 'queue.name.on.rabbit',
                'auto_create' => true,
                'bind' => [['exchange' => 'foo', 'routing_key' => '*']]
            ]
        );
        $queue->publish('a');
    }

    public function testProcessorCallback()
    {
        $amqpConnection = $this->getMockBuilder(AMQPConnection::class)
            ->disableOriginalConstructor()
            ->getMock();

        $processorMock = $this->getMockBuilder(MessageProcessorInterface::class)
            ->getMock();

        $loggerMock = $this->getMockBuilder(LoggerInterface::class)
            ->getMock();

        $queue = QueueEntity::createQueue(
            $amqpConnection,
            'foo',
            [
                'name' => 'queue.name.on.rabbit',
                'auto_create' => true,
                'bind' => [['exchange' => 'foo', 'routing_key' => '*']]
            ]
        );
        $queue->setLogger($loggerMock);

        $class = new \ReflectionClass(get_class($queue));
        $property = $class->getProperty('messageProcessor');
        $property->setAccessible(true);
        $property->setValue($queue, $processorMock);

        $processorMock->expects($this->once())
            ->method('consume')
            ->willReturn(null);

        $queue->consume(new AMQPMessage('FooBar'));
    }

    public function testPublishRetry()
    {
        $amqpConnection = $this->getMockBuilder(AMQPConnection::class)
            ->disableOriginalConstructor()
            ->getMock();

        $channelMock = $this->getMockBuilder(AMQPChannel::class)
            ->disableOriginalConstructor()
            ->getMock();

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

        $queue = QueueEntity::createQueue(
            $amqpConnection,
            'foo',
            [
                'name' => 'queue.name.on.rabbit'
            ]
        );
        $queue->publish('a');
        $this->assertEquals(1, $retries);
    }

    public function testPublishMaxRetry()
    {
        $amqpConnection = $this->getMockBuilder(AMQPConnection::class)
            ->disableOriginalConstructor()
            ->getMock();

        $channelMock = $this->getMockBuilder(AMQPChannel::class)
            ->disableOriginalConstructor()
            ->getMock();

        $amqpConnection->expects($this->atLeastOnce())
            ->method('getChannel')
            ->willReturn($channelMock);

        $amqpConnection->expects($this->atLeastOnce())
            ->method('reconnect')
            ->willReturn($channelMock);

        $channelMock->expects($this->exactly(3))
            ->method('basic_publish')
            ->will($this->throwException(new AMQPChannelClosedException("Channel is Closed")));

        $queue = QueueEntity::createQueue(
            $amqpConnection,
            'foo',
            [
                'name' => 'queue.name.on.rabbit'
            ]
        );
        $this->expectException(AMQPChannelClosedException::class);
        $queue->publish('a');
    }
}
