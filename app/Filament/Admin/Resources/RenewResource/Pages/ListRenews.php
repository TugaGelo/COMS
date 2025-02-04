<?php

namespace App\Filament\Admin\Resources\RenewResource\Pages;

use App\Filament\Admin\Resources\RenewResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListRenews extends ListRecords
{
    protected static string $resource = RenewResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
