<?php

namespace App\Filament\Admin\Widgets;

use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use App\Models\Payment;
use App\Models\Space;
use Illuminate\Support\Facades\DB;

class StatsOverview extends BaseWidget
{
    /**
     * Sort
     */
    protected static ?int $sort = 1;

    /**
     * Widget content height
     */
    protected static ?int $contentHeight = 270;


    protected function getStats(): array
    {
        $totalRevenue = Space::where('rent_payment_status', 'unpaid')->sum('rent_bills');
        $totalUnpaidRevenue = Space::where('rent_payment_status', 'unpaid')->sum('rent_bills');
        $paidPenalty = Payment::where('payment_status', 'paid')->sum('penalty');

        // Water stats from Spaces
        $unpaidWaterBill = Space::where('water_payment_status', 'unpaid')->sum('water_bills');
        $paidWaterBill = Payment::where('payment_status', 'paid')->sum('water_bill');

        // Electric stats from Spaces
        $unpaidElectricBill = Space::where('electricity_payment_status', 'unpaid')->sum('electricity_bills');
        $paidElectricBill = Payment::where('payment_status', 'paid')->sum('electricity_bill');

        $paidThisMonth = Payment::where('created_at', '>=', now()->startOfMonth())->sum('amount');
        $unpaidThisMonth = Space::where('updated_at', '>=', now()->startOfMonth())->where('rent_payment_status', 'unpaid')
            ->sum('rent_bills') + Space::where('updated_at', '>=', now()->startOfMonth())->where('water_payment_status', 'unpaid')
            ->sum('water_bills') + Space::where('updated_at', '>=', now()->startOfMonth())->where('electricity_payment_status', 'unpaid')
            ->sum('electricity_bills');

        $pastDue = Payment::where('payment_status', 'unpaid')->where('paid_date', '<', now())->sum('amount');

        // $revenueChart = $this->getChartData(Space::class, 'rent_bills');
        // $waterBillChart = $this->getSpaceChartData('water_bills');
        // $electricBillChart = $this->getSpaceChartData('electricity_bills');

        return [
            Stat::make('Total Paid Rent', '₱' . number_format($totalRevenue, 2))
                ->description('₱' . number_format($totalUnpaidRevenue, 2) . ' Total Unpaid Rent')
                ->color('danger')
            // ->description($this->getChangeDescription($revenueChart))
            // ->chart($revenueChart)
            // ->color($this->getChangeColor($revenueChart))
            ,
            Stat::make('Total Paid Water Bills', '₱' . number_format($paidWaterBill, 2))
                ->description('₱' . number_format($unpaidWaterBill, 2) . ' Total Unpaid')
                ->color('danger')
            // ->chart($waterBillChart)
            // ->color($this->getChangeColor($waterBillChart))
            ,
            Stat::make('Total Paid Electric Bills', '₱' . number_format($paidElectricBill, 2))
                ->description('₱' . number_format($unpaidElectricBill, 2) . ' Total Unpaid')
                ->color('danger')
            // ->chart($electricBillChart)
            // ->color($this->getChangeColor($electricBillChart))
            ,
            Stat::make('Total Penalties Collected', '₱' . number_format($paidPenalty, 2))
            // ->chart($electricBillChart)
            // ->color($this->getChangeColor($electricBillChart))
            ,
            Stat::make('Total Paid this Month', '₱' . number_format($paidThisMonth, 2))
                ->description('₱' . number_format($unpaidThisMonth, 2) . ' Total Unpaid This Month')
                ->color('danger')
            // ->chart($electricBillChart)
            // ->color($this->getChangeColor($electricBillChart))
            ,
            Stat::make('Past Due', '₱' . number_format($pastDue, 2))
            // ->chart($electricBillChart)
            // ->color($this->getChangeColor($electricBillChart))
            ,
        ];
    }

    private function getChartData(string $model, string $column, string $aggregation = 'sum'): array
    {
        return Space::query()
            ->where('rent_payment_status', 'unpaid')
            ->where('updated_at', '>=', now()->subDays(7))
            ->groupBy(DB::raw('DATE(updated_at)'))
            ->orderBy('updated_at')
            ->pluck(DB::raw("sum($column) as total"))
            ->toArray();
    }

    private function getSpaceChartData(string $column): array
    {
        return Space::query()
            ->where('updated_at', '>=', now()->subDays(7))
            ->groupBy(DB::raw('DATE(updated_at)'))
            ->orderBy('updated_at')
            ->pluck(DB::raw("sum($column) as total"))
            ->toArray();
    }

    private function getChangeDescription(array $chartData): string
    {
        $change = $this->calculateChange($chartData);
        return abs($change) . '% ' . ($change >= 0 ? 'increase' : 'decrease');
    }

    private function getChangeIcon(array $chartData): string
    {
        $change = $this->calculateChange($chartData);
        return $change >= 0 ? 'heroicon-m-arrow-trending-up' : 'heroicon-m-arrow-trending-down';
    }

    private function getChangeColor(array $chartData): string
    {
        $change = $this->calculateChange($chartData);
        return $change >= 0 ? 'success' : 'danger';
    }

    private function calculateChange(array $chartData): float
    {
        if (count($chartData) < 2) {
            return 0;
        }

        $oldValue = $chartData[0];
        $newValue = end($chartData);

        if ($oldValue == 0) {
            return $newValue > 0 ? 100 : 0;
        }

        return round((($newValue - $oldValue) / $oldValue) * 100, 1);
    }
}
