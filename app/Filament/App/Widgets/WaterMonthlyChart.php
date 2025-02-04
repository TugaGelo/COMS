<?php

namespace App\Filament\App\Widgets;

use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Radio;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Illuminate\Support\Facades\Auth;
use Leandrocfe\FilamentApexCharts\Widgets\ApexChartWidget;
use Illuminate\Support\Facades\DB;
use App\Models\Space;

class WaterMonthlyChart extends ApexChartWidget
{
    /**
     * Chart Id
     *
     * @var string
     */
    protected static ?string $chartId = 'waterMonthlyChart';

    /**
     * Widget Title
     *
     * @var string|null
     */
    protected static ?string $heading = 'Water Consumption Monthly Chart';

     /**
     * Sort
     */
    protected static ?int $sort = 2;

    /**
     * Widget content height
     */
    protected static ?int $contentHeight = 260;

     /**
     * Filter Form
     */
    protected function getFormSchema(): array
    {
        return [

            Radio::make('ordersChartType')
                ->default('bar')
                ->options([
                    'line' => 'Line',
                    'bar' => 'Col',
                    'area' => 'Area',
                ])
                ->inline(true)
                ->label('Type'),

            Grid::make()
                ->schema([
                    Toggle::make('ordersChartMarkers')
                        ->default(false)
                        ->label('Markers'),

                    Toggle::make('ordersChartGrid')
                        ->default(false)
                        ->label('Grid'),
                ]),

            TextInput::make('ordersChartAnnotations')
                ->required()
                ->numeric()
                ->default(7500)
                ->label('Annotations'),
        ];
    }

    /**
     * Chart options (series, labels, types, size, animations...)
     * https://apexcharts.com/docs/options
     */
    protected function getOptions(): array
    {
        $filters = $this->filterFormData;

        $monthlyData = $this->getMonthlyWaterConsumption();

        return [
            'chart' => [
                'type' => $filters['ordersChartType'],
                'height' => 300,
                'toolbar' => [
                    'show' => false,
                ],
            ],
            'series' => [
                [
                    'name' => 'Water Consumption (m³)',
                    'type' => 'column',
                    'data' => $monthlyData['consumption'],
                ],
                [
                    'name' => 'Water Bills (₱)',
                    'type' => 'line',
                    'data' => $monthlyData['bills'],
                ],
            ],
            'plotOptions' => [
                'bar' => [
                    'borderRadius' => 2,
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
                [
                    'title' => [
                        'text' => 'Water Consumption (m³)',
                    ],
                    'labels' => [
                        'style' => [
                            'fontWeight' => 400,
                            'fontFamily' => 'inherit',
                        ],
                    ],
                ],
                [
                    'opposite' => true,
                    'title' => [
                        'text' => 'Water Bills (₱)',
                    ],
                    'labels' => [
                        'style' => [
                            'fontWeight' => 400,
                            'fontFamily' => 'inherit',
                        ],
                    ],
                ],
            ],
            'dataLabels' => [
                'enabled' => false,
            ],
            'stroke' => [
                'width' => [0, 4],
            ],
            'colors' => ['#3b82f6', '#ef4444'],
            'tooltip' => [
                'shared' => true,
                'intersect' => false,
                'y' => [
                    'formatter' => null,
                ],
            ],
        ];
    
    }

    /**
     * Get monthly water consumption and bills data
     */
    protected function getMonthlyWaterConsumption(): array
    {
        $data = Space::select(
            DB::raw('MONTH(created_at) as month'),
            DB::raw('SUM(water_consumption) as total_consumption'),
            DB::raw('SUM(water_bills) as total_bill')
        )
        ->whereYear('created_at', date('Y'))
        ->where('user_id', Auth::id())
        ->where('water_payment_status', 'unpaid')
        ->groupBy('month')
        ->orderBy('month')
        ->get();

        $months = [];
        $consumption = array_fill(0, 12, 0);
        $bills = array_fill(0, 12, 0);

        for ($i = 0; $i < 12; $i++) {
            $months[$i] = date('M', mktime(0, 0, 0, $i + 1, 1));
        }

        foreach ($data as $item) {
            $monthIndex = $item->month - 1;
            $consumption[$monthIndex] = $item->total_consumption;
            $bills[$monthIndex] = $item->total_bill;
        }

        return [
            'months' => $months,
            'consumption' => $consumption,
            'bills' => $bills,
        ];
    }
}
