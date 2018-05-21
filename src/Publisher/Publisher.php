<?php
namespace NeedleProject\LaravelRabbitMq\Publisher;

use NeedleProject\LaravelRabbitMq\Entity\AbstractAMQPEntity;
use NeedleProject\LaravelRabbitMq\Entity\QueueEntity;
use PhpAmqpLib\Message\AMQPMessage;

/**
 * Class Publisher
 *
 * @package NeedleProject\LaravelRabbitMq\Publisher
 * @author  Adrian Tilita <adrian@tilita.ro>
 */
class Publisher implements PublisherInterface
{
    private $entity = null;

    /**
     * Publisher constructor.
     *
     * @param AbstractAMQPEntity $entity
     */
    public function __construct(AbstractAMQPEntity $entity)
    {
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
     * Start a transaction
     */
    public function startTransaction()
    {
        $this->getEntity()
            ->getConnection()
            ->getChannel()
            ->tx_select();
    }

    /**
     * Commit a transaction
     */
    public function commit()
    {
        $this->getEntity()
            ->getConnection()
            ->getChannel()
            ->tx_commit();
    }

    /**
     * RollBack a transaction
     */
    public function rollBack()
    {
        $this->getEntity()
            ->getConnection()
            ->getChannel()
            ->tx_rollback();
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
        $exchangeName = $this->getEntity()->getName();
        if ($this->getEntity() instanceof QueueEntity) {
            $exchangeName = '';
            $routingKey = $this->getEntity()->getName();
        }

        $this->getEntity()
            ->getConnection()
            ->getChannel()
            ->basic_publish(
                new AMQPMessage($message),
                $exchangeName,
                $routingKey,
                true
            );
    }
}
