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
    protected $signature = 'rabbitmq:consume {consumer} {--time=600} {--messages=100} {--memory=128}';

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
     */
    public function handle()
    {
        $messageCount = $this->input->getOption('messages');
        $waitTime = $this->input->getOption('time');
        $memoryLimit = $this->input->getOption('memory');

        /** @var ConsumerInterface $consumer */
        $consumer = $this->getConsumer($this->input->getArgument('consumer'));
        $consumer->startConsuming($messageCount, $waitTime, $memoryLimit);
    }
}
