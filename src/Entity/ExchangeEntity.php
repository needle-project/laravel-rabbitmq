<?php
namespace NeedleProject\LaravelRabbitMq\Entity;

/**
 * Class ExchangeEntity
 *
 * @package NeedleProject\LaravelRabbitMq\Entity
 * @author  Adrian Tilita <adrian@tilita.ro>
 */
class ExchangeEntity extends AbstractAMQPEntity
{
    /**
     * {@inheritdoc}
     * @return array
     */
    protected function getDefaultAttributes(): array
    {
        return [
            'exchange_type' => 'topic',
            'passive'       => false,
            'durable'       => false,
            'auto_delete'   => false,
            'internal'      => false,
            'nowait'        => false,
        ];
    }

    /**
     * Create the Queue
     */
    public function create()
    {
        $this->getConnection()
            ->getChannel()
            ->exchange_declare(
                $this->getName(),
                $this->attributes['exchange_type'],
                $this->attributes['passive'],
                $this->attributes['durable'],
                $this->attributes['auto_delete'],
                $this->attributes['internal'],
                $this->attributes['nowait']
            );
        if (isset($this->attributes['bind'])) {
            $this->getConnection()
                ->getChannel()
                ->queue_bind(
                    $this->attributes['bind']['queue'],
                    $this->getName(),
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
            ->exchange_delete($this->getName());
    }
}
