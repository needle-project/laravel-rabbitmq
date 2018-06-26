# laravel-rabbitmq
A simple rabbitmq library for laravel


# Configuration

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
