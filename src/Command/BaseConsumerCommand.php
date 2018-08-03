<?php
namespace NeedleProject\LaravelRabbitMq\Command;

use Illuminate\Console\Command;
use NeedleProject\LaravelRabbitMq\ConsumerInterface;

/**
 * Class BaseConsumerCommand
 *
 * @package NeedleProject\LaravelRabbitMq\Command
 */
class BaseConsumerCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'rabbitmq:consume {consumer} {--time=60} {--messages=300} {--memory=64}';

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
    private function getConsumer(string $consumerAliasName): ConsumerInterface
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

        /** @var ConsumerInterface $consumer */
        $consumer = $this->getConsumer($this->input->getArgument('consumer'));
        try {
            return $consumer->startConsuming($messageCount, $waitTime, $memoryLimit);
        } catch (\Throwable $e) {
            $consumer->stopConsuming();
            $this->output->error($e->getMessage());
            return -1;
        }
    }
}
