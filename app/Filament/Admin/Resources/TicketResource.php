<?php

namespace App\Filament\Admin\Resources;

use App\Filament\Admin\Resources\TicketResource\Pages;
use App\Filament\Admin\Resources\TicketResource\RelationManagers;
use App\Models\Ticket;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use BezhanSalleh\FilamentShield\Contracts\HasShieldPermissions;

class TicketResource extends Resource implements HasShieldPermissions
{
    public static function getPermissionPrefixes(): array
    {
        return [
            'view',
            'view_any',
            'create',
            'update',
            'delete',
            'delete_any',
            'publish'
        ];
    }

    protected static ?string $navigationGroup = 'Others';

    protected static ?string $model = Ticket::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Grid::make(2)->schema([
                    Forms\Components\Section::make()->schema([
                        Forms\Components\TextInput::make('incident_ticket_number')
                            ->required()
                            ->default(fn() => 'INC' . str_pad(rand(0, 99999999), 8, '0', STR_PAD_LEFT))
                            ->columnSpanFull(),
                    ])->hiddenOn('create'),
                    Forms\Components\Section::make()->schema([

                        Forms\Components\Select::make('concourse_id')
                            ->relationship('concourse', 'name')
                            ->required()
                            ->native(false)
                            ->disabled(fn() => !auth()->user()->hasRole('super_admin')),
                        Forms\Components\Select::make('space_id')
                            ->relationship('space', 'name', function ($query, $get) {
                                return $query->when($get('concourse_id'), function ($query, $concourseId) {
                                    return $query->where('concourse_id', $concourseId);
                                });
                            })
                            ->required()
                            ->disabled(fn() => !auth()->user()->hasRole('super_admin'))
                            ->reactive()
                            ->native(false),
                        Forms\Components\TextInput::make('title')
                            ->required()
                            ->maxLength(255)
                            ->columnSpanFull(),
                        Forms\Components\Textarea::make('description')
                            ->required()
                            ->columnSpanFull(),
                    ])->columns(2),

                    Forms\Components\Section::make()->schema([
                        Forms\Components\FileUpload::make('images')
                            ->multiple()
                            ->columnSpanFull(),
                    ]),
                ])->columnSpan([
                    'sm' => 3,
                    'md' => 3,
                    'lg' => 2
                ]),
                Forms\Components\Grid::make(1)->schema([
                    Forms\Components\Section::make()->schema([
                        Forms\Components\Select::make('concern_type')
                            ->options([
                                'maintenance and repair' => 'Maintenance and Repair',
                                'safety and security' => 'Safety and Security',
                                'cleanliness and sanitation' => 'Cleanliness and Sanitation',
                                'lease and contractual' => 'Lease and Contractual Issues',
                                'utilities concerns' => 'Utilities Concerns',
                                'aesthetic and comestics' => 'Aesthetic and Comestics Issues',
                                'general support' => 'General Support',
                                'others' => 'Others',
                            ])
                            ->native(false)
                            ->required(),
                        Forms\Components\Select::make('priority')
                            ->required()
                            ->options([
                                'low' => 'Low',
                                'medium' => 'Medium',
                                'critical' => 'Critical',
                            ])
                            ->default('low'),
                        Forms\Components\Select::make('created_by')
                            ->label('Created by')
                            ->relationship('createdBy', 'name')
                            ->required()
                            ->default(auth()->user()->id)
                            ->disabled(),
                        Forms\Components\Select::make('assigned_to')
                            ->label('Assigned to')
                            ->relationship('assignedTo', 'name', fn($query) => $query->whereHas('roles', function ($q) {
                                $q->where('name', 'analyst');
                            }))
                            ->searchable()
                            ->preload()
                            ->native(false)
                            ->afterStateUpdated(fn($state, $record) => $record->status = 'in_progress'),
                    ]),

                    Forms\Components\Section::make()->schema([
                        Forms\Components\Placeholder::make('created_at')
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
                        Forms\Components\Placeholder::make('updated_at')
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

            ])->columns(3);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('incident_ticket_number')
                    ->label('INC Ticket & Concern Type')
                    ->description(fn($record) => $record->concern_type)
                    ->searchable(),
                Tables\Columns\TextColumn::make('concourse.name')
                    ->description(fn($record) => $record->space->name)
                    ->sortable(),
                Tables\Columns\TextColumn::make('createdBy.name')
                    ->label('Tenant')
                    ->searchable(),
                Tables\Columns\TextColumn::make('remarks')
                    ->limit(10)
                    ->default(fn($record) => $record->remarks ? $record->remarks : 'No remarks'),
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->extraAttributes(['class' => 'capitalize'])
                    ->color(fn($record) => match ($record->status) {
                        'pending' => 'warning',
                        'in_progress' => 'info',
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
                    }),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->options(Ticket::query()->select('status')->distinct()->pluck('status')),
                SelectFilter::make('concern_type')
                    ->options(Ticket::query()->select('concern_type')->distinct()->pluck('concern_type')),
            ])
            ->actions([
                Tables\Actions\EditAction::make()
                    ->visible(fn($record) => auth()->user()->hasRole('super_admin') || auth()->user()->hasRole('analyst') || $record->assigned_to == auth()->user()->name),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListTickets::route('/'),
            'create' => Pages\CreateTicket::route('/create'),
            'edit' => Pages\EditTicket::route('/{record}/edit'),
        ];
    }
}
