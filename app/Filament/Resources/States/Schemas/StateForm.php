<?php

declare(strict_types=1);

namespace App\Filament\Resources\States\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class StateForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('State Information')
                    ->columnSpanFull()
                    ->schema([
                        Grid::make(2)->schema([
                            TextInput::make('code')
                                ->required()
                                ->maxLength(30)
                                ->unique(ignoreRecord: true)
                                ->label('State Code'),
                            TextInput::make('name')
                                ->required()
                                ->maxLength(60)
                                ->label('State Name'),
                        ]),
                    ]),
            ]);
    }
}
