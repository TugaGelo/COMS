<?php

namespace App\Filament\App\Pages;

use App\Mail\PaymentConfirmation;
use App\Models\Payment;
use App\Models\Space;
use Filament\Pages\Page;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables;
use Filament\Tables\Table;
use Ixudra\Curl\Facades\Curl;
use App\Services\RenewForm;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Mail;
use App\Models\Application;
use Carbon\Carbon;
use Filament\Forms\Components\Checkbox;
use App\Models\User;
use App\Models\AppRequirement;
use App\Filament\App\Pages\EditRequirement;
use App\Services\ReportForm;
use Illuminate\Support\Facades\Auth;
use App\Mail\TicketReportMail;
use App\Models\Concourse;
use App\Models\Renew;
use App\Models\RenewAppRequirements;
use Filament\Tables\Filters\SelectFilter;
use App\Mail\RenewApplication;

class TenantSpace extends Page implements HasForms, HasTable
{
    public $tenantId;

    use InteractsWithForms, InteractsWithTable;

    protected static ?string $navigationLabel = 'My Space';

    protected static ?string $navigationIcon = 'heroicon-o-home-modern';

    protected static string $view = 'filament.app.pages.tenant-space';

    public function getTenantSpacesProperty()
    {
        return Space::with('concourse')
            ->where('user_id', auth()->id())
            ->where('is_active', true)
            ->get();
    }

