# Laravel RabbitMQ
A simple rabbitmq library for laravel

Installation
------------

Add to composer.json
```json
"repositories": [
    {
      "type": "vcs",
      "url": "git@github.com:needle-project/laravel-rabbitmq.git",
      "no-api": true
    }
]
```

Then run:
```bash
composer require needle-project/laravel-rabbitmq:dev-develop
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
Inside `laravel_rabbitmq.php` you must define a configuration for the library.

Example config:
```php
return [
    'connections' => [
        'default' => [
            'hostname' => 'localhost', // default localhost,
            'port' => 5672,
            'username' => 'guest', // default guest
            'password' => 'guest', // default guest,
            'vhost' => '/', // default "/"

            # More info about timeouts can be found on https://www.rabbitmq.com/networking.html
            'connect_timeout' => 1  // default connection timeout
        ]
    ],
    'entities' => [
        'order.create.exchange' => [
            // used connection for the producer
            'connection' => 'default',
            'name'       => 'order.create',
            'type'       => 'exchange',
            'attributes' => [
                'exchange_type' => 'topic',
                // optional fields
                'passive' => false,
                'durable' => false,
                'auto_delete' => false,
                'internal' => false,
                'nowait' => false
            ]
        ],
        'order.update.exchange' => [
            // used connection for the producer
            'connection' => 'default',
            'name'       => 'order.update',
            'type'       => 'exchange',
            'attributes' => [
                'exchange_type' => 'topic',
                // optional fields
                'passive' => false,
                'durable' => false,
                'auto_delete' => false,
                'internal' => false,
                'nowait' => false,
            ]
        ],
        'dwh.order.proxy' => [
            // used connection for the producer
            'connection' => 'default',
            'name'       => 'dwh.order.proxy',
            'type'       => 'queue',
            'attributes' => [
                // optional fields
                'passive' => false,
                'durable' => false,
                'auto_delete' => false,
                'internal' => false,
                'nowait' => false,
                'exchange' => [
                    'order.update',
                    'order.create'
                ]
            ]
        ],
    ],
    'publishers' => [
        'order.create' => 'order.create.exchange',
        'order.update' => 'order.update.exchange'
    ],
    'consumers' => [
        'dwh.order.queue' => [
            'queue' => 'dwh.order.proxy',
            'prefetch_count' => 10,
            'message_processor' => \NeedleProject\LaravelRabbitMq\Processor\CliOutputProcessor::class
        ]
    ]
];
```

To simply create all the defined exchanges and bound queues as defined in the configuration file, run:
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
| 1 | Publisher | publisher.name            |
+---+-----------+---------------------------+
| 2 | Consumer  | consumer.name             |
+---+-----------+---------------------------+
```

#### Publish a message:
```php
<?php

/**
 * @var $app \Illuminate\Contracts\Container\Container
 * @var $publisher \NeedleProject\LaravelRabbitMq\PublisherInterface 
 */
$publisher = $app->makeWith(PublisherInterface::class, ['vendor.create']);

$message = [
    'title' => 'Hello world',
    'body' => 'Lorem ipsum dolor sit amet, consectetur adipiscing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua.',
];
$routingKey = '*';

$publisher->publish(json_encode($message), $routingKey);

```

#### Consume messages:
Create a message processor by extending `\NeedleProject\LaravelRabbitMq\Processor\AbstractMessageProcessor` and implementing the `processMessage(AMQPMessage $message): bool` method.

Start the message consumer/listener:
```bash
php artisan rabbitmq:consume consumer.name
```

#### Delete all the defined exchanges and bound queues, run:

```bash
php artisan rabbitmq:delete
```
