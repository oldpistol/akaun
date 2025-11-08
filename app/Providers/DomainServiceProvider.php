<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class DomainServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        // Bind Customer Repository Interface to Eloquent Implementation
        $this->app->bind(
            \Domain\Customer\Repositories\CustomerRepositoryInterface::class,
            \Infrastructure\Customer\Repositories\EloquentCustomerRepository::class
        );

        // Bind Mappers
        $this->app->singleton(\Infrastructure\Customer\Mappers\AddressMapper::class);
        $this->app->singleton(\Infrastructure\Customer\Mappers\CustomerMapper::class, function (\Illuminate\Contracts\Foundation\Application $app) {
            return new \Infrastructure\Customer\Mappers\CustomerMapper(
                $app->make(\Infrastructure\Customer\Mappers\AddressMapper::class)
            );
        });

        // Bind Use Cases
        $this->app->bind(\Application\Customer\UseCases\CreateCustomerUseCase::class);
        $this->app->bind(\Application\Customer\UseCases\UpdateCustomerUseCase::class);
        $this->app->bind(\Application\Customer\UseCases\DeleteCustomerUseCase::class);
        $this->app->bind(\Application\Customer\UseCases\GetCustomerUseCase::class);
        $this->app->bind(\Application\Customer\UseCases\ListCustomersUseCase::class);

        // Bind State Repository Interface to Eloquent Implementation
        $this->app->bind(
            \Domain\State\Repositories\StateRepositoryInterface::class,
            \Infrastructure\State\Repositories\EloquentStateRepository::class
        );

        // Bind State Mapper
        $this->app->singleton(\Infrastructure\State\Mappers\StateMapper::class);

        // Bind State Use Cases
        $this->app->bind(\Application\State\UseCases\CreateStateUseCase::class);
        $this->app->bind(\Application\State\UseCases\UpdateStateUseCase::class);
        $this->app->bind(\Application\State\UseCases\DeleteStateUseCase::class);
        $this->app->bind(\Application\State\UseCases\GetStateUseCase::class);
        $this->app->bind(\Application\State\UseCases\ListStatesUseCase::class);
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        //
    }
}
