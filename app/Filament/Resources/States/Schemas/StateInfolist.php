<?php

declare(strict_types=1);

namespace App\Filament\Resources\States\Schemas;

use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class StateInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('State Information')
                    ->columnSpanFull()
                    ->schema([
                        Grid::make(2)->schema([
                            TextEntry::make('code')->label('State Code'),
                            TextEntry::make('name')->label('State Name'),
                            TextEntry::make('created_at')->dateTime(),
                            TextEntry::make('updated_at')->dateTime(),
                        ]),
                    ]),
            ]);
    }
}
