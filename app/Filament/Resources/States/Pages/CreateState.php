<?php

namespace App\Filament\Resources\States\Pages;

use App\Filament\Resources\States\StateResource;
use Application\State\DTOs\CreateStateDTO;
use Application\State\UseCases\CreateStateUseCase;
use Filament\Resources\Pages\CreateRecord;
use Infrastructure\State\Mappers\StateMapper;

class CreateState extends CreateRecord
{
    protected static string $resource = StateResource::class;

    protected function handleRecordCreation(array $data): \Illuminate\Database\Eloquent\Model
    {
        $createStateUseCase = app(CreateStateUseCase::class);

        // Convert form data to DTO
        $dto = CreateStateDTO::fromArray($data);

        // Execute use case
        $domainState = $createStateUseCase->execute($dto);

        // Convert domain entity back to Infrastructure Eloquent model
        return StateMapper::toEloquent($domainState);
    }
}
