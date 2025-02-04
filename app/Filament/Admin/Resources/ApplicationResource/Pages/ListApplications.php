<?php

namespace App\Filament\Admin\Resources\ApplicationResource\Pages;

use App\Filament\Admin\Resources\ApplicationResource;
use App\Models\Application;
use Filament\Actions;
use Filament\Resources\Components\Tab;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Database\Eloquent\Builder;

class ListApplications extends ListRecords
{
    protected static string $resource = ApplicationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            // Actions\CreateAction::make(),
        ];
    }

    // public function getTabs(): array
    // {
    //     return [
    //         'pending' => Tab::make('Pending')
    //             ->query(fn(Builder $query): Builder => $query->where('application_status', 'pending')->orWhere('application_status', 'renewal')),
    //         'renewal' => Tab::make('Renewal')
    //             ->query(fn(Builder $query): Builder => $query->where('space_type', 'renewal')),
    //         'all' => Tab::make('All')
    //             ->query(fn(Builder $query): Builder => $query),
    //     ];
    // }
}
