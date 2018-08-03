<?php
namespace NeedleProject\LaravelRabbitMq;

/**
 * Interface PublisherInterface
 *
 * @package NeedleProject\LaravelRabbitMq\Publisher
 * @author  Adrian Tilita <adrian@tilita.ro>
 */
interface PublisherInterface
{
    /**
     * Publish a new message
     *
     * @param string $message
     * @param string $routingKey
     * @return mixed
     */
    public function publish(string $message, string $routingKey = '');
}
