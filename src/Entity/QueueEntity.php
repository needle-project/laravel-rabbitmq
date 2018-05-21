<?php
namespace NeedleProject\LaravelRabbitMq\Entity;

/**
 * Class QueueEntity
 *
 * @package NeedleProject\LaravelRabbitMq\Entity
 * @author  Adrian Tilita <adrian@tilita.ro>
 */
class QueueEntity extends AbstractAMQPEntity
{
    /**
     * @return array
     */
    protected function getDefaultAttributes(): array
    {
        return [
            'passive'   => false,
            'durable'   => false,
            'exclusive' => false,
            'auto_delete' => false,
            'internal'  => false,
            'nowait'    => false,
        ];
    }

    /**
     * Create the Queue
     */
    public function create()
    {
        $this->getConnection()->getChannel()
            ->queue_declare(
                $this->getName(),
                $this->attributes['passive'],
                $this->attributes['durable'],
                $this->attributes['exclusive'],
                $this->attributes['auto_delete'],
                $this->attributes['internal'],
                $this->attributes['nowait']
            );
        if (isset($this->attributes['bind'])) {
            $this->getConnection()
                ->getChannel()
                ->queue_bind(
                    $this->getName(),
                    $this->attributes['bind']['exchange'],
                    $this->attributes['bind']['routing_key']
                );
        }
    }

    /**
     * Delete the queue
     */
    public function delete()
    {
        $this->getConnection()->getChannel()
            ->queue_delete($this->getName());
    }
}
