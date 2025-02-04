<?php

namespace App\Filament\Admin\Resources\ConcourseRateResource\Pages;

use App\Filament\Admin\Resources\ConcourseRateResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListConcourseRates extends ListRecords
{
    protected static string $resource = ConcourseRateResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