    public function getConcourseLayoutProperty()
    {
        $firstSpace = $this->tenantSpaces->first();
        return $firstSpace ? $firstSpace->concourse : null;
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(Space::query()
                ->where('is_active', true)
                ->where('user_id', auth()->user()->id))
            ->columns([
                Tables\Columns\TextColumn::make('concourse.name')
                    ->label('Concourse')
                    ->description(fn($record) => $record->name)
                    ->searchable(),
                Tables\Columns\TextColumn::make('Contract')
                    ->label('Contract')
                    ->default(function ($record) {
                        if (!$record->lease_start) return null;
                        return 'Start: ' . $record->lease_start->format('F j, Y');
                    })
                    ->description(function ($record) {
                        if (!$record->lease_end) return null;
                        return 'End: ' . $record->lease_end->format('F j, Y');
                    }),
                Tables\Columns\TextColumn::make('Rent Bills')
                    ->label('Rent Bills')
                    ->default(fn($record) => $record->rent_bills > 0 ? '₱' . number_format($record->rent_bills, 2) : '₱0.00')
                    ->description(fn($record) => $record->rent_payment_status == 'paid' ? '' : 'Unpaid')
                    ->tooltip(fn($record) => $record->rent_due ? 'Due: ' . Carbon::parse($record->rent_due)->format('M d, Y') : ''),
                Tables\Columns\TextColumn::make('Water Bills')
                    ->label('Water Bills')
                    ->default(fn($record) => $record->water_bills > 0 ? '₱' . number_format($record->water_bills, 2) : '₱0.00')
                    ->description(fn($record) => $record->water_payment_status == 'paid' ? '' : 'Unpaid')
                    ->tooltip(fn($record) => $record->water_due ? 'Due: ' . Carbon::parse($record->water_due)->format('M d, Y') : ''),
                Tables\Columns\TextColumn::make('Electricity Bills')
                    ->label('Electricity Bills')
                    ->default(fn($record) => $record->electricity_bills > 0 ? '₱' . number_format($record->electricity_bills, 2) : '₱0.00')
                    ->description(fn($record) => $record->electricity_payment_status == 'paid' ? '' : 'Unpaid')
                    ->tooltip(fn($record) => $record->electricity_due ? 'Due: ' . Carbon::parse($record->electricity_due)->format('M d, Y') : ''),
            ])
            ->filters([
                SelectFilter::make('concourse_id')
                    ->label('Concourse')
                    ->options(
                        Space::where('user_id', auth()->id())
                            ->with('concourse')
                            ->get()
                            ->pluck('concourse.name', 'concourse_id')
                            ->unique()
                    )
            ])
            ->actions([
                Tables\Actions\ActionGroup::make([

                    Tables\Actions\Action::make('renew')
                        ->label('Renew Lease')
                        ->button()
                        ->slideOver()
                        ->form(fn($record) => RenewForm::schema($record))
                        ->visible(function ($record) {
                            // Check if user already has a pending renewal application for this space
                            return !Renew::where('user_id', auth()->user()->id)
                                ->where('space_id', $record->id)
                                ->where('concourse_id', $record->concourse_id)
                                ->whereIn('application_status', ['pending', 'processing'])
                                ->exists();
                        })
                        ->action(function (array $data, $record) {
                            // Create the Renew application first
                            $renew = Renew::create([
                                'user_id' => auth()->user()->id,
                                'space_id' => $record->id,
                                'concourse_id' => $record->concourse_id,
                                'business_name' => $data['business_name'] ?? null,
                                'owner_name' => $data['owner_name'] ?? null,
                                'address' => $data['address'] ?? null,
                                'phone_number' => $data['phone_number'] ?? null,
                                'email' => $data['email'] ?? null,
                                'business_type' => $data['business_type'] ?? null,
                                'requirements_status' => 'pending',
                                'application_status' => 'pending',
                                'space_type' => 'renewal',
                                'concourse_lease_term' => $data['concourse_lease_term'] ?? null,
                                'remarks' => $data['remarks'] ?? null,
                            ]);

                            // Store the uploaded requirements
                            if (isset($data['requirements'])) {
                                foreach ($data['requirements'] as $requirementId => $file) {
                                    if ($file) {
                                        RenewAppRequirements::create([
                                            'requirement_id' => $requirementId,
                                            'user_id' => auth()->user()->id,
                                            'space_id' => $record->id,
                                            'concourse_id' => $record->concourse_id,
                                            'application_id' => $renew->id, // Use the newly created renew application's ID
                                            'name' => \App\Models\Requirement::find($requirementId)->name,
                                            'remarks' => $data['remarks'] ?? null,
                                            'status' => 'pending',
                                            'file' => $file,
                                        ]);
                                    }
                                }
                            }

                            Notification::make()
                                ->title('Lease Renewal Application Submitted')
                                ->body('Your application for lease renewal has been submitted successfully.')
                                ->success()
                                ->send();

                            Mail::to($record->user->email)->send(new RenewApplication($record));
                        }),
                    Tables\Actions\Action::make('payBills')
                        ->label('Pay Bills')
                        ->button()
                        ->action(fn($record, array $data) => $this->payWithGCash($record, $data))
                        ->form(function ($record) {
                            // Define $now at the beginning
                            $now = now();

                            // Header Section
                            $header = [
                                \Filament\Forms\Components\Section::make('Space Details')
                                    ->schema([
                                        \Filament\Forms\Components\Placeholder::make('space_name')
                                            ->label('Space')
                                            ->content($record->concourse->name . ' - ' . $record->name),
                                        \Filament\Forms\Components\Placeholder::make('payment_due_dates')
                                            ->label('Payment Due Dates')
                                            ->content(function () use ($record) {
                                                return collect([
                                                    'Rent: ' . ($record->rent_due ? Carbon::parse($record->rent_due)->format('M d, Y') : 'N/A'),
                                                    'Water: ' . ($record->water_due ? Carbon::parse($record->water_due)->format('M d, Y') : 'N/A'),
                                                    'Electricity: ' . ($record->electricity_due ? Carbon::parse($record->electricity_due)->format('M d, Y') : 'N/A'),
                                                ])->map(fn($item) => "••••• {$item} •••••")
                                                    ->implode("");
                                            })->columnSpanFull(),
                                        \Filament\Forms\Components\Placeholder::make('current_penalties')
                                            ->label('Current Penalties')
                                            ->content(function () use ($record, $now) {
                                                $rentPenalty = $record->rent_due && $now->gt(Carbon::parse($record->rent_due)) ? '₱' . number_format($record->rent_bills * 0.02, 2) : 'N/A';
                                                $waterPenalty = $record->water_due && $now->gt(Carbon::parse($record->water_due)) ? '₱' . number_format($record->water_bills * 0.02, 2) : 'N/A';
                                                $electricityPenalty = $record->electricity_due && $now->gt(Carbon::parse($record->electricity_due)) ? '₱' . number_format($record->electricity_bills * 0.02, 2) : 'N/A';

                                                return collect([
                                                    'Rent: ' . $rentPenalty,
                                                    'Water: ' . $waterPenalty,
                                                    'Electricity: ' . $electricityPenalty,
                                                ])->map(fn($item) => "••••• {$item} •••••")
                                                    ->implode("");
                                            })->columnSpanFull(),
                                    ])
                                    ->columnSpanFull(),
                            ];

                            // Existing Checkboxes Logic
                            $checkboxes = [];
                            $now = now();

                            if ($record->water_bills > 0) {
                                $waterDue = Carbon::parse($record->water_due);
                                $penalty = $now->gt($waterDue) ? ($record->water_bills * 0.02) : 0;
                                $totalWater = $record->water_bills + $penalty;

                                $checkboxes[] = Checkbox::make('pay_water')
                                    ->label("Water Bill: ₱" . number_format($record->water_bills, 2))
                                    ->default(true);
                            }

                            if ($record->electricity_bills > 0) {
                                $electricityDue = Carbon::parse($record->electricity_due);
                                $penalty = $now->gt($electricityDue) ? ($record->electricity_bills * 0.02) : 0;
                                $totalElectricity = $record->electricity_bills + $penalty;

                                $checkboxes[] = Checkbox::make('pay_electricity')
                                    ->label("Electricity Bill: ₱" . number_format($record->electricity_bills, 2))
                                    ->default(true);
                            }

                            if ($record->rent_bills > 0) {
                                $rentDue = Carbon::parse($record->rent_due);
                                $penalty = $now->gt($rentDue) ? ($record->rent_bills * 0.02) : 0;
                                $totalRent = $record->rent_bills + $penalty;

                                $checkboxes[] = Checkbox::make('pay_rent')
                                    ->label("Rent: ₱" . number_format($record->rent_bills, 2))
                                    ->default(true);
                            }

                            // Footer Section
                            $footer = [
                                \Filament\Forms\Components\Section::make()
                                    ->schema([
                                        \Filament\Forms\Components\Placeholder::make('total_bills')
                                            ->label('Total Bills:')
                                            ->content('₱' . number_format($record->water_bills + $record->electricity_bills + $record->rent_bills, 2)),
                                        \Filament\Forms\Components\Placeholder::make('total_penalties')
                                            ->label('Total Penalties:')
                                            ->content(function () use ($record, $now) {
                                                $totalPenalty = 0;
                                                if ($record->rent_due && $now->gt(Carbon::parse($record->rent_due))) {
                                                    $totalPenalty += $record->rent_bills * 0.02;
                                                }
                                                if ($record->water_due && $now->gt(Carbon::parse($record->water_due))) {
                                                    $totalPenalty += $record->water_bills * 0.02;
                                                }
                                                if ($record->electricity_due && $now->gt(Carbon::parse($record->electricity_due))) {
                                                    $totalPenalty += $record->electricity_bills * 0.02;
                                                }
                                                return $totalPenalty > 0 ? '₱' . number_format($totalPenalty, 2) : 'N/A';
                                            }),
                                        \Filament\Forms\Components\Placeholder::make('grand_total')
                                            ->label('Grand Total:')
                                            ->content(function () use ($record, $now) {
                                                $total = $record->water_bills + $record->electricity_bills + $record->rent_bills;
                                                if ($record->rent_due && $now->gt(Carbon::parse($record->rent_due))) {
                                                    $total += $record->rent_bills * 0.02;
                                                }
                                                if ($record->water_due && $now->gt(Carbon::parse($record->water_due))) {
                                                    $total += $record->water_bills * 0.02;
                                                }
                                                if ($record->electricity_due && $now->gt(Carbon::parse($record->electricity_due))) {
                                                    $total += $record->electricity_bills * 0.02;
                                                }
                                                return '₱' . number_format($total, 2);
                                            })
                                            ->extraAttributes(['class' => 'font-bold']),
                                    ])
                                    ->columns(1),
                            ];

                            // Combine all sections
                            return array_merge($header, $checkboxes, $footer);
                        })
                        ->visible(fn($record) => $record->electricity_bills > 0 || $record->water_bills > 0 || $record->rent_bills > 0),
                    Tables\Actions\Action::make('Check Application')
                        ->link()
                        ->icon('heroicon-o-pencil')
                        ->url(fn($record) => route('filament.app.pages.renew-edit-requirement', ['concourse_id' => $record->concourse_id, 'space_id' => $record->id, 'user_id' => Auth::id()]))
                        ->openUrlInNewTab()
                        ->visible(function ($record) {
                            // Hide if status is approved
                            if ($record->requirements_status === 'approved' || $record->application_status === 'approved') {
                                return false;
                            }

                            return \App\Models\Renew::where('user_id', auth()->user()->id)
                                ->where('concourse_id', $record->concourse_id)
                                ->where('space_id', $record->id)
                                ->exists();
                        }),
                    Tables\Actions\Action::make('Report')
                        ->button()
                        ->visible(fn($record) => $record->status === 'occupied')
                        ->form(fn($record) => ReportForm::schema($record))
                        ->action(function (array $data, $record) {
                            $ticket = new \App\Models\Ticket($data);
                            $ticket->save();

                            \Filament\Notifications\Notification::make()
                                ->title('Success')
                                ->body('Your ticket has been submitted successfully.')
                                ->success()
                                ->send();

                            \Filament\Notifications\Notification::make()
                                ->title('Success')
                                ->body('Your ticket has been submitted successfully.')
                                ->success()
                                ->sendToDatabase(auth()->user());

                            \Filament\Notifications\Notification::make()
                                ->title('New Ticket')
                                ->body('A new ticket has been submitted.')
                                ->success()
                                ->sendToDatabase(User::find(1));

                            // Send email to admin
                            $admin = User::find(1); // Assuming admin is always user with ID 1
                            $tenant = auth()->user();
                            $spaceName = $record->name;
                            $concourseName = $record->concourse->name;

                            Mail::to($admin->email)->send(new TicketReportMail($admin, $tenant, $ticket, $spaceName, $concourseName));
                        }),
                ])
                    ->icon('heroicon-m-ellipsis-vertical')
                    ->tooltip('Actions'),
            ])->headerActions([
                Tables\Actions\Action::make('My Report')
                    ->link()
                    ->icon('heroicon-o-paper-airplane')
                    ->url(fn($record) => route('filament.app.pages.my-report'))
                    ->openUrlInNewTab()
            ]);
    }

