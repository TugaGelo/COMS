<?php

namespace App\Filament\Admin\Pages;

use App\Models\Application;
use Filament\Forms\Components\TextInput;
use Filament\Pages\Page;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\FileUpload;
use BezhanSalleh\FilamentShield\Traits\HasPageShield;

class Approve extends Page implements HasForms, HasTable
{
    use InteractsWithForms, InteractsWithTable, HasPageShield;

    public static function shouldRegisterNavigation(): bool
    {
        return auth()->user()->hasRole('super_admin');
    }

    protected static ?string $navigationGroup = 'Applications Settings';

    protected static ?string $navigationLabel = 'Tenants';

    protected static ?string $navigationIcon = 'heroicon-o-document-text';

    protected static string $view = 'filament.admin.pages.approve';

    public static function getNavigationBadge(): ?string
    {
        return Application::onlyTrashed()->count();
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(Application::onlyTrashed())
            ->columns([
                Tables\Columns\TextColumn::make('user.name')
                    ->sortable()
                    ->label('Tenant'),
                Tables\Columns\TextColumn::make('concourse.name')
                    ->sortable(),
                Tables\Columns\TextColumn::make('space.name')
                    ->sortable(),
                Tables\Columns\TextColumn::make('business_name')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: false),
                Tables\Columns\TextColumn::make('owner_name')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('address')
                    ->searchable()
                    ->limit(20)
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('phone_number')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('email')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('business_type')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('concourse_lease_term')
                    ->label('Lease Term')
                    ->date()
                    ->sortable()
                    ->getStateUsing(function ($record) {
                        if ($record->concourse_lease_term) {
                            return $record->created_at->addMonths($record->concourse_lease_term);
                        }
                        return null;
                    }),
                Tables\Columns\TextColumn::make('status')
                    ->searchable()
                    ->badge()
                    ->extraAttributes(['class' => 'capitalize'])
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('remarks')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\ToggleColumn::make('is_active')
                    ->label('Active')
                    ->onIcon('heroicon-m-bolt')
                    ->offIcon('heroicon-m-bolt-slash')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('deleted_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->actions([
                Tables\Actions\ViewAction::make('view')
                    ->form([
                        Section::make('Application Details')
                            ->schema([
                                TextInput::make('business_name')
                                    ->disabled(),
                                TextInput::make('owner_name')
                                    ->disabled(),
                                TextInput::make('address')
                                    ->disabled(),
                                TextInput::make('phone_number')
                                    ->disabled(),
                                TextInput::make('email')
                                    ->disabled(),
                                TextInput::make('business_type')
                                    ->disabled(),
                                TextInput::make('status')
                                    ->disabled(),
                                TextInput::make('remarks')
                                    ->disabled(),
                            ])->columns(2),

                        Section::make('Requirements')
                            ->schema([
                                Repeater::make('app_requirements')
                                    ->relationship('appRequirements')
                                    ->schema([
                                        TextInput::make('name')
                                            ->disabled(),
                                        TextInput::make('status')
                                            ->disabled(),
                                        FileUpload::make('file')
                                            ->disabled()
                                            ->downloadable()
                                    ])
                                    ->columns(3)
                                    ->disabled()
                            ])
                    ])
                    ->modalWidth('7xl'),
            ])
            ->poll('10s');
    }
}
