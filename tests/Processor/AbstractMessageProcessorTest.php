<?php
namespace NeedleProject\LaravelRabbitMq\Processor;

use PhpAmqpLib\Channel\AMQPChannel;
use PhpAmqpLib\Message\AMQPMessage;
use PHPUnit\Framework\TestCase;

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

        $channelMock = $this->getMockBuilder(AMQPChannel::class)
            ->disableOriginalConstructor()
            ->getMock();
        $channelMock->expects($this->once())
            ->method('basic_ack')
            ->with('foo')
            ->willReturn(null);

        $amqpMessage = $this->getMockBuilder(AMQPMessage::class)
            ->disableOriginalConstructor()
            ->getMock();
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

        $channelMock = $this->getMockBuilder(AMQPChannel::class)
            ->disableOriginalConstructor()
            ->getMock();
        $channelMock->expects($this->once())
            ->method('basic_nack')
            ->with('foo')
            ->willReturn(null);

        $amqpMessage = $this->getMockBuilder(AMQPMessage::class)
            ->disableOriginalConstructor()
            ->getMock();
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

        $channelMock = $this->getMockBuilder(AMQPChannel::class)
            ->disableOriginalConstructor()
            ->getMock();
        $channelMock->expects($this->once())
            ->method('basic_nack')
            ->with('foo')
            ->willReturn(null);

        $amqpMessage = $this->getMockBuilder(AMQPMessage::class)
            ->disableOriginalConstructor()
            ->getMock();
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

        $channelMock = $this->getMockBuilder(AMQPChannel::class)
            ->disableOriginalConstructor()
            ->getMock();

        $amqpMessage = $this->getMockBuilder(AMQPMessage::class)
            ->disableOriginalConstructor()
            ->getMock();
        $amqpMessage->delivery_info['channel'] = $channelMock;
        $amqpMessage->delivery_info['delivery_tag'] = 'foo';

        $messageProcessor->consume($amqpMessage);
        $messageProcessor->consume($amqpMessage);
        $messageProcessor->consume($amqpMessage);

        $this->assertEquals(3, $messageProcessor->getProcessedMessages());
    }
}
