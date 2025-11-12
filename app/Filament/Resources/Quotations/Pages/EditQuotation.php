<?php

namespace App\Filament\Resources\Quotations\Pages;

use App\Enums\QuotationStatus;
use App\Filament\Resources\Quotations\QuotationResource;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use Filament\Support\Icons\Heroicon;

class EditQuotation extends EditRecord
{
    protected static string $resource = QuotationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('download_pdf')
                ->label('Download PDF')
                ->icon(Heroicon::OutlinedArrowDownTray)
                ->url(fn (): string => route('quotations.pdf.download', ['uuid' => $this->record->uuid]))
                ->openUrlInNewTab()
                ->color('success'),
            Action::make('view_pdf')
                ->label('View PDF')
                ->icon(Heroicon::OutlinedEye)
                ->url(fn (): string => route('quotations.pdf.view', ['uuid' => $this->record->uuid]))
                ->openUrlInNewTab()
                ->color('info'),
            Action::make('accept')
                ->label('Accept Quotation')
                ->icon(Heroicon::OutlinedCheck)
                ->color('success')
                ->requiresConfirmation()
                ->visible(fn (): bool => in_array($this->record->status->value, [QuotationStatus::Draft->value, QuotationStatus::Sent->value]))
                ->action(function (): void {
                    $this->record->update([
                        'status' => QuotationStatus::Accepted->value,
                        'accepted_at' => now(),
                    ]);

                    $this->refreshFormData(['status', 'accepted_at']);
                }),
            Action::make('decline')
                ->label('Decline Quotation')
                ->icon(Heroicon::OutlinedXMark)
                ->color('danger')
                ->requiresConfirmation()
                ->visible(fn (): bool => in_array($this->record->status->value, [QuotationStatus::Draft->value, QuotationStatus::Sent->value]))
                ->action(function (): void {
                    $this->record->update([
                        'status' => QuotationStatus::Declined->value,
                        'declined_at' => now(),
                    ]);

                    $this->refreshFormData(['status', 'declined_at']);
                }),
            Action::make('convert_to_invoice')
                ->label('Convert to Invoice')
                ->icon(Heroicon::OutlinedArrowPath)
                ->color('warning')
                ->requiresConfirmation()
                ->visible(fn (): bool => $this->record->status->value === QuotationStatus::Accepted->value)
                ->url(fn (): string => route('filament.admin.resources.invoices.create', ['quotation_id' => $this->record->id]))
                ->tooltip('Create an invoice from this quotation'),
            DeleteAction::make(),
        ];
    }
}
