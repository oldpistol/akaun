<?php

namespace App\Filament\Resources\Customers\Pages;

use App\Filament\Resources\Customers\CustomerResource;
use Application\Customer\DTOs\UpdateCustomerDTO;
use Application\Customer\UseCases\DeleteCustomerUseCase;
use Application\Customer\UseCases\UpdateCustomerUseCase;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;
use Infrastructure\Customer\Mappers\CustomerMapper;
use Infrastructure\Customer\Persistence\Eloquent\CustomerModel;

class EditCustomer extends EditRecord
{
    protected static string $resource = CustomerResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            DeleteAction::make()
                ->using(function (Model $record): void {
                    /** @var CustomerModel $record */
                    $deleteCustomerUseCase = app(DeleteCustomerUseCase::class);
                    $deleteCustomerUseCase->execute($record->id);
                }),
        ];
    }

    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        /** @var CustomerModel $record */
        $updateCustomerUseCase = app(UpdateCustomerUseCase::class);

        // Convert form data to DTO
        $dto = UpdateCustomerDTO::fromArray($data);

        // Execute use case - this updates the customer via the infrastructure layer
        $domainCustomer = $updateCustomerUseCase->execute($record->id, $dto);

        // Convert domain entity back to Infrastructure Eloquent model
        $mapper = app(CustomerMapper::class);

        return $mapper->toEloquent($domainCustomer);
    }
}
