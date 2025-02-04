<?php

namespace App\Filament\Admin\Resources\ConcourseResource\RelationManagers;

use App\Models\Space;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use pxlrbt\FilamentExcel\Actions\Tables\ExportBulkAction;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Mail;
use App\Mail\RentBillMail;
use App\Mail\UtilityBillMail;

class SpaceRelationManager extends RelationManager
{
    protected static string $relationship = 'spaces';

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

            // Send email to tenant
            $tenant = $record->user;
            $dueDate = now()->addDays(7)->format('F j, Y');
            $penalty = 10; // Adjust penalty percentage as needed
            $waterRate = $concourse->water_rate ?? 0;

            // Mail::to($tenant->email)->send(new UtilityBillMail(
            //     tenantName: $tenant->name,
            //     month: now()->format('F Y'),
            //     waterConsumption: $state,
            //     waterRate: $waterRate,
            //     waterBill: $record->water_bills,
            //     electricityConsumption: $record->electricity_consumption,
            //     electricityRate: $concourse->electricity_rate ?? 0,
            //     electricityBill: $record->electricity_bills,
            //     dueDate: $dueDate,
            //     penalty: $penalty
            // ));

            Notification::make()
                ->title('Water bill updated and email sent')
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

            // Send email to tenant
            $tenant = $record->user;
            $dueDate = now()->addDays(7)->format('F j, Y');
            $penalty = 10; // Adjust penalty percentage as needed
            $electricityRate = $concourse->electricity_rate ?? 0;

            // Mail::to($tenant->email)->send(new UtilityBillMail(
            //     tenantName: $tenant->name,
            //     month: now()->format('F Y'),
            //     waterConsumption: $record->water_consumption,
            //     waterRate: $concourse->water_rate ?? 0,
            //     waterBill: $record->water_bills,
            //     electricityConsumption: $state,
            //     electricityRate: $electricityRate,
            //     electricityBill: $record->electricity_bills,
            //     dueDate: $dueDate,
            //     penalty: $penalty
            // ));

