<?php

declare(strict_types=1);

namespace App\Filament\Resources\Customers\Schemas;

use App\Enums\CustomerType;
use App\Enums\RiskLevel;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class CustomerForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('General Information')
                    ->columnSpanFull()
                    ->schema([
                        Grid::make(2)->schema([
                            TextInput::make('name')->required()->maxLength(150),
                            Select::make('customer_type')
                                ->options(collect(CustomerType::cases())->mapWithKeys(fn ($c) => [$c->value => $c->value])->all())
                                ->required(),
                            TextInput::make('email')->email()->maxLength(191),
                            TextInput::make('phone_primary')->required()->maxLength(20),
                            TextInput::make('phone_secondary')->maxLength(20),
                            DateTimePicker::make('email_verified_at'),
                            Select::make('risk_level')
                                ->options(collect(RiskLevel::cases())->mapWithKeys(fn ($r) => [$r->value => $r->value])->all())
                                ->native(false),
                            TextInput::make('credit_limit')->numeric()->minValue(0),
                            Toggle::make('is_active')->label('Active')->inline(false),
                            TextInput::make('billing_attention')->maxLength(120),
                        ]),
                    ]),
                Section::make('Identification')
                    ->columnSpanFull()
                    ->schema([
                        Grid::make(2)->schema([
                            TextInput::make('nric')->maxLength(14),
                            TextInput::make('passport_no')->maxLength(20),
                            TextInput::make('company_ssm_no')->maxLength(20),
                            TextInput::make('gst_number')->maxLength(25),
                        ]),
                    ]),
                Section::make('Addresses')
                    ->columnSpanFull()
                    ->schema([
                        Repeater::make('addresses')
                            ->relationship('addresses')
                            ->reorderable(false)
                            ->minItems(1)
                            ->label('')
                            ->schema([
                                TextInput::make('label')
                                    ->maxLength(50)
                                    ->placeholder('e.g., Home, Office'),
                                TextInput::make('line1')
                                    ->required()
                                    ->maxLength(120)
                                    ->label('Address Line 1'),
                                TextInput::make('line2')
                                    ->maxLength(120)
                                    ->label('Address Line 2'),
                                TextInput::make('city')
                                    ->required()
                                    ->maxLength(80),
                                TextInput::make('postcode')
                                    ->required()
                                    ->maxLength(10),
                                Select::make('state_id')
                                    ->relationship('state', 'name')
                                    ->searchable()
                                    ->preload()
                                    ->required()
                                    ->label('State'),
                                TextInput::make('country_code')
                                    ->default('MY')
                                    ->maxLength(2)
                                    ->label('Country'),
                                Toggle::make('is_primary')
                                    ->label('Primary')
                                    ->inline(false),
                            ]),
                    ]),
            ]);
    }
}
