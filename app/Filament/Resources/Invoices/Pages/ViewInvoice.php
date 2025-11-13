<?php

namespace App\Filament\Resources\Invoices\Pages;

use App\Enums\InvoiceStatus;
use App\Filament\Resources\Invoices\InvoiceResource;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Pages\ViewRecord;
use Filament\Schemas\Components\Grid;
use Filament\Support\Icons\Heroicon;
use Infrastructure\Invoice\Persistence\Eloquent\InvoiceModel;

/**
 * @property InvoiceModel $record
 */
class ViewInvoice extends ViewRecord
{
    protected static string $resource = InvoiceResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
            Action::make('mark_as_paid')
                ->label('Mark as Paid')
                ->icon(Heroicon::OutlinedCheckCircle)
                ->color('success')
                ->visible(fn (): bool => $this->record->status !== InvoiceStatus::Paid)
                ->form([
                    Grid::make(2)->schema([
                        DateTimePicker::make('paid_at')
                            ->label('Payment Date')
                            ->required()
                            ->default(now())
                            ->columnSpanFull(),
                        Select::make('payment_method_id')
                            ->label('Payment Method')
                            ->relationship('paymentMethod', 'name', fn ($query) => $query->where('is_active', true)->orderBy('sort_order'))
                            ->searchable()
                            ->preload()
                            ->required(),
                        TextInput::make('payment_reference')
                            ->label('Payment Reference Number')
                            ->maxLength(255)
                            ->placeholder('e.g., Transaction ID, Check Number'),
                        FileUpload::make('payment_receipt_path')
                            ->label('Payment Receipt/Document')
                            ->disk('private')
                            ->directory('payment-receipts')
                            ->acceptedFileTypes(['application/pdf', 'image/*'])
                            ->maxSize(5120)
                            ->downloadable()
                            ->openable()
                            ->columnSpanFull(),
                    ]),
                ])
                ->action(function (array $data): void {
                    $this->record->update([
                        'status' => InvoiceStatus::Paid,
                        'paid_at' => $data['paid_at'],
                        'payment_method_id' => $data['payment_method_id'],
                        'payment_reference' => $data['payment_reference'] ?? null,
                        'payment_receipt_path' => $data['payment_receipt_path'] ?? null,
                    ]);

                    $this->redirect(static::getUrl(['record' => $this->record]));
                })
                ->successNotificationTitle('Invoice marked as paid'),
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
