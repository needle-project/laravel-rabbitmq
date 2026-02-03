<?php

namespace NeedleProject\LaravelRabbitMq\Entity;

// Helper function for guaranteed console output (global namespace)
if (!function_exists('rmq_log')) {
    function rmq_log(string $message) {
        // Use fwrite to stderr for guaranteed console output
        // In tests, STDERR might not be available, so we check first
        if (defined('STDERR') && is_resource(STDERR)) {
            @fwrite(STDERR, $message . PHP_EOL);
        } elseif (function_exists('error_log')) {
            // Fallback to error_log if STDERR is not available (e.g., in tests)
            @error_log($message);
        }
    }
}

use NeedleProject\LaravelRabbitMq\AMQPConnection;
use NeedleProject\LaravelRabbitMq\ConsumerInterface;
use NeedleProject\LaravelRabbitMq\Interpreter\EntityArgumentsInterpreter;
use NeedleProject\LaravelRabbitMq\Processor\AbstractMessageProcessor;
use NeedleProject\LaravelRabbitMq\Processor\MessageProcessorInterface;
use NeedleProject\LaravelRabbitMq\PublisherInterface;
use PhpAmqpLib\Channel\AMQPChannel;
use PhpAmqpLib\Exception\AMQPProtocolChannelException;
use PhpAmqpLib\Exception\AMQPTimeoutException;
use PhpAmqpLib\Message\AMQPMessage;
use PhpAmqpLib\Wire\AMQPTable;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use PhpAmqpLib\Exception\AMQPChannelClosedException;

/**
 * Class QueueEntity
 *
 * @package NeedleProject\LaravelRabbitMq\Entity
 * @author  Adrian Tilita <adrian@tilita.ro>
 */
class QueueEntity implements PublisherInterface, ConsumerInterface, AMQPEntityInterface, LoggerAwareInterface
{
    use LoggerAwareTrait;

    /**
     * @const int   Retry count when a Channel Closed exeption is thrown
     */
    const MAX_RETRIES = 3;

