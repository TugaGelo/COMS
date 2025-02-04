<?php

namespace App\Filament\Admin\Resources\ConcourseResource\Pages;

use App\Filament\Admin\Resources\ConcourseResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListConcourses extends ListRecords
{
    protected static string $resource = ConcourseResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }

}
