<?php

declare(strict_types=1);

namespace App\Filament\Resources\Invoices\Tables;

use App\Enums\InvoiceStatus;
use Application\Invoice\UseCases\MarkInvoiceAsPaidUseCase;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\RestoreAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;

class InvoicesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('invoice_number')
                    ->searchable()
                    ->sortable()
                    ->label('Invoice #'),
                TextColumn::make('customer.name')
                    ->searchable()
                    ->sortable()
                    ->label('Customer'),
                TextColumn::make('status')
                    ->badge()
                    ->sortable()
                    ->color(fn (InvoiceStatus $state): string => match ($state) {
                        InvoiceStatus::Draft => 'gray',
                        InvoiceStatus::Sent => 'info',
                        InvoiceStatus::Paid => 'success',
                        InvoiceStatus::Overdue => 'danger',
                        InvoiceStatus::Cancelled => 'warning',
                        InvoiceStatus::Void => 'gray',
                    }),
                TextColumn::make('total')
                    ->money('MYR')
                    ->sortable(),
                TextColumn::make('issued_at')
                    ->date()
                    ->sortable()
                    ->label('Issued'),
                TextColumn::make('due_at')
                    ->date()
                    ->sortable()
                    ->label('Due'),
                TextColumn::make('paid_at')
                    ->date()
                    ->sortable()
                    ->label('Paid')
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->options(collect(InvoiceStatus::cases())->mapWithKeys(fn ($s) => [$s->value => $s->value])->all()),
                SelectFilter::make('customer')
                    ->relationship('customer', 'name')
                    ->searchable()
                    ->preload(),
                TrashedFilter::make(),
            ])
            ->recordActions([
                ActionGroup::make([
                    Action::make('mark_as_paid')
                        ->label('Mark as Paid')
                        ->icon(Heroicon::OutlinedCheckCircle)
                        ->color('success')
                        ->visible(fn ($record): bool => $record->status !== InvoiceStatus::Paid)
                        ->schema([
                            DateTimePicker::make('paid_at')
                                ->label('Payment Date')
                                ->default(now())
                                ->required(),
                            Select::make('payment_method')
                                ->label('Payment Method')
                                ->options([
                                    'Cash' => 'Cash',
                                    'Bank Transfer' => 'Bank Transfer',
                                    'Credit Card' => 'Credit Card',
                                    'Debit Card' => 'Debit Card',
                                    'Check' => 'Check',
                                    'PayPal' => 'PayPal',
                                    'Other' => 'Other',
                                ])
                                ->required(),
                            TextInput::make('payment_reference')
                                ->label('Payment Reference/Transaction ID')
                                ->placeholder('e.g., TXN123456, Check #001')
                                ->maxLength(100),
                        ])
                        ->action(function ($record, array $data): void {
                            $useCase = app(MarkInvoiceAsPaidUseCase::class);
                            $useCase->execute(
                                id: $record->id,
                                paidAt: new \DateTimeImmutable((string) $data['paid_at']),
                                paymentMethod: $data['payment_method'],
                                paymentReference: $data['payment_reference'] ?? null
                            );

                            Notification::make()
                                ->success()
                                ->title('Invoice Marked as Paid')
                                ->body("Invoice {$record->invoice_number} has been marked as paid.")
                                ->send();
                        })
                        ->requiresConfirmation()
                        ->modalHeading('Mark Invoice as Paid')
                        ->modalDescription('Enter the payment details to mark this invoice as paid.')
                        ->modalSubmitActionLabel('Mark as Paid'),
                    Action::make('download_receipt')
                        ->label('Download Receipt')
                        ->icon(Heroicon::OutlinedDocumentText)
                        ->url(fn ($record): string => route('invoices.receipt.download', ['uuid' => $record->uuid]))
                        ->openUrlInNewTab()
                        ->color('success')
                        ->visible(fn ($record): bool => $record->status === InvoiceStatus::Paid),
                    Action::make('view_receipt')
                        ->label('View Receipt')
                        ->icon(Heroicon::OutlinedEye)
                        ->url(fn ($record): string => route('invoices.receipt.view', ['uuid' => $record->uuid]))
                        ->openUrlInNewTab()
                        ->color('info')
                        ->visible(fn ($record): bool => $record->status === InvoiceStatus::Paid),
                    Action::make('download_pdf')
                        ->label('Download PDF')
                        ->icon(Heroicon::OutlinedArrowDownTray)
                        ->url(fn ($record): string => route('invoices.pdf.download', ['uuid' => $record->uuid]))
                        ->openUrlInNewTab()
                        ->color('success'),
                    Action::make('view_pdf')
                        ->label('View PDF')
                        ->icon(Heroicon::OutlinedEye)
                        ->url(fn ($record): string => route('invoices.pdf.view', ['uuid' => $record->uuid]))
                        ->openUrlInNewTab()
                        ->color('info'),
                    EditAction::make(),
                    DeleteAction::make(),
                    RestoreAction::make(),
                ]),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                    RestoreBulkAction::make(),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
    }
}
