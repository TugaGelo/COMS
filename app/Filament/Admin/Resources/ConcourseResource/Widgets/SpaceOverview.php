<?php

namespace App\Filament\Admin\Resources\ConcourseResource\Widgets;

use App\Models\Space;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class SpaceOverview extends BaseWidget
{
    protected function getStats(): array
    {
        return [
            Stat::make('Total Spaces', Space::count()),
            Stat::make('Available Spaces', Space::where('status', 'available')->count()),
            Stat::make('Occupied Spaces', Space::where('status', 'occupied')->count()),
            Stat::make('Under Maintenance Spaces', Space::where('status', 'maintenance')->count()),
        ];
    }
}
