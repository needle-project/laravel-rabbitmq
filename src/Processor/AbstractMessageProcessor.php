<?php
namespace NeedleProject\LaravelRabbitMq\Processor;

use PhpAmqpLib\Message\AMQPMessage;

abstract class AbstractMessageProcessor
{
    private $messageCount = 0;

    public function consume(AMQPMessage $message)
    {
        $this->messageCount++;
        try {
            $response = $this->processMessage($message);
            if ($response === true) {
                $message->delivery_info['channel']->basic_ack($message->delivery_info['delivery_tag']);
            } else {
                throw new \RuntimeException('Dummy');
            }
        } catch (\Exception $e) {
            $message->delivery_info['channel']->basic_nack($message->delivery_info['delivery_tag']);
        }
    }

    public function getProcessedMessages(): int
    {
        return $this->messageCount;
    }

    abstract public function processMessage(AMQPMessage $message): bool;
}
