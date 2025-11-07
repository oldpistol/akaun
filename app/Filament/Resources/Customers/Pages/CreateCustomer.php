<?php

namespace App\Filament\Resources\Customers\Pages;

use App\Filament\Resources\Customers\CustomerResource;
use Application\Customer\DTOs\CreateCustomerDTO;
use Application\Customer\UseCases\CreateCustomerUseCase;
use Filament\Resources\Pages\CreateRecord;
use Infrastructure\Customer\Mappers\CustomerMapper;

class CreateCustomer extends CreateRecord
{
    protected static string $resource = CustomerResource::class;

    protected function handleRecordCreation(array $data): \Illuminate\Database\Eloquent\Model
    {
        $createCustomerUseCase = app(CreateCustomerUseCase::class);

        // Convert form data to DTO
        $dto = CreateCustomerDTO::fromArray($data);

        // Execute use case - this creates the customer via the infrastructure layer
        $domainCustomer = $createCustomerUseCase->execute($dto);

        // Convert domain entity back to Infrastructure Eloquent model
        $mapper = app(CustomerMapper::class);

        return $mapper->toEloquent($domainCustomer);
    }
}
