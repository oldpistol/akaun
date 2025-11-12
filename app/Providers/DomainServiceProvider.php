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

        // Bind Invoice Repository Interface to Eloquent Implementation
        $this->app->bind(
            \Domain\Invoice\Repositories\InvoiceRepositoryInterface::class,
            \Infrastructure\Invoice\Repositories\EloquentInvoiceRepository::class
        );

        // Bind Invoice Mappers
        $this->app->singleton(\Infrastructure\Invoice\Mappers\InvoiceItemMapper::class);
        $this->app->singleton(\Infrastructure\Invoice\Mappers\InvoiceMapper::class, function (\Illuminate\Contracts\Foundation\Application $app) {
            return new \Infrastructure\Invoice\Mappers\InvoiceMapper(
                $app->make(\Infrastructure\Invoice\Mappers\InvoiceItemMapper::class)
            );
        });

        // Bind Invoice Use Cases
        $this->app->bind(\Application\Invoice\UseCases\CreateInvoiceUseCase::class);
        $this->app->bind(\Application\Invoice\UseCases\UpdateInvoiceUseCase::class);
        $this->app->bind(\Application\Invoice\UseCases\DeleteInvoiceUseCase::class);
        $this->app->bind(\Application\Invoice\UseCases\GetInvoiceUseCase::class);
        $this->app->bind(\Application\Invoice\UseCases\ListInvoicesUseCase::class);
        $this->app->bind(\Application\Invoice\UseCases\MarkInvoiceAsPaidUseCase::class);

        // Bind Quotation Repository Interface to Eloquent Implementation
        $this->app->bind(
            \Domain\Quotation\Repositories\QuotationRepositoryInterface::class,
            \Infrastructure\Quotation\Repositories\EloquentQuotationRepository::class
        );

        // Bind Quotation Mappers
        $this->app->singleton(\Infrastructure\Quotation\Mappers\QuotationItemMapper::class);
        $this->app->singleton(\Infrastructure\Quotation\Mappers\QuotationMapper::class, function (\Illuminate\Contracts\Foundation\Application $app) {
            return new \Infrastructure\Quotation\Mappers\QuotationMapper(
                $app->make(\Infrastructure\Quotation\Mappers\QuotationItemMapper::class)
            );
        });

        // Bind Quotation Use Cases
        $this->app->bind(\Application\Quotation\UseCases\CreateQuotationUseCase::class);
        $this->app->bind(\Application\Quotation\UseCases\UpdateQuotationUseCase::class);
        $this->app->bind(\Application\Quotation\UseCases\DeleteQuotationUseCase::class);
        $this->app->bind(\Application\Quotation\UseCases\GetQuotationUseCase::class);
        $this->app->bind(\Application\Quotation\UseCases\ListQuotationsUseCase::class);
        $this->app->bind(\Application\Quotation\UseCases\AcceptQuotationUseCase::class);
        $this->app->bind(\Application\Quotation\UseCases\DeclineQuotationUseCase::class);
        $this->app->bind(\Application\Quotation\UseCases\ConvertQuotationToInvoiceUseCase::class);
        $this->app->bind(\Application\Quotation\UseCases\GenerateQuotationPDFUseCase::class);
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        //
    }
}
