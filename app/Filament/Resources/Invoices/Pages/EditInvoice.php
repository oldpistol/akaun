<?php

namespace App\Filament\Resources\Invoices\Pages;

use App\Filament\Resources\Invoices\InvoiceResource;
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
                ->url(fn (): string => route('invoices.pdf.download', ['uuid' => $this->record->uuid]))
                ->openUrlInNewTab()
                ->color('success'),
            Action::make('view_pdf')
                ->label('View PDF')
                ->icon(Heroicon::OutlinedEye)
                ->url(fn (): string => route('invoices.pdf.view', ['uuid' => $this->record->uuid]))
                ->openUrlInNewTab()
                ->color('info'),
            DeleteAction::make(),
        ];
    }
}
