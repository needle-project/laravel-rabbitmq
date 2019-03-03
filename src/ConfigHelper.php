<?php
namespace NeedleProject\LaravelRabbitMq;

/**
 * Class ConfigHelper
 *
 * @package NeedleProject\LaravelRabbitMq
 * @author  Adrian Tilita <adrian@tilita.ro>
 */
class ConfigHelper
{
    const ROOT_KEY_CONNECTIONS = 'connections';
    const ROOT_KEY_QUEUES = 'queues';
    const ROOT_KEY_EXCHANGES = 'exchanges';
    const ROOT_KEY_PUBLISHERS = 'publishers';
    const ROOT_KEY_CONSUMERS = 'consumers';

    /**
     * @param array $config
     * @return array
     */
    public function addDefaults(array $config): array
    {
        $config = $this->addMainKeys($config);
        $config[static::ROOT_KEY_QUEUES] = $this->addAttributesOnEntities($config[static::ROOT_KEY_QUEUES]);
        $config[static::ROOT_KEY_EXCHANGES] = $this->addAttributesOnEntities($config[static::ROOT_KEY_EXCHANGES]);
        $config[static::ROOT_KEY_CONSUMERS] = $this->addPrefetchOnConsumers($config[static::ROOT_KEY_CONSUMERS]);
        return $config;
    }

    /**
     * Add root keys on config
     *
     * @param array $config
     * @return array
     */
    private function addMainKeys(array $config): array
    {
        if (!isset($config[static::ROOT_KEY_CONNECTIONS])) {
            $config[static::ROOT_KEY_CONNECTIONS] = [];
        }
        if (!isset($config[static::ROOT_KEY_EXCHANGES])) {
            $config[static::ROOT_KEY_EXCHANGES] = [];
        }
        if (!isset($config[static::ROOT_KEY_QUEUES])) {
            $config[static::ROOT_KEY_QUEUES] = [];
        }
        if (!isset($config[static::ROOT_KEY_PUBLISHERS])) {
            $config[static::ROOT_KEY_PUBLISHERS] = [];
        }
        if (!isset($config[static::ROOT_KEY_CONSUMERS])) {
            $config[static::ROOT_KEY_CONSUMERS] = [];
        }
        return $config;
    }

    /**
     * Add attributes entities (queues|exchanges)
     * @param array $entityConfig
     * @return array
     */
    private function addAttributesOnEntities(array $entityConfig): array
    {
        foreach ($entityConfig as $entityAliasName => $entityProperties) {
            if (isset($entityProperties['attributes'])) {
                continue;
            }
            $entityProperties['attributes'] = [];
            $entityConfig[$entityAliasName] = $entityProperties;
        }
        return $entityConfig;
    }

    /**
     * Add prefetch key on consumer config
     *
     * @param array $consumers
     * @return array
     */
    private function addPrefetchOnConsumers(array $consumers): array
    {
        foreach ($consumers as $consumerAliasName => $consumerDefinition)
        {
            if (isset($consumerDefinition['prefetch_count'])) {
                continue;
            }
            $consumerDefinition['prefetch_count'] = 1;
            $consumers[$consumerAliasName] = $consumerDefinition;
        }
        return $consumers;
    }
}
