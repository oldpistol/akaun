<?php

declare(strict_types=1);

namespace App\Filament\Resources\Quotations\Pages;

use App\Enums\QuotationStatus;
use App\Filament\Resources\Quotations\QuotationResource;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;
use Filament\Support\Icons\Heroicon;
use Infrastructure\Quotation\Persistence\Eloquent\QuotationModel;

/**
 * @property QuotationModel $record
 */
class ViewQuotation extends ViewRecord
{
    protected static string $resource = QuotationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
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

                    $this->redirect(static::getUrl(['record' => $this->record]));
                })
                ->successNotificationTitle('Quotation accepted'),
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

                    $this->redirect(static::getUrl(['record' => $this->record]));
                })
                ->successNotificationTitle('Quotation declined'),
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
