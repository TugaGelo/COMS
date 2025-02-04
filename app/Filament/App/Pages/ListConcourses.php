<?php

namespace App\Filament\App\Pages;

use Filament\Pages\Page;
use App\Models\Concourse;

class ListConcourses extends Page
{
    protected static ?string $navigationLabel = 'Concourse';

    protected static ?string $navigationIcon = 'heroicon-o-building-storefront';

    protected static string $view = 'filament.app.pages.list-concourses';

  
}
