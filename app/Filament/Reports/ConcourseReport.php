<?php

namespace App\Filament\Reports;

use EightyNine\Reports\Report;
use App\Models\Concourse;
use App\Models\Payment;
use App\Models\Space;
use EightyNine\Reports\Components\Body;
use EightyNine\Reports\Components\Footer;
use EightyNine\Reports\Components\Header;
use EightyNine\Reports\Components\Text;
use EightyNine\Reports\Components\VerticalSpace;
use Filament\Forms\Form;
use Illuminate\Support\Collection;
use EightyNine\Reports\Components\Table;
use Illuminate\Support\Facades\DB;

class ConcourseReport extends Report
{
    public ?string $heading = "Concourse Report";

    // public ?string $subHeading = "A great report";

    public function header(Header $header): Header
    {
        $concourse = Concourse::first(); // Or however you want to select the Concourse

        return $header
            ->schema([
                Header\Layout\HeaderRow::make()
                    ->schema([
                        Header\Layout\HeaderColumn::make()
                            ->schema([
                                Text::make('Concourse Report')
                                    ->title(),
                                Text::make('This report shows the status of the concourse')
                                    ->subtitle(),
                            ]),
                        Header\Layout\HeaderColumn::make()
                            ->schema([
                                Text::make(now()->format('F d, Y'))
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
                                fn(?array $filters) => $this->spaceSummary($filters)
                            ),
                        VerticalSpace::make(),
                        Body\Table::make()
                            ->data(
                                fn(?array $filters) => $this->spaceStatusSummary($filters)
                            ),
                    ]),
            ]);
    }

    private function getSpaces(): Collection
    {
        return Space::query()
            ->select('name', 'status', DB::raw('price / 100 as price'))
            ->where('concourse_id', Concourse::first()->id)
            ->get();
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
                \Filament\Forms\Components\Select::make('concourse_id')
                    ->label('Concourse')
                    ->options(Concourse::pluck('name', 'id'))
                    ->searchable()
                    ->native(false),
                \Filament\Forms\Components\TextInput::make('search')
                    ->placeholder('Search')
                    ->autofocus(),
                \Filament\Forms\Components\Select::make('status')
                    ->label('Space Status')
                    ->native(false)
                    ->options([
                        'all' => 'All',
                        'available' => 'Available',
                        'occupied' => 'Occupied',
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
                                'concourse_id' => null,
                                'search' => null,
                                'status' => null,
                                'date_from' => null,
                                'date_to' => null,
                            ]);
                        })
                ]),
            ]);
    }

    public function spaceSummary(?array $filters): Collection
    {
        $query = Space::query()
            ->with(['user']); 

        if (isset($filters['concourse_id'])) {
            $query->where('concourse_id', $filters['concourse_id']);
        }

        $filtersApplied = false;

        if (isset($filters['search']) && !empty($filters['search'])) {
            $query->where('name', 'like', '%' . $filters['search'] . '%');
            $filtersApplied = true;
        }

        if (isset($filters['status']) && $filters['status'] !== 'all') {
            $query->where('status', $filters['status']);
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
            $spaces = $query->latest('created_at')->get();
        } else {
            $spaces = $query->latest('created_at')->get();
        }

        $headerRow = [
            'column1' => 'Space Name',
            'column2' => 'Status',
            'column3' => 'Owner Name',
            'column4' => 'Business Type',
            'column5' => 'Business Name',
            'column6' => 'Rent Price',
            'column7' => 'Lease Start',
            'column8' => 'Lease End',
        ];

        return collect([$headerRow])
            ->concat($spaces->map(function ($space) {
                return [
                    'column1' => $space->name,
                    'column2' => $space->status,
                    'column3' => $space->user->name ?? 'N/A',
                    'column4' => $space->business_type ?? 'N/A',
                    'column5' => $space->business_name ?? 'N/A',
                    'column6' => number_format($space->price, 2),
                    'column7' => $space->lease_start ? $space->lease_start->format('F d, Y') : 'N/A',
                    'column8' => $space->lease_end ? $space->lease_end->format('F d, Y') : 'N/A',
                ];
            }));
    }

    public function spaceStatusSummary(?array $filters): Collection
    {
        $query = Space::query();

        if (isset($filters['concourse_id'])) {
            $query->where('concourse_id', $filters['concourse_id']);
        }

        if (isset($filters['date_from'])) {
            $query->whereDate('created_at', '>=', $filters['date_from']);
        }

        if (isset($filters['date_to'])) {
            $query->whereDate('created_at', '<=', $filters['date_to']);
        }

        $totalCount = $query->count();
        $availableCount = (clone $query)->where('status', 'available')->count();
        $occupiedCount = $totalCount - $availableCount;

        return collect([
            [
                'column1' => 'Status',
                'column2' => 'Count',
            ],
            [
                'column1' => 'Available',
                'column2' => $availableCount,
            ],
            [
                'column1' => 'Occupied',
                'column2' => $occupiedCount,
            ],
            [
                'column1' => 'Total',
                'column2' => $totalCount,
            ],
        ]);
    }
}
