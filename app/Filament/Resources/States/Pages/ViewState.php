<?php

namespace App\Filament\Resources\States\Pages;

use App\Filament\Resources\States\StateResource;
use Application\State\UseCases\DeleteStateUseCase;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;
use Illuminate\Database\Eloquent\Model;
use Infrastructure\State\Persistence\Eloquent\StateModel;

class ViewState extends ViewRecord
{
    protected static string $resource = StateResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
            DeleteAction::make()
                ->using(function (Model $record): void {
                    /** @var StateModel $record */
                    $deleteStateUseCase = app(DeleteStateUseCase::class);
                    $deleteStateUseCase->execute($record->id);
                }),
        ];
    }
}
