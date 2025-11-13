<?php

declare(strict_types=1);

namespace App\Filament\Resources\Invoices\Schemas;

use App\Enums\InvoiceStatus;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Storage;

class InvoiceInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Invoice Information')
                    ->columnSpanFull()
                    ->schema([
                        Grid::make(3)->schema([
                            TextEntry::make('invoice_number'),
                            TextEntry::make('status')->badge(),
                            TextEntry::make('customer.name')->label('Customer'),
                            TextEntry::make('issued_at')->dateTime(),
                            TextEntry::make('due_at')->dateTime(),
                            TextEntry::make('uuid')->label('UUID')->placeholder('-'),
                        ]),
                    ]),
                Section::make('Payment Information')
                    ->columnSpanFull()
                    ->visible(fn ($record): bool => $record->status === InvoiceStatus::Paid)
                    ->schema([
                        Grid::make(2)->schema([
                            TextEntry::make('paid_at')->dateTime()->placeholder('-'),
                            TextEntry::make('paymentMethod.name')->label('Payment Method')->placeholder('-'),
                            TextEntry::make('payment_reference')->label('Payment Reference')->placeholder('-'),
                            TextEntry::make('payment_receipt_path')
                                ->label('Payment Receipt')
                                ->placeholder('-')
                                ->formatStateUsing(fn ($state) => $state ? 'View Receipt' : '-')
                                ->url(fn ($record) => $record->payment_receipt_path ? Storage::disk('local')->url($record->payment_receipt_path) : null, shouldOpenInNewTab: true),
                        ]),
                    ]),
                Section::make('Invoice Items')
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
                        Grid::make(3)->schema([
                            TextEntry::make('subtotal')->money('myr'),
                            TextEntry::make('tax_total')->money('myr')->label('Tax'),
                            TextEntry::make('total')->money('myr')->weight('bold'),
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
