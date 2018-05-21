<?php
namespace NeedleProject\LaravelRabbitMq\Processor;

use PhpAmqpLib\Message\AMQPMessage;

abstract class AbstractMessageProcessor
{
    public function consume(AMQPMessage $message)
    {
        $this->processMessage($message);
    }

    abstract public function processMessage(AMQPMessage $message): bool;
}
