<?php

namespace App\Filament\Reports;

use EightyNine\Reports\Report;
use EightyNine\Reports\Components\Body;
use EightyNine\Reports\Components\Footer;
use EightyNine\Reports\Components\Header;
use App\Models\Payment;
use EightyNine\Reports\Components\Text;
use EightyNine\Reports\Components\VerticalSpace;
use Filament\Forms\Form;
use Illuminate\Support\Collection;

class RentIncomeReport extends Report
{
    public ?string $heading = "Rent Income/Loss Report";

    public function header(Header $header): Header
    {
        return $header
            ->schema([
                Header\Layout\HeaderRow::make()
                    ->schema([
                        Header\Layout\HeaderColumn::make()
                            ->schema([
                                Text::make('Rent Income/Loss Report')
                                    ->title(),
                                Text::make('This report shows rent payments and income analysis')
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
                        Body\Table::make()
                            ->data(fn(?array $filters) => $this->paymentsSummary($filters)),
                        VerticalSpace::make(),
                        Body\Table::make()
                            ->data(fn(?array $filters) => $this->paymentMethodSummary($filters)),
                        VerticalSpace::make(),
                        Body\Table::make()
                            ->data(fn(?array $filters) => $this->grossAmountSummary($filters)),
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
                \Filament\Forms\Components\Select::make('month')
                    ->label('Select Month')
                    ->native(false)
                    ->columnSpan('full')
                    ->options(array_combine(
                        range(1, 12),
                        array_map(fn($m) => date('F', mktime(0, 0, 0, $m, 1)),
                        range(1, 12))
                    ))
                    ->default(now()->month)
                    ->reactive(),

                \Filament\Forms\Components\DatePicker::make('date_from')
                    ->label('Date From')
                    ->native(false)
                    ->columnSpan('full'),
                
                \Filament\Forms\Components\DatePicker::make('date_to')
                    ->label('Date To')
                    ->native(false)
                    ->columnSpan('full'),

                \Filament\Forms\Components\Select::make('payment_status')
                    ->label('Payment Status')
                    ->options([
                        Payment::STATUS_PAID => 'Paid',
                        Payment::STATUS_UNPAID => 'Unpaid',
                        Payment::STATUS_DELAYED => 'Delayed',
                    ])
                    ->native(false)
                    ->columnSpan('full'),
            ])
            ->columns(1);
    }

    protected function applyFilters($query, ?array $filters)
    {
        if (isset($filters['month'])) {
            $query->whereMonth('created_at', $filters['month'])
                  ->whereYear('created_at', now()->year);
        }

        if (isset($filters['payment_status'])) {
            $query->where('payment_status', $filters['payment_status']);
        }

        if (isset($filters['date_from'])) {
            $query->whereDate('created_at', '>=', $filters['date_from']);
        }

        if (isset($filters['date_to'])) {
            $query->whereDate('created_at', '<=', $filters['date_to']);
        }

        return $query;
    }

    public function paymentsSummary(?array $filters): Collection
    {
        if (empty($filters)) {
            return collect();
        }

        $query = Payment::query()->where('rent_bill', '>', 0);
        $query = $this->applyFilters($query, $filters);
        $payments = $query->get();

        return collect([
            [
                'column1' => 'Date',
                'column2' => 'Tenant',
                'column3' => 'Amount',
                'column4' => 'Payment Status',
                'column5' => 'Date Paid',
                'column6' => 'Due Date',
            ]
        ])->concat($payments->map(function ($payment) {
            return [
                'column1' => $payment->created_at->format('F d, Y'),
                'column2' => $payment->tenant->name,
                'column3' => number_format($payment->rent_bill, 2),
                'column4' => $payment->payment_status,
                'column5' => $payment->paid_date?->format('F d, Y') ?? '-',
                'column6' => $payment->due_date?->format('F d, Y') ?? '-',
            ];
        }));
    }

    public function paymentMethodSummary(?array $filters): Collection
    {
        if (empty($filters)) {
            return collect();
        }

        $query = Payment::query()->where('rent_bill', '>', 0);
        $query = $this->applyFilters($query, $filters);
        $paymentMethods = $query->get()->groupBy('payment_method');

        $monthLabel = isset($filters['month']) 
            ? date('F', mktime(0, 0, 0, $filters['month'], 1))
            : 'All Months';

        return collect([
            [
                'column1' => "Total Transactions ($monthLabel)",
                'column2' => "Total Income ($monthLabel)",
                'column3' => "Amount of Income Loss ($monthLabel)",
                // 'column5' => "Payments for $monthLabel",
            ]
        ])->concat($paymentMethods->map(function ($payments, $method) {
            $onTimePayments = $payments->filter(fn($p) => 
                $p->paid_date && $p->due_date && $p->paid_date->lte($p->due_date)
            );
            $latePayments = $payments->filter(fn($p) => 
                $p->paid_date && $p->due_date && $p->paid_date->gt($p->due_date)
            );

            // Get unique payment dates and format them
            $paymentDates = $payments->pluck('paid_date')
                ->filter()
                ->unique(function ($date) {
                    return $date->format('Y-m-d');
                })
                ->map->format('F d, Y')
                ->map(fn($date) => "• {$date}")
                ->join("\n");

            return [
                'column1' => $payments->count(),
                'column2' => number_format($onTimePayments->sum('rent_bill'), 2),
                'column3' => number_format($latePayments->sum('rent_bill'), 2),
                // 'column5' => $paymentDates ?: '- No payments -',
            ];
        }));
    }

    public function grossAmountSummary(?array $filters): Collection
    {
        if (empty($filters)) {
            return collect();
        }

        $query = Payment::query()->where('rent_bill', '>', 0);
        $query = $this->applyFilters($query, $filters);
        $payments = $query->get();

        $monthLabel = isset($filters['month']) 
            ? date('F', mktime(0, 0, 0, $filters['month'], 1))
            : 'All Months';

        // Calculate monthly totals
        $onTimeTotal = $payments->filter(fn($p) => 
            $p->paid_date && $p->due_date && $p->paid_date->lte($p->due_date)
        )->sum('rent_bill');
        
        $lateTotal = $payments->filter(fn($p) => 
            $p->paid_date && $p->due_date && $p->paid_date->gt($p->due_date)
        )->sum('rent_bill');

        $unpaidTotal = $payments->filter(fn($p) => 
            $p->payment_status === Payment::STATUS_UNPAID
        )->sum('rent_bill');

        return collect([
            [
                'column1' => 'Summary Type',
                'column2' => "Amount ($monthLabel)",
            ],
            [
                'column1' => 'Present Gross Income',
                'column2' => number_format($onTimeTotal, 2),
            ],
            [
                'column1' => 'Present Gross Loss',
                'column2' => number_format($lateTotal, 2),
            ],
            [
                'column1' => 'Unpaid Amount',
                'column2' => number_format($unpaidTotal, 2),
            ],
            [
                'column1' => 'Total Expected Income',
                'column2' => number_format($onTimeTotal + $lateTotal + $unpaidTotal, 2),
            ],
        ]);
    }
}
