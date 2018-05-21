<?php
return [
    'connections' => [
        'default' => [
            'hostname' => '10.101.100.57', // default localhost,
            'port' => 5672,
            'username' => 'order', // default guest
            'password' => 'order', // default guest,
            'vhost' => 'order', // default "/"

            # More info about timeouts can be found on https://www.rabbitmq.com/networking.html
            'connect_timeout' => 1  // default connection timeout
        ]
    ],
    'entities' => [
        'order.create.exchange' => [
            // used connection for the producer
            'connection' => 'default',
            'exchange' => [
                'name' => 'order.create',
                'type' => 'topic',
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
            'exchange' => [
                'name' => 'order.update',
                'type' => 'topic',
                // optional fields
                'passive' => false,
                'durable' => false,
                'auto_delete' => false,
                'internal' => false,
                'nowait' => false
            ]
        ],
        'order.create.queue' => [
            // used connection for the producer
            'connection' => 'default',
            'queue' => [
                'name' => 'order.create',
                'passive' => false,
                'durable' => false,
                'auto_delete' => false,
                'internal' => false,
                'nowait' => false,
                'exchange' => 'order.create',
                'routing_key' => '*'
            ]
        ]
    ],
    'producers' => [
        'order.place' => 'order.create.exchange',
        'order.update' => 'order.update.exchange'
    ],
    'consumers' => [
        'order.create' => [
            'queue' => 'order.create.queue',
            'prefetch_size' => 1,
            'message_processor' => \NeedleProject\LaravelRabbitMq\Consumer\BasicProcessor::class
        ]
    ]
];
