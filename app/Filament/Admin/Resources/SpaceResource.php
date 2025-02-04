<?php

namespace App\Filament\Admin\Resources;

use App\Filament\Admin\Resources\SpaceResource\Pages;
use App\Filament\Admin\Resources\SpaceResource\RelationManagers;
use App\Models\Concourse;
use App\Models\Space;
use Filament\Forms;
use Filament\Forms\Components\Select;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Filament\Notifications\Notification;
use Filament\Tables\Filters\SelectFilter;
use BezhanSalleh\FilamentShield\Contracts\HasShieldPermissions;

class SpaceResource extends Resource implements HasShieldPermissions
{
    public static function getPermissionPrefixes(): array
    {
        return [
            'view',
            'view_any',
            'create',
            'update',
            'delete',
            'delete_any',
            'publish'
        ];
    }

    protected static ?string $navigationGroup = 'Concourse Settings';

    protected static ?string $navigationLabel = 'Bills';

    protected static ?string $model = Space::class;

    protected static ?string $navigationIcon = 'heroicon-o-credit-card';

    protected static bool $shouldRegisterNavigation = false;
    
    protected function updateWaterBills($state, $set, $get, $record)
    {
        if ($record && $record->status === 'occupied') {
            $concourse = $record->concourse;

            // Update the space's water consumption
            $record->update(['water_consumption' => $state]);

            // Recalculate the concourse's total water consumption
            $concourse->updateTotalWaterConsumption();

            // Calculate water bill for this space
            $record->calculateWaterBill();

            // Update the form fields
            $set('water_bills', $record->water_bills);
            $set('water_payment_status', $record->water_payment_status);

            Notification::make()
                ->title('Water bill updated')
                ->success()
                ->send();
        }
    }

    protected function updateElectricityBills($state, $set, $get, $record)
    {
        if ($record && $record->status === 'occupied') {
            $concourse = $record->concourse;

            // Update the space's electricity consumption
            $record->update(['electricity_consumption' => $state]);

            // Recalculate the concourse's total electricity consumption
            $concourse->updateTotalElectricityConsumption();

            // Calculate electricity bill for this space
            $record->calculateElectricityBill();

            // Update the form fields
            $set('electricity_bills', $record->electricity_bills);
            $set('electricity_payment_status', $record->electricity_payment_status);

            Notification::make()
                ->title('Electricity bill updated')
                ->success()
                ->send();
        }
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Bills Utility')->description('Add the utility bills for the tenant')->schema([
                    Forms\Components\TextInput::make('water_consumption')
                        ->label('Water Consumption')
                        ->prefix('m3')
                        ->minValue(0)
                        ->numeric()
                        ->decimalPlaces(2)
                        ->required()
                        ->afterStateUpdated(function ($state, $set, $get, $record) {
                            $this->updateWaterBills($state, $set, $get, $record);
                        }),
                    Forms\Components\TextInput::make('electricity_consumption')
                        ->label('Electricity Consumption')
                        ->prefix('kWh')
                        ->minValue(0)
                        ->numeric()
                        ->decimalPlaces(2)
                        ->required()
                        ->afterStateUpdated(function ($state, $set, $get, $record) {
                            $this->updateElectricityBills($state, $set, $get, $record);
                        }),
                ])->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('user.name')
                    ->label('Tenant')
                    ->default(fn($record) => $record->user->name ?? 'No Tenant')
                    ->description(fn($record) => $record->name)
                    ->extraAttributes(['class' => 'capitalize'])
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('price')
                    ->label('Price')
                    ->numeric()
                    ->sortable()
                    ->prefix('₱')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('sqm')
                    ->label('Sqm')
                    ->numeric()
                    ->sortable()
                    ->description(fn($record) => 'Price: ' . '₱' . number_format($record->price ?? 0, 2))
                    ->toggleable(isToggledHiddenByDefault: false),
                Tables\Columns\TextColumn::make('Lease Term')
                    ->label('Lease Term')
                    ->default(fn($record) => 'Lease Due:' . \Carbon\Carbon::parse($record->lease_due)->format('F j, Y'))
                    ->description(fn($record) => 'Lease End: ' . \Carbon\Carbon::parse($record->lease_end)->format('F j, Y'))
                    ->numeric(),
                Tables\Columns\TextColumn::make('water_bills')
                    ->label('Water Bills')
                    ->default(fn($record) => 'Water: ' . '₱' . number_format($record->water_bills ?? 0, 2))
                    ->description(fn($record) => 'Status: ' . $record->water_payment_status ?? null . ', Consumption: ' . $record->water_consumption . ' m3')
                    ->numeric()
                    ->sortable()
                    ->money('PHP')
                    ->toggleable(isToggledHiddenByDefault: false),
                Tables\Columns\TextColumn::make('electricity_bills')
                    ->label('Electricity Bills')
                    ->default(fn($record) => '₱' . number_format($record->electricity_bills ?? 0, 2))
                    ->description(fn($record) => 'Status: ' . $record->electricity_payment_status ?? null)
                    ->numeric()
                    ->sortable()
                    ->money('PHP')
                    ->toggleable(isToggledHiddenByDefault: false),
                Tables\Columns\TextColumn::make('rent_bills')
                    ->label('Rent Bills')
                    ->numeric()
                    ->sortable()
                    ->money('PHP')
                    ->default(fn($record) => 'Rent: ' . number_format($record->rent_bills ?? 0, 2))
                    ->description(fn($record) => 'Status: ' . $record->rent_payment_status ?? null)
                    ->toggleable(isToggledHiddenByDefault: false),
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn($record) => $record->status === 'occupied' ? 'secondary' : 'warning')
                    ->extraAttributes(['class' => 'capitalize']),
                Tables\Columns\TextColumn::make('Consumptions')
                    ->label('Consumptions')
                    ->numeric()
                    ->sortable()
                    ->default(fn($record) => 'Water: ' . number_format($record->water_consumption ?? 0, 2) . ' m3')
                    ->description(fn($record) => 'Electricity: ' . number_format($record->electricity_consumption ?? 0, 2) . ' kWh'),
                Tables\Columns\IconColumn::make('is_active')
                    ->label('Visible in Tenant')
                    ->boolean()
                    ->extraAttributes(['class' => 'capitalize'])
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('concourse_id')
                    ->label('Concourse')
                    ->options(Concourse::all()->pluck('name', 'id'))
            ])
            ->actions([
                Tables\Actions\EditAction::make()
                    ->modalWidth('sm')
                    ->label('Bills'),
                Tables\Actions\Action::make('Add Monthly Rent')
                    ->icon('heroicon-m-currency-dollar')
                    ->color('warning')
                    ->requiresConfirmation()
                    ->action(function (Space $record) {
                        $rentAmount = $record->price ?? 0;
                        $record->rent_bills = $rentAmount;
                        $record->rent_payment_status = 'unpaid';
                        $record->save();
                    }),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListSpaces::route('/'),
            'create' => Pages\CreateSpace::route('/create'),
            'edit' => Pages\EditSpace::route('/{record}/edit'),
        ];
    }
}
