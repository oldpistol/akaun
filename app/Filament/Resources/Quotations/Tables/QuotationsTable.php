<?php

declare(strict_types=1);

namespace App\Filament\Resources\Quotations\Tables;

use App\Enums\QuotationStatus;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\RestoreAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;

class QuotationsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('quotation_number')
                    ->searchable()
                    ->sortable()
                    ->label('Quotation #'),
                TextColumn::make('customer.name')
                    ->searchable()
                    ->sortable()
                    ->label('Customer'),
                TextColumn::make('status')
                    ->badge()
                    ->sortable()
                    ->color(fn (QuotationStatus $state): string => match ($state) {
                        QuotationStatus::Draft => 'gray',
                        QuotationStatus::Sent => 'info',
                        QuotationStatus::Accepted => 'success',
                        QuotationStatus::Declined => 'danger',
                        QuotationStatus::Expired => 'warning',
                        QuotationStatus::Converted => 'success',
                    }),
                TextColumn::make('total')
                    ->money('MYR')
                    ->sortable(),
                TextColumn::make('discount_rate')
                    ->suffix('%')
                    ->sortable()
                    ->label('Discount')
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('issued_at')
                    ->date()
                    ->sortable()
                    ->label('Issued'),
                TextColumn::make('valid_until')
                    ->date()
                    ->sortable()
                    ->label('Valid Until'),
                TextColumn::make('accepted_at')
                    ->date()
                    ->sortable()
                    ->label('Accepted')
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('converted_at')
                    ->date()
                    ->sortable()
                    ->label('Converted')
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->options(collect(QuotationStatus::cases())->mapWithKeys(fn ($s) => [$s->value => $s->value])->all()),
                SelectFilter::make('customer')
                    ->relationship('customer', 'name')
                    ->searchable()
                    ->preload(),
                TrashedFilter::make(),
            ])
            ->recordActions([
                ActionGroup::make([
                    Action::make('download_pdf')
                        ->label('Download PDF')
                        ->icon(Heroicon::OutlinedArrowDownTray)
                        ->url(fn ($record): string => route('quotations.pdf.download', ['uuid' => $record->uuid]))
                        ->openUrlInNewTab()
                        ->color('success'),
                    Action::make('view_pdf')
                        ->label('View PDF')
                        ->icon(Heroicon::OutlinedEye)
                        ->url(fn ($record): string => route('quotations.pdf.view', ['uuid' => $record->uuid]))
                        ->openUrlInNewTab()
                        ->color('info'),
                    Action::make('accept')
                        ->label('Accept')
                        ->icon(Heroicon::OutlinedCheck)
                        ->color('success')
                        ->requiresConfirmation()
                        ->visible(fn ($record): bool => in_array($record->status->value, [QuotationStatus::Draft->value, QuotationStatus::Sent->value]))
                        ->action(function ($record): void {
                            $record->update([
                                'status' => QuotationStatus::Accepted->value,
                                'accepted_at' => now(),
                            ]);
                        }),
                    Action::make('decline')
                        ->label('Decline')
                        ->icon(Heroicon::OutlinedXMark)
                        ->color('danger')
                        ->requiresConfirmation()
                        ->visible(fn ($record): bool => in_array($record->status->value, [QuotationStatus::Draft->value, QuotationStatus::Sent->value]))
                        ->action(function ($record): void {
                            $record->update([
                                'status' => QuotationStatus::Declined->value,
                                'declined_at' => now(),
                            ]);
                        }),
                    Action::make('convert_to_invoice')
                        ->label('Convert to Invoice')
                        ->icon(Heroicon::OutlinedArrowPath)
                        ->color('warning')
                        ->requiresConfirmation()
                        ->modalHeading('Convert Quotation to Invoice')
                        ->modalDescription('This will create a new invoice from this quotation and mark the quotation as converted.')
                        ->visible(fn ($record): bool => $record->status->value === QuotationStatus::Accepted->value)
                        ->action(function ($record): void {
                            $useCase = app(\Application\Quotation\UseCases\ConvertQuotationToInvoiceUseCase::class);

                            $invoice = $useCase->execute($record->id);

                            \Filament\Notifications\Notification::make()
                                ->success()
                                ->title('Quotation Converted')
                                ->body("Invoice {$invoice->invoiceNumber()->value()} has been created from this quotation.")
                                ->send();
                        })
                        ->successRedirectUrl(fn ($record): string => route('filament.admin.resources.invoices.index')),
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
