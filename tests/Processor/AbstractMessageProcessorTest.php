<?php
namespace NeedleProject\LaravelRabbitMq\Processor;

use PhpAmqpLib\Channel\AMQPChannel;
use PhpAmqpLib\Message\AMQPMessage;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

class AbstractMessageProcessorTest extends TestCase
{
    public function testSuccesfullyAck()
    {
        $messageProcessor = new class extends AbstractMessageProcessor {
            public function processMessage(AMQPMessage $message): bool
            {
                return true;
            }
        };
        $loggerMock = $this->createMock(LoggerInterface::class);
        $messageProcessor->setLogger($loggerMock);

        $channelMock = $this->createMock(AMQPChannel::class);
        $channelMock->expects($this->once())
            ->method('basic_ack')
            ->with('foo')
            ->willReturn(null);

        $amqpMessage = $this->createMock(AMQPMessage::class);
        $amqpMessage->delivery_info['channel'] = $channelMock;
        $amqpMessage->delivery_info['delivery_tag'] = 'foo';

        $messageProcessor->consume($amqpMessage);
    }

    public function testNackFromException()
    {
        $messageProcessor = new class extends AbstractMessageProcessor {
            public function processMessage(AMQPMessage $message): bool
            {
                throw new \Exception('foo');
            }
        };
        $loggerMock = $this->createMock(LoggerInterface::class);
        $messageProcessor->setLogger($loggerMock);

        $channelMock = $this->createMock(AMQPChannel::class);
        $channelMock->expects($this->once())
            ->method('basic_nack')
            ->with('foo')
            ->willReturn(null);

        $amqpMessage = $this->createMock(AMQPMessage::class);
        $amqpMessage->delivery_info['channel'] = $channelMock;
        $amqpMessage->delivery_info['delivery_tag'] = 'foo';

        $messageProcessor->consume($amqpMessage);
    }

    public function testControlledNack()
    {
        $messageProcessor = new class extends AbstractMessageProcessor {
            public function processMessage(AMQPMessage $message): bool
            {
                return false;
            }
        };
        $loggerMock = $this->createMock(LoggerInterface::class);
        $messageProcessor->setLogger($loggerMock);

        $channelMock = $this->createMock(AMQPChannel::class);
        $channelMock->expects($this->once())
            ->method('basic_nack')
            ->with('foo')
            ->willReturn(null);

        $amqpMessage = $this->createMock(AMQPMessage::class);
        $amqpMessage->delivery_info['channel'] = $channelMock;
        $amqpMessage->delivery_info['delivery_tag'] = 'foo';

        $messageProcessor->consume($amqpMessage);
    }

    public function testMessageCounter()
    {
        $messageProcessor = new class extends AbstractMessageProcessor {
            public function processMessage(AMQPMessage $message): bool
            {
                return true;
            }
        };
        $loggerMock = $this->createMock(LoggerInterface::class);
        $messageProcessor->setLogger($loggerMock);

        $channelMock = $this->createMock(AMQPChannel::class);
        $amqpMessage = $this->createMock(AMQPMessage::class);
        $amqpMessage->delivery_info['channel'] = $channelMock;
        $amqpMessage->delivery_info['delivery_tag'] = 'foo';

        $messageProcessor->consume($amqpMessage);
        $messageProcessor->consume($amqpMessage);
        $messageProcessor->consume($amqpMessage);

        $this->assertEquals(3, $messageProcessor->getProcessedMessages());
    }

    public function testErrorAck()
    {
        $messageProcessor = new class extends AbstractMessageProcessor {
            public function processMessage(AMQPMessage $message): bool
            {
                return false;
            }
        };
        $loggerMock = $this->createMock(LoggerInterface::class);
        $messageProcessor->setLogger($loggerMock);

        $channelMock = $this->createMock(AMQPChannel::class);
        $amqpMessage = $this->createMock(AMQPMessage::class);
        $amqpMessage->delivery_info['channel'] = $channelMock;

        $loggerMock->expects($this->once())
            ->method('error');

        $messageProcessor->consume($amqpMessage);
    }


    public function testErrorNack()
    {
        $messageProcessor = new class extends AbstractMessageProcessor {
            public function processMessage(AMQPMessage $message): bool
            {
                // trigger nack
                return false;
            }
        };
        $loggerMock = $this->createMock(LoggerInterface::class);
        $messageProcessor->setLogger($loggerMock);
        $channelMock = $this->createMock(AMQPChannel::class);
        $channelMock->expects($this->atLeastOnce())
            ->method('basic_nack')
            ->will($this->throwException(new \RuntimeException('FooBar')));

        $amqpMessage = $this->createMock(AMQPMessage::class);
        $amqpMessage->delivery_info['channel'] = $channelMock;
        $amqpMessage->delivery_info['delivery_tag'] = 1;
        $amqpMessage->expects($this->atLeastOnce())
            ->method('getBody')
            ->willReturn('foo');

        $loggerMock->expects($this->atLeastOnce())
            ->method('debug')
            ->with('Did not processed with success message foo');
        $messageProcessor->consume($amqpMessage);
    }
}
