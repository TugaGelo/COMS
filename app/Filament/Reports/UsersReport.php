<?php

namespace App\Filament\Reports;

use App\Models\User;
use EightyNine\Reports\Report;
use EightyNine\Reports\Components\Body;
use EightyNine\Reports\Components\Footer;
use EightyNine\Reports\Components\Header;
use EightyNine\Reports\Components\Text;
use EightyNine\Reports\Components\VerticalSpace;
use Filament\Forms\Form;
use Illuminate\Support\Collection;

class UsersReport extends Report
{
    public ?string $heading = "Users Report";

    // public ?string $subHeading = "A great report";

    public function header(Header $header): Header
    {
        return $header
            ->schema([
                Header\Layout\HeaderRow::make()
                    ->schema([
                        Header\Layout\HeaderColumn::make()
                            ->schema([
                                Text::make('Users Report')
                                    ->title(),
                                Text::make('This report shows users in the system')
                                    ->subtitle(),
                            ]),
                        Header\Layout\HeaderColumn::make()
                            ->schema([
                                Text::make(now()->format('F, d Y'))
                                    ->subtitle(),
                            ])->alignRight(),
                    ])
            ]);
    }


    public function body(Body $body): Body
    {
        return $body
            ->schema([
                Body\Layout\BodyColumn::make()
                    ->schema([
                        Body\Table::make()
                            ->data(
                                fn(?array $filters) => $this->registrationSummary($filters)
                            ),
                        VerticalSpace::make(),
                        Body\Table::make()
                            ->data(
                                fn(?array $filters) => $this->verificationSummary($filters)
                            ),
                    ]),
            ]);
    }

    public function footer(Footer $footer): Footer
    {
        return $footer
            ->schema([
                Footer\Layout\FooterRow::make()
                    ->schema([
                        Footer\Layout\FooterColumn::make()
                            ->schema([
                                Text::make("Coms")
                                    ->title()
                                    ->primary(),
                                Text::make("All Rights Reserved")
                                    ->subtitle(),
                            ]),
                        Footer\Layout\FooterColumn::make()
                            ->schema([
                                Text::make("Generated on: " . now()->format('F d, Y')),
                            ])
                            ->alignRight(),
                    ]),
            ]);
    }

    public function filterForm(Form $form): Form
    {
        return $form
            ->schema([
                \Filament\Forms\Components\TextInput::make('search')
                    ->placeholder('Search')
                    ->autofocus(),
                \Filament\Forms\Components\Select::make('email_verified_at')
                    ->label('Email verification status')
                    ->native(false)
                    ->options([
                        'all' => 'All',
                        'verified' => 'Verified',
                        'not_verified' => 'Not Verified',
                    ]),
                \Filament\Forms\Components\DatePicker::make('date_from')
                    ->label('Date From')
                    ->placeholder('Start Date')
                    ->timezone('Asia/Manila')
                    ->displayFormat('F d, Y')
                    ->maxDate(now())
                    ->native(false),
                \Filament\Forms\Components\DatePicker::make('date_to')
                    ->label('Date To')
                    ->placeholder('End Date')
                    ->timezone('Asia/Manila')
                    ->displayFormat('F d, Y')
                    ->maxDate(now())
                    ->native(false),
                \Filament\Forms\Components\Actions::make([
                    \Filament\Forms\Components\Actions\Action::make('reset')
                        ->label('Reset Filters')
                        ->color('danger')
                        ->action(function (Form $form) {
                            $form->fill([
                                'search' => null,
                                'email_verified_at' => null,
                                'date_from' => null,
                                'date_to' => null,
                            ]);
                        })
                ]),
            ]);
    }

    public function registrationSummary(?array $filters): Collection
    {
        $query = User::query();

        $filtersApplied = false;

        if (isset($filters['search']) && !empty($filters['search'])) {
            $query->where(function ($q) use ($filters) {
                $q->where('first_name', 'like', '%' . $filters['search'] . '%')
                  ->orWhere('last_name', 'like', '%' . $filters['search'] . '%')
                  ->orWhere('email', 'like', '%' . $filters['search'] . '%');
            });
            $filtersApplied = true;
        }

        if (isset($filters['email_verified_at']) && $filters['email_verified_at'] !== 'all') {
            if ($filters['email_verified_at'] === 'verified') {
                $query->whereNotNull('email_verified_at');
            } elseif ($filters['email_verified_at'] === 'not_verified') {
                $query->whereNull('email_verified_at');
            }
            $filtersApplied = true;
        }

        if (isset($filters['date_from'])) {
            $query->whereDate('created_at', '>=', $filters['date_from']);
            $filtersApplied = true;
        }

        if (isset($filters['date_to'])) {
            $query->whereDate('created_at', '<=', $filters['date_to']);
            $filtersApplied = true;
        }

        if (!$filtersApplied) {
            return collect();
        }

        $users = $query->latest('created_at')->get();

        return collect([
            [
                'column1' => 'Date Created',
                'column2' => 'Full Name',
                'column3' => 'Email',
                'column4' => 'Status',
            ]
        ])->concat($users->map(function ($user) {
            return [
                'column1' => $user->created_at->format('F d, Y'),
                'column2' => $user->first_name . ' ' . $user->last_name,
                'column3' => $user->email,
                'column4' => $user->email_verified_at ? 'Verified' : 'Not Verified',
            ];
        }));
    }

    public function verificationSummary(?array $filters): Collection
    {
        $query = User::query();

        $filtersApplied = false;

        if (isset($filters['date_from'])) {
            $query->whereDate('created_at', '>=', $filters['date_from']);
            $filtersApplied = true;
        }

        if (isset($filters['date_to'])) {
            $query->whereDate('created_at', '<=', $filters['date_to']);
            $filtersApplied = true;
        }

        if (!$filtersApplied) {
            return collect();
        }

        // Get the total count of users matching the filter
        $totalCount = $query->count();

        // Count verified users
        $verifiedCount = (clone $query)->whereNotNull('email_verified_at')->count();

        // Calculate unverified count by subtracting verified from total
        $unverifiedCount = $totalCount - $verifiedCount;

        return collect([
            [
                'column1' => 'Status',
                'column2' => 'Count',
            ],
            [
                'column1' => 'Verified',
                'column2' => $verifiedCount,
            ],
            [
                'column1' => 'Not Verified',
                'column2' => $unverifiedCount,
            ],
            [
                'column1' => 'Total',
                'column2' => $totalCount,
            ],
        ]);
    }
}
