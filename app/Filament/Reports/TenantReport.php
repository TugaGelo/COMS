<?php

namespace App\Filament\Reports;

use EightyNine\Reports\Report;
use App\Models\Concourse;
use App\Models\Space;
use EightyNine\Reports\Components\Body;
use EightyNine\Reports\Components\Footer;
use EightyNine\Reports\Components\Header;
use EightyNine\Reports\Components\Text;
use EightyNine\Reports\Components\VerticalSpace;
use Filament\Forms\Form;
use Illuminate\Support\Collection;
use App\Models\Payment;

class TenantReport extends Report
{
    public ?string $heading = "Tenant Report";

    protected array $filters = [];

    public function filterFormSubmitted(array $data): void
    {
        $this->filters = $data;
    }

    public function header(Header $header): Header
    {
        return $header
            ->schema([
                Header\Layout\HeaderRow::make()
                    ->schema([
                        Header\Layout\HeaderColumn::make()
                            ->schema([
                                Text::make('Tenant Report')
                                    ->title(),
                                Text::make('This report shows the status of the tenant')
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

    private function spaceSummary(?array $filters): Collection
    {
        if (empty($filters)) {
            return collect(); // Return an empty collection if no filters are applied
        }
        $query = Space::query()
            ->with(['user']);

        if (isset($filters['concourse']) && $filters['concourse']) {
            $query->where('concourse_id', $filters['concourse']);
        }

        $spaces = $query->latest('created_at')->get();

        $headerRow = [
            'column1' => 'Space Name',
            'column2' => 'Status',
            'column3' => 'Area',
            'column4' => 'Rent Bill',
            'column5' => 'Rent Due',
            'column6' => 'Transaction Date',
            'column7' => 'On Time',
            'column8' => 'Days Late',
            'column9' => 'Penalty',
            'column10' => 'Delayed Payments',
            'column11' => 'Start Lease',
            'column12' => 'End Lease',
        ];

        return collect([$headerRow])
            ->concat($spaces->map(function ($space) {
                $payment = $space->payments()->latest('due_date')->first();
                return [
                    'column1' => $space->name,
                    'column2' => ucfirst($space->status),
                    'column3' => $space->sqm . ' sqm',
                    'column4' => $payment ? number_format($payment->rent_bill, 2) : 'N/A', 
                    'column5' => $payment && $payment->rent_due ? $payment->rent_due->format('m-d-Y') : 'N/A', 
                    'column6' => $payment ? $payment->created_at->format('m-d-Y') : 'N/A',
                    'column7' => $payment ? ($payment->rent_due >= now() ? 'Yes' : 'No') : 'N/A',
                    'column8' => $payment && $payment->rent_due ? number_format($payment->rent_due->diffInDays(now()), 0) : 'N/A',
                    'column9' => $payment ? number_format($payment->penalty, 2) : 'N/A',
                    'column10' => $payment ? (
                        ($payment->payment_status == 'paid' && 
                        ($payment->rent_due > now() || $payment->water_due > now() || $payment->electricity_due > now())) ? 1 : 0) : 'N/A',
                    'column11' => $space->lease_start ? $space->lease_start->format('m-d-Y') : 'N/A',
                    'column12' => $space->lease_end ? $space->lease_end->format('m-d-Y') : 'N/A',
                ];
            }));
    }

    private function spaceStatusSummary(?array $filters): Collection
    {
        if (empty($filters)) {
            return collect(); // Return an empty collection if no filters are applied
        }
        $query = Space::query();

        if (isset($filters['concourse']) && $filters['concourse']) {
            $query->where('concourse_id', $filters['concourse']);
        }

        $totalCount = $query->count();
        $availableCount = (clone $query)->where('status', 'available')->count();
        $pendingCount = (clone $query)->where('status', 'pending')->count();
        $occupiedCount = (clone $query)->where('status', 'occupied')->count();

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
                'column1' => 'Pending',
                'column2' => $pendingCount,
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
                \Filament\Forms\Components\Select::make('concourse')
                    ->label('Concourse')
                    ->options(Concourse::pluck('name', 'id'))
                    ->native(false)
                    ->placeholder('Select a concourse')
                    ->live(),
            ]);
    }
}
