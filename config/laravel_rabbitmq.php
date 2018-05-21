<?php
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
        'resource.create.exchange' => [
            // used connection for the producer
            'connection' => 'default',
            'exchange' => [
                'name' => 'resource.create',
                'type' => 'topic',
                // optional fields
                'passive' => false,
                'durable' => false,
                'auto_delete' => false,
                'internal' => false,
                'nowait' => false
            ]
        ],
        'resource.update.exchange' => [
            // used connection for the producer
            'connection' => 'default',
            'exchange' => [
                'name' => 'resource.update',
                'type' => 'topic',
                // optional fields
                'passive' => false,
                'durable' => false,
                'auto_delete' => false,
                'internal' => false,
                'nowait' => false
            ]
        ],
        'resource.create.queue' => [
            // used connection for the producer
            'connection' => 'default',
            'queue' => [
                'name' => 'resource.create',
                'passive' => false,
                'durable' => false,
                'auto_delete' => false,
                'internal' => false,
                'nowait' => false,
                'exchange' => 'resource.create',
                'routing_key' => '*'
            ]
        ]
    ],
    'producers' => [
        'resource.place' => 'resource.create.exchange',
        'resource.update' => 'resource.update.exchange'
    ],
    'consumers' => [
        'order.create' => [
            'queue' => 'resource.create.queue',
            'prefetch_size' => 1,
            'message_processor' => \NeedleProject\LaravelRabbitMq\Processor\CliOutputProcessor::class
        ]
    ]
];
