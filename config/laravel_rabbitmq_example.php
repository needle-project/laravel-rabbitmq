<?php
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
