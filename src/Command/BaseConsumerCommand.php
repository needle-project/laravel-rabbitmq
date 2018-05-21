<?php
namespace NeedleProject\LaravelRabbitMq\Command;

use Illuminate\Console\Command;
use NeedleProject\LaravelRabbitMq\Consumer\BasicProcessor;
use NeedleProject\LaravelRabbitMq\Consumer\ConsumerInterface;
use NeedleProject\LaravelRabbitMq\Entity\AMQPQueueEntity;
use NeedleProject\LaravelRabbitMq\Entity\EntityCollection;
use NeedleProject\LaravelRabbitMq\Producer\ProducerCollection;

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
    protected $signature = 'rabbitmq:consume {consumer}';

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
        /** @var ConsumerInterface $consumer */
        $consumer = $this->getConsumer($this->input->getArgument('consumer'));
        $consumer->startConsuming();
    }
}
