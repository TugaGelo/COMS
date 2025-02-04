<?php

namespace App\Filament\Admin\Resources\RequirementResource\Pages;

use App\Filament\Admin\Resources\RequirementResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateRequirement extends CreateRecord
{
    protected static string $resource = RequirementResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
