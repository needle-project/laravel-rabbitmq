<?php
namespace NeedleProject\LaravelRabbitMq\Builder;

use Illuminate\Support\Collection;
use NeedleProject\LaravelRabbitMq\Connection\AMQPConnection;
use NeedleProject\LaravelRabbitMq\Consumer\Consumer;
use NeedleProject\LaravelRabbitMq\Container;
use NeedleProject\LaravelRabbitMq\Entity\EntityFactory;
use NeedleProject\LaravelRabbitMq\Publisher\Publisher;

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
        $entities    = $this->createEntities($config['entities'], $connections);

        $container = new Container();
        // create publishers
        foreach ($config['publishers'] as $publisherAliasName => $publisherEntityBind) {
            $container->addPublisher(
                $publisherAliasName,
                new Publisher($entities->get($publisherEntityBind))
            );
        }

        foreach ($config['consumers'] as $consumerAliasName => $consumerDetails) {
             $prefetchCount = $consumerDetails['prefetch_count'];
             $messageProcessor = $consumerDetails['message_processor'];

             $consumer = new Consumer(
                 $consumerAliasName,
                 $entities->get($consumerDetails['queue']),
                 $messageProcessor,
                 $prefetchCount
             );
             $container->addConsumer($consumerAliasName, $consumer);
        }

        return $container;
    }

    /**
     * Create connections
     *
     * @todo    Inject config validator
     * @param array $connectionConfig
     * @return Collection
     */
    private function createConnections(array $connectionConfig): Collection
    {
        $connections = new Collection();
        foreach ($connectionConfig as $connectionAliasName => $connectionCredentials) {
            $connections->put(
                $connectionAliasName,
                new AMQPConnection($connectionAliasName, $connectionCredentials)
            );
        }
        return $connections;
    }

    private function createEntities(array $entitiesConfig, Collection $connections): Collection
    {
        $entities = new Collection();
        foreach ($entitiesConfig as $entityAliasName => $entityDetails) {
            $entities[$entityAliasName] = EntityFactory::createEntity(
                $connections->get($entityDetails['connection']),
                $entityDetails['name'],
                $entityDetails['type'],
                $entityDetails['attributes']
            );
        }
        return $entities;
    }
}
