<?php

declare(strict_types=1);

namespace App\Filament\Resources\Invoices\Schemas;

use App\Enums\InvoiceStatus;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Repeater\TableColumn;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class InvoiceForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(3)
            ->components([
                Section::make('Invoice Details')
                    ->columnSpan(2)
                    ->schema([
                        Grid::make(2)->schema([
                            Select::make('customer_id')
                                ->relationship('customer', 'name')
                                ->searchable()
                                ->preload()
                                ->required()
                                ->columnSpanFull(),
                            TextInput::make('invoice_number')
                                ->maxLength(50)
                                ->unique(ignoreRecord: true)
                                ->placeholder('Auto-generated if left empty'),
                            Select::make('status')
                                ->options(collect(InvoiceStatus::cases())->mapWithKeys(fn ($s) => [$s->value => $s->value])->all())
                                ->required()
                                ->live()
                                ->default(InvoiceStatus::Draft->value),
                            DateTimePicker::make('issued_at')
                                ->required()
                                ->default(now()),
                            DateTimePicker::make('due_at')
                                ->required()
                                ->default(now()->addDays(30)),
                            DateTimePicker::make('paid_at')
                                ->label('Payment Date')
                                ->visible(fn (callable $get): bool => $get('status') === InvoiceStatus::Paid->value)
                                ->requiredIf('status', InvoiceStatus::Paid->value)
                                ->default(now()),
                            Select::make('payment_method_id')
                                ->label('Payment Method')
                                ->relationship('paymentMethod', 'name', fn ($query) => $query->where('is_active', true)->orderBy('sort_order'))
                                ->searchable()
                                ->preload()
                                ->visible(fn (callable $get): bool => $get('status') === InvoiceStatus::Paid->value),
                            TextInput::make('payment_reference')
                                ->label('Payment Reference Number')
                                ->maxLength(255)
                                ->visible(fn (callable $get): bool => $get('status') === InvoiceStatus::Paid->value)
                                ->placeholder('e.g., Transaction ID, Check Number'),
                            FileUpload::make('payment_receipt_path')
                                ->label('Payment Receipt/Document')
                                ->disk('local')
                                ->directory('payment-receipts')
                                ->acceptedFileTypes(['application/pdf', 'image/*'])
                                ->maxSize(5120)
                                ->downloadable()
                                ->openable()
                                ->visible(fn (callable $get): bool => $get('status') === InvoiceStatus::Paid->value)
                                ->columnSpanFull(),
                        ]),
                    ]),

                Section::make('Summary')
                    ->columnSpan(1)
                    ->schema([
                        TextInput::make('subtotal')
                            ->numeric()
                            ->prefix('RM')
                            ->disabled()
                            ->dehydrated(false),
                        TextInput::make('tax_total')
                            ->numeric()
                            ->prefix('RM')
                            ->disabled()
                            ->dehydrated(false),
                        TextInput::make('total')
                            ->numeric()
                            ->prefix('RM')
                            ->disabled()
                            ->dehydrated(false),
                    ]),

                Section::make('Invoice Items')
                    ->columnSpanFull()
                    ->schema([
                        Repeater::make('items')
                            ->relationship('items')
                            ->reorderable(false)
                            ->label('')
                            ->table([
                                TableColumn::make('Description')
                                    ->markAsRequired(),
                                TableColumn::make('Quantity')
                                    ->markAsRequired()
                                    ->width('120px'),
                                TableColumn::make('Unit Price')
                                    ->markAsRequired()
                                    ->width('140px'),
                                TableColumn::make('Tax Rate')
                                    ->markAsRequired()
                                    ->width('120px'),
                            ])
                            ->compact()
                            ->schema([
                                TextInput::make('description')
                                    ->required()
                                    ->maxLength(255),
                                TextInput::make('quantity')
                                    ->required()
                                    ->numeric()
                                    ->minValue(1)
                                    ->default(1),
                                TextInput::make('unit_price')
                                    ->required()
                                    ->numeric()
                                    ->minValue(0)
                                    ->prefix('RM'),
                                TextInput::make('tax_rate')
                                    ->required()
                                    ->numeric()
                                    ->minValue(0)
                                    ->maxValue(100)
                                    ->default(0)
                                    ->suffix('%'),
                            ]),
                    ]),

                Section::make('Additional Information')
                    ->columnSpanFull()
                    ->schema([
                        Textarea::make('notes')
                            ->maxLength(65535)
                            ->rows(3),
                    ]),
            ]);
    }
}
