[![Build Status](https://travis-ci.org/needle-project/process-transaction.svg?branch=master)](https://travis-ci.org/needle-project/process-transaction)
[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/needle-project/laravel-rabbitmq/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/needle-project/laravel-rabbitmq/?branch=master)
[![Code Coverage](https://scrutinizer-ci.com/g/needle-project/laravel-rabbitmq/badges/coverage.png?b=master)](https://scrutinizer-ci.com/g/needle-project/laravel-rabbitmq/?branch=master)
[![Total Downloads](https://poser.pugx.org/needle-project/laravel-rabbitmq/downloads)](https://packagist.org/packages/needle-project/laravel-rabbitmq)

# Laravel RabbitMQ
A simple rabbitmq library for laravel

Installation
------------

Run:
```bash
composer require needle-project/laravel-rabbitmq
```

For Laravel version 5.5 or higher the library should be automatically loaded via [Package discovery](https://laravel.com/docs/5.6/packages#package-discovery).

For Laravel versions below 5.5 you need to add the service provider to `app.php`:

```php
<?php

return [
    // ...
    'providers' => [
        // ...
        NeedleProject\LaravelRabbitMq\Providers\ServiceProvider::class,
    ],
    // ...
];
```

Configuration
-------------
* Create a new file called `laravel_rabbitmq.php` inside your Laravel's config directory.
(Or use `artisan vendor:publish` - Read more [here](https://laravel.com/docs/5.0/packages))
* Fill out the config based on your needs.

Configuration anatomy
```php
return [
    'connections' => [
        'connectionA' => [/** Connection A attributes */],
        'connectionB' => [/** Connection B attributes */],
    ],
    'exchanges' => [
        'exchangeA' => [
            // Tells that the exchange will use the connection A
            'connection' => 'connectionA',
            /** Exchange A Attributes */
        ],
        'exchangeB' => [
            // Tells that the exchange will use the connection B
            'connection' => 'connectionB',
            /** Exchange B Attributes */
        ]
    ],
    'queues' => [
        'queueA' => [
            // Tells that the queue will use the connection A
            'connection' => 'connectionA',
            /** Queue A Attributes */
        ]
    ],
    'publishers' => [
        'aPublisherName' => /** will publish to */ 'exchangeA'
    ],
    'consumers' => [
        'aConsumerName' => [
            // will read messages from
            'queue' => 'queueA',
            // and will send the for processing to an "NeedleProject\LaravelRabbitMq\Processor\MessageProcessorInterface"
            'message_processor' => \NeedleProject\LaravelRabbitMq\Processor\CliOutputProcessor::class
        ]
    ]
]
```

Example of a full configuration:
```php
return [
    'connections' => [
        'myConnectionAliasName' => [
            // all fields are optional, if they are not defined they
            // will take the default values
            'hostname'           => '127.0.0.1',
            'port'               => 5672,
            'username'           => 'guest',
            'password'           => 'guest',
            'vhost'              => '/',
    
            # whether the connection should be lazy
            'lazy'               => true,
    
            # More info about timeouts can be found on https://www.rabbitmq.com/networking.html
            'read_write_timeout' => 8,   // default timeout for writing/reading (in seconds)
            'connect_timeout'    => 10,
            'heartbeat'          => 4
        ]
    ],
    'exchanges' => [
        'InternalAliasNameForTheExchange' => [
            // used connection for the producer
            'connection' => 'myConnectionAliasName',
            'name'       => 'my.exachange.name.in.rabbitMq',
            'attributes' => [
                // mandatory fields
                'exchange_type' => 'topic',
                // optional fields - if none is set,
                // the defaults will be used
                'passive' => false,
                'durable' => false,
                'auto_delete' => false,
                'internal' => false,
                'nowait' => false,
                
                // whether the exchange should create a bind
                // with a queue
                'bind' => [
                    [
                        'queue' => 'my.queue.that.will.receive.messages',
                        'routing_key' => '*'
                    ]
                ]
            ]
        ]
    ],
    'queues' => [
        'InternalAliasNameForTheQueue' => [
            // used connection for the producer
            'connection' => 'myConnectionAliasName',
            'name'       => 'my.queue.name.on.rabbitMq',
            'attributes' => [
                // optional fields
                'passive' => false,
                'durable' => false,
                'auto_delete' => false,
                'internal' => false,
                'nowait' => false,
                'exclusive' => false,
                // bind with an exchange
                'bind' => [
                    [
                        'exchange' => 'my.queue.that.will.receive.messages',
                        'routing_key' => '*'
                    ]
                ]
            ]
        ],
    ],
    'publishers' => [
        'publisherAliasName' => 'InternalAliasNameForTheExchange'
    ],
    'consumers' => [
        'consumerAliasName' => [
            'queue' => 'InternalAliasNameForTheQueue',
            'prefetch_count' => 10,
            'message_processor' => \NeedleProject\LaravelRabbitMq\Processor\CliOutputProcessor::class
        ]
    ]
];
```

##### To simply create all the defined exchanges and bound queues as defined in the configuration file, run:
```bash
php artisan rabbitmq:setup
```

Usage
-----

#### List all registered entities (producers and consumers):
```bash
$ php artisan rabbitmq:list

+---+-----------+---------------------------+
| # | Type      | Name                      |
+---+-----------+---------------------------+
| 1 | Publisher | publisherAliasName        |
+---+-----------+---------------------------+
| 2 | Consumer  | consumerAliasName         |
+---+-----------+---------------------------+
```

#### Publish a message:
```php
<?php

/**
 * @var $app \Illuminate\Contracts\Container\Container
 * @var $publisher \NeedleProject\LaravelRabbitMq\PublisherInterface 
 */
$publisher = $app->makeWith(PublisherInterface::class, ['publisherAliasName']);

$message = [
    'title' => 'Hello world',
    'body' => 'Lorem ipsum dolor sit amet, consectetur adipiscing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua.',
];
$routingKey = '*';

$publisher->publish(json_encode($message), /* optional */$routingKey);

```

#### Consume messages:
Create a message processor by extending `\NeedleProject\LaravelRabbitMq\Processor\AbstractMessageProcessor` and implementing the `processMessage(AMQPMessage $message): bool` method.

Start the message consumer/listener:
```bash
php artisan rabbitmq:consume consumerAliasName
```
Running consumers with limit (it will stop when one of the limits are reached)

```bash
php artisan rabbitmq:consume consumerAliasName --time=60 --messages=100 --memory=64
```
This tells the consumer to stop if it run for 1 minute or consumer 100 messages or has reached 64MB of memory usage


#### Delete all the defined exchanges and bound queues, run:

```bash
php artisan rabbitmq:delete
```
