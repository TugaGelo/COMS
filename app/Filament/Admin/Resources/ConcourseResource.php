<?php

namespace App\Filament\Admin\Resources;

use App\Filament\Admin\Resources\ConcourseResource\Pages;
use App\Filament\Admin\Resources\ConcourseResource\RelationManagers;
use App\Filament\Admin\Resources\ConcourseResource\RelationManagers\SpaceRelationManager;
use App\Models\Concourse;
use App\Models\ConcourseRate;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use pxlrbt\FilamentExcel\Actions\Tables\ExportBulkAction;
use Cheesegrits\FilamentGoogleMaps\Fields\Map;
use Cheesegrits\FilamentGoogleMaps\Fields\Geocomplete;
use BezhanSalleh\FilamentShield\Contracts\HasShieldPermissions;

class ConcourseResource extends Resource implements HasShieldPermissions
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


    protected static ?string $navigationGroup = 'Concourse Settings';

    protected static ?string $navigationLabel = 'Concourse';

    protected static ?string $model = Concourse::class;

    protected static ?string $navigationIcon = 'heroicon-o-building-office';

    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::count();
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Map::make('location')
                    ->reactive()
                    ->afterStateUpdated(function ($state, callable $get, callable $set) {
                        $set('lat', $state['lat']);
                        $set('lng', $state['lng']);
                    })
                    ->autocomplete(
                        fieldName: 'location',
                        types: ['geocode'],
                        placeField: 'address',
                        countries: ['PH'],
                    )
                    ->mapControls([
                        'mapTypeControl'    => true,
                        'scaleControl'      => true,
                        'streetViewControl' => true,
                        'rotateControl'     => true,
                        'fullscreenControl' => true,
                        'searchBoxControl'  => false, // creates geocomplete field inside map
                        'zoomControl'       => false,
                    ])
                    ->height(fn() => '400px') // map height (width is controlled by Filament options)
                    ->defaultZoom(15) // default zoom level when opening form
                    ->autocomplete('address') // field on form to use as Places geocompletion field
                    ->autocompleteReverse(true) // reverse geocode marker location to autocomplete field
                    ->reverseGeocode([
                        'street' => '%n %S',
                        'city' => '%L',
                        'state' => '%A1',
                        'zip' => '%z',
                    ]) // reverse geocode marker location to form fields, see notes below
                    ->defaultLocation([14.599512, 120.984222]) // default for new forms Manila
                    ->draggable() // allow dragging to move marker
                    // ->clickable(true) // allow clicking to move marker
                    // ->geolocate() // adds a button to request device location and set map marker accordingly
                    // ->geolocateLabel('Get Location') // overrides the default label for geolocate button
                    // ->geolocateOnLoad(true, false) // geolocate on load, second arg 'always' (default false, only for new form))
                    ->columnSpanFull(),
                Forms\Components\Grid::make(2)->schema([
                    Forms\Components\Section::make()->schema([
                        Forms\Components\Section::make('Concourse Details')->schema([
                            Geocomplete::make('location')
                                ->isLocation()
                                ->default(fn($record) => $record->address ?? null)
                                ->countries(['PH'])
                                ->reverseGeocode([
                                    'city'   => '%L',
                                    'zip'    => '%z',
                                    'state'  => '%A1',
                                    'street' => '%n %S',
                                ])
                                ->placeholder('Start typing an address ...')
                                ->reactive()
                                ->default(fn($record) => $record->address ?? null)
                                ->afterStateUpdated(function ($state, callable $set) {
                                    if (is_array($state) && isset($state['formatted_address'])) {
                                        $set('address', $state['formatted_address']);
                                    }
                                })->columnSpanFull(),
                            Forms\Components\TextInput::make('address')
                                ->label('Address')
                                ->required()
                                ->readOnly()
                                ->dehydrated(),
                            Forms\Components\Grid::make()->schema([
                                Forms\Components\TextInput::make('name')
                                    ->required()
                                    ->maxLength(255),
                                Forms\Components\Select::make('rate_id')
                                    ->native(false)
                                    ->preload()
                                    ->relationship('concourseRate', 'name')
                                    ->getOptionLabelFromRecordUsing(fn(ConcourseRate $record) => "{$record->name} - ₱{$record->price}")
                                    ->getSearchResultsUsing(fn(string $search) => ConcourseRate::where('name', 'like', "%{$search}%")->limit(50)->pluck('name', 'id'))
                                    ->required(),
                                Forms\Components\Select::make('lease_term')
                                    ->default(12)
                                    ->native(false)
                                    ->required()
                                    ->options([
                                        '3' => '3 months',
                                        '6' => '6 months',
                                        '12' => '1 year',
                                        '24' => '2 years',
                                        '36' => '3 years',
                                    ]),
                                Forms\Components\Hidden::make('lat')
                                    ->reactive()
                                    ->afterStateUpdated(function ($state, callable $get, callable $set) {
                                        $set('location', [
                                            'lat' => floatVal($state),
                                            'lng' => floatVal($get('lng')),
                                        ]);
                                    })
                                    ->lazy(), // important to use lazy, to avoid updates as you type
                                Forms\Components\Hidden::make('lng')
                                    ->reactive()
                                    ->afterStateUpdated(function ($state, callable $get, callable $set) {
                                        $set('location', [
                                            'lat' => floatval($get('lat')),
                                            'lng' => floatVal($state),
                                        ]);
                                    })
                                    ->lazy(), // important to use lazy, to avoid updates as you type
                            ])->columns(3),
                        ]),

                        Forms\Components\Section::make('Attachments')->schema([
                            Forms\Components\FileUpload::make('image')
                                ->image()
                                ->label('Concourse Image')
                                ->imageEditor()
                                ->openable()
                                ->downloadable()
                                ->disk('public'),
                            Forms\Components\FileUpload::make('layout')
                                ->image()
                                ->label('Space Layout')
                                ->imageEditor()
                                ->openable()
                                ->downloadable()
                                ->disk('public'),
                        ])->columns(2),
                    ])->columnSpan([
                        'sm' => 3,
                        'md' => 3,
                        'lg' => 2
                    ]),

                    Forms\Components\Grid::make(1)->schema([
                        Forms\Components\Section::make('Visibility in Tenant')->schema([
                            Forms\Components\Toggle::make('is_active')
                                ->onIcon('heroicon-s-eye')
                                ->offIcon('heroicon-s-eye-slash')
                                ->label('Visible')
                                ->default(true),
                        ]),

                        Forms\Components\Section::make()->schema([
                            Forms\Components\Placeholder::make('created_at')
                                ->label('Created at')
                                ->hiddenOn('create')
                                ->content(function (\Illuminate\Database\Eloquent\Model $record): String {
                                    $category = Concourse::find($record->id);
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
                                    $category = Concourse::find($record->id);
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
                        ])->hiddenOn('create')

                    ])->columnSpan([
                        'sm' => 3,
                        'md' => 3,
                        'lg' => 1
                    ]),

                ])->columns(3)
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\ImageColumn::make('image')
                    ->square()
                    ->width(150)
                    ->height(150)
                    ->label('Concourse Image')
                    ->defaultImageUrl(fn($record) => $record->image === null ? asset('https://placehold.co/600x800') : null),
                Tables\Columns\ImageColumn::make('layout')
                    ->square()
                    ->width(150)
                    ->height(150)
                    ->label('Space Layout')
                    ->defaultImageUrl(fn($record) => $record->layout === null ? asset('https://placehold.co/600x800') : null)
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('name')
                    ->sortable()
                    ->searchable()
                    ->description(fn($record) => $record->address),
                Tables\Columns\TextColumn::make('address')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('concourseRate.price')
                    ->label('Rate')
                    ->prefix('₱')
                    ->money('PHP')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('Total Spaces')
                    ->label('Spaces')
                    ->default(fn($record) => $record->spaces()->count())
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('updated_at')
                    ->since()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\ToggleColumn::make('is_active')
                    ->label('Active')
                    ->onIcon('heroicon-m-bolt')
                    ->offIcon('heroicon-m-bolt-slash')
                    ->sortable(),
            ])
            ->filters([
                TrashedFilter::make(),
                SelectFilter::make('is_active')
                    ->options([
                        '1' => 'Active',
                        '0' => 'Inactive',
                    ])
                    ->label('Active'),
            ])
            ->actions([
                Tables\Actions\ActionGroup::make([
                    Tables\Actions\ViewAction::make()->color('info'),
                    Tables\Actions\EditAction::make()->color('primary'),
                    Tables\Actions\Action::make('bills')
                        ->label('Bills')
                        ->icon('heroicon-o-credit-card')
                        ->url(fn(Concourse $record): string => route('filament.admin.pages.concourse-spaces', ['concourseId' => $record->id])),
                    Tables\Actions\DeleteAction::make()->label('Archive')->visible(fn($record) => $record->spaces()->count() === 0),
                    Tables\Actions\RestoreAction::make(),
                    Tables\Actions\ForceDeleteAction::make()->label('Permanent Delete'),
                    Tables\Actions\Action::make('viewSpaces')
                        ->label('View Layout')
                        ->icon('heroicon-o-map')
                        ->url(fn(Concourse $record): string => route('filament.admin.resources.concourses.view-spaces', ['record' => $record->id]))
                        ->color('success'),
                ])
                    ->icon('heroicon-m-ellipsis-vertical')
                    ->tooltip('Actions')
            ])
            ->bulkActions([
                ExportBulkAction::make(),
                // Tables\Actions\BulkActionGroup::make([
                //     Tables\Actions\DeleteBulkAction::make(),
                //     Tables\Actions\RestoreBulkAction::make(),
                //     Tables\Actions\ForceDeleteBulkAction::make(),
                // ]),
            ])
            ->poll('30s');
    }

    public static function getRelations(): array
    {
        return [
            SpaceRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListConcourses::route('/'),
            'create' => Pages\CreateConcourse::route('/create'),
            'edit' => Pages\EditConcourse::route('/{record}/edit'),
            'view-spaces' => Pages\ViewSpaceConcourses::route('/{record}/spaces'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);
    }
}
