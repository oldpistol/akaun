<?php

namespace App\Filament\Widgets;

use App\Enums\RiskLevel;
use App\Models\Customer;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class CustomerStatsOverview extends StatsOverviewWidget
{
    protected function getStats(): array
    {
        $activeCount = Customer::where('is_active', true)->count();
        $inactiveCount = Customer::where('is_active', false)->count();
        $totalCreditLimit = (float) Customer::sum('credit_limit');
        $highRiskCount = Customer::where('risk_level', RiskLevel::High)->count();

        return [
            Stat::make('Total Customers', Customer::count())
                ->description('All customers in the system')
                ->descriptionIcon('heroicon-m-users')
                ->color('primary'),
            Stat::make('Active Customers', $activeCount)
                ->description("{$inactiveCount} inactive")
                ->descriptionIcon('heroicon-m-check-circle')
                ->color('success'),
            Stat::make('Total Credit Limit', '$'.number_format($totalCreditLimit, 2))
                ->description('Sum of all customer credit limits')
                ->descriptionIcon('heroicon-m-currency-dollar')
                ->color('warning'),
            Stat::make('High Risk Customers', $highRiskCount)
                ->description('Customers with high risk level')
                ->descriptionIcon('heroicon-m-exclamation-triangle')
                ->color('danger'),
        ];
    }
}
