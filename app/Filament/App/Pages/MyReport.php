<?php

namespace App\Filament\App\Pages;

use Filament\Pages\Page;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables;
use Filament\Tables\Table;
use App\Models\Ticket;
use Filament\Tables\Filters\SelectFilter;
use Illuminate\Support\Facades\Auth;

class MyReport extends Page implements HasTable
{
    use InteractsWithTable;

    protected static ?string $navigationIcon = 'heroicon-o-document-text';

    protected static string $view = 'filament.app.pages.my-report';

    protected static bool $shouldRegisterNavigation = false;

    public function table(Table $table): Table
    {
        return $table
            ->query(Ticket::query()
                ->where('created_by', Auth::id()))
            ->columns([
                Tables\Columns\TextColumn::make('incident_ticket_number')
                    ->label('Ticket Number')
                    ->description(fn($record) => $record->concern_type)
                    ->searchable(),
                Tables\Columns\TextColumn::make('concourse.name')
                    ->label('Concourse')
                    ->description(fn($record) => $record->space->name),
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->extraAttributes(['class' => 'capitalize'])
                    ->color(fn($record) => match ($record->status) {
                        'pending' => 'warning',
                        'in progress' => 'info',
                        'resolved' => 'success',
                        default => 'info', // Default color if none of the above
                    }),
                Tables\Columns\TextColumn::make('priority')
                    ->badge()
                    ->extraAttributes(['class' => 'capitalize'])
                    ->color(fn($record) => match ($record->priority) {
                        'low' => 'primary',
                        'medium' => 'warning',
                        'critical' => 'danger',
                        default => 'secondary', // Default color if none of the above
                    })
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime('F j, Y')
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('updated_at')
                    ->dateTime('F j, Y')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->options(Ticket::query()->select('status')->distinct()->pluck('status')),
                SelectFilter::make('concern_type')
                    ->options(Ticket::query()->select('concern_type')->distinct()->pluck('concern_type')),
            ])
            ->actions([
                Tables\Actions\Action::make('view')
                    ->icon('heroicon-o-eye')
                    ->label('View Details')
                    ->form([
                        \Filament\Forms\Components\Section::make()->schema([
                            \Filament\Forms\Components\Grid::make(2)->schema([
                                \Filament\Forms\Components\Section::make()->schema([
                                    \Filament\Forms\Components\TextInput::make('title')
                                        ->disabled()
                                        ->default(fn($record) => $record->title)
                                        ->columnSpanFull(),
                                    \Filament\Forms\Components\Textarea::make('description')
                                        ->disabled()
                                        ->default(fn($record) => $record->description)
                                        ->columnSpanFull(),
                                ])->columns(2),

                                \Filament\Forms\Components\Section::make()->schema([
                                    \Filament\Forms\Components\FileUpload::make('images')
                                        ->multiple()
                                        ->disabled()
                                        ->default(fn($record) => $record->images)
                                        ->columnSpanFull(),
                                ]),
                            ])->columnSpan([
                                'sm' => 3,
                                'md' => 3,
                                'lg' => 2
                            ]),
                            \Filament\Forms\Components\Grid::make(1)->schema([
                                \Filament\Forms\Components\Section::make()->schema([
                                    \Filament\Forms\Components\TextInput::make('concern_type')
                                        ->disabled()
                                        ->default(fn($record) => $record->concern_type),
                                    \Filament\Forms\Components\TextInput::make('priority')
                                        ->disabled()
                                        ->default(fn($record) => $record->priority),
                                    \Filament\Forms\Components\TextInput::make('created_by')
                                        ->label('Created by')
                                        ->disabled()
                                        ->default(fn($record) => $record->createdBy->name)
                                        ->extraAttributes(['class' => 'capitalize']),
                                    \Filament\Forms\Components\TextInput::make('assigned_to')
                                        ->label('Assigned to')
                                        ->disabled()
                                        ->default(fn($record) => $record->assignedTo->name ?? 'Not assigned')
                                        ->extraAttributes(['class' => 'capitalize']),
                                ]),

                                \Filament\Forms\Components\Section::make()->schema([
                                    \Filament\Forms\Components\Placeholder::make('created_at')
                                        ->label('Created at')
                                        ->hiddenOn('create')
                                        ->content(function (\Illuminate\Database\Eloquent\Model $record): String {
                                            $category = Ticket::find($record->id);
                                            $now = \Carbon\Carbon::now();

                                            $diff = $category->created_at->diff($now);
                                            if ($diff->y > 0) {
                                                return $diff->y . ' years ago';
                                            } elseif ($diff->m > 0) {
                                                if ($diff->m == 1) {
                                                    return '1 month ago';
                                                } else {
                                                    return $diff->m . ' months ago';
                                                }
                                            } elseif ($diff->d >= 7) {
                                                $weeks = floor($diff->d / 7);
                                                if ($weeks == 1) {
                                                    return 'a week ago';
                                                } else {
                                                    return $weeks . ' weeks ago';
                                                }
                                            } elseif ($diff->d > 0) {
                                                if ($diff->d == 1) {
                                                    return 'yesterday';
                                                } else {
                                                    return $diff->d . ' days ago';
                                                }
                                            } elseif ($diff->h > 0) {
                                                if ($diff->h == 1) {
                                                    return '1 hour ago';
                                                } else {
                                                    return $diff->h . ' hours ago';
                                                }
                                            } elseif ($diff->i > 0) {
                                                if ($diff->i == 1) {
                                                    return '1 minute ago';
                                                } else {
                                                    return $diff->i . ' minutes ago';
                                                }
                                            } elseif ($diff->s > 0) {
                                                if ($diff->s == 1) {
                                                    return '1 second ago';
                                                } else {
                                                    return $diff->s . ' seconds ago';
                                                }
                                            } else {
                                                return 'just now';
                                            }
                                        }),
                                    \Filament\Forms\Components\Placeholder::make('updated_at')
                                        ->label('Last modified at')
                                        ->content(function (\Illuminate\Database\Eloquent\Model $record): String {
                                            $category = Ticket::find($record->id);
                                            $now = \Carbon\Carbon::now();

                                            $diff = $category->updated_at->diff($now);
                                            if ($diff->y > 0) {
                                                return $diff->y . ' years ago';
                                            } elseif ($diff->m > 0) {
                                                if ($diff->m == 1) {
                                                    return '1 month ago';
                                                } else {
                                                    return $diff->m . ' months ago';
                                                }
                                            } elseif ($diff->d >= 7) {
                                                $weeks = floor($diff->d / 7);
                                                if ($weeks == 1) {
                                                    return 'a week ago';
                                                } else {
                                                    return $weeks . ' weeks ago';
                                                }
                                            } elseif ($diff->d > 0) {
                                                if ($diff->d == 1) {
                                                    return 'yesterday';
                                                } else {
                                                    return $diff->d . ' days ago';
                                                }
                                            } elseif ($diff->h > 0) {
                                                if ($diff->h == 1) {
                                                    return '1 hour ago';
                                                } else {
                                                    return $diff->h . ' hours ago';
                                                }
                                            } elseif ($diff->i > 0) {
                                                if ($diff->i == 1) {
                                                    return '1 minute ago';
                                                } else {
                                                    return $diff->i . ' minutes ago';
                                                }
                                            } elseif ($diff->s > 0) {
                                                if ($diff->s == 1) {
                                                    return '1 second ago';
                                                } else {
                                                    return $diff->s . ' seconds ago';
                                                }
                                            } else {
                                                return 'just now';
                                            }
                                        }),
                                ])->hiddenOn('create'),

                            ])->columnSpan([
                                'sm' => 3,
                                'md' => 3,
                                'lg' => 1
                            ]),

                        ])->columns(3),
                    ])
                    ->modalButton('Close')
                    ->modalActions(null),

            ]);
    }
}
