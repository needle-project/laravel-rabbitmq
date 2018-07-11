<?php
namespace NeedleProject\LaravelRabbitMq\Entity;

use NeedleProject\LaravelRabbitMq\Connection\AMQPConnection;
use NeedleProject\LaravelRabbitMq\ConsumerInterface;
use NeedleProject\LaravelRabbitMq\Processor\MessageProcessorInterface;
use NeedleProject\LaravelRabbitMq\PublisherInterface;
use PhpAmqpLib\Channel\AMQPChannel;
use PhpAmqpLib\Exception\AMQPTimeoutException;
use PhpAmqpLib\Message\AMQPMessage;

/**
 * Class QueueEntity
 *
 * @package NeedleProject\LaravelRabbitMq\Entity
 * @author  Adrian Tilita <adrian@tilita.ro>
 */
class QueueEntity implements PublisherInterface, ConsumerInterface
{
    /**
     * @const array Default connections parameters
     */
    const DEFAULTS = [
        'passive'   => false,
        'durable'   => false,
        'exclusive' => false,
        'auto_delete' => false,
        'internal'  => false,
        'nowait'    => false,
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
     * @var int
     */
    private $prefetchCount = 1;

    /**
     * @var null|MessageProcessorInterface
     */
    private $messageProcessor = null;

    /**
     * @var int
     */
    private $limitMessageCount;

    /**
     * @var int
     */
    private $limitSecondsUptime;

    /**
     * @var int
     */
    private $limitMemoryConsumption;

    /**
     * @var int
     */
    private $startTime = 0;

    /**
     * @param AMQPConnection $connection
     * @param string $aliasName
     * @param array $exchangeDetails
     * @return QueueEntity
     */
    public static function createQueue(AMQPConnection $connection, string $aliasName, array $exchangeDetails)
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
     * @param int $prefetchCount
     * @return ConsumerInterface
     */
    public function setPrefetchCount(int $prefetchCount): ConsumerInterface
    {
        $this->prefetchCount = $prefetchCount;
        return $this;
    }

    /**
     * @param string $messageProcessor
     * @return ConsumerInterface
     */
    public function setMessageProcessor(string $messageProcessor): ConsumerInterface
    {
        $this->messageProcessor = $messageProcessor;
        return $this;
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
            ->queue_declare(
                $this->attributes['name'],
                $this->attributes['passive'],
                $this->attributes['durable'],
                $this->attributes['exclusive'],
                $this->attributes['auto_delete'],
                $this->attributes['internal'],
                $this->attributes['nowait']
            );
    }

    public function bind()
    {
        if (isset($this->attributes['bind'])) {
            foreach ($this->attributes['bind'] as $bindItem) {
                $this->getChannel()
                    ->queue_bind(
                        $this->attributes['name'],
                        $bindItem['exchange'],
                        $bindItem['routing_key']
                    );
            }
        }
    }

    /**
     * Delete the queue
     */
    public function delete()
    {
        $this->getChannel()->queue_delete($this->attributes['name']);
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
        $this->getChannel()
            ->basic_publish(
                new AMQPMessage($message),
                '',
                $this->attributes['name'],
                true
            );
    }

    /**
     * {@inheritdoc}
     *
     * @param int $messages
     * @param int $seconds
     * @param int $maxMemory
     * @return int
     */
    public function startConsuming(int $messages, int $seconds, int $maxMemory)
    {
        $this->setupConsumer($messages, $seconds, $maxMemory);
        while (false === $this->shouldStopConsuming()) {
            try {
                $this->getChannel()->wait(null, false, $seconds);
            } catch (AMQPTimeoutException $e) {
                usleep(1000);
                $this->getConnection()->reconnect();
            } catch (\Throwable $e) {
                return 1;
            }
        }
        return 0;
    }

    /**
     * @return bool
     */
    protected function shouldStopConsuming(): bool
    {
        if ((microtime(true) - $this->startTime) > $this->limitSecondsUptime) {
            $this->log("Stopped! Timeout reached");
            return true;
        }
        // memory_get_peak_usage(true) >= ($this->limitMemoryConsumption / 1048576)

        if (memory_get_peak_usage(true) >= ($this->limitMemoryConsumption * 1048576)) {
            $this->log(
                sprintf(
                    "Stopped! Memory consumption reached %d MB",
                    (int)round(memory_get_peak_usage(true) / 1048576, 2)
                )
            );
            return true;
        }

        if ($this->getMessageProcessor()->getProcessedMessages() >= $this->limitMessageCount) {
            $this->log(
                sprintf(
                    "Stopped! %d total messages consumed!",
                    (int)$this->getMessageProcessor()->getProcessedMessages()
                )
            );
            return true;
        }
        return false;
    }

    /**
     * Setup the consumer
     *
     * @param int $messages
     * @param int $seconds
     * @param int $maxMemory
     */
    protected function setupConsumer(int $messages, int $seconds, int $maxMemory)
    {
        $this->limitMessageCount = $messages;
        $this->limitSecondsUptime = $seconds;
        $this->limitMemoryConsumption = $maxMemory;

        $this->startTime = microtime(true);

        $this->getChannel()
            ->basic_qos(null, $this->prefetchCount, true);

        $this->getChannel()
            ->basic_consume(
                $this->attributes['name'],
                sprintf("%s_%s_%s", $this->aliasName, gethostname(), getmypid()),
                false,
                false,
                false,
                false,
                [
                    $this,
                    'consume'
                ]
            );
    }

    /**
     * @return MessageProcessorInterface
     */
    private function getMessageProcessor(): MessageProcessorInterface
    {
        if (!($this->messageProcessor instanceof MessageProcessorInterface)) {
            $this->messageProcessor = app($this->messageProcessor);
        }
        return $this->messageProcessor;
    }

    /**
     * @param AMQPMessage $message
     */
    public function consume(AMQPMessage $message)
    {
        $this->log("Received: " . $message->getBody());
        $this->getMessageProcessor()->consume($message);
    }

    /**
     * @param string $message
     */
    private function log(string $message)
    {
        // do nothing at the moment
    }
}
