<?php
namespace NeedleProject\LaravelRabbitMq\Publisher;

/**
 * Interface PublisherInterface
 *
 * @package NeedleProject\LaravelRabbitMq\Publisher
 * @author  Adrian Tilita <adrian@tilita.ro>
 */
interface PublisherInterface
{
    /**
     * Switch the publisher in transaction mode
     * @return mixed
     */
    public function startTransaction();

    /**
     * Commit transaction
     * @return mixed
     */
    public function commit();

    /**
     * RollBack a transaction
     */
    public function rollBack();

    /**
     * Publish a new message
     *
     * @param string $message
     * @param string $routingKey
     * @return mixed
     */
    public function publish(string $message, string $routingKey = '');
}
