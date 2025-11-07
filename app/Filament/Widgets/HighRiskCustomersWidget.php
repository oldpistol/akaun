<?php

namespace App\Filament\Widgets;

use App\Enums\RiskLevel;
use App\Models\Customer;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class HighRiskCustomersWidget extends StatsOverviewWidget
{
    protected int|string|array $columnSpan = [
        'md' => 2,
        'xl' => 3,
    ];

    protected function getStats(): array
    {
        $highRiskCount = Customer::where('risk_level', RiskLevel::High)->count();

        return [
            Stat::make('High Risk Customers', $highRiskCount)
                ->description('Customers with high risk level')
                ->descriptionIcon('heroicon-m-exclamation-triangle')
                ->color('danger'),
        ];
    }
}
