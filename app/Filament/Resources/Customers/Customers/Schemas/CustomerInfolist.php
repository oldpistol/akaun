<?php

declare(strict_types=1);

namespace App\Filament\Resources\Customers\Customers\Schemas;

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
                Section::make('Address')
                    ->schema([
                        TextEntry::make('address_line1'),
                        TextEntry::make('address_line2')->placeholder('-'),
                        TextEntry::make('city'),
                        TextEntry::make('postcode'),
                        TextEntry::make('state'),
                        TextEntry::make('country_code'),
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
