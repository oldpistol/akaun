<?php

namespace App\Filament\Widgets;

use App\Models\Customer;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class TotalCreditLimitWidget extends StatsOverviewWidget
{
    protected int|string|array $columnSpan = [
        'md' => 2,
        'xl' => 3,
    ];

    protected function getStats(): array
    {
        $totalCreditLimit = (float) Customer::sum('credit_limit');

        return [
            Stat::make('Total Credit Limit', '$'.number_format($totalCreditLimit, 2))
                ->description('Sum of all customer credit limits')
                ->descriptionIcon('heroicon-m-currency-dollar')
                ->color('warning'),
        ];
    }
}
