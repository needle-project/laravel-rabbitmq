<?php
namespace NeedleProject\LaravelRabbitMq\Entity;

use NeedleProject\LaravelRabbitMq\Connection\AMQPConnection;
use NeedleProject\LaravelRabbitMq\PublisherInterface;
use PhpAmqpLib\Channel\AMQPChannel;
use PhpAmqpLib\Message\AMQPMessage;

/**
 * Class ExchangeEntity
 *
 * @package NeedleProject\LaravelRabbitMq\Entity
 * @author  Adrian Tilita <adrian@tilita.ro>
 */
class ExchangeEntity implements PublisherInterface
{
    /**
     * @const array Default connections parameters
     */
    const DEFAULTS = [
        'exchange_type' => 'topic',
        'passive'       => false,
        'durable'       => false,
        'auto_delete'   => false,
        'internal'      => false,
        'nowait'        => false
    ];

    /**
     * @var AMQPConnection
     */
    private $connection;

    /**
     * @var string
     */
    private $aliasName;

    /**
     * @var array
     */
    private $attributes;

    /**
     * ExchangeEntity constructor.
     *
     * @param AMQPConnection $connection
     * @param string $aliasName
     * @param array $attributes
     */
    public function __construct(AMQPConnection $connection, string $aliasName, array $attributes = [])
    {
        $this->connection = $connection;
        $this->aliasName  = $aliasName;
        $this->attributes = $attributes;
    }

    /**
     * @param AMQPConnection $connection
     * @param string $aliasName
     * @param array $exchangeDetails
     * @return ExchangeEntity
     */
    public static function createExchange(AMQPConnection $connection, string $aliasName, array $exchangeDetails)
    {
        return new self(
            $connection,
            $aliasName,
            array_merge(self::DEFAULTS, $exchangeDetails)
        );
    }

    /**
     * @return string
     */
    public function getAliasName(): string
    {
        return $this->aliasName;
    }

    /**
     * @return AMQPConnection
     */
    protected function getConnection(): AMQPConnection
    {
        return $this->connection;
    }

    /**
     * @return AMQPChannel
     */
    protected function getChannel(): AMQPChannel
    {
        return $this->getConnection()->getChannel();
    }

    /**
     * Create the Queue
     */
    public function create()
    {
        $this->getChannel()
            ->exchange_declare(
                $this->attributes['name'],
                $this->attributes['exchange_type'],
                $this->attributes['passive'],
                $this->attributes['durable'],
                $this->attributes['auto_delete'],
                $this->attributes['internal'],
                $this->attributes['nowait']
            );
    }

    public function bind()
    {
        if (!isset($this->attributes['bind'])) {
            return;
        }
        foreach ($this->attributes['bind'] as $bindItem) {
            $this->getChannel()
                ->queue_bind(
                    $bindItem['queue'],
                    $this->attributes['name'],
                    $bindItem['routing_key']
                );
        }
    }

    /**
     * Delete the queue
     */
    public function delete()
    {
        $this->getChannel()->exchange_delete($this->attributes['name']);
    }

    /**
     * Publish a message
     *
     * @param string $message
     * @param string $routingKey
     * @return void
     */
    public function publish(string $message, string $routingKey = '')
    {
        $this->getChannel()->basic_publish(
            new AMQPMessage($message),
            $this->attributes['name'],
            $routingKey,
            true
        );
    }
}
