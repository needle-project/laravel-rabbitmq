<?php
namespace NeedleProject\LaravelRabbitMq\Command;

use Illuminate\Console\Command;
use NeedleProject\LaravelRabbitMq\Container;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Helper\TableSeparator;

/**
 * Class ListEntitiesCommand
 *
 * @package NeedleProject\LaravelRabbitMq\Commad
 * @author  Adrian Tilita <adrian@tilita.ro>
 */
class ListEntitiesCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'rabbitmq:list';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'List all entities by type: producers|consumers';

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
        $table = new Table($this->output);
        $table->setHeaders(array('#', 'Type', 'Name'));

        $rows = [];
        $nr = 1;
        // Publishers
        foreach (array_keys($this->container->getPublishers()) as $publisherName) {
            $rows[] = [
                $nr,
                "<options=bold;fg=yellow>Publisher</>",
                $publisherName,
            ];
            $nr++;
        }
        $rows[] = new TableSeparator();
        // Consumers
        foreach (array_keys($this->container->getConsumers()) as $publisherName) {
            $rows[] = [
                $nr,
                "<options=bold;fg=cyan>Consumer</>",
                $publisherName,
            ];
            $nr++;
        }
        $table->setRows($rows);
        $table->render();
    }
}
