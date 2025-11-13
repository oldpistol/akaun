<?php

declare(strict_types=1);

namespace App\Filament\Resources\Quotations\Schemas;

use App\Enums\QuotationStatus;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class QuotationForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(3)
            ->components([
                Section::make('Quotation Details')
                    ->columnSpan(2)
                    ->schema([
                        Grid::make(2)->schema([
                            Select::make('customer_id')
                                ->relationship('customer', 'name')
                                ->searchable()
                                ->preload()
                                ->required()
                                ->columnSpanFull(),
                            TextInput::make('quotation_number')
                                ->maxLength(50)
                                ->unique(ignoreRecord: true)
                                ->placeholder('Auto-generated if left empty'),
                            Select::make('status')
                                ->options(collect(QuotationStatus::cases())->mapWithKeys(fn ($s) => [$s->value => $s->value])->all())
                                ->required()
                                ->default(QuotationStatus::Draft->value),
                            DateTimePicker::make('issued_at')
                                ->required()
                                ->default(now()),
                            DateTimePicker::make('valid_until')
                                ->required()
                                ->default(now()->addDays(30))
                                ->label('Valid Until'),
                            TextInput::make('discount_rate')
                                ->numeric()
                                ->minValue(0)
                                ->maxValue(100)
                                ->default(0)
                                ->suffix('%')
                                ->label('Discount Rate'),
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
                        TextInput::make('discount_amount')
                            ->numeric()
                            ->prefix('RM')
                            ->disabled()
                            ->dehydrated(false)
                            ->label('Discount'),
                        TextInput::make('total')
                            ->numeric()
                            ->prefix('RM')
                            ->disabled()
                            ->dehydrated(false),
                    ]),

                Section::make('Quotation Items')
                    ->columnSpanFull()
                    ->schema([
                        Repeater::make('items')
                            ->relationship('items')
                            ->reorderable(false)
                            ->label('')
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
