<?php
namespace NeedleProject\LaravelRabbitMq;

use PHPUnit\Framework\TestCase;

class ContainerTest extends TestCase
{
    public function testAddPublisher()
    {
        $publisherMock = $this->getMockBuilder(PublisherInterface::class)
            ->getMock();

        $container = new Container();
        $container->addPublisher('foo', $publisherMock);

        $this->assertTrue($container->hasPublisher('foo'));
        $this->assertEquals(1, count($container->getPublishers()));

        $this->assertEquals($publisherMock, $container->getPublisher('foo'));
    }

    public function testReAddPublisher()
    {
        $publisherMock = $this->getMockBuilder(PublisherInterface::class)
            ->getMock();

        $container = new Container();
        $container->addPublisher('foo', $publisherMock);
        $container->addPublisher('foo', $publisherMock);

        $this->assertEquals(1, count($container->getPublishers()));
    }

    public function testNoPublisher()
    {
        $container = new Container();
        $this->assertEquals(0, count($container->getPublishers()));
        $this->assertEmpty($container->getPublishers());
        $this->assertFalse($container->hasPublisher('foo'));
    }

    public function testAddConsumer()
    {
        $consumerMock = $this->getMockBuilder(ConsumerInterface::class)
            ->getMock();

        $container = new Container();
        $container->addConsumer('foo', $consumerMock);

        $this->assertTrue($container->hasConsumer('foo'));
        $this->assertEquals(1, count($container->getConsumers()));

        $this->assertEquals($consumerMock, $container->getConsumer('foo'));
    }

    public function testReAddConsumer()
    {
        $consumerMock = $this->getMockBuilder(ConsumerInterface::class)
            ->getMock();

        $container = new Container();
        $container->addConsumer('foo', $consumerMock);
        $container->addConsumer('foo', $consumerMock);

        $this->assertEquals(1, count($container->getConsumers()));
    }

    public function testNoConsumer()
    {
        $container = new Container();
        $this->assertEquals(0, count($container->getConsumers()));
        $this->assertEmpty($container->getConsumers());
        $this->assertFalse($container->hasPublisher('foo'));
    }

    public function testConsumerPublisherIntersection()
    {
        $consumerMock = $this->getMockBuilder(ConsumerInterface::class)
            ->getMock();

        $publisherMock = $this->getMockBuilder(PublisherInterface::class)
            ->getMock();

        $container = new Container();
        $container->addPublisher('foo', $publisherMock);
        $container->addConsumer('bar', $consumerMock);

        $this->assertFalse($container->hasPublisher('bar'));
        $this->assertFalse($container->hasConsumer('foo'));
    }
}
