<?php
namespace NeedleProject\LaravelRabbitMq\Entity;

use NeedleProject\LaravelRabbitMq\AMQPConnection;
use NeedleProject\LaravelRabbitMq\ConsumerInterface;
use NeedleProject\LaravelRabbitMq\Processor\MessageProcessorInterface;
use NeedleProject\LaravelRabbitMq\PublisherInterface;
use PhpAmqpLib\Channel\AMQPChannel;
use PhpAmqpLib\Exception\AMQPTimeoutException;
use PhpAmqpLib\Message\AMQPMessage;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;

/**
 * Class QueueEntity
 *
 * @package NeedleProject\LaravelRabbitMq\Entity
 * @author  Adrian Tilita <adrian@tilita.ro>
 */
class QueueEntity implements PublisherInterface, ConsumerInterface, LoggerAwareInterface
{
    use LoggerAwareTrait;

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
     * @param AMQPConnection $connection
     * @param string $aliasName
     * @param array $exchangeDetails
     * @return QueueEntity
     */
    public static function createQueue(AMQPConnection $connection, string $aliasName, array $exchangeDetails)
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
                $this->getChannel()->wait(null, false, 1);
            } catch (AMQPTimeoutException $e) {
                $this->logger->debug("Timeout exceeded, reconnecting!");
                usleep(1000);
                $this->getConnection()->reconnect();
                $this->setupChannelConsumer();
            } catch (\Throwable $e) {
                // stop the consumer
                $this->stopConsuming();
                $this->logger->critical(sprintf(
                    "Stopped consuming: %s in %s:%d",
                    $e->getMessage(),
                    (string)$e->getFile(),
                    (int)$e->getLine()
                ));
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
            $this->logger->debug(
                "Stopped consumer",
                [
                    'limit' => 'time_limit',
                    'value' => sprintf("%.2f", microtime(true) - $this->startTime)
                ]
            );
            return true;
        }
        if (memory_get_peak_usage(true) >= ($this->limitMemoryConsumption * 1048576)) {
            $this->logger->debug(
                "Stopped consumer",
                [
                    'limit' => 'memory_limit',
                    'value' => (int)round(memory_get_peak_usage(true) / 1048576, 2)
                ]
            );
            return true;
        }

        if ($this->getMessageProcessor()->getProcessedMessages() >= $this->limitMessageCount) {
            $this->logger->debug(
                "Stopped consumer",
                ['limit' => 'message_count', 'value' => (int)$this->getMessageProcessor()->getProcessedMessages()]
            );
            return true;
        }
        return false;
    }

    /**
     * Stop the consumer
     */
    public function stopConsuming()
    {
        $this->logger->debug("Stopping consumer!");
        $this->getChannel()->basic_cancel($this->getConsumerTag(), false, true);
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

        $this->setupChannelConsumer();

        $this->registerShutdownHandler();
        $this->handleKillSignals();
    }

    private function setupChannelConsumer()
    {
        $this->getChannel()
            ->basic_qos(null, $this->prefetchCount, true);

        $this->getChannel()
            ->basic_consume(
                $this->attributes['name'],
                $this->getConsumerTag(),
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
    private function handleKillSignals()
    {
        if (extension_loaded('pcntl')) {
            pcntl_signal(SIGTERM, [$this, 'catchKillSignal']);
            pcntl_signal(SIGKILL, [$this, 'catchKillSignal']);
            pcntl_signal(SIGSTOP, [$this, 'catchKillSignal']);

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
    private function catchKillSignal(int $signalNumber)
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
            $this->messageProcessor = app($this->messageProcessor);
        }
        return $this->messageProcessor;
    }

    /**
     * @param AMQPMessage $message
     * @throws \Throwable
     */
    public function consume(AMQPMessage $message)
    {
        $this->logger->debug("Consumed message", [$message->getBody()]);
        try {
            $this->getMessageProcessor()->consume($message);
        } catch (\Throwable $e) {
            $this->logger->notice(
                sprintf(
                    "Got %s from %s in %d",
                    $e->getMessage(),
                    (string)$e->getFile(),
                    (int)$e->getLine()
                )
            );
            // let the exception slide, the processor should handle
            // exception, this is just a notice that should not
            // ever appear
            throw $e;
        }
    }
}
