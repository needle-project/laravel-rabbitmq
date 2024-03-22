<?php

namespace NeedleProject\LaravelRabbitMq\Interpreter;

use PhpAmqpLib\Wire\AMQPTable;

class EntityArgumentsInterpreter
{
    /**
     * For a accurate usage, please reffer to https://www.rabbitmq.com/queues.html#optional-arguments
     *
     * @param AMQPTable|array $entityArguments
     * @return AMQPTable
     */
    public static function interpretArguments($entityArguments): AMQPTable
    {
        if ($entityArguments instanceof AMQPTable) {
            return $entityArguments;
        }
        return new AMQPTable($entityArguments);
    }

    public static function interpretProperties(array $attributes, array $properties): array
    {
        if (isset($attributes['durable']) && $attributes['durable'] === true) {
            $properties['delivery_mode'] = 2;
        }
        return $properties;
    }
}
