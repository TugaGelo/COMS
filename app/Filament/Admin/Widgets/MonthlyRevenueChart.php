<?php

namespace App\Filament\Admin\Widgets;

use App\Models\Payment;
use Filament\Support\RawJs;
use Illuminate\Support\Facades\DB;
use Leandrocfe\FilamentApexCharts\Widgets\ApexChartWidget;
use Filament\Actions\Action;
use App\Models\Space;

class MonthlyRevenueChart extends ApexChartWidget
{
    /**
     * Chart Id
     *
     * @var string
     */
    protected static ?string $chartId = 'monthlyRevenueChart';

    /**
     * Widget Title
     *
     * @var string|null
     */
    protected static ?string $heading = 'Rent Monthly Revenue';

    /**
     * Sort
     */
    protected static ?int $sort = 8;

    /**
     * Widget content height
     */
    protected static ?int $contentHeight = 275;

    /**
     * Add this line to enable the header
     */
    protected static bool $showHeader = true;

    /**
     * Chart options (series, labels, types, size, animations...)
     * https://apexcharts.com/docs/options
     *
     * @return array
     */

    protected function getOptions(): array
    {
        $data = $this->getRentData();

        return [
            'chart' => [
                'type' => 'bar',
                'height' => 260,
                'parentHeightOffset' => 2,
                'stacked' => false,
                'toolbar' => [
                    'show' => true,
                    'tools' => [
                        'download' => true,
                        'selection' => false,
                        'zoom' => false,
                        'zoomin' => false,
                        'zoomout' => false,
                        'pan' => false,
                        'reset' => false,
                    ],
                ],
            ],
            'series' => [
                [
                    'name' => 'Rent Revenue',
                    'data' => $data['amounts'],
                    'type' => 'bar',
                ],
                [
                    'name' => 'Total Space Value',
                    'data' => $data['space_values'],
                    'type' => 'line',
                ],
            ],
            'plotOptions' => [
                'bar' => [
                    'horizontal' => false,
                    'columnWidth' => '50%',
                ],
            ],
            'dataLabels' => [
                'enabled' => false,
            ],
            'legend' => [
                'show' => true,
                'horizontalAlign' => 'right',
                'position' => 'top',
                'fontFamily' => 'inherit',
                'markers' => [
                    'height' => 12,
                    'width' => 12,
                    'radius' => 12,
                    'offsetX' => -3,
                    'offsetY' => 2,
                ],
                'itemMargin' => [
                    'horizontal' => 5,
                ],
            ],
            'grid' => [
                'show' => false,
            ],
            'xaxis' => [
                'categories' => $data['months'],
                'labels' => [
                    'style' => [
                        'fontFamily' => 'inherit',
                    ],
                ],
                'axisTicks' => [
                    'show' => false,
                ],
                'axisBorder' => [
                    'show' => false,
                ],
            ],
            'yaxis' => [
                'labels' => [
                    'style' => [
                        'fontFamily' => 'inherit',
                    ],
                ],
                'tickAmount' => 5,
            ],
            'fill' => [
                'type' => 'gradient',
                'gradient' => [
                    'shade' => 'dark',
                    'type' => 'vertical',
                    'shadeIntensity' => 0.5,
                    'gradientToColors' => ['#d97706', '#c2410c'],
                    'opacityFrom' => 1,
                    'opacityTo' => 1,
                    'stops' => [0, 100],
                ],
            ],
            'stroke' => [
                'curve' => 'smooth',
                'width' => [2, 3],
                'lineCap' => 'round',
            ],
            'colors' => ['#f59e0b', '#ea580c'],
            'tooltip' => [
                'enabled' => true,
                'shared' => true,
                'intersect' => false,
                'followCursor' => true,
                'marker' => [
                    'show' => true,
                ],
            ],
            'markers' => [
                'size' => 5,
                'hover' => [
                    'size' => 7,
                    'sizeOffset' => 3,
                ],
            ],
        ];
    }

    protected function getRentData(): array
    {
        $rentData = Payment::select(
            DB::raw('MONTH(created_at) as month'),
            DB::raw('SUM(rent_bill) as total_rent')
        )
            ->where('payment_status', 'paid')
            ->whereYear('created_at', date('Y'))
            ->groupBy('month')
            ->orderBy('month')
            ->get();

        $spaceData = Space::select(
            DB::raw('MONTH(created_at) as month'),
            DB::raw('SUM(price) as total_price')
        )
            ->whereYear('created_at', date('Y'))
            ->groupBy('month')
            ->orderBy('month')
            ->get();

        $months = [];
        $amounts = array_fill(0, 12, 0);
        $spaceValues = array_fill(0, 12, 0);

        foreach ($rentData as $data) {
            $monthIndex = $data->month - 1;
            $months[$monthIndex] = date('M', mktime(0, 0, 0, $data->month, 1));
            $amounts[$monthIndex] = round($data->total_rent, 2);
        }

        foreach ($spaceData as $data) {
            $monthIndex = $data->month - 1;
            $spaceValues[$monthIndex] = round($data->total_price, 2);
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
            'amounts' => $amounts,
            'space_values' => $spaceValues,
        ];
    }

    protected function extraJsOptions(): ?RawJs
    {
        return RawJs::make(<<<'JS'
        {
            xaxis: {
                labels: {
                    formatter: function (val, timestamp, opts) {
                        return val
                    }
                }
            },
            yaxis: {
                labels: {
                    formatter: function (val, index) {
                        return '₱' + val.toFixed(2)
                    }
                }
            },
            tooltip: {
                shared: true,
                intersect: false,
                y: [{
                    formatter: function (val) {
                        return '₱' + val.toFixed(2)
                    }
                }, {
                    formatter: function (val) {
                        return '₱' + val.toFixed(2)
                    }
                }]
            },
            chart: {
                events: {
                    mouseMove: function(event, chartContext, config) {
                        // Add hover effect
                    },
                    mounted: function(chartContext, config) {
                        const exportButton = chartContext.toolbar.exportMenu.exportSelected.bind(chartContext.toolbar.exportMenu);
                        const customExportButton = document.createElement('div');
                        customExportButton.classList.add('apexcharts-menu-item');
                        customExportButton.innerHTML = 'Download Chart';
                        customExportButton.addEventListener('click', function() {
                            exportButton('png');
                        });
                        chartContext.toolbar.elTools.appendChild(customExportButton);
                    }
                }
            }
        }
        JS);
    }

    public function getActions(): array
    {
        return [
            Action::make('export')
                ->label('Export Data')
                ->icon('heroicon-o-arrow-down-tray')
                ->action('exportData'),
        ];
    }

    public function exportData()
    {
        $rentData = $this->getRentData();
        
        $csvContent = "Month,Revenue,Space Value\n";
        foreach ($rentData['months'] as $index => $month) {
            $csvContent .= "{$month},{$rentData['amounts'][$index]},{$rentData['space_values'][$index]}\n";
        }

        $fileName = 'monthly_rent_revenue_' . date('Y-m-d') . '.csv';

        return response()->streamDownload(function () use ($csvContent) {
            echo $csvContent;
        }, $fileName, [
            'Content-Type' => 'text/csv',
        ]);
    }
}
