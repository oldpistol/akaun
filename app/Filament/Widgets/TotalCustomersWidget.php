<?php

namespace App\Filament\Widgets;

use App\Models\Customer;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class TotalCustomersWidget extends StatsOverviewWidget
{
    protected int|string|array $columnSpan = [
        'md' => 2,
        'xl' => 3,
    ];

    protected function getStats(): array
    {
        return [
            Stat::make('Total Customers', Customer::count())
                ->description('All customers in the system')
                ->descriptionIcon('heroicon-m-users')
                ->color('primary'),
        ];
    }
}
