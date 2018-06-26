<?php
namespace NeedleProject\LaravelRabbitMq\Command;

use Illuminate\Console\Command;
use NeedleProject\LaravelRabbitMq\Consumer\ConsumerInterface;
use NeedleProject\LaravelRabbitMq\Container;
use NeedleProject\LaravelRabbitMq\Entity\QueueEntity;
use NeedleProject\LaravelRabbitMq\Publisher\PublisherInterface;

/**
 * Class SetupCommand
 *
 * @package NeedleProject\LaravelRabbitMq\Commad
 * @author  Adrian Tilita <adrian@tilita.ro>
 */
class SetupCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'rabbitmq:setup';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create all queues, exchanges and binds that are defined in entities AND referenced to' .
        ' either a publisher or a consumer';

    /**
     * @var Container
     */
    private $container;

    /**
     * CreateEntitiesCommand constructor.
     *
     * @param Container $container
     */
    public function __construct(Container $container)
    {
        $this->container = $container;
        parent::__construct();
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $hasErrors = false;
        /** @var PublisherInterface $entity */
        foreach ($this->container->getPublishers() as $publisherName => $publisher) {
            try {
                $entity = $publisher->getEntity();
                $entity->create();
                $this->output->writeln(
                    sprintf(
                        "Created entity <info>%s</info> for publisher [<fg=yellow>%s</>]",
                        (string)$entity->getName(),
                        (string)$publisherName
                    )
                );
            } catch (\Exception $e) {
                $hasErrors = true;
                $this->output->error(
                    sprintf(
                        "Could not create entity %s for publisher [%s], got:\n%s",
                        (string)$entity->getName(),
                        (string)$publisherName,
                        (string)$e->getMessage()
                    )
                );
            }
        }

        /** @var ConsumerInterface $entity */
        foreach ($this->container->getConsumers() as $consumerAliasName => $consumer) {
            try {
                /** @var QueueEntity $entity */
                $entity = $consumer->getEntity();
                $entity->create();
                $this->output->writeln(
                    sprintf(
                        "Created entity <info>%s</info> for consumer [<fg=yellow>%s</>]",
                        (string)$entity->getName(),
                        (string)$consumerAliasName
                    )
                );
            } catch (\Exception $e) {
                $hasErrors = true;
                $this->output->error(
                    sprintf(
                        "Could not create entity %s for consumer [%s], got:\n%s",
                        (string)$entity->getName(),
                        (string)$consumerAliasName,
                        (string)$e->getMessage()
                    )
                );
            }
        }


        $this->output->block("Create binds");
        /** @var PublisherInterface $entity */
        foreach ($this->container->getPublishers() as $publisherName => $publisher) {
            try {
                $entity = $publisher->getEntity();
                $entity->bind();
                $this->output->writeln(
                    sprintf(
                        "Created bind <info>%s</info> for publisher [<fg=yellow>%s</>]",
                        (string)$entity->getName(),
                        (string)$publisherName
                    )
                );
            } catch (\Exception $e) {
                $hasErrors = true;
                $this->output->error(
                    sprintf(
                        "Could not bind entity %s for publisher [%s], got:\n%s",
                        (string)$entity->getName(),
                        (string)$publisherName,
                        (string)$e->getMessage()
                    )
                );
            }
        }

        /** @var ConsumerInterface $entity */
        foreach ($this->container->getConsumers() as $consumerAliasName => $consumer) {
            try {
                $entity = $consumer->getEntity();
                $entity->bind();
                $this->output->writeln(
                    sprintf(
                        "Bind entity <info>%s</info> for consumer [<fg=yellow>%s</>]",
                        (string)$entity->getName(),
                        (string)$consumerAliasName
                    )
                );
            } catch (\Exception $e) {
                $hasErrors = true;
                $this->output->error(
                    sprintf(
                        "Could not create bind %s for consumer [%s], got:\n%s",
                        (string)$entity->getName(),
                        (string)$consumerAliasName,
                        (string)$e->getMessage()
                    )
                );
            }
        }
        return (int)$hasErrors;
    }
}