    /**
     * @const array Default connections parameters
     */
    const DEFAULTS = [
        // Whether to check if it exists or to verify existance using argument types (Throws PRECONDITION_FAILED)
        'passive'                      => false,
        // Entities with durable will be re-created uppon server restart
        'durable'                      => false,
        // whether to use it by only one channel, then it gets deleted
        'exclusive'                    => false,
        // Whether to delete it when the queue has no event on it
        'auto_delete'                  => false,
        // Whether to receive a Declare confirmation
        'nowait'                       => false,
        // Additional arguments for queue creation
        'arguments'                    => [],
        // Whether to auto create the entity before publishing/consuming it
        'auto_create'                  => false,
        // whether to "hide" the exception on re-declare.
        // if the `passive` filter is set true, this is redundant
        'throw_exception_on_redeclare' => true,
        // whether to throw on exception when trying to
        // bind to an in-existent queue/exchange
        'throw_exception_on_bind_fail' => true,
        // no ideea what it represents - @todo - find a documentation that states it's role
        'ticket'                       => null
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
    protected $prefetchCount = 1;

    /**
     * @var null|string|MessageProcessorInterface
     */
    protected $messageProcessor = null;

    /**
     * @var int
     */
    protected $limitMessageCount;

    /**
     * @var int
     */
    protected $limitSecondsUptime;

    /**
     * @var int
     */
    protected $limitMemoryConsumption;

    /**
     * @var double
     */
    protected $startTime = 0;

    /**
     * @var int Memory usage at start (for calculating memory growth)
     */
    protected $startMemory = 0;

    /**
     * @var int
     */
    protected $retryCount = 0;
    /**
     * @var bool
     */
    protected $globalPrefetch = true;

    /**
     * @var float Timeout in seconds between retry attempts (default: 0.001 = 1ms)
     */
    protected $retryTimeout = 0.001;

    /**
     * @param AMQPConnection $connection
     * @param string $aliasName
     * @param array $queueDetails
     * @return QueueEntity
     */
    public static function createQueue(AMQPConnection $connection, string $aliasName, array $queueDetails)
    {
        return new static(
            $connection,
            $aliasName,
            array_merge(self::DEFAULTS, $queueDetails)
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
     * @param bool $globalPrefetch
     * @return ConsumerInterface
     */
    public function setGlobalPrefetch(bool $globalPrefetch): ConsumerInterface
    {
        $this->globalPrefetch = $globalPrefetch;

        return $this;
    }

    /**
     * Set timeout between retry attempts in seconds
     *
     * @param float $timeout Timeout in seconds (1.0 = 1 second, 0.1 = 100ms)
     * @return ConsumerInterface
     */
    public function setRetryTimeout(float $timeout): ConsumerInterface
    {
        $this->retryTimeout = $timeout;
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
        try {
            $this->getChannel()
                ->queue_declare(
                    $this->attributes['name'],
                    $this->attributes['passive'],
                    $this->attributes['durable'],
                    $this->attributes['exclusive'],
                    $this->attributes['auto_delete'],
                    $this->attributes['nowait'],
                    EntityArgumentsInterpreter::interpretArguments(
                        $this->attributes['arguments']
                    ),
                    $this->attributes['ticket']
                );
        } catch (AMQPProtocolChannelException $e) {
            // 406 is a soft error triggered for precondition failure (when redeclaring with different parameters)
            if (true === $this->attributes['throw_exception_on_redeclare'] || $e->amqp_reply_code !== 406) {
                throw $e;
            }
            // a failure trigger channels closing process
            $this->reconnect();
        }
    }

    public function bind()
    {
        if (!isset($this->attributes['bind']) || empty($this->attributes['bind'])) {
            return;
        }
        foreach ($this->attributes['bind'] as $bindItem) {
            try {
                $this->getChannel()
                    ->queue_bind(
                        $this->attributes['name'],
                        $bindItem['exchange'],
                        $bindItem['routing_key'] ?? ''
                    );
            } catch (AMQPProtocolChannelException $e) {
                // 404 is the code for trying to bind to an non-existing entity
                if (true === $this->attributes['throw_exception_on_bind_fail'] || $e->amqp_reply_code !== 404) {
                    throw $e;
                }
                $this->reconnect();
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
     * @param array $properties
     * @return mixed|void
     * @throws AMQPProtocolChannelException
     */
    public function publish(string $message, string $routingKey = '', array $properties = [])
    {
        if ($this->attributes['auto_create'] === true) {
            $this->create();
            $this->bind();
        }

        try {
            $this->getChannel()
                ->basic_publish(
                    new AMQPMessage(
                        $message,
                        EntityArgumentsInterpreter::interpretProperties(
                            $this->attributes,
                            $properties
                        )
                    ),
                    '',
                    $this->attributes['name'],
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
        rmq_log(sprintf(
            "[RMQ] Starting consumer: queue=%s, messages=%s, time=%s, memory=%sMB",
            $this->attributes['name'],
            $messages < 0 ? 'unlimited' : $messages,
            $seconds < 0 ? 'unlimited' : $seconds . 's',
            $maxMemory
        ));
        
        if ($this->logger) {
            $this->logger->info("startConsuming called", [
                'queue' => $this->attributes['name'],
                'messages' => $messages,
                'seconds' => $seconds,
                'maxMemory' => $maxMemory
            ]);
        }
        
        $this->setupConsumer($messages, $seconds, $maxMemory);
        
        if ($this->logger) {
            $this->logger->info("setupConsumer completed, entering main loop", [
                'queue' => $this->attributes['name'],
                'limitMessageCount' => $this->limitMessageCount,
                'limitSecondsUptime' => $this->limitSecondsUptime,
                'limitMemoryConsumption' => $this->limitMemoryConsumption
            ]);
        }
        
        // Check shouldStopConsuming before entering loop
        $shouldStop = $this->shouldStopConsuming();
        
        if ($shouldStop) {
            rmq_log(sprintf("[RMQ] Consumer stopped before entering loop: queue=%s", $this->attributes['name']));
            if ($this->logger) {
                $this->logger->warning("shouldStopConsuming returned true before entering loop", [
                    'queue' => $this->attributes['name']
                ]);
            }
            return 0;
        }
        
        while (false === $this->shouldStopConsuming()) {
            try {
                $this->getChannel()->wait(null, false, $seconds);
            } catch (AMQPTimeoutException $e) {
                if ($this->shouldStopConsuming()) {
                    break;
                }
                
                if ($this->logger) {
                    $this->logger->debug("Reconnecting after timeout", [
                        'queue' => $this->attributes['name'],
                        'retry_timeout' => $this->retryTimeout
                    ]);
                }
                
                // Convert seconds to microseconds for usleep
                usleep((int)($this->retryTimeout * 1000000));
                $this->getConnection()->reconnect();
                $this->setupChannelConsumer();
            } catch (\Throwable $e) {
                // stop the consumer
                rmq_log(sprintf(
                    "[RMQ] ERROR in consumer loop: %s - %s",
                    get_class($e),
                    $e->getMessage()
                ));
                
                if ($this->logger) {
                    $this->logger->error("Exception in consumer loop", [
                        'queue' => $this->attributes['name'],
                        'exception' => get_class($e),
                        'message' => $e->getMessage(),
                        'file' => $e->getFile(),
                        'line' => $e->getLine(),
                        'trace' => $e->getTraceAsString()
                    ]);
                }
                
                $this->stopConsuming();
                $this->logger->notice(sprintf(
                    "Stopped consuming: %s in %s:%d",
                    get_class($e) . ' - ' . $e->getMessage(),
                    (string)$e->getFile(),
                    (int)$e->getLine()
                ));
                return 1;
            }
        }
        
        rmq_log(sprintf("[RMQ] Consumer finished: queue=%s", $this->attributes['name']));
        
        if ($this->logger) {
            $this->logger->info("Consumer loop finished", [
                'queue' => $this->attributes['name']
            ]);
        }
        
        return 0;
    }

    /**
     * @return bool
     */
    protected function shouldStopConsuming(): bool
    {
        $currentTime = microtime(true);
        $elapsedTime = $this->startTime > 0 ? ($currentTime - $this->startTime) : 0;
        
        // Check time limit
        if ($this->limitSecondsUptime > 0 && $elapsedTime > $this->limitSecondsUptime) {
            if ($this->logger) {
                $this->logger->debug("shouldStopConsuming: time limit reached", [
                    'queue' => $this->attributes['name'],
                    'limitSecondsUptime' => $this->limitSecondsUptime,
                    'elapsedTime' => sprintf("%.2f", $elapsedTime)
                ]);
            }
            return true;
        }
        
        // Check memory limit (only if limit is set and > 0)
        // Check memory growth from start, not absolute value
        if ($this->limitMemoryConsumption > 0) {
            $currentMemory = memory_get_usage(true);
            $memoryGrowth = $this->startMemory > 0 ? ($currentMemory - $this->startMemory) : $currentMemory;
            $memoryLimit = $this->limitMemoryConsumption * 1048576;
            // Check if memory growth exceeds limit, not absolute memory
            if ($memoryGrowth >= $memoryLimit) {
                if ($this->logger) {
                    $this->logger->debug("shouldStopConsuming: memory limit reached", [
                        'queue' => $this->attributes['name'],
                        'limitMemoryConsumption' => $this->limitMemoryConsumption,
                        'currentMemory' => (int)round($currentMemory / 1048576, 2),
                        'memoryGrowth' => (int)round($memoryGrowth / 1048576, 2),
                        'memoryLimit' => (int)round($memoryLimit / 1048576, 2)
                    ]);
                }
                return true;
            }
        }

        // Check message count limit
        if ($this->limitMessageCount > 0) {
            try {
                $processedMessages = $this->getMessageProcessor()->getProcessedMessages();
                if ($processedMessages >= $this->limitMessageCount) {
                    if ($this->logger) {
                        $this->logger->debug("shouldStopConsuming: message count limit reached", [
                            'queue' => $this->attributes['name'],
                            'limitMessageCount' => $this->limitMessageCount,
                            'processedMessages' => $processedMessages
                        ]);
                    }
                    return true;
                }
            } catch (\Throwable $e) {
                if ($this->logger) {
                    $this->logger->error("Error getting processed messages count", [
                        'queue' => $this->attributes['name'],
                        'exception' => get_class($e),
                        'message' => $e->getMessage()
                    ]);
                }
            }
        }
        
        return false;
    }

    /**
     * Stop the consumer
     */
    public function stopConsuming()
    {
        try {
            $this->getChannel()->basic_cancel($this->getConsumerTag(), false, true);
        } catch (\Throwable $e) {
            $this->logger->notice("Got " . $e->getMessage() . " of type " . get_class($e));
        }
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
        try {
            $this->limitMessageCount = $messages;
            $this->limitSecondsUptime = $seconds;
            $this->limitMemoryConsumption = $maxMemory;

            $this->startTime = microtime(true);
            $this->startMemory = memory_get_usage(true);

            if ($this->logger) {
                $this->logger->debug("Limits set", [
                    'queue' => $this->attributes['name'],
                    'limitMessageCount' => $this->limitMessageCount,
                    'limitSecondsUptime' => $this->limitSecondsUptime,
                    'limitMemoryConsumption' => $this->limitMemoryConsumption,
                    'startTime' => $this->startTime
                ]);
            }

            $this->setupChannelConsumer();
            $this->registerShutdownHandler();
            $this->handleKillSignals();
        } catch (\Throwable $e) {
            rmq_log(sprintf(
                "[RMQ] ERROR in setupConsumer: %s - %s",
                get_class($e),
                $e->getMessage()
            ));
            
            if ($this->logger) {
                $this->logger->error("Error in setupConsumer", [
                    'queue' => $this->attributes['name'],
                    'exception' => get_class($e),
                    'message' => $e->getMessage(),
                    'file' => $e->getFile(),
                    'line' => $e->getLine(),
                    'trace' => $e->getTraceAsString()
                ]);
            }
            throw $e;
        }
    }

    private function setupChannelConsumer()
    {
        try {
            if ($this->attributes['auto_create'] === true) {
                $this->create();
                $this->bind();
            }

            $channel = $this->getChannel();
            $channel->basic_qos(null, $this->prefetchCount, $this->globalPrefetch);

            $consumerTag = $this->getConsumerTag();

            if ($this->logger) {
                $this->logger->debug("Starting basic_consume", [
                    'queue' => $this->attributes['name'],
                    'consumer_tag' => $consumerTag,
                    'prefetch_count' => $this->prefetchCount
                ]);
            }

            $channel->basic_consume(
                $this->attributes['name'],
                $consumerTag,
                false,
                false,
                false,
                false,
                [
                    $this,
                    'consume'
                ]
            );
        } catch (\Throwable $e) {
            rmq_log(sprintf(
                "[RMQ] ERROR in setupChannelConsumer: %s - %s",
                get_class($e),
                $e->getMessage()
            ));
            
            if ($this->logger) {
                $this->logger->error("Error in setupChannelConsumer: " . $e->getMessage(), [
                    'file' => $e->getFile(),
                    'line' => $e->getLine()
                ]);
            }
            throw $e;
        }
    }

    /**
     * Handle shutdown - Usually in case "Allowed memory size of x bytes exhausted"
     */
    private function registerShutdownHandler()
    {
        $consumer = $this;
        register_shutdown_function(function () use ($consumer) {
            $consumer->stopConsuming();
        });
    }

    /**
     * Register signals
     */
    protected function handleKillSignals()
    {
        if (extension_loaded('pcntl')) {
            pcntl_signal(SIGTERM, [$this, 'catchKillSignal']);
            pcntl_signal(SIGINT, [$this, 'catchKillSignal']);

            if (function_exists('pcntl_signal_dispatch')) {
                // let the signal go forward
                pcntl_signal_dispatch();
            }
        }
    }

    /**
     * Handle Kill Signals
     * @param int $signalNumber
     */
    public function catchKillSignal(int $signalNumber)
    {
        $this->stopConsuming();
        $this->logger->debug(sprintf("Caught signal %d", $signalNumber));
    }

    /**
     * It is the tag that is listed in RabbitMQ UI as the consumer "name"
     *
     * @return string
     */
    private function getConsumerTag(): string
    {
        return sprintf("%s_%s_%s", $this->aliasName, gethostname(), getmypid());
    }

    /**
     * @return MessageProcessorInterface
     */
    private function getMessageProcessor(): MessageProcessorInterface
    {
        if (!($this->messageProcessor instanceof MessageProcessorInterface)) {
            try {
                $this->messageProcessor = app($this->messageProcessor);
                if ($this->messageProcessor instanceof AbstractMessageProcessor) {
                    $this->messageProcessor->setLogger($this->logger);
                }
            } catch (\Throwable $e) {
                if ($this->logger) {
                    $this->logger->error("Error creating message processor", [
                        'exception' => get_class($e),
                        'message' => $e->getMessage()
                    ]);
                }
                throw $e;
            }
        }
        
        return $this->messageProcessor;
    }

    /**
     * @param AMQPMessage $message
     * @throws \Throwable
     */
    public function consume(AMQPMessage $message)
    {
        // Get routing info from delivery info
        $deliveryInfo = $message->getDeliveryInfo();
        $routingKey = $deliveryInfo['routing_key'] ?? 'unknown';
        $exchange = $deliveryInfo['exchange'] ?? 'unknown';
        $from = $exchange . ':' . $routingKey;
        
        try {
            $processor = $this->getMessageProcessor();
            $processor->consume($message);
            
            rmq_log(sprintf(
                "[RMQ] Message processed: queue=%s, from=%s, size=%d bytes",
                $this->attributes['name'],
                $from,
                strlen($message->getBody())
            ));
            
            if ($this->logger) {
                $this->logger->debug("Consumed message", [
                    'queue' => $this->attributes['name'],
                    'from' => $from,
                    'message' => $message->getBody()
                ]);
            }
        } catch (\Throwable $e) {
            rmq_log(sprintf(
                "[RMQ] ERROR processing message: queue=%s, from=%s, error=%s - %s",
                $this->attributes['name'],
                $from,
                get_class($e),
                $e->getMessage()
            ));
            
            if ($this->logger) {
                $this->logger->notice(
                    sprintf(
                        "Got %s from %s in %d",
                        $e->getMessage(),
                        (string)$e->getFile(),
                        (int)$e->getLine()
                    )
                );
            }
            // let the exception slide, the processor should handle
            // exception, this is just a notice that should not
            // ever appear
            throw $e;
        }
    }
}
