<?php
namespace NeedleProject\LaravelRabbitMq\Processor;

use PhpAmqpLib\Message\AMQPMessage;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;

/**
 * Class AbstractMessageProcessor
 *
 * @package NeedleProject\LaravelRabbitMq\Processor
 * @author  Adrian tilita <adrian@tilita.ro>
 */
abstract class AbstractMessageProcessor implements MessageProcessorInterface, LoggerAwareInterface
{
    use LoggerAwareTrait;

    /**
     * @var int
     */
    private $messageCount = 0;

    /**
     * {@inheritdoc}
     * @param AMQPMessage $message
     */
    public function consume(AMQPMessage $message)
    {
        $this->messageCount++;
        try {
            $response = $this->processMessage($message);
            if ($response === true) {
                $this->logger->debug(sprintf("Processed with success message %s", $message->getBody()));
                $message->delivery_info['channel']->basic_ack($message->delivery_info['delivery_tag']);
            } else {
                $this->logger->debug(sprintf("Did not processed with success message %s", $message->getBody()));
                $message->delivery_info['channel']->basic_nack($message->delivery_info['delivery_tag'], false, true);
            }
        } catch (\Exception $e) {
            $this->logger->notice(
                sprintf(
                    "Could not process message, got %s from %s in %d for message: %s",
                    $e->getMessage(),
                    (string)$e->getFile(),
                    (int)$e->getLine(),
                    (string)$message->getBody()
                )
            );
            $message->delivery_info['channel']->basic_nack($message->delivery_info['delivery_tag'], false, true);
        }
    }

    /**
     * @return int
     */
    public function getProcessedMessages(): int
    {
        return $this->messageCount;
    }

    /**
     * @param AMQPMessage $message
     * @return bool
     */
    abstract public function processMessage(AMQPMessage $message): bool;
}
