<?php

namespace App\Filament\Admin\Widgets;

use Leandrocfe\FilamentApexCharts\Widgets\ApexChartWidget;

class TicketReportChart extends ApexChartWidget
{
    /**
     * Chart Id
     *
     * @var string
     */
    protected static ?string $chartId = 'ticketReportChart';

    /**
     * Widget Title
     *
     * @var string|null
     */
    protected static ?string $heading = 'Ticket Report Chart';


     /**
     * Sort
     */
    protected static ?int $sort = 3;

    /**
     * Widget content height
     */
    protected static ?int $contentHeight = 275;
    /**
     * Chart options (series, labels, types, size, animations...)
     * https://apexcharts.com/docs/options
     *
     * @return array
     */
    protected function getOptions(): array
    {
        $concernTypeCounts = \App\Models\Ticket::query()
            ->select('concern_type', \DB::raw('count(*) as total'))
            ->groupBy('concern_type')
            ->orderBy('total', 'desc')
            ->limit(5) // Adjust the limit as needed
            ->get()
            ->pluck('total', 'concern_type')
            ->toArray();

        $reporterCounts = \App\Models\Ticket::query()
            ->select('created_by', \DB::raw('count(*) as total'))
            ->groupBy('created_by')
            ->with('createdBy')
            ->orderBy('total', 'desc')
            ->limit(5) // Adjust the limit as needed
            ->get()
            ->mapWithKeys(function ($item) {
                return [$item->createdBy->name => $item->total];
            })
            ->toArray();

        return [
            'chart' => [
                'type' => 'pie',
                'height' => 300,
            ],
            'series' => array_values($concernTypeCounts),
            'labels' => array_keys($concernTypeCounts),
            'legend' => [
                'labels' => [
                    'fontFamily' => 'inherit',
                ],
            ],
            'reporters' => [
                'series' => array_values($reporterCounts),
                'labels' => array_keys($reporterCounts),
            ],
        ];
    }
}