            Notification::make()
                ->title('Electricity bill updated and email sent')
                ->success()
                ->send();
        }
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Bills Utility')->description('Add the utility bills for the tenant')->schema([
                    Forms\Components\TextInput::make('water_consumption')
                        ->label('Water Consumption')
                        ->prefix('m3')
                        ->minValue(0)
                        ->numeric()
                        ->required()
                        ->afterStateUpdated(function ($state, $set, $get, $record) {
                            $this->updateWaterBills($state, $set, $get, $record);
                        }),
                    Forms\Components\TextInput::make('electricity_consumption')
                        ->label('Electricity Consumption')
                        ->prefix('kWh')
                        ->minValue(0)
                        ->numeric()
                        ->required()
                        ->afterStateUpdated(function ($state, $set, $get, $record) {
                            $this->updateElectricityBills($state, $set, $get, $record);
                        }),
                ])->columns(2),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('user_id')
            ->columns([
                Tables\Columns\TextColumn::make('user.name')
                    ->label('Tenant')
                    ->default(fn($record) => $record->user->name ?? 'No Tenant')
                    ->description(fn($record) => $record->name)
                    ->extraAttributes(['class' => 'capitalize'])
                    ->searchable(),
                Tables\Columns\TextColumn::make('price')
                    ->label('Price')
                    ->numeric()
                    ->prefix('₱')
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('Space')
                    ->label('Space')
                    ->default(fn($record) => 'SQM: ' . $record->sqm)
                    ->description(fn($record) => 'Price: ' . '₱' . number_format($record->price ?? 0, 2))
                    ->toggleable(isToggledHiddenByDefault: false),
                Tables\Columns\TextColumn::make('Lease Term')
                    ->label('Lease Term')
                    ->default(fn($record) => 'Lease Due:' . \Carbon\Carbon::parse($record->lease_due)->format('F j, Y'))
                    ->description(fn($record) => 'Lease End: ' . \Carbon\Carbon::parse($record->lease_end)->format('F j, Y'))
                    ->numeric()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('water_bills')
                    ->label('Water Bills')
                    ->default(fn($record) => 'Water: ' . '₱' . number_format($record->water_bills ?? 0, 2))
                    ->description(fn($record) => 'Due: ' . \Carbon\Carbon::parse($record->water_due)->format('F j, Y'))
                    ->tooltip(fn($record) => 'Status: ' . $record->water_payment_status ?? null)
                    ->money('PHP')
                    ->toggleable(isToggledHiddenByDefault: false),
                Tables\Columns\TextColumn::make('electricity_bills')
                    ->label('Electricity Bills')
                    ->default(fn($record) => '₱' . number_format($record->electricity_bills ?? 0, 2))
                    ->description(fn($record) => 'Due: ' . \Carbon\Carbon::parse($record->electricity_due)->format('F j, Y'))
                    ->tooltip(fn($record) => 'Status: ' . $record->electricity_payment_status ?? null)
                    ->money('PHP')
                    ->toggleable(isToggledHiddenByDefault: false),
                Tables\Columns\TextColumn::make('rent_bills')
                    ->label('Rent Bills')
                    ->money('PHP')
                    ->default(fn($record) => 'Rent: ' . number_format($record->rent_bills ?? 0, 2))
                    ->description(fn($record) => 'Due: ' . \Carbon\Carbon::parse($record->rent_due)->format('F j, Y'))
                    ->tooltip(fn($record) => 'Status: ' . $record->rent_payment_status ?? null)
                    ->toggleable(isToggledHiddenByDefault: false),
                Tables\Columns\TextColumn::make('Consumptions')
                    ->label('Consumptions')
                    ->default(fn($record) => 'Water: ' . number_format($record->water_consumption ?? 0, 2) . ' m3')
                    ->description(fn($record) => 'Electricity: ' . number_format($record->electricity_consumption ?? 0, 2) . ' kWh'),
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn($record) => $record->status === 'occupied' ? 'secondary' : 'warning')
                    ->extraAttributes(['class' => 'capitalize'])
                    ->toggleable(isToggledHiddenByDefault: false),
                Tables\Columns\IconColumn::make('is_active')
                    ->label('Visible in Tenant')
                    ->boolean()
                    ->extraAttributes(['class' => 'capitalize'])
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                TrashedFilter::make(),
                SelectFilter::make('status')
                    ->options([
                        'available' => 'Available',
                        'occupied' => 'Occupied',
                        'under_maintenance' => 'Under Maintenance',
                    ]),
                SelectFilter::make('is_active')
                    ->options([
                        '1' => 'Active',
                        '0' => 'Inactive',
                    ]),
            ])

            ->actions([
                Tables\Actions\ActionGroup::make([
                    Tables\Actions\ViewAction::make()->color('info'),
                    Tables\Actions\Action::make('Add Monthly Rent')
                        ->icon('heroicon-m-currency-dollar')
                        ->color('warning')
                        ->requiresConfirmation()
                        ->visible(fn($record) => $record->status === 'occupied')
                        ->action(function (Space $record) {
                            $rentAmount = $record->price ?? 0;
                            $record->rent_bills = $rentAmount;
                            $record->rent_payment_status = 'unpaid';
                            $record->save();

                            // Send email to tenant
                            $tenant = $record->user;
                            $dueDate = now()->addDays(7)->format('F j, Y'); // Adjust due date as needed
                            $penalty = 10; // Adjust penalty percentage as needed

                            Mail::to($tenant->email)->send(new RentBillMail(
                                tenantName: $tenant->name,
                                month: now()->format('F Y'),
                                rentAmount: $rentAmount,
                                totalAmount: $rentAmount,
                                dueDate: $dueDate,
                                penalty: $penalty
                            ));

                            Notification::make()
                                ->title('Rent bill added and email sent')
                                ->success()
                                ->send();
                        }),
                    Tables\Actions\EditAction::make()
                        ->visible(fn($record) => $record->status === 'occupied')
                        ->label('Add Utility Bill'),
                    Tables\Actions\DeleteAction::make()
                        ->visible(fn($record) => $record->status === 'available')
                        ->label('Archive'),
                    Tables\Actions\RestoreAction::make(),
                    Tables\Actions\ForceDeleteAction::make()->label('Permanent Delete'),
                ])
                    ->icon('heroicon-m-ellipsis-vertical')
                    ->tooltip('Actions')
            ])
            ->bulkActions([
                ExportBulkAction::make()->label('Generate Selected Records'),
                // Tables\Actions\BulkActionGroup::make([
                //     Tables\Actions\DeleteBulkAction::make(),
                // ]),
            ])
            ->poll('30s');
    }
}
