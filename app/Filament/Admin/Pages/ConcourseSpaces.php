<?php

namespace App\Filament\Admin\Pages;

use Filament\Pages\Page;
use Filament\Forms\Contracts\HasForms;
use Filament\Tables\Contracts\HasTable;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables;
use Filament\Tables\Table;
use App\Models\Space;
use App\Models\Concourse;
use Filament\Forms;
use Filament\Forms\Form;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Filament\Notifications\Notification;
use Filament\Actions\Action;
use Illuminate\Support\Facades\Mail;
use App\Mail\UtilityBillMail;
use App\Models\User;
use BezhanSalleh\FilamentShield\Traits\HasPageShield;

class ConcourseSpaces extends Page implements HasForms, HasTable
{
    use InteractsWithForms, InteractsWithTable, HasPageShield;

    protected static ?string $navigationIcon = 'heroicon-o-document-text';

    protected static string $view = 'filament.admin.pages.concourse-spaces';

    protected static bool $shouldRegisterNavigation = false;

    public $concourse;

    protected function updateWaterBills($state, $set, $get, $record)
    {
        if ($record && $record->status === 'occupied') {
            $concourse = $record->concourse;

            // Update the space's water consumption
            $record->water_consumption = $state;
            $record->water_due = now()->addDays(7);
            $record->save();

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
            $penalty = 10;
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
            $record->update(['electricity_due' => now()->addDays(7)]);

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
            $penalty = 10;
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
                        ->required(),
                    Forms\Components\TextInput::make('electricity_consumption')
                        ->label('Electricity Consumption')
                        ->prefix('kWh')
                        ->minValue(0)
                        ->numeric()
                        ->required(),
                ])->columns(2),
            ]);
    }


    public static function getRoutes(): \Closure
    {
        return function () {
            Route::get('/concourse-spaces', static::class)
                ->name('filament.admin.pages.concourse-spaces');
        };
    }

    public function mount(Request $request)
    {
        $concourseId = $request->query('concourseId');
        $this->concourse = Concourse::find($concourseId);

        if (!$this->concourse) {
            // Redirect to an error page or the concourse list
            return redirect()->route('filament.admin.resources.concourses.index')
                ->with('error', 'Concourse not found');
        }
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(Space::query()->where('concourse_id', $this->concourse->id)->where('status', 'occupied'))
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
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn($record) => $record->status === 'occupied' ? 'secondary' : 'warning')
                    ->extraAttributes(['class' => 'capitalize'])
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('Consumptions')
                    ->label('Consumptions')
                    ->default(fn($record) => 'Water: ' . number_format($record->water_consumption ?? 0, 2) . ' m3')
                    ->description(fn($record) => 'Electricity: ' . number_format($record->electricity_consumption ?? 0, 2) . ' kWh'),
                Tables\Columns\IconColumn::make('is_active')
                    ->label('Visible in Tenant')
                    ->boolean()
                    ->extraAttributes(['class' => 'capitalize'])
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\ActionGroup::make([
                    Tables\Actions\EditAction::make()
                        ->label('Bills')
                        ->form($this->getFormSchema())
                        ->visible(fn($record) => $record->status === 'occupied')
                        ->using(function ($record, array $data) {
                            $this->updateWaterBills($data['water_consumption'], fn($value) => null, fn() => null, $record);
                            $this->updateElectricityBills($data['electricity_consumption'], fn($value) => null, fn() => null, $record);
                            return $record;
                        }),
                    // Tables\Actions\Action::make('Add Monthly Rent')
                    //     ->icon('heroicon-m-currency-dollar')
                    //     ->color('warning')
                    //     ->requiresConfirmation()
                    //     ->visible(fn($record) => $record->status === 'occupied')
                    //     ->action(function (Space $record) {
                    //         $rentAmount = $record->price ?? 0;
                    //         $record->rent_bills = $rentAmount;
                    //         $record->rent_payment_status = 'unpaid';
                    //         $record->save();

                    //         // Send email to tenant
                    //         $tenant = $record->user;
                    //         $dueDate = now()->addDays(7)->format('F j, Y');
                    //         $penalty = 10;

                    //         Mail::to($tenant->email)->send(new RentBillMail(
                    //             tenantName: $tenant->name,
                    //             month: now()->format('F Y'),
                    //             rentAmount: $rentAmount,
                    //             totalAmount: $rentAmount,
                    //             dueDate: $dueDate,
                    //             penalty: $penalty
                    //         ));

                    //         Notification::make()
                    //             ->title('Rent bill added and email sent')
                    //             ->success()
                    //             ->send();
                    //     }),
                ])
                    ->icon('heroicon-m-ellipsis-vertical')
                    ->tooltip('Actions')
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    //
                ]),
            ]);
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('updateBills')
                ->label('Update Concourse Bills')
                ->form([
                    Forms\Components\Section::make('Total Water Bill')->schema([
                        Forms\Components\TextInput::make('water_bills')
                            ->label('Monthly Water Bill')
                            ->default(fn() => $this->concourse->water_bills ?? 0)
                            ->minValue(0)
                            ->numeric()
                            ->prefix('₱'),
                        Forms\Components\TextInput::make('electricity_bills')
                            ->label('Monthly Electricity Bill')
                            ->default(fn() => $this->concourse->electricity_bills ?? 0)
                            ->minValue(0)
                            ->numeric()
                            ->prefix('₱'),
                    ])
                ])
                ->action(function (array $data): void {
                    $this->concourse->update([
                        'water_bills' => $data['water_bills'],
                        'electricity_bills' => $data['electricity_bills'],
                    ]);

                    $this->notifySpacesAboutBills();

                    Notification::make()
                        ->title('Bills updated successfully')
                        ->success()
                        ->send();
                }),
            // Action::make('notifySpaces')
            //     ->label('Notify Spaces')
            //     ->action(function () {
            //         $this->notifySpacesAboutBills();
            //     })
            //     ->color('warning')
            //     ->icon('heroicon-o-bell')
            //     ->requiresConfirmation(),
        ];
    }

    public function getTitle(): string
    {
        return $this->concourse
            ? "Spaces for Concourse: {$this->concourse->name}"
            : "Concourse Spaces";
    }

    protected function getFormSchema(): array
    {
        return [
            Forms\Components\Section::make('Bills Utility')
                ->description('Add the utility bills for the tenant')
                ->schema([
                    Forms\Components\TextInput::make('water_consumption')
                        ->label('Water Consumption')
                        ->prefix('m3')
                        ->minValue(0)
                        ->numeric()
                        ->required(),
                    Forms\Components\TextInput::make('electricity_consumption')
                        ->label('Electricity Consumption')
                        ->prefix('kWh')
                        ->minValue(0)
                        ->numeric()
                        ->required(),
                ])->columns(2),
        ];
    }

    protected function notifySpacesAboutBills(): void
    {
        $concourse = $this->concourse;

        $spaces = $concourse->spaces()
            ->where('is_active', true)
            ->get();

        if ($spaces->isEmpty()) {
            Notification::make()
                ->warning()
                ->title('No Active Spaces')
                ->body('There are no active spaces to notify.')
                ->send();
            return;
        }

        foreach ($spaces as $space) {
            // Create database notification
            $notification = Notification::make()
                ->warning()
                ->title('Monthly Bill Available')
                ->body("Your monthly bill for space {$space->name} in {$concourse->name} is now available for review.");

            // Send notification to the space owner or associated user
            $spaceUser = User::find($space->user_id);
            if ($spaceUser) {
                // Send database notification
                $notification->sendToDatabase($spaceUser);

                // Send email notification
                Mail::to($spaceUser->email)->send(new UtilityBillMail(
                    tenantName: $spaceUser->name,
                    month: now()->format('F Y'),
                    waterConsumption: $space->water_consumption,
                    waterRate: $concourse->water_rate ?? 0,
                    waterBill: $space->water_bills,
                    electricityConsumption: $space->electricity_consumption,
                    electricityRate: $concourse->electricity_rate ?? 0,
                    electricityBill: $space->electricity_bills,
                    dueDate: now()->addDays(7)->format('F j, Y'),
                    penalty: 10
                ));
            }
        }

        Notification::make()
            ->success()
            ->title('Notifications Sent')
            ->body('All spaces have been notified about their monthly bills via email and database notifications.')
            ->send();
    }
}
