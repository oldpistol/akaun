<?php

namespace App\Filament\Resources\States\Pages;

use App\Filament\Resources\States\StateResource;
use Application\State\DTOs\UpdateStateDTO;
use Application\State\UseCases\DeleteStateUseCase;
use Application\State\UseCases\UpdateStateUseCase;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;
use Infrastructure\State\Mappers\StateMapper;
use Infrastructure\State\Persistence\Eloquent\StateModel;

class EditState extends EditRecord
{
    protected static string $resource = StateResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make()
                ->using(function (Model $record): void {
                    /** @var StateModel $record */
                    $deleteStateUseCase = app(DeleteStateUseCase::class);
                    $deleteStateUseCase->execute($record->id);
                }),
        ];
    }

    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        /** @var StateModel $record */
        $updateStateUseCase = app(UpdateStateUseCase::class);

        // Add the ID to the data
        $data['id'] = $record->id;

        // Convert form data to DTO
        $dto = UpdateStateDTO::fromArray($data);

        // Execute use case
        $domainState = $updateStateUseCase->execute($dto);

        // Convert domain entity back to Infrastructure Eloquent model
        return StateMapper::toEloquent($domainState);
    }
}
