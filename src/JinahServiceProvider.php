<?php

namespace AnyTech\Jinah;

use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;
use AnyTech\Jinah\Commands\JinahCommand;
use AnyTech\Jinah\Services\WebhookVerificationService;

class JinahServiceProvider extends PackageServiceProvider
{
    public function configurePackage(Package $package): void
    {
        /*
         * This class is a Package Service Provider
         *
         * More info: https://github.com/spatie/laravel-package-tools
         */
        $package
            ->name('jinah')
            ->hasConfigFile()
            ->hasViews()
            ->hasRoute('webhook')
            ->hasRoute('payment')
            ->hasMigration('create_jinah_table')
            ->hasCommand(JinahCommand::class);
    }

    public function packageRegistered(): void
    {
        $this->app->singleton('jinah', function ($app) {
            $config = config('jinah');
            return new Jinah($config);
        });
        
        // Register the payment service factory
        $this->app->singleton('jinah.factory', function ($app) {
            $config = config('jinah');
            return new \AnyTech\Jinah\Factories\PaymentServiceFactory($config);
        });

        // Allow creating specific payment services
        $this->app->bind('jinah.service', function ($app, $parameters) {
            $serviceName = $parameters['service'] ?? null;
            $factory = $app->make('jinah.factory');
            return $factory->create($serviceName);
        });

        // Register webhook verification service
        $this->app->singleton(WebhookVerificationService::class, function ($app) {
            $config = config('jinah');
            return new WebhookVerificationService($config);
        });
    }

    public function packageBooted(): void
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([
                $this->package->basePath('/../config/jinah.php') => config_path('jinah.php'),
            ], 'jinah-config');

            $this->publishes([
                $this->package->basePath('/../resources/views') => resource_path('views/vendor/jinah'),
            ], 'jinah-views');

            $this->publishes([
                $this->package->basePath('/../database/migrations') => database_path('migrations'),
            ], 'jinah-migrations');

            $this->publishes([
                $this->package->basePath('/../resources/assets') => public_path('vendor/jinah'),
            ], 'jinah-assets');
        }
    }
}
