<?php
namespace NeedleProject\LaravelRabbitMq\Command;

// Helper function for guaranteed console output (global namespace)
if (!function_exists('rmq_log')) {
    function rmq_log(string $message) {
        // Use fwrite to stderr for guaranteed console output
        // In tests, STDERR might not be available, so we check first
        if (defined('STDERR') && is_resource(STDERR)) {
            @fwrite(STDERR, $message . PHP_EOL);
        } elseif (function_exists('error_log')) {
            // Fallback to error_log if STDERR is not available (e.g., in tests)
            @error_log($message);
        }
    }
}

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
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
    protected $signature = 'rabbitmq:consume {consumer} {--time=60} {--messages=100} {--memory=128}';

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
        return app()->make(ConsumerInterface::class, [$consumerAliasName]);
    }

    public function handle()
    {
        $messageCount = (int)$this->input->getOption('messages');
        $waitTime = (int)$this->input->getOption('time');
        $memoryLimit = (int)$this->input->getOption('memory');
        $consumerName = $this->input->getArgument('consumer');

        $isVerbose = in_array(
            $this->output->getVerbosity(),
            [OutputInterface::VERBOSITY_VERBOSE, OutputInterface::VERBOSITY_VERY_VERBOSE]
        );

        $this->info("Initializing consumer: {$consumerName}");
        $this->info("Parameters: messages={$messageCount}, time={$waitTime}, memory={$memoryLimit}MB");

        try {
            /** @var ConsumerInterface $consumer */
            $consumer = $this->getConsumer($consumerName);
            $this->info("Consumer created successfully");

            if ($consumer instanceof LoggerAwareInterface && $isVerbose) {
                try {
                    $this->injectCliLogger($consumer);
                } catch (\Throwable $e) {
                    // Do nothing, we cannot inject a STDOUT logger
                }
            }

            $this->info("Starting to consume messages...");
            $result = $consumer->startConsuming($messageCount, $waitTime, $memoryLimit);
            $this->info("Consumer finished with code: {$result}");
            return $result;
        } catch (\Throwable $e) {
            rmq_log(sprintf(
                "[RMQ] ERROR in consumer: %s - %s",
                get_class($e),
                $e->getMessage()
            ));

            $this->error("Error in consumer: " . $e->getMessage());
            $this->error("File: " . $e->getFile() . ":" . $e->getLine());
            if ($this->output->isVerbose()) {
                $this->error($e->getTraceAsString());
            }
            if (isset($consumer)) {
                try {
                    $consumer->stopConsuming();
                } catch (\Throwable $stopException) {
                    // Ignore errors when stopping
                }
            }
            throw $e;
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
