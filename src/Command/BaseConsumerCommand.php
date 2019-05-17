<?php
namespace NeedleProject\LaravelRabbitMq\Command;

use Illuminate\Console\Command;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;
use NeedleProject\LaravelRabbitMq\ConsumerInterface;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class BaseConsumerCommand
 *
 * @package NeedleProject\LaravelRabbitMq\Command
 * @author  Adrian Tilita <adrian@tilita.ro>
 */
class BaseConsumerCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'rabbitmq:consume {consumer} {--time=60} {--messages=100} {--memory=64}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Start consuming messages';

    /**
     * @param string $consumerAliasName
     * @return ConsumerInterface
     */
    protected function getConsumer(string $consumerAliasName): ConsumerInterface
    {
        return app()->makeWith(ConsumerInterface::class, [$consumerAliasName]);
    }

    /**
     * Execute the console command.
     * @return int
     */
    public function handle()
    {
        $messageCount = $this->input->getOption('messages');
        $waitTime = $this->input->getOption('time');
        $memoryLimit = $this->input->getOption('memory');
        $isVerbose = in_array(
            $this->output->getVerbosity(),
            [OutputInterface::VERBOSITY_VERBOSE, OutputInterface::VERBOSITY_VERY_VERBOSE]
        );

        /** @var ConsumerInterface $consumer */
        $consumer = $this->getConsumer($this->input->getArgument('consumer'));
        if ($consumer instanceof LoggerAwareInterface && $isVerbose) {
            try {
                $this->injectCliLogger($consumer);
            } catch (\Throwable $e) {
                // Do nothing, we cannot inject a STDOUT logger
            }
        }
        try {
            return $consumer->startConsuming($messageCount, $waitTime, $memoryLimit);
        } catch (\Throwable $e) {
            $consumer->stopConsuming();
            $this->output->error($e->getMessage());
            return -1;
        }
    }

    /**
     * Inject a stdout logger
     *
     * This is a "hackish" method because we handle a interface to deduce an implementation
     * that exposes certain methods.
     *
     * @todo - Find a better way to inject a CLI logger when running in verbose mode
     *
     * @param LoggerAwareInterface $consumerWithLogger
     * @throws \Exception
     */
    protected function injectCliLogger(LoggerAwareInterface $consumerWithLogger)
    {
        $stdHandler = new StreamHandler('php://stdout');
        $class = new \ReflectionClass(get_class($consumerWithLogger));
        $property = $class->getProperty('logger');
        $property->setAccessible(true);
        /** @var LoggerInterface $logger */
        $logger = $property->getValue($consumerWithLogger);
        if ($logger instanceof \Illuminate\Log\LogManager) {
            /** @var Logger $logger */
            $logger = $logger->channel()->getLogger();
            $logger->pushHandler($stdHandler);
        }
        $property->setAccessible(false);
    }
}
