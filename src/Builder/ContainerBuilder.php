<?php
namespace NeedleProject\LaravelRabbitMq\Builder;

use Illuminate\Support\Collection;
use NeedleProject\LaravelRabbitMq\AMQPConnection;
use NeedleProject\LaravelRabbitMq\Container;
use NeedleProject\LaravelRabbitMq\Entity\ExchangeEntity;
use NeedleProject\LaravelRabbitMq\Entity\QueueEntity;

/**
 * Class ContainerBuilder
 *
 * @package NeedleProject\LaravelRabbitMq\Builder
 * @author  Adrian Tilita <adrian@tilita.ro>
 * @todo    Add config validator
 */
class ContainerBuilder
{
    /**
     * Create RabbitMQ Container
     *
     * @param array $config
     * @return Container
     */
    public function createContainer(array $config)
    {
        $connections = $this->createConnections($config['connections']);
        $exchanges = $this->createExchanges($config['exchanges'], $connections);
        $queues = $this->createQueues($config['queues'], $connections);

        $container = new Container();
        // create publishers
        foreach ($config['publishers'] as $publisherAliasName => $publisherEntityBind) {
            if ($exchanges->has($publisherEntityBind)) {
                $entity = $exchanges->get($publisherEntityBind);
            } elseif ($queues->has($publisherEntityBind)) {
                $entity = $queues->get($publisherEntityBind);
            } else {
                throw new \RuntimeException(
                    sprintf(
                        "Cannot create publisher %s: no exchange or queue named %s defined!",
                        (string)$publisherAliasName,
                        (string)$publisherEntityBind
                    )
                );
            }

            $container->addPublisher(
                $publisherAliasName,
                $entity
            );
        }

        foreach ($config['consumers'] as $consumerAliasName => $consumerDetails) {
            $prefetchCount    = $consumerDetails['prefetch_count'];
            $messageProcessor = $consumerDetails['message_processor'];

            if ($queues->has($consumerDetails['queue'])) {
                /** @var QueueEntity $entity */
                $entity = $queues->get($consumerDetails['queue']);
            } else {
                throw new \RuntimeException(
                    sprintf(
                        "Cannot create consumer %s: no queue named %s defined!",
                        (string)$consumerAliasName,
                        (string)$consumerDetails['queue']
                    )
                );
            }

            $entity->setPrefetchCount($prefetchCount);
            $entity->setMessageProcessor($messageProcessor);
            $container->addConsumer($consumerAliasName, $entity);
        }

        return $container;
    }

    /**
     * Create connections
     *
     * @param array $connectionConfig
     * @return Collection
     */
    private function createConnections(array $connectionConfig): Collection
    {
        $connections = new Collection();
        foreach ($connectionConfig as $connectionAliasName => $connectionCredentials) {
            $connections->put(
                $connectionAliasName,
                AMQPConnection::createConnection($connectionAliasName, $connectionCredentials)
            );
        }
        return $connections;
    }

    /**
     * @param array $exchangeConfigList
     * @param Collection $connections
     * @return Collection
     */
    private function createExchanges(array $exchangeConfigList, Collection $connections): Collection
    {
        $exchanges = new Collection();
        foreach ($exchangeConfigList as $exchangeAliasName => $exchangeDetails) {
            // verify if the connection exists
            if (array_key_exists('connection', $exchangeDetails) &&
                false === $connections->has($exchangeDetails['connection'])) {
                throw new \RuntimeException(
                    sprintf(
                        "Could not create exchange %s: connection name %s is not defined!",
                        (string)$exchangeAliasName,
                        (string)$exchangeDetails['connection']
                    )
                );
            }

            $exchanges->put(
                $exchangeAliasName,
                ExchangeEntity::createExchange(
                    $connections->get($exchangeDetails['connection']),
                    $exchangeAliasName,
                    array_merge($exchangeDetails['attributes'], ['name' => $exchangeDetails['name']])
                )
            );
        }
        return $exchanges;
    }

    /**
     * @param array $queueConfigList
     * @param Collection $connections
     * @return Collection
     */
    private function createQueues(array $queueConfigList, Collection $connections): Collection
    {
        $queue = new Collection();
        foreach ($queueConfigList as $queueAliasName => $queueDetails) {
            // verify if the connection exists
            if (array_key_exists('connection', $queueDetails) &&
                false === $connections->has($queueDetails['connection'])) {
                throw new \RuntimeException(
                    sprintf(
                        "Could not create exchange %s: connection name %s is not defined!",
                        (string)$queueAliasName,
                        (string)$queueDetails['connection']
                    )
                );
            }

            $queue->put(
                $queueAliasName,
                QueueEntity::createQueue(
                    $connections->get($queueDetails['connection']),
                    $queueAliasName,
                    array_merge($queueDetails['attributes'], ['name' => $queueDetails['name']])
                )
            );
        }
        return $queue;
    }
}
