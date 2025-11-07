<?php

declare(strict_types=1);

namespace App\Filament\Resources\Customers\Schemas;

use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class CustomerInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('General Information')
                    ->columnSpanFull()
                    ->schema([
                        Grid::make(2)->schema([
                            TextEntry::make('name'),
                            TextEntry::make('customer_type')->badge(),
                            TextEntry::make('email')->placeholder('-'),
                            TextEntry::make('phone_primary')->label('Primary Phone'),
                            TextEntry::make('phone_secondary')->label('Secondary Phone')->placeholder('-'),
                            TextEntry::make('email_verified_at')->dateTime()->placeholder('-'),
                            TextEntry::make('risk_level')->badge()->placeholder('-'),
                            TextEntry::make('credit_limit')->money('myr')->placeholder('-'),
                            IconEntry::make('is_active')->boolean()->label('Active'),
                            TextEntry::make('billing_attention')->placeholder('-'),
                        ]),
                    ]),
                Section::make('Identification')
                    ->columnSpanFull()
                    ->schema([
                        Grid::make(2)->schema([
                            TextEntry::make('nric')->placeholder('-'),
                            TextEntry::make('passport_no')->placeholder('-'),
                            TextEntry::make('company_ssm_no')->label('Company SSM No')->placeholder('-'),
                            TextEntry::make('gst_number')->label('GST Number')->placeholder('-'),
                        ]),
                    ]),
                Section::make('Addresses')
                    ->columnSpanFull()
                    ->schema([
                        RepeatableEntry::make('addresses')
                            ->label('')
                            ->schema([
                                Grid::make(2)->schema([
                                    TextEntry::make('label')->placeholder('-'),
                                    TextEntry::make('line1')->label('Address Line 1'),
                                    TextEntry::make('line2')->label('Address Line 2')->placeholder('-'),
                                    TextEntry::make('city'),
                                    TextEntry::make('postcode'),
                                    TextEntry::make('state.name')->label('State'),
                                    TextEntry::make('country_code')->label('Country Code'),
                                    IconEntry::make('is_primary')->boolean()->label('Primary'),
                                ]),
                            ]),
                    ]),
            ]);
    }
}
