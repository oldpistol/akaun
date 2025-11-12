<?php

namespace App\Filament\Resources\Invoices\Pages;

use App\Filament\Resources\Invoices\InvoiceResource;
use Application\Invoice\UseCases\GenerateInvoicePDFUseCase;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use Filament\Support\Icons\Heroicon;

class EditInvoice extends EditRecord
{
    protected static string $resource = InvoiceResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('download_pdf')
                ->label('Download PDF')
                ->icon(Heroicon::OutlinedArrowDownTray)
                ->action(function (): mixed {
                    $useCase = app(GenerateInvoicePDFUseCase::class);

                    return $useCase->execute($this->record->uuid);
                })
                ->color('success'),
            Action::make('view_pdf')
                ->label('View PDF')
                ->icon(Heroicon::OutlinedEye)
                ->action(function (): mixed {
                    $useCase = app(GenerateInvoicePDFUseCase::class);

                    return $useCase->stream($this->record->uuid);
                })
                ->color('info')
                ->openUrlInNewTab(),
            DeleteAction::make(),
        ];
    }
}
