<?php
namespace NeedleProject\LaravelRabbitMq\Consumer;

use NeedleProject\LaravelRabbitMq\Entity\AbstractAMQPEntity;
use NeedleProject\LaravelRabbitMq\Processor\AbstractMessageProcessor;

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
     */
    public function __construct(
        string $aliasName,
        AbstractAMQPEntity $entity,
        AbstractMessageProcessor $processor
    ) {
        $this->aliasName = $aliasName;
        $this->processor = $processor;
        $this->entity = $entity;
    }

    /**
     * @return AbstractAMQPEntity
     */
    protected function getEntity(): AbstractAMQPEntity
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
                    $this->processor,
                    'consume'
                ]
            );
    }
}
