<?php

declare(strict_types=1);

namespace App\Filament\Resources\Customers\Schemas;

use Filament\Infolists\Components\IconEntry;
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
                Section::make('Customer')
                    ->schema([
                        Grid::make(2)->schema([
                            TextEntry::make('name'),
                            TextEntry::make('customer_type'),
                            TextEntry::make('email')->label('Email'),
                            TextEntry::make('phone_primary')->label('Phone'),
                            TextEntry::make('phone_secondary')->label('Alt Phone')->placeholder('-'),
                            TextEntry::make('risk_level')->placeholder('-'),
                            TextEntry::make('credit_limit')->money('myr')->placeholder('-'),
                            IconEntry::make('is_active')->boolean(),
                        ]),
                        Grid::make(2)->schema([
                            TextEntry::make('nric')->placeholder('-'),
                            TextEntry::make('passport_no')->placeholder('-'),
                            TextEntry::make('company_ssm_no')->placeholder('-'),
                            TextEntry::make('gst_number')->placeholder('-'),
                        ])->columns(4),
                    ]),
                Section::make('Primary Address')
                    ->schema([
                        TextEntry::make('primaryAddress.line1')->label('Line 1'),
                        TextEntry::make('primaryAddress.line2')->placeholder('-')->label('Line 2'),
                        TextEntry::make('primaryAddress.city')->label('City'),
                        TextEntry::make('primaryAddress.postcode')->label('Postcode'),
                        TextEntry::make('primaryAddress.state.name')->label('State'),
                        TextEntry::make('primaryAddress.country_code')->label('Country'),
                    ]),
                Section::make('Meta')
                    ->schema([
                        TextEntry::make('email_verified_at')->dateTime()->placeholder('-'),
                        TextEntry::make('created_at')->dateTime(),
                        TextEntry::make('updated_at')->dateTime(),
                        TextEntry::make('deleted_at')->dateTime()->placeholder('-'),
                    ]),
            ]);
    }
}