    protected function payWithGCash($record, $data)
    {
        $lineItems = [];
        $totalAmount = 0;
        $description = "Payment for: ";
        $now = now();
        $dueData = [];

        // Validate that at least one payment option is selected
        if (!isset($data['pay_water']) && !isset($data['pay_electricity']) && !isset($data['pay_rent'])) {
            $this->notify('danger', 'Payment Error', 'Please select at least one bill to pay.');
            return null;
        }

        // Water Bill
        if (!empty($data['pay_water'])) {
            $waterDue = $record->water_due ? Carbon::parse($record->water_due) : null;
            $penalty = ($waterDue && $now->gt($waterDue)) ? ($record->water_bills * 0.02) : 0;
            $totalWater = $record->water_bills + $penalty;

            if ($totalWater > 0) {
                $lineItems[] = [
                    'currency' => 'PHP',
                    'amount' => (int)($totalWater * 100), // Ensure amount is an integer
                    'description' => 'Water Bill' . ($penalty > 0 ? ' + 2% Penalty' : ''),
                    'name' => 'Water Bill',
                    'quantity' => 1,
                ];
                $totalAmount += $totalWater;
                $description .= "Water Bill" . ($penalty > 0 ? " (incl. ₱" . number_format($penalty, 2) . " penalty), " : ", ");

                if ($waterDue && $waterDue->isPast()) {
                    $dueData['water_due'] = $record->water_due;
                    $dueData['paid_late'] = $now;
                }
            }
        }

        // Electricity Bill
        if (!empty($data['pay_electricity'])) {
            $electricityDue = $record->electricity_due ? Carbon::parse($record->electricity_due) : null;
            $penalty = ($electricityDue && $now->gt($electricityDue)) ? ($record->electricity_bills * 0.02) : 0;
            $totalElectricity = $record->electricity_bills + $penalty;

            if ($totalElectricity > 0) {
                $lineItems[] = [
                    'currency' => 'PHP',
                    'amount' => (int)($totalElectricity * 100), // Ensure amount is an integer
                    'description' => 'Electricity Bill' . ($penalty > 0 ? ' + 2% Penalty' : ''),
                    'name' => 'Electricity Bill',
                    'quantity' => 1,
                ];
                $totalAmount += $totalElectricity;
                $description .= "Electricity Bill" . ($penalty > 0 ? " (incl. ₱" . number_format($penalty, 2) . " penalty), " : ", ");

                if ($electricityDue && $electricityDue->isPast()) {
                    $dueData['electricity_due'] = $record->electricity_due;
                    if (!isset($dueData['paid_late'])) {
                        $dueData['paid_late'] = $now;
                    }
                }
            }
        }

        // Rent Bill
        if (!empty($data['pay_rent'])) {
            $rentDue = $record->rent_due ? Carbon::parse($record->rent_due) : null;
            $penalty = ($rentDue && $now->gt($rentDue)) ? ($record->rent_bills * 0.02) : 0;
            $totalRent = $record->rent_bills + $penalty;

            if ($totalRent > 0) {
                $lineItems[] = [
                    'currency' => 'PHP',
                    'amount' => (int)($totalRent * 100), // Ensure amount is an integer
                    'description' => 'Monthly Rent' . ($penalty > 0 ? ' + 2% Penalty' : ''),
                    'name' => 'Monthly Rent',
                    'quantity' => 1,
                ];
                $totalAmount += $totalRent;
                $description .= "Monthly Rent" . ($penalty > 0 ? " (incl. ₱" . number_format($penalty, 2) . " penalty), " : ", ");

                if ($rentDue && $rentDue->isPast()) {
                    $dueData['rent_due'] = $record->rent_due;
                    if (!isset($dueData['paid_late'])) {
                        $dueData['paid_late'] = $now;
                    }
                }
            }
        }

        // Validate that we have at least one line item
        if (empty($lineItems)) {
            $this->notify('danger', 'Payment Error', 'No valid bills selected for payment.');
            return null;
        }

        // Remove trailing comma and space from description
        $description = rtrim($description, ", ");

        // Store due data in session for later use
        session(['payment_due_data' => $dueData]);

        // Get authenticated user details
        $user = auth()->user();

        $sessionData = [
            'data' => [
                'attributes' => [
                    'line_items' => $lineItems,
                    'amount_total' => $totalAmount * 100,
                    'payment_method_types' => ['gcash'],
                    'success_url' => route('filament.app.pages.tenant-space.payment-success', ['record' => $record->id]),
                    'cancel_url' => route('filament.app.pages.tenant-space.payment-cancel'),
                    'description' => $description,
                    'customer' => [
                        'name' => $user->name,
                        'email' => $user->email,
                        'phone' => $user->phone_number ?? '',
                    ],
                    'billing' => [
                        'name' => $user->name,
                        'email' => $user->email,
                        'phone' => $user->phone_number ?? '',
                    ],
                ],
            ],
        ];

        // Store payment data in session
        session(['payment_data' => $sessionData]);

        $response = Curl::to('https://api.paymongo.com/v1/checkout_sessions')
            ->withHeader('Content-Type: application/json')
            ->withHeader('accept: application/json')
            ->withHeader('Authorization: Basic c2tfdGVzdF9ZS1lMMnhaZWVRRDZjZ1dYWkJYZ1dHVU46')
            ->withData($sessionData)
            ->asJson()
            ->post();

        if (isset($response->data)) {
            $checkoutSession = $response->data;
            $checkoutUrl = $checkoutSession->attributes->checkout_url;

            // Redirect the user to the GCash checkout URL
            return redirect()->away($checkoutUrl);
        } else {
            $this->notify('danger', 'Payment Failed', 'An error occurred while processing your payment. Please try again.');
            return null;
        }
    }

