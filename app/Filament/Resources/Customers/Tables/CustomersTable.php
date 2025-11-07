<?php

declare(strict_types=1);

namespace App\Filament\Resources\Customers\Tables;

use App\CustomerType;
use App\RiskLevel;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\RestoreAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class CustomersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')->searchable()->sortable(),
                TextColumn::make('email')->searchable()->sortable(),
                TextColumn::make('phone_primary')->label('Phone')->searchable(),
                TextColumn::make('customer_type')->badge()->sortable(),
                TextColumn::make('primaryAddress.state.name')->label('State')->badge()->sortable(),
                TextColumn::make('risk_level')->badge()->toggleable(isToggledHiddenByDefault: true),
                IconColumn::make('is_active')->boolean()->label('Active')->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('created_at')->dateTime()->sortable()->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('customer_type')
                    ->options(collect(CustomerType::cases())->mapWithKeys(fn ($c) => [$c->value => $c->value])->all()),
                SelectFilter::make('risk_level')
                    ->options(collect(RiskLevel::cases())->mapWithKeys(fn ($r) => [$r->value => $r->value])->all()),
                SelectFilter::make('state')
                    ->label('State')
                    ->relationship('primaryAddress.state', 'name')
                    ->query(fn (Builder $query, array $data): Builder => $query->whereHas('primaryAddress', function (Builder $q) use ($data): void {
                        if (! empty($data['value'])) {
                            $q->where('state_id', $data['value']);
                        }
                    })),
                TrashedFilter::make(),
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
                DeleteAction::make(),
                RestoreAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                    RestoreBulkAction::make(),
                ]),
            ]);
    }
}
