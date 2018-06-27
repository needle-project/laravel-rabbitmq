<?php
namespace NeedleProject\LaravelRabbitMq\Consumer;

use NeedleProject\LaravelRabbitMq\Entity\AbstractAMQPEntity;
use NeedleProject\LaravelRabbitMq\Processor\AbstractMessageProcessor;
use PhpAmqpLib\Message\AMQPMessage;

class Consumer implements ConsumerInterface
{
    /**
     * @var string
     */
    private $aliasName = '';

    /**
     * @var AbstractMessageProcessor|null
     */
    private $processor = null;

    /**
     * @var int
     */
    private $prefetchCount = 1;

    /** @var AbstractAMQPEntity */
    private $entity = null;

    /**
     * Consumer constructor.
     *
     * @param string $aliasName
     * @param AbstractAMQPEntity $entity
     * @param AbstractMessageProcessor $processor
     * @param int $prefetchCount
     */
    public function __construct(
        string $aliasName,
        AbstractAMQPEntity $entity,
        string $processor,
        int $prefetchCount = 1
    ) {
        $this->aliasName = $aliasName;
        $this->processor = $processor;
        $this->entity = $entity;
        $this->prefetchCount = $prefetchCount;
    }

    /**
     * @return AbstractAMQPEntity
     */
    public function getEntity(): AbstractAMQPEntity
    {
        return $this->entity;
    }

    /**
     * Start consuming messages
     */
    public function startConsuming()
    {
        $this->setupConsumer();
        while (false === $this->shouldStopConsuming()) {
            try {
                $this->getEntity()
                    ->getConnection()
                    ->getChannel()
                    ->wait();
            } catch (\Throwable $e) {
                dump(get_class($e));
                dump($e->getMessage());
            }
        }
    }

    /**
     * @return bool
     */
    protected function shouldStopConsuming(): bool
    {
        return false;
    }

    /**
     * Setup the consumer
     */
    protected function setupConsumer()
    {
        $this->getEntity()
            ->getConnection()
            ->getChannel()
            ->basic_qos(null, $this->prefetchCount, true);

        $this->getEntity()
            ->getConnection()
            ->getChannel()
            ->basic_consume(
                $this->entity->getName(),
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
     * @return AbstractMessageProcessor
     */
    private function getMessageProcessor()
    {
        if (!($this->processor instanceof AbstractMessageProcessor)) {
            $this->processor = app($this->processor);
        }
        return $this->processor;
    }

    /**
     * @param AMQPMessage $message
     */
    public function consume(AMQPMessage $message)
    {
        $this->getMessageProcessor()->consume($message);
    }
}