    protected function notify($status, $title, $message)
    {
        Notification::make()
            ->title($title)
            ->body($message)
            ->status($status)
            ->send();
    }

    protected function sendPaymentConfirmationEmail($space, $payment)
    {
        $user = auth()->user();
        $admin = User::find(1);

        // Only send the email if there's an actual payment amount
        if ($payment->amount > 0) {
            // Send email to the user who made the payment
            Mail::to($user->email)->send(new PaymentConfirmation($space, $user, $payment));

            // Send email to the admin (user with ID 1) only if it's a different user
            if ($admin && $admin->id !== $user->id) {
                Mail::to($admin->email)->send(new PaymentConfirmation($space, $admin, $payment));
            }
        }
    }

    public function handlePaymentSuccess($recordId)
    {
        $space = Space::findOrFail($recordId);

        // Retrieve the payment data from the session
        $paymentData = session('payment_data', []);
        $dueData = session('payment_due_data', []);

        // Check if payment has already been processed
        if (!$paymentData || !isset($paymentData['data']['attributes']['line_items'])) {
            $this->notify('warning', 'Payment Already Processed', 'This payment has already been processed.');
            return redirect()->route('filament.app.pages.tenant-space');
        }

        $totalPaid = 0;
        $waterBillPaid = 0;
        $electricityBillPaid = 0;
        $electricityConsumptionPaid = 0;
        $waterConsumptionPaid = 0;
        $rentBillPaid = 0;
        $totalPenalty = 0;
        $now = now();

        foreach ($paymentData['data']['attributes']['line_items'] as $item) {
            $penalty = 0;
            switch ($item['name']) {
                case 'Water Bill':
                    $waterBillPaid = $space->water_bills;
                    if (isset($dueData['water_due']) && $now->gt(Carbon::parse($dueData['water_due']))) {
                        $penalty = $waterBillPaid * 0.02;
                    }
                    $totalPenalty += $penalty;
                    $space->water_bills = 0;
                    $space->water_payment_status = 'paid';
                    $waterConsumptionPaid = $space->water_consumption;
                    $space->water_consumption = 0;
                    $totalPaid += ($waterBillPaid + $penalty);
                    break;
                case 'Electricity Bill':
                    $electricityBillPaid = $space->electricity_bills;
                    if (isset($dueData['electricity_due']) && $now->gt(Carbon::parse($dueData['electricity_due']))) {
                        $penalty = $electricityBillPaid * 0.02;
                    }
                    $totalPenalty += $penalty;
                    $space->electricity_bills = 0;
                    $space->electricity_payment_status = 'paid';
                    $electricityConsumptionPaid = $space->electricity_consumption;
                    $space->electricity_consumption = 0;
                    $totalPaid += ($electricityBillPaid + $penalty);
                    break;
                case 'Monthly Rent':
                    $rentBillPaid = $space->rent_bills;
                    if (isset($dueData['rent_due']) && $now->gt(Carbon::parse($dueData['rent_due']))) {
                        $penalty = $rentBillPaid * 0.02;
                    }
                    $totalPenalty += $penalty;
                    $space->rent_bills = 0;
                    $space->rent_payment_status = 'paid';
                    $totalPaid += ($rentBillPaid + $penalty);
                    break;
            }
        }

        $space->save();
        $space->refresh();

        // Create payment record only if total paid is greater than 0
        if ($totalPaid > 0) {
            $paymentData = [
                'tenant_id' => $space->user_id,
                'space_id' => $space->id,
                'concourse_id' => $space->concourse_id,
                'payment_type' => 'e-wallet',
                'payment_method' => 'gcash',
                'water_bill' => $waterBillPaid,
                'electricity_bill' => $electricityBillPaid,
                'electricity_consumption' => $electricityConsumptionPaid,
                'water_consumption' => $waterConsumptionPaid,
                'rent_bill' => $rentBillPaid,
                'amount' => $totalPaid,
                'penalty' => $totalPenalty,
                'payment_status' => Payment::STATUS_PAID,
                'paid_date' => $now,
                'water_due' => $dueData['water_due'] ?? null,
                'electricity_due' => $dueData['electricity_due'] ?? null,
                'rent_due' => $dueData['rent_due'] ?? null,
                'due_date' => $dueData['paid_late'] ?? null,
                'is_water_late' => isset($dueData['water_due']),
                'is_electricity_late' => isset($dueData['electricity_due']),
                'is_rent_late' => isset($dueData['rent_due']),
                'is_penalty' => $totalPenalty > 0,
            ];

            $payment = Payment::create($paymentData);

            // Send email confirmation
            $this->sendPaymentConfirmationEmail($space, $payment);
        }

        // Clear the payment data from the session
        session()->forget(['payment_data', 'payment_due_data']);

        $this->notify('success', 'Payment Successful', 'Your payment has been processed successfully.');
        return redirect()->route('filament.app.pages.tenant-space');
    }

    public function handlePaymentCancel()
    {
        $this->notify('warning', 'Payment Cancelled', 'Your payment has been cancelled.');
        return redirect()->route('filament.app.pages.tenant-space');
    }
}
