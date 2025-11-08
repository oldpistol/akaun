<?php

namespace App\Filament\Resources\Customers\Pages;

use App\Filament\Resources\Customers\CustomerResource;
use Application\Customer\UseCases\DeleteCustomerUseCase;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;
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

        // Filament's repeater with relationship() handles addresses automatically
        // We should not manually interfere with address handling
        // Just update the customer fields directly on the Eloquent model
        // to avoid the repository's save() method which deletes all addresses

        // Remove addresses from data as Filament handles them via the relationship
        unset($data['addresses']);

        // Update the model directly
        $record->fill($data);
        $record->save();

        return $record->fresh() ?? $record;
    }
}
