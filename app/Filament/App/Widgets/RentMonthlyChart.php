<?php

namespace App\Filament\App\Widgets;

use Leandrocfe\FilamentApexCharts\Widgets\ApexChartWidget;
use App\Models\Space;
use Illuminate\Support\Facades\Auth;

class RentMonthlyChart extends ApexChartWidget
{
    /**
     * Chart Id
     *
     * @var string
     */
    protected static ?string $chartId = 'rentMonthlyChart';

    /**
     * Widget Title
     *
     * @var string|null
     */
    protected static ?string $heading = 'Rent Monthly Payment Chart';

      /**
     * Sort
     */
    protected static ?int $sort = 3;

    /**
     * Widget content height
     */
    protected static ?int $contentHeight = 260;

     /**
     * Chart options (series, labels, types, size, animations...)
     * https://apexcharts.com/docs/options
     */
    protected function getOptions(): array
    {
        $monthlyData = $this->getMonthlyRentData();

        return [
            'chart' => [
                'type' => 'line',
                'height' => 250,
                'toolbar' => [
                    'show' => false,
                ],
            ],
            'series' => [
                [
                    'name' => 'Monthly Rent',
                    'data' => $monthlyData['amounts'],
                ],
            ],
            'xaxis' => [
                'categories' => $monthlyData['months'],
                'labels' => [
                    'style' => [
                        'fontWeight' => 400,
                        'fontFamily' => 'inherit',
                    ],
                ],
            ],
            'yaxis' => [
                'labels' => [
                    'style' => [
                        'fontWeight' => 400,
                        'fontFamily' => 'inherit',
                    ],
                ],
            ],
            'fill' => [
                'type' => 'gradient',
                'gradient' => [
                    'shade' => 'dark',
                    'type' => 'horizontal',
                    'shadeIntensity' => 1,
                    'gradientToColors' => ['#ea580c'],
                    'inverseColors' => true,
                    'opacityFrom' => 1,
                    'opacityTo' => 1,
                    'stops' => [0, 100, 100, 100],
                ],
            ],

            'dataLabels' => [
                'enabled' => false,
            ],
            'grid' => [
                'show' => false,
            ],
            'markers' => [
                'size' => 2,
            ],
            'tooltip' => [
                'enabled' => true,
            ],
            'stroke' => [
                'width' => 4,
            ],
            'colors' => ['#f59e0b'],
        ];
    }

    private function getMonthlyRentData(): array
    {
        $currentYear = now()->year;
        $monthlyData = Space::selectRaw('MONTH(created_at) as month, SUM(rent_bills) as total_rent')
            ->whereYear('created_at', $currentYear)
            ->where('rent_payment_status', 'unpaid')
            ->where('user_id', Auth::user()->id)
            ->groupBy('month')
            ->orderBy('month')
            ->get();

        $months = [];
        $amounts = [];

        foreach (range(1, 12) as $month) {
            $monthName = date('M', mktime(0, 0, 0, $month, 1));
            $months[] = $monthName;

            $amount = $monthlyData->firstWhere('month', $month)?->total_rent ?? 0;
            $amounts[] = $amount;
        }

        return [
            'months' => $months,
            'amounts' => $amounts,
        ];
    }
}
