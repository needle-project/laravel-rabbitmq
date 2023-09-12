<?php
namespace NeedleProject\LaravelRabbitMq;

use PHPUnit\Framework\TestCase;

class ConfigHelperTest extends TestCase
{
    /**
     * @dataProvider provideScenarios
     * @param $inputConfig
     * @param $expectedConfig
     */
    public function testDefaultsForMainKeys($inputConfig, $expectedConfig)
    {
        $configHelper = new ConfigHelper();
        $newConfig = $configHelper->addDefaults($inputConfig);

        $this->assertEquals($expectedConfig, $newConfig);
    }

    public static function provideScenarios(): array
    {
        return [
            // first scenario -- add root keys
            [
                [],
                [
                    'connections' => [],
                    'exchanges' => [],
                    'queues' => [],
                    'publishers' => [],
                    'consumers' => []
                ]
            ],
            // second scenario -- add attributes on queues
            [
                [
                    'connections' => [
                        'bar' => []
                    ],
                    'queues' => [
                        'foo' => [
                            'name' => 'foo.bar',
                            'connection' => 'bar'
                        ]
                    ]
                ],
                [
                    'connections' => [
                        'bar' => []
                    ],
                    'queues' => [
                        'foo' => [
                            'name' => 'foo.bar',
                            'connection' => 'bar',
                            'attributes' => []
                        ]
                    ],
                    'exchanges' => [],
                    'publishers' => [],
                    'consumers' => []
                ]
            ],
            // third scenario -- add prefetch_count and global_prefetch on consumer
            [
                [
                    'connections' => [
                        'bar' => []
                    ],
                    'queues' => [
                        'foo' => [
                            'name' => 'foo.bar',
                            'connection' => 'bar'
                        ]
                    ],
                    'consumers' => [
                        'foo_consumer' => [
                            'queue' => 'foo',
                            'message_processor' => 'BAR'
                        ]
                    ]
                ],
                [
                    'connections' => [
                        'bar' => []
                    ],
                    'queues' => [
                        'foo' => [
                            'name' => 'foo.bar',
                            'connection' => 'bar',
                            'attributes' => []
                        ]
                    ],
                    'exchanges' => [],
                    'publishers' => [],
                    'consumers' => [
                        'foo_consumer' => [
                            'queue' => 'foo',
                            'prefetch_count' => 1,
                            'global_prefetch' => true,
                            'message_processor' => 'BAR'
                        ]
                    ]
                ]
            ],
            // 4th scenario -- don't override prefetch_count and global_prefetch on consumer
            [
                [
                    'connections' => [
                        'bar' => []
                    ],
                    'queues' => [
                        'foo' => [
                            'name' => 'foo.bar',
                            'connection' => 'bar'
                        ]
                    ],
                    'consumers' => [
                        'foo_consumer' => [
                            'queue' => 'foo',
                            'prefetch_count' => 3,
                            'global_prefetch' => false,
                            'message_processor' => 'BAR'
                        ]
                    ]
                ],
                [
                    'connections' => [
                        'bar' => []
                    ],
                    'queues' => [
                        'foo' => [
                            'name' => 'foo.bar',
                            'connection' => 'bar',
                            'attributes' => []
                        ]
                    ],
                    'exchanges' => [],
                    'publishers' => [],
                    'consumers' => [
                        'foo_consumer' => [
                            'queue' => 'foo',
                            'prefetch_count' => 3,
                            'global_prefetch' => false,
                            'message_processor' => 'BAR'
                        ]
                    ]
                ]
            ]
        ];
    }
}
