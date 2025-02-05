<?php

namespace App\Filament\Admin\Widgets;

use App\Models\Concourse;
use App\Models\Space;
use Illuminate\Support\Facades\DB;
use Leandrocfe\FilamentApexCharts\Widgets\ApexChartWidget;

class WaterChart extends ApexChartWidget
{
    /**
     * Chart Id
     *
     * @var string
     */
    protected static ?string $chartId = 'waterChart';
 
    /**
     * Sort
     */
    protected static ?int $sort = 6;

    /**
     * Widget content height
     */
    protected static ?int $contentHeight = 275;

    /**
     * Widget Title
     *
     * @var string|null
     */
    protected static ?string $heading = 'Water Monthly Chart';

    /**
     * Chart options (series, labels, types, size, animations...)
     * https://apexcharts.com/docs/options
     *
     * @return array
     */
    protected function getOptions(): array
    {
        $data = $this->getBillData();

        return [
            'chart' => [
                'type' => 'bar',  
                'height' => 300,
                'toolbar' => ['show' => false],
            ],
            'series' => [
                [
                    'name' => 'Water Bill',
                    'data' => $data['water'],
                ],
            ],
            'xaxis' => [
                'categories' => $data['months'],
                'labels' => [
                    'style' => [
                        'fontFamily' => 'inherit',
                    ],
                ],
            ],
            'yaxis' => [
                [
                    'title' => [
                        'text' => 'Water Bill',
                    ],
                    'labels' => [
                        'style' => [
                            'fontFamily' => 'inherit',
                        ],
                    ],
                ],
            ],
            'colors' => ['#3b82f6'],
            'plotOptions' => [
                'bar' => [
                    'borderRadius' => 3,
                    'horizontal' => false,
                ],
            ],
        ];
    }

    protected function getBillData(): array
    {
        $currentYear = date('Y');
        
        $billData = Concourse::select(
            DB::raw('MONTH(updated_at) as month'),
            DB::raw('SUM(water_bills) as total_water'),
        )
            ->whereYear('updated_at', $currentYear)
            ->groupBy('month')
            ->orderBy('month')
            ->get();

        $months = [];
        $water = array_fill(0, 12, 0);

        foreach ($billData as $data) {
            $monthIndex = $data->month - 1;
            $months[$monthIndex] = date('M', mktime(0, 0, 0, $data->month, 1));
            $water[$monthIndex] = round($data->total_water, 2);;
        }

        // Fill in any missing months
        for ($i = 0; $i < 12; $i++) {
            if (!isset($months[$i])) {
                $months[$i] = date('M', mktime(0, 0, 0, $i + 1, 1));
            }
        }

        ksort($months);

        return [
            'months' => array_values($months),
            'water' => $water,
        ];
    }

    public function exportData()
    {
        $data = $this->getBillData();
        
        $csvContent = "Month,Water Bill\n";
        foreach ($data['months'] as $index => $month) {
            $csvContent .= "{$month},{$data['water'][$index]}\n";
        }

        $fileName = 'monthly_water_payments_' . date('Y-m-d') . '.csv';

        return response()->streamDownload(function () use ($csvContent) {
            echo $csvContent;
        }, $fileName, [
            'Content-Type' => 'text/csv',
        ]);
    }
}
