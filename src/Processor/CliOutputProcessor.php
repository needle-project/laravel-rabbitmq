<?php
namespace NeedleProject\LaravelRabbitMq\Processor;

use PhpAmqpLib\Message\AMQPMessage;

/**
 * Class CliOutputProcessor
 *
 * @package NeedleProject\LaravelRabbitMq\Processor
 * @author  Adrian Tilita <adrian@tilita.ro>
 * @codeCoverageIgnore
 */
class CliOutputProcessor extends AbstractMessageProcessor
{
    /**
     * @param AMQPMessage $message
     * @return bool
     */
    public function processMessage(AMQPMessage $message): bool
    {
        echo $message->getBody() . "\n";
        return true;
    }
}
