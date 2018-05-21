<?php
namespace NeedleProject\LaravelRabbitMq\Entity;

use NeedleProject\LaravelRabbitMq\Connection\AMQPConnection;

abstract class AbstractAMQPEntity
{
    /**
     * @var array
     */
    protected $attributes = [];

    /**
     * @var AMQPConnection|null
     */
    private $connection = null;

    /**
     * @var string|null
     */
    private $name = null;

    /**
     * QueueEntity constructor.
     *
     * @param AMQPConnection $connection
     * @param string $name
     * @param array $attributes
     */
    public function __construct(AMQPConnection $connection, string $name, array $attributes)
    {
        $this->connection = $connection;
        $this->name       = $name;
        $this->attributes = array_merge(
            $this->attributes,
            $attributes
        );
    }

    /**
     * Get default entity attributes if not all are defined
     * @return array
     */
    abstract protected function getDefaultAttributes(): array;

    /**
     * Create the Queue
     */
    abstract public function create();

    /**
     * Delete the entity
     */
    abstract public function delete();

    /**
     * @return AMQPConnection
     */
    public function getConnection(): AMQPConnection
    {
        return $this->connection;
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }
}
