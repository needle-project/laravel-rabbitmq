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

    public function provideScenarios()
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
            // third scenario -- add prefetch_count on consumer
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
                            'message_processor' => 'BAR'
                        ]
                    ]
                ]
            ]
        ];
    }
}
