<?php

declare(strict_types=1);

namespace App\Filament\Resources\PaymentMethods\Schemas;

use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class PaymentMethodInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Payment Method Information')
                    ->columnSpanFull()
                    ->schema([
                        Grid::make(2)->schema([
                            TextEntry::make('name')->label('Payment Method Name'),
                            TextEntry::make('code')
                                ->badge()
                                ->label('Code'),
                            TextEntry::make('description')
                                ->columnSpanFull()
                                ->placeholder('No description provided')
                                ->label('Description'),
                            IconEntry::make('is_active')
                                ->boolean()
                                ->label('Active'),
                            TextEntry::make('sort_order')->label('Sort Order'),
                            TextEntry::make('created_at')->dateTime(),
                            TextEntry::make('updated_at')->dateTime(),
                        ]),
                    ]),
            ]);
    }
}
