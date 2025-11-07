<?php

namespace App\Filament\Widgets;

use App\Enums\RiskLevel;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Infrastructure\Customer\Persistence\Eloquent\CustomerModel;

class CustomerStatsOverview extends StatsOverviewWidget
{
    protected function getStats(): array
    {
        $activeCount = CustomerModel::where('is_active', true)->count();
        $inactiveCount = CustomerModel::where('is_active', false)->count();
        $totalCreditLimit = (float) CustomerModel::sum('credit_limit');
        $highRiskCount = CustomerModel::where('risk_level', RiskLevel::High)->count();

        return [
            Stat::make('Total Customers', CustomerModel::count())
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
