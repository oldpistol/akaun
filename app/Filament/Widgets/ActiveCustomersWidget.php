<?php

namespace App\Filament\Widgets;

use App\Models\Customer;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class ActiveCustomersWidget extends StatsOverviewWidget
{
    protected int|string|array $columnSpan = [
        'md' => 2,
        'xl' => 3,
    ];

    protected function getStats(): array
    {
        $activeCount = Customer::where('is_active', true)->count();
        $inactiveCount = Customer::where('is_active', false)->count();

        return [
            Stat::make('Active Customers', $activeCount)
                ->description("{$inactiveCount} inactive")
                ->descriptionIcon('heroicon-m-check-circle')
                ->color('success'),
        ];
    }
}
