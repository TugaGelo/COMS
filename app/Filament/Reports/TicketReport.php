<?php

namespace App\Filament\Reports;

use EightyNine\Reports\Report;
use EightyNine\Reports\Components\Body;
use EightyNine\Reports\Components\Footer;
use EightyNine\Reports\Components\Header;
use Filament\Forms\Form;
use EightyNine\Reports\Components\Text;
use EightyNine\Reports\Components\VerticalSpace;
use Illuminate\Support\Collection;
use App\Models\Ticket;

class TicketReport extends Report
{
    public ?string $heading = "Tickets Report";

    // public ?string $subHeading = "A great report";

    public function header(Header $header): Header
    {
        return $header
            ->schema([
                Header\Layout\HeaderRow::make()
                    ->schema([
                        Header\Layout\HeaderColumn::make()
                            ->schema([
                                Text::make('Tickets Report')
                                    ->title(),
                                Text::make('This report shows tickets in the system')
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
                                fn(?array $filters) => $this->ticketSummary($filters)
                            ),
                        VerticalSpace::make(),
                        Body\Table::make()
                            ->data(
                                fn(?array $filters) => $this->statusSummary($filters)
                            ),
                        VerticalSpace::make(),
                        Body\Table::make()
                            ->data(
                                fn(?array $filters) => $this->ticketMethodSummary($filters)
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
                // \Filament\Forms\Components\Select::make('concern_type')
                //     ->label('Concern Type')
                //     ->native(false)
                //     ->multiple()
                //     ->options([
                //         'maintenance and repair' => 'Maintenance and Repair',
                //         'safety and security' => 'Safety and Security',
                //         'cleanliness and sanitation' => 'Cleanliness and Sanitation',
                //         'lease and contractual' => 'Lease and Contractual Issues',
                //         'utilities concerns' => 'Utilities Concerns',
                //         'aesthetic and comestics' => 'Aesthetic and Comestics',
                //         'general support' => 'General Support',
                //         'others' => 'Others',
                //     ]),
                // \Filament\Forms\Components\Actions::make([
                //     \Filament\Forms\Components\Actions\Action::make('reset')
                //         ->label('Reset Filter')
                //         ->color('danger')
                //         ->action(function (Form $form) {
                //             $form->fill([
                //                 'concern_type' => null,
                //             ]);
                //         })
                // ]),
            ]);
    }

    public function ticketSummary(?array $filters): Collection
    {
        $query = Ticket::query();

        $filtersApplied = true;

        // if (isset($filters['search']) && !empty($filters['search'])) {
        //     $query->where(function ($q) use ($filters) {
        //         $q->where('incident_ticket_number', 'like', '%' . $filters['search'] . '%')
        //           ->orWhere('title', 'like', '%' . $filters['search'] . '%')
        //           ->orWhere('description', 'like', '%' . $filters['search'] . '%');
        //     });
        //     $filtersApplied = true;
        // }

        // if (isset($filters['concern_type']) && !empty($filters['concern_type'])) {
        //     $query->whereIn('concern_type', $filters['concern_type']);
        //     $filtersApplied = true;
        // }

        if (isset($filters['status']) && $filters['status'] !== 'all') {
            $query->where('status', $filters['status']);
            $filtersApplied = true;
        }

        if (isset($filters['priority']) && $filters['priority'] !== 'all') {
            $query->where('priority', $filters['priority']);
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

        $tickets = $query->with(['createdBy', 'assignedTo', 'space', 'concourse'])->latest('created_at')->get();

        return collect([
            [
                'column1' => 'Tenant',
                'column2' => 'Maintenance and Repair',
                'column3' => 'Safety and Security',
                'column4' => 'Cleaning and Sanitation',
                'column5' => 'Lease End contractual issues',
                'column6' => 'Utilities concerns',
                'column7' => 'Aesthetic and Cosmetic Issues',
                'column8' => 'General Support',
                'column9' => 'Other',
                'column10' => 'Total',
            ]
        ])->concat(
            $tickets->groupBy('createdBy.name')
                ->map(function ($groupedTickets, $tenantName) {
                    return [
                        'column1' => $tenantName ?? 'N/A',
                        'column2' => $groupedTickets->where('concern_type', 'maintenance and repair')->count(),
                        'column3' => $groupedTickets->where('concern_type', 'safety and security')->count(),
                        'column4' => $groupedTickets->where('concern_type', 'cleanliness and sanitation')->count(),
                        'column5' => $groupedTickets->where('concern_type', 'lease and contractual')->count(),
                        'column6' => $groupedTickets->where('concern_type', 'utilities concerns')->count(),
                        'column7' => $groupedTickets->where('concern_type', 'aesthetic and comestics')->count(),
                        'column8' => $groupedTickets->where('concern_type', 'general support')->count(),
                        'column9' => $groupedTickets->where('concern_type', 'others')->count(),
                        'column10' => $groupedTickets->count(),
                    ];
                })
        );
    }

    public function statusSummary(?array $filters): Collection
    {
        $query = Ticket::query();

        $filtersApplied = true;

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


        $totalCount = $query->count();
        $openCount = (clone $query)->where('status', 'open')->count();
        $inProgressCount = (clone $query)->where('status', 'in_progress')->count();
        $resolvedCount = (clone $query)->where('status', 'resolved')->count();
        $closedCount = (clone $query)->where('status', 'closed')->count();

        $maintenanceAndRepairCount = (clone $query)->where('concern_type', 'maintenance and repair')->count();
        $safetyAndSecurityCount = (clone $query)->where('concern_type', 'safety and security')->count();
        $cleanlinessAndSanitationCount = (clone $query)->where('concern_type', 'cleanliness and sanitation')->count();
        $leaseAndContractualCount = (clone $query)->where('concern_type', 'lease and contractual')->count();
        $utilitiesConcernsCount = (clone $query)->where('concern_type', 'utilities concerns')->count();
        $aestheticAndCosmeticsCount = (clone $query)->where('concern_type', 'aesthetic and comestics')->count();
        $generalSupportCount = (clone $query)->where('concern_type', 'general support')->count();
        $othersCount = (clone $query)->where('concern_type', 'others')->count();

        return collect([
            [
                'column1' => 'Concern Type',
                'column2' => 'Count',
            ],
            [
                'column1' => 'Maintenance and Repair',
                'column2' => $maintenanceAndRepairCount,
            ],
            [
                'column1' => 'Safety and Security',
                'column2' => $safetyAndSecurityCount,
            ],
            [
                'column1' => 'Cleanliness and Sanitation',
                'column2' => $cleanlinessAndSanitationCount,
            ],
            [
                'column1' => 'Lease and Contractual',
                'column2' => $leaseAndContractualCount,
            ],
            [
                'column1' => 'Utilities Concerns',
                'column2' => $utilitiesConcernsCount,
            ],
            [
                'column1' => 'Aesthetic and Cosmetics',
                'column2' => $aestheticAndCosmeticsCount,
            ],
            [
                'column1' => 'General Support',
                'column2' => $generalSupportCount,
            ],
            [
                'column1' => 'Others',
                'column2' => $othersCount,
            ],
            [
                'column1' => 'Total',
                'column2' => $totalCount,
            ],
        ]);
    }

    public function ticketMethodSummary(?array $filters): Collection
    {
        $query = Ticket::query();

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

        $tickets = $query->get();

        $concernTypes = [
            'maintenance and repair' => 'Maintenance and Repair',
            'safety and security' => 'Safety and Security',
            'cleanliness and sanitation' => 'Cleanliness and Sanitation',
            'lease and contractual' => 'Lease and Contractual Issues',
            'utilities concerns' => 'Utilities Concerns',
            'aesthetic and comestics' => 'Aesthetic and Cosmetics',
            'general support' => 'General Support',
            'others' => 'Others',
        ];

        return collect([
            [
                'column1' => 'Concern Type',
                'column2' => 'Total Count',
            ]
        ])->concat(
            collect($concernTypes)->map(function ($label, $type) use ($tickets) {
                return [
                    'column1' => $label,
                    'column2' => $tickets->where('concern_type', $type)->count(),
                ];
            })
        )->push([
            'column1' => 'Total',
            'column2' => $tickets->count(),
        ]);
    }
}
