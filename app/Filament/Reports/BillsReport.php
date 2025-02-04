<?php

namespace App\Filament\Reports;

use EightyNine\Reports\Report;
use EightyNine\Reports\Components\Body;
use EightyNine\Reports\Components\Footer;
use EightyNine\Reports\Components\Header;
use EightyNine\Reports\Components\Text;
use EightyNine\Reports\Components\VerticalSpace;
use Filament\Forms\Form;
use Illuminate\Support\Collection;
use App\Models\Space;
use App\Models\Concourse;
use BezhanSalleh\FilamentShield\Traits\HasPageShield;

class BillsReport extends Report
{
    use HasPageShield;

    public ?string $heading = "Bills Report";

    // public ?string $subHeading = "A great report";

    public function header(Header $header): Header
    {

        return $header
            ->schema([
                Header\Layout\HeaderRow::make()
                    ->schema([
                        Header\Layout\HeaderColumn::make()
                            ->schema([
                                Text::make('Bills Report')
                                    ->title()
                                    ->primary(),
                                Text::make('Utility Bills Report')
                                    ->subtitle(),
                            ]),
                    ])
            ]);
    }


    public function body(Body $body): Body
    {
        return $body
            ->schema([
                Body\Layout\BodyColumn::make()
                    ->schema([
                        Text::make('Concourse Summary')
                            ->color('secondary')
                            ->title(),
                        Text::make('Detailed Space Summary')
                            ->subtitle(),
                        Body\Table::make()
                            ->data(
                                fn(?array $filters) => $this->paymentsSummary($filters)
                            ),
                        VerticalSpace::make(),
                        Body\Table::make()
                            ->data(
                                fn(?array $filters) => $this->paymentMethodSummary($filters)
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
        $query = Concourse::query();

        // If user is not super_admin, show only their concourses
        if (!auth()->user()->hasRole('super_admin')) {
            $query->whereHas('spaces', function ($query) {
                $query->whereNotNull('user_id')
                    ->where('user_id', auth()->id());
            });
        }

        return $form
            ->schema([
                \Filament\Forms\Components\Select::make('concourse_id')
                    ->label('Concourse')
                    ->multiple()
                    ->options($query->pluck('name', 'id'))
                    ->native(false)
                    ->required(),
            ]);
    }

    public function paymentsSummary(?array $filters): Collection
    {
        $filtersApplied = false;

        $query = Space::query()
            ->with(['concourse', 'user'])
            ->whereHas('concourse');

        if (!auth()->user()->hasRole('super_admin')) {
            $query->where('user_id', auth()->id());
        }

        if (isset($filters['concourse_id'])) {
            $query->whereIn('concourse_id', $filters['concourse_id']);
            $filtersApplied = true;
        }

        if (!$filtersApplied) {
            return collect();
        }

        $spaces = $query->latest('updated_at')->get();

        return collect([
            [
                'column1' => 'Concourse',
                'column2' => 'Space',
                'column3' => 'Tenant',
                'column4' => 'Water Usage',
                'column5' => 'Water Bill',
                'column6' => 'Electric Usage',
                'column7' => 'Electric Bill',
                'column8' => 'Unpaid Water',
                'column9' => 'Unpaid Electric',
            ]
        ])->concat($spaces->map(function ($space) {
            $user = $space->user;
            return [
                'column1' => $space->concourse->name ?? 'N/A',
                'column2' => $space->name ?? 'N/A',
                'column3' => $user ? "{$user->first_name} {$user->last_name}" : 'N/A',
                'column4' => is_numeric($space->water_consumption ?? 0) ? number_format($space->water_consumption ?? 0, 2) : 'N/A',
                'column5' => is_numeric($space->water_bills ?? 0) ? number_format($space->water_bills ?? 0, 2) : 'N/A',
                'column6' => is_numeric($space->electricity_consumption ?? 0) ? number_format($space->electricity_consumption ?? 0, 2) : 'N/A',
                'column7' => is_numeric($space->electricity_bills ?? 0) ? number_format($space->electricity_bills ?? 0, 2) : 'N/A',
                'column8' => is_numeric($space->water_due ?? 0) ? number_format($space->water_due ?? 0, 2) : 'N/A',
                'column9' => is_numeric($space->electricity_due ?? 0) ? number_format($space->electricity_due ?? 0, 2) : 'N/A',
            ];
        }));
    }

    public function paymentMethodSummary(?array $filters): Collection
    {
        if (!isset($filters['concourse_id'])) {
            return collect();
        }

        $query = Concourse::query()->whereIn('id', $filters['concourse_id']);

        if (!auth()->user()->hasRole('super_admin')) {
            $query->whereHas('spaces', function ($query) {
                $query->where('user_id', auth()->id());
            });
        }

        $concourses = $query->get();

        return collect([
            [
                'column1' => 'Summary',
                'column2' => 'Total',
            ]
        ])->concat(collect([
            [
                'column1' => 'Total Water Consumption',
                'column2' => number_format($concourses->sum('total_water_consumption') ?? 0, 2),
            ],
            [
                'column1' => 'Total Water Bill',
                'column2' => number_format($concourses->sum('water_bills') ?? 0, 2),
            ],
            [
                'column1' => 'Total Electric Consumption',
                'column2' => number_format($concourses->sum('total_electricity_consumption') ?? 0, 2),
            ],
            [
                'column1' => 'Total Electric Bill',
                'column2' => number_format($concourses->sum('electricity_bills') ?? 0, 2),
            ],
        ]));
    }
}
