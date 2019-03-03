<?php
namespace NeedleProject\LaravelRabbitMq\Command;

use NeedleProject\LaravelRabbitMq\PublisherInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class BasePublisherCommandTest extends TestCase
{
    public function testHandle()
    {
        $inputMock = $this->getMockBuilder(InputInterface::class)
            ->getMock();
        $outputMock = $this->getMockBuilder(OutputInterface::class)
            ->getMock();
        $publisherMock = $this->getMockBuilder(PublisherInterface::class)
            ->getMock();

        $command = new class($inputMock, $outputMock, $publisherMock) extends BasePublisherCommand {

            private $publisherMock;

            public function __construct($inputMock, $outputMock, $publisherMock)
            {
                $this->input = $inputMock;
                $this->output = $outputMock;
                $this->publisherMock = $publisherMock;
            }

            public function getPublisher(string $publisherAliasName): PublisherInterface
            {
                return $this->publisherMock;
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
                return $argument === 'publisher' ? 'foo' : 'bar';
            }));

        $command->handle();
    }
}
