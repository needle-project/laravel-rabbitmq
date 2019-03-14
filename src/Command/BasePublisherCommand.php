<?php
namespace NeedleProject\LaravelRabbitMq\Command;

use Illuminate\Console\Command;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;
use NeedleProject\LaravelRabbitMq\ConsumerInterface;
use NeedleProject\LaravelRabbitMq\Container;
use NeedleProject\LaravelRabbitMq\PublisherInterface;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class BasePublisherCommand
 *
 * @package NeedleProject\LaravelRabbitMq\Command
 * @author  Adrian Tilita <adrian@tilita.ro>
 */
class BasePublisherCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'rabbitmq:publish {publisher} {message}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Publish one message';

    /**
     * @var null|Container
     */
    private $container = null;

    /**
     * BasePublisherCommand constructor.
     *
     * @param Container $container
     */
    public function __construct(Container $container)
    {
        $this->container = $container;
        parent::__construct();
    }

    /**
     * @param string $publisherAliasName
     * @return PublisherInterface
     */
    protected function getPublisher(string $publisherAliasName): PublisherInterface
    {
        return $this->container->getPublisher($publisherAliasName);
    }

    /**
     * Execute the console command.
     * @return int
     */
    public function handle()
    {
        $this->getPublisher($this->input->getArgument('publisher'))
            ->publish($this->input->getArgument('message'));
    }
}