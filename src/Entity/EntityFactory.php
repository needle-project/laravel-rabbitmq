<?php
namespace NeedleProject\LaravelRabbitMq\Entity;

use NeedleProject\LaravelRabbitMq\Connection\AMQPConnection;
use NeedleProject\LaravelRabbitMq\Exception\InvalidArgumentException;

/**
 * Class EntityFactory
 *
 * @package NeedleProject\LaravelRabbitMq\Entity
 * @author  Adrian Tilita <adrian@tilita.ro>
 */
class EntityFactory
{
    /**
     * @param AMQPConnection $connection
     * @param string $name
     * @param string $entityType
     * @param array $entityAttributes
     * @return ExchangeEntity|QueueEntity
     */
    public static function createEntity(
        AMQPConnection $connection,
        string $name,
        string $entityType,
        array $entityAttributes = []
    ) {
        switch ($entityType) {
            case 'exchange':
                return new ExchangeEntity($connection, $name, $entityAttributes);
                break;
            case 'queue':
                return new QueueEntity($connection, $name, $entityAttributes);
                break;
        }
        throw new InvalidArgumentException(
            sprintf("Unknown entity type %s", (string)$entityType)
        );
    }
}
