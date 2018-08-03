<?php
namespace NeedleProject\LaravelRabbitMq;

/**
 * Interface ConsumerInterface
 *
 * @package NeedleProject\LaravelRabbitMq\Consumer
 * @author  Adrian Tilita <adrian@tilita.ro>
 */
interface ConsumerInterface
{
    /**
     * Consume messages
     *
     * @param int $messages The number of message
     * @param int $seconds  The amount of time a consumer should listen for messages
     * @param int $maxMemory    The amount of memory when a consumer should stop consuming
     * @return mixed
     */
    public function startConsuming(int $messages, int $seconds, int $maxMemory);

    /**
     * Stop the consumer
     */
    public function stopConsuming();
}
