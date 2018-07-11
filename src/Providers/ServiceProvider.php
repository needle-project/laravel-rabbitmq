<?php
namespace NeedleProject\LaravelRabbitMq\Providers;

use Illuminate\Foundation\Application;
use Illuminate\Support\ServiceProvider as LaravelServiceProvider;
use NeedleProject\LaravelRabbitMq\Builder\ContainerBuilder;
use NeedleProject\LaravelRabbitMq\Command\BaseConsumerCommand;
use NeedleProject\LaravelRabbitMq\Command\DeleteAllCommand;
use NeedleProject\LaravelRabbitMq\Command\SetupCommand;
use NeedleProject\LaravelRabbitMq\Command\ListEntitiesCommand;
use NeedleProject\LaravelRabbitMq\ConsumerInterface;
use NeedleProject\LaravelRabbitMq\Container;
use NeedleProject\LaravelRabbitMq\Exception\LaravelRabbitMqException;
use NeedleProject\LaravelRabbitMq\PublisherInterface;

/**
 * Class ServiceProvider
 *
 * @package NeedleProject\LaravelRabbitMq\Providers
 * @author  Adrian Tilita <adrian@tilita.ro>
 */
class ServiceProvider extends LaravelServiceProvider
{
    /**
     * Indicates if loading of the provider is deferred.
     *
     * @var bool
     */
    protected $defer = false;

    /**
     * Perform post-registration booting of services.
     *
     * @return void
     * @throws LaravelRabbitMqException
     */
    public function boot()
    {
        $config = config('laravel_rabbitmq');
        $this->app->singleton(
            Container::class,
            function () use ($config) {
                $container = new ContainerBuilder();
                return $container->createContainer($config);
            }
        );

        // Get "tagged" like Publisher
        $this->app->singleton(PublisherInterface::class, function (Application $application, $arguments) {
            /** @var Container $container */
            $container = $application->make(Container::class);
            if (empty($arguments)) {
                throw new \RuntimeException("Cannot make Publisher. No publisher identifier provided!");
            }
            $aliasName = $arguments[0];
            return $container->getPublisher($aliasName);
        });

        // Get "tagged" like Consumers
        $this->app->singleton(ConsumerInterface::class, function (Application $application, $arguments) {
            /** @var Container $container */
            $container = $application->make(Container::class);
            if (empty($arguments)) {
                throw new \RuntimeException("Cannot make Consumer. No consumer identifier provided!");
            }
            $aliasName = $arguments[0];

            if (!$container->hasConsumer($aliasName)) {
                throw new \RuntimeException("Cannot make Consumer.\nNo consumer with alias name {$aliasName} found!");
            }
            return $container->getConsumer($aliasName);
        });

        $this->commands([
            SetupCommand::class,
            ListEntitiesCommand::class,
            BaseConsumerCommand::class,
            DeleteAllCommand::class
        ]);
    }
}
