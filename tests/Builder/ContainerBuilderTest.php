<?php
namespace NeedleProject\LaravelRabbitMq\Builder;

use PHPUnit\Framework\TestCase;

class ContainerBuilderTest extends TestCase
{
    public function testPublisher()
    {
        $containerBuilder = new ContainerBuilder();
        $container = $containerBuilder->createContainer([
            'connections' => [
                'foo' => []
            ],
            'queues' => [],
            'consumers' => [],
            'exchanges' => [
                'my.exchange' => [
                    // used connection for the producer
                    'connection' => 'foo',
                    'name'       => 'the.exchange.name',
                    'attributes' => [
                        'exchange_type' => 'topic'
                    ]
                ]
            ],
            'publishers' => [
                'fooPublisher' => 'my.exchange'
            ]
        ]);

        $this->assertEquals(1, count($container->getPublishers()));
        $this->assertEquals(
            'my.exchange',
            $container->getPublisher('fooPublisher')->getAliasName()
        );
    }

    public function testBadConnectionReferenceForExchange()
    {
        $containerBuilder = new ContainerBuilder();

        $this->expectException(\RuntimeException::class);

        $containerBuilder->createContainer([
            'connections' => [
                'foo' => []
            ],
            'queues' => [],
            'consumers' => [],
            'exchanges' => [
                'my.exchange' => [
                    'connection' => 'un-existent',
                    'name'       => 'the.exchange.name',
                    'attributes' => [
                        'exchange_type' => 'topic'
                    ]
                ]
            ],
            'publishers' => [
                'fooPublisher' => 'my.exchange'
            ]
        ]);
    }

    public function testConsumer()
    {
        $containerBuilder = new ContainerBuilder();
        $container = $containerBuilder->createContainer([
            'connections' => [
                'foo' => []
            ],
            'exchanges' => [],
            'consumers' => [
                'my-consumer' => [
                    'queue' => 'my.queue',
                    'prefetch_count' => 1,
                    'message_processor' => \NeedleProject\LaravelRabbitMq\Processor\CliOutputProcessor::class
                ]
            ],
            'queues' => [
                'my.queue' => [
                    // used connection for the producer
                    'connection' => 'foo',
                    'name'       => 'my.queue',
                    'attributes' => []
                ]
            ],
            'publishers' => []
        ]);

        $this->assertEquals(1, count($container->getConsumers()));
        $this->assertEquals(
            'my.queue',
            $container->getConsumer('my-consumer')->getAliasName()
        );
    }

    public function testQueueBadConnection()
    {
        $containerBuilder = new ContainerBuilder();

        $this->expectException(\RuntimeException::class);

        $containerBuilder->createContainer([
            'connections' => [
                'foo' => []
            ],
            'exchanges' => [],
            'consumers' => [
                'my-consumer' => [
                    'queue' => 'my.queue',
                    'prefetch_count' => 1,
                    'message_processor' => \NeedleProject\LaravelRabbitMq\Processor\CliOutputProcessor::class
                ]
            ],
            'queues' => [
                'my.queue' => [
                    // used connection for the producer
                    'connection' => 'bar',
                    'name'       => 'my.queue',
                    'attributes' => []
                ]
            ],
            'publishers' => []
        ]);
    }

    public function testBadConsumerReference()
    {
        $containerBuilder = new ContainerBuilder();

        $this->expectException(\RuntimeException::class);

        $containerBuilder->createContainer([
            'connections' => [
                'foo' => []
            ],
            'exchanges' => [],
            'consumers' => [
                'my-consumer' => [
                    'queue' => 'my.queue',
                    'prefetch_count' => 1,
                    'message_processor' => \NeedleProject\LaravelRabbitMq\Processor\CliOutputProcessor::class
                ]
            ],
            'queues' => [
                'my.queue.typo' => [
                    // used connection for the producer
                    'connection' => 'foo',
                    'name'       => 'my.queue',
                    'attributes' => []
                ]
            ],
            'publishers' => []
        ]);
    }

    public function testBadPublisherReference()
    {
        $containerBuilder = new ContainerBuilder();

        $this->expectException(\RuntimeException::class);

        $containerBuilder->createContainer([
            'connections' => [
                'foo' => []
            ],
            'queues' => [],
            'consumers' => [],
            'exchanges' => [
                'my.exchange' => [
                    // used connection for the producer
                    'connection' => 'foo',
                    'name'       => 'the.exchange.name',
                    'attributes' => [
                        'exchange_type' => 'topic'
                    ]
                ]
            ],
            'publishers' => [
                'fooPublisher' => 'my.exchange.typo'
            ]
        ]);
    }

    public function testQueuePublisherReference()
    {
        $containerBuilder = new ContainerBuilder();
        $container = $containerBuilder->createContainer([
            'connections' => [
                'foo' => []
            ],
            'queues' => [
                'a.queue' => [
                    // used connection for the producer
                    'connection' => 'foo',
                    'name'       => 'a.queue.name',
                    'attributes' => [
                    ]
                ]
            ],
            'consumers' => [],
            'exchanges' => [],
            'publishers' => [
                'fooPublisher' => 'a.queue'
            ]
        ]);


        $this->assertEquals(1, count($container->getPublishers()));
        $this->assertEquals(
            'a.queue',
            $container->getPublisher('fooPublisher')->getAliasName()
        );
    }
}
