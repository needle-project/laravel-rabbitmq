<?php
namespace NeedleProject\LaravelRabbitMq\Command;

use PHPUnit\Framework\TestCase;
use NeedleProject\LaravelRabbitMq\ConsumerInterface;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class BaseConsumerCommandTest extends TestCase
{
    public function testHandleCollaboration()
    {
        $consumerMock = $this->getMockBuilder(ConsumerInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
        $inputInterfaceMock = $this->getMockBuilder(InputInterface::class)
            ->getMock();
        $outputInterfaceMock = $this->getMockBuilder(OutputInterface::class)
            ->getMock();

        $consumerCommand = new class($consumerMock, $inputInterfaceMock, $outputInterfaceMock) extends BaseConsumerCommand {

            private $consumerMock = null;

            public function __construct($consumerMock, $inputInterfaceMock, $outputInterfaceMock)
            {
                $this->input = $inputInterfaceMock;
                $this->output = $outputInterfaceMock;
                $this->consumerMock = $consumerMock;
            }

            protected function getConsumer(string $consumerAliasName): ConsumerInterface
            {
                return $this->consumerMock;
            }
        };

        // actual collaboration scenarios
        $inputInterfaceMock->expects($this->exactly(3))
            ->method('getOption')
            ->with($this->logicalOr(
                $this->equalTo('time'),
                $this->equalTo('messages'),
                $this->equalTo('memory')
            ))
            ->will($this->returnCallback(function () {
                switch (func_get_arg(0)) {
                    case 'messages':
                        return 10;
                        break;
                    case 'time':
                        return 20;
                        break;
                    case 'memory':
                        return 128;
                        break;
                    case 'consumer':
                        return 'fooBar';
                        break;
                }
            }));
        $inputInterfaceMock->expects($this->once())
            ->method('getArgument')
            ->willReturn('fooBar');

        $consumerMock->expects($this->once())
            ->method('startConsuming')
            ->with(10, 20, 128)
            ->willReturn(null);

        $consumerCommand->handle();
    }

    public function testFailConsumeCollaboration()
    {
        $consumerMock = $this->getMockBuilder(ConsumerInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
        $inputInterfaceMock = $this->getMockBuilder(InputInterface::class)
            ->getMock();
        $outputInterfaceMock = $this->getMockBuilder(SymfonyStyle::class)
            ->disableOriginalConstructor()
            ->getMock();

        $consumerCommand = new class($consumerMock, $inputInterfaceMock, $outputInterfaceMock) extends BaseConsumerCommand {

            private $consumerMock = null;

            public function __construct($consumerMock, $inputInterfaceMock, $outputInterfaceMock)
            {
                $this->input = $inputInterfaceMock;
                $this->consumerMock = $consumerMock;
                $this->output = $outputInterfaceMock;
            }

            protected function getConsumer(string $consumerAliasName): ConsumerInterface
            {
                return $this->consumerMock;
            }
        };

        // actual collaboration scenarios
        $inputInterfaceMock->expects($this->exactly(3))
            ->method('getOption')
            ->will($this->returnValue(2));
        $inputInterfaceMock->expects($this->once())
            ->method('getArgument')
            ->willReturn('fooBar');

        $consumerMock->expects($this->once())
            ->method('startConsuming')
            ->will($this->throwException(new \Exception('foo')));

        $outputInterfaceMock->expects($this->once())
            ->method('error')
            ->with('foo')
            ->willReturn(null);

        $consumerCommand->handle();
    }
}
