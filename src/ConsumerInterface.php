<?php
namespace NeedleProject\LaravelRabbitMq\Consumer;

/**
 * Interface ConsumerInterface
 *
 * @package NeedleProject\LaravelRabbitMq\Consumer
 * @author  Adrian Tilita <adrian@tilita.ro>
 */
interface ConsumerInterface
{
    public function startConsuming();
}
