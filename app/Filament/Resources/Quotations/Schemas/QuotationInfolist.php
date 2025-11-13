<?php

declare(strict_types=1);

namespace App\Filament\Resources\Quotations\Schemas;

use App\Enums\QuotationStatus;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class QuotationInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Quotation Information')
                    ->columnSpanFull()
                    ->schema([
                        Grid::make(3)->schema([
                            TextEntry::make('quotation_number'),
                            TextEntry::make('status')->badge(),
                            TextEntry::make('customer.name')->label('Customer'),
                            TextEntry::make('issued_at')->dateTime(),
                            TextEntry::make('valid_until')->dateTime(),
                            TextEntry::make('uuid')->label('UUID')->placeholder('-'),
                        ]),
                    ]),
                Section::make('Status Details')
                    ->columnSpanFull()
                    ->visible(fn ($record): bool => in_array($record->status, [QuotationStatus::Accepted, QuotationStatus::Declined, QuotationStatus::Converted]))
                    ->schema([
                        Grid::make(3)->schema([
                            TextEntry::make('accepted_at')
                                ->dateTime()
                                ->placeholder('-')
                                ->visible(fn ($record): bool => $record->status === QuotationStatus::Accepted || $record->status === QuotationStatus::Converted),
                            TextEntry::make('declined_at')
                                ->dateTime()
                                ->placeholder('-')
                                ->visible(fn ($record): bool => $record->status === QuotationStatus::Declined),
                            TextEntry::make('converted_at')
                                ->dateTime()
                                ->placeholder('-')
                                ->visible(fn ($record): bool => $record->status === QuotationStatus::Converted),
                            TextEntry::make('convertedInvoice.invoice_number')
                                ->label('Converted Invoice')
                                ->placeholder('-')
                                ->url(fn ($record) => $record->convertedInvoice ? route('filament.admin.resources.invoices.view', ['record' => $record->convertedInvoice->id]) : null)
                                ->visible(fn ($record): bool => $record->status === QuotationStatus::Converted),
                        ]),
                    ]),
                Section::make('Quotation Items')
                    ->columnSpanFull()
                    ->schema([
                        RepeatableEntry::make('items')
                            ->label('')
                            ->schema([
                                Grid::make(4)->schema([
                                    TextEntry::make('description'),
                                    TextEntry::make('quantity')->numeric(),
                                    TextEntry::make('unit_price')
                                        ->money('myr')
                                        ->label('Unit Price'),
                                    TextEntry::make('tax_rate')
                                        ->suffix('%')
                                        ->label('Tax Rate'),
                                ]),
                            ]),
                    ]),
                Section::make('Totals')
                    ->columnSpanFull()
                    ->schema([
                        Grid::make(4)->schema([
                            TextEntry::make('subtotal')->money('myr'),
                            TextEntry::make('tax_total')->money('myr')->label('Tax'),
                            TextEntry::make('discount_amount')
                                ->money('myr')
                                ->label('Discount'),
                            TextEntry::make('total')->money('myr')->weight('bold'),
                        ]),
                        Grid::make(4)->schema([
                            TextEntry::make('discount_rate')
                                ->suffix('%')
                                ->label('Discount Rate')
                                ->visible(fn ($record): bool => $record->discount_rate > 0),
                        ]),
                    ]),
                Section::make('Additional Information')
                    ->columnSpanFull()
                    ->visible(fn ($record): bool => ! empty($record->notes))
                    ->schema([
                        TextEntry::make('notes')
                            ->placeholder('-')
                            ->columnSpanFull(),
                    ]),
            ]);
    }
}
