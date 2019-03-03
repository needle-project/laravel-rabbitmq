<?php
namespace NeedleProject\LaravelRabbitMq\Command;

use NeedleProject\LaravelRabbitMq\Container;
use NeedleProject\LaravelRabbitMq\PublisherInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class BasePublisherCommandTest extends TestCase
{
    public function testHandle()
    {
        $containerMock = $this->getMockBuilder(Container::class)
            ->disableOriginalConstructor()
            ->getMock();

        $inputMock = $this->getMockBuilder(InputInterface::class)
            ->getMock();
        $outputMock = $this->getMockBuilder(OutputInterface::class)
            ->getMock();
        $publisherMock = $this->getMockBuilder(PublisherInterface::class)
            ->getMock();

        $command = new class($inputMock, $outputMock, $containerMock) extends BasePublisherCommand {
            public function __construct($inputMock, $outputMock, $containerMock)
            {
                $this->input = $inputMock;
                $this->output = $outputMock;
                parent::__construct($containerMock);
            }
        };

        $publisherMock->expects($this->once())
            ->method('publish')
            ->with('bar')
            ->willReturn(null);

        $inputMock->expects($this->exactly(2))
            ->method('getArgument')
            ->with(
                $this->logicalOr(
                    $this->equalTo('publisher'),
                    $this->equalTo('message')
                )
            )
            ->will($this->returnCallback(function ($argument) {
                return $argument === 'publisher' ? 'fooPublisher' : 'bar';
            }));

        $containerMock->expects($this->once())
            ->method('getPublisher')
            ->with('fooPublisher')
            ->willReturn($publisherMock);

        $command->handle();
    }
}
