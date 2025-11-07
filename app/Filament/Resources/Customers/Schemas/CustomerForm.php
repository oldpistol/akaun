<?php

declare(strict_types=1);

namespace App\Filament\Resources\Customers\Schemas;

use App\CustomerType;
use App\RiskLevel;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Fieldset;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Schema;

class CustomerForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Tabs::make('Customer')
                    ->tabs([
                        Tab::make('General')
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
                                Section::make('Identification')
                                    ->schema([
                                        TextInput::make('nric')->maxLength(14),
                                        TextInput::make('passport_no')->maxLength(20),
                                        TextInput::make('company_ssm_no')->maxLength(20),
                                        TextInput::make('gst_number')->maxLength(25),
                                    ]),
                            ]),
                        Tab::make('Addresses')
                            ->schema([
                                Repeater::make('addresses')
                                    ->relationship('addresses')
                                    ->reorderable(false)
                                    ->minItems(1)
                                    ->schema([
                                        Fieldset::make('Address')
                                            ->schema([
                                                TextInput::make('label')->maxLength(50),
                                                TextInput::make('line1')->required()->maxLength(120),
                                                TextInput::make('line2')->maxLength(120),
                                                TextInput::make('city')->required()->maxLength(80),
                                                TextInput::make('postcode')->required()->maxLength(10),
                                                Select::make('state_id')->relationship('state', 'name')->searchable()->preload()->required(),
                                                TextInput::make('country_code')->default('MY')->maxLength(2),
                                                Toggle::make('is_primary')->label('Primary')->inline(false),
                                            ]),
                                    ]),
                            ]),
                    ]),
            ]);
    }
}
