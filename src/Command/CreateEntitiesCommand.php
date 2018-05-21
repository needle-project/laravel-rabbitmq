<?php
namespace NeedleProject\LaravelRabbitMq\Command;

use Illuminate\Console\Command;
use NeedleProject\LaravelRabbitMq\Container;
use NeedleProject\LaravelRabbitMq\Entity\AbstractAMQPEntity;
use NeedleProject\LaravelRabbitMq\Entity\AbstractEntity;
use NeedleProject\LaravelRabbitMq\Publisher\PublisherInterface;

/**
 * Class CreateEntitiesCommand
 *
 * @package NeedleProject\LaravelRabbitMq\Commad
 * @author  Adrian Tilita <adrian@tilita.ro>
 */
class CreateEntitiesCommand extends Command
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
        return (int)$hasErrors;
    }
}
