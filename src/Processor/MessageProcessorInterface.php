<?php
namespace NeedleProject\LaravelRabbitMq\Processor;

use PhpAmqpLib\Message\AMQPMessage;

/**
 * Interface MessageProcessorInterface
 *
 * @package NeedleProject\LaravelRabbitMq\Processor
 * @author  Adrian Tilita <adrian@tilita.ro>
 */
interface MessageProcessorInterface
{
    /**
     * @param AMQPMessage $message
     * @return mixed
     */
    public function consume(AMQPMessage $message);

    /**
     * @return int
     */
    public function getProcessedMessages(): int;
}
