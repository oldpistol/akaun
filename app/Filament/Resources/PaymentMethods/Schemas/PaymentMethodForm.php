<?php

declare(strict_types=1);

namespace App\Filament\Resources\PaymentMethods\Schemas;

use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class PaymentMethodForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Payment Method Information')
                    ->columnSpanFull()
                    ->schema([
                        Grid::make(2)->schema([
                            TextInput::make('name')
                                ->required()
                                ->maxLength(100)
                                ->placeholder('e.g., PayPal')
                                ->label('Payment Method Name'),
                            TextInput::make('code')
                                ->required()
                                ->unique(ignoreRecord: true)
                                ->maxLength(50)
                                ->placeholder('e.g., PAYPAL')
                                ->helperText('Unique identifier for this payment method (will be converted to uppercase)')
                                ->label('Code'),
                        ]),
                        Textarea::make('description')
                            ->rows(3)
                            ->maxLength(500)
                            ->placeholder('Optional description of this payment method')
                            ->label('Description'),
                        Grid::make(2)->schema([
                            Toggle::make('is_active')
                                ->label('Active')
                                ->default(true)
                                ->inline(false)
                                ->helperText('Inactive payment methods will not be available for selection'),
                            TextInput::make('sort_order')
                                ->numeric()
                                ->default(0)
                                ->minValue(0)
                                ->helperText('Lower numbers appear first')
                                ->label('Sort Order'),
                        ]),
                    ]),
            ]);
    }
}
