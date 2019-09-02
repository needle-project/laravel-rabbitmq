<?php
namespace NeedleProject\LaravelRabbitMq\Entity;

use NeedleProject\LaravelRabbitMq\AMQPConnection;
use NeedleProject\LaravelRabbitMq\PublisherInterface;
use PhpAmqpLib\Channel\AMQPChannel;
use PhpAmqpLib\Exception\AMQPChannelClosedException;
use PhpAmqpLib\Exception\AMQPProtocolChannelException;
use PhpAmqpLib\Message\AMQPMessage;

/**
 * Class ExchangeEntity
 *
 * @package NeedleProject\LaravelRabbitMq\Entity
 * @author  Adrian Tilita <adrian@tilita.ro>
 */
class ExchangeEntity implements PublisherInterface, AMQPEntityInterface
{
    /**
     * @const int   Retry count when a Channel Closed exeption is thrown
     */
    const MAX_RETRIES = 3;

    /**
     * @const array Default connections parameters
     */
    const DEFAULTS = [
        'exchange_type'                => 'topic',
        // Whether to check if it exists or to verify existance using argument types (Throws PRECONDITION_FAILED)
        'passive'                      => false,
        // Entities with durable will be re-created uppon server restart
        'durable'                      => false,
        // Whether to delete it when no queues ar bind to it
        'auto_delete'                  => false,
        // Whether the exchange can be used by a publisher or block it (declared just for internal "wiring")
        'internal'                     => false,
        // Whether to receive a Declare confirmation
        'nowait'                       => false,
        // Whether to auto create the entity before publishing/consuming it
        'auto_create'                  => false,
        // whether to "hide" the exception on re-declare.
        // if the `passive` filter is set true, this is redundant
        'throw_exception_on_redeclare' => true,
        // whether to throw on exception when trying to
        // bind to an in-existent queue/exchange
        'throw_exception_on_bind_fail' => true,
    ];

    /**
     * @var AMQPConnection
     */
    protected $connection;

    /**
     * @var string
     */
    protected $aliasName;

    /**
     * @var array
     */
    protected $attributes;

    /**
     * @var int 
     */
    protected $retryCount = 0;

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
        return new static(
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
        try {
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
        } catch (AMQPProtocolChannelException $e) {
            // 406 is a soft error triggered for precondition failure (when redeclaring with different parameters)
            if (true === $this->attributes['throw_exception_on_redeclare'] || $e->amqp_reply_code !== 406) {
                throw $e;
            }
            // a failure trigger channels closing process
            $this->getConnection()->reconnect();
        }
    }

    /**
     * @throws AMQPProtocolChannelException
     */
    public function bind()
    {
        if (!isset($this->attributes['bind']) || empty($this->attributes['bind'])) {
            return;
        }
        foreach ($this->attributes['bind'] as $bindItem) {
            try {
                $this->getChannel()
                    ->queue_bind(
                        $bindItem['queue'],
                        $this->attributes['name'],
                        $bindItem['routing_key']
                    );
            } catch (AMQPProtocolChannelException $e) {
                // 404 is the code for trying to bind to an non-existing entity
                if (true === $this->attributes['throw_exception_on_bind_fail'] || $e->amqp_reply_code !== 404) {
                    throw $e;
                }
                $this->getConnection()->reconnect();
            }
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
     * {@inheritdoc}
     */
    public function reconnect()
    {
        $this->getConnection()->reconnect();
    }

    /**
     * Publish a message
     *
     * @param string $message
     * @param string $routingKey
     * @return mixed|void
     * @throws AMQPProtocolChannelException
     */
    public function publish(string $message, string $routingKey = '')
    {
        if ($this->attributes['auto_create'] === true) {
            $this->create();
            $this->bind();
        }
        try {
            $this->getChannel()->basic_publish(
                new AMQPMessage($message),
                $this->attributes['name'],
                $routingKey,
                true
            );
            $this->retryCount = 0;
        } catch (AMQPChannelClosedException $exception) {
            $this->retryCount++;
            // Retry publishing with re-connect
            if ($this->retryCount < self::MAX_RETRIES) {
                $this->getConnection()->reconnect();
                $this->publish($message, $routingKey);
                return;
            }
            throw $exception;
        }
    }
}
