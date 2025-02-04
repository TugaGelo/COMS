<?php

namespace App\Filament\Admin\Resources;

use App\Filament\Admin\Resources\RenewResource\Pages;
use App\Models\Renew;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use BezhanSalleh\FilamentShield\Contracts\HasShieldPermissions;

class RenewResource extends Resource implements HasShieldPermissions
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
    
    protected static ?string $navigationGroup = 'Applications Settings';

    protected static ?string $navigationLabel = 'Renewal';

    protected static ?string $model = Renew::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::where('application_status', 'pending')
            ->orWhere('application_status', 'renewal')
            ->count();
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make()
                    ->schema([
                        Forms\Components\Grid::make()->schema([
                            Forms\Components\Select::make('concourse_id')
                                ->relationship('concourse', 'name')
                                ->required()
                                ->searchable()
                                ->preload(),
                            Forms\Components\Select::make('space_id')
                                ->relationship('space', 'name')
                                ->required()
                                ->searchable()
                                ->preload(),
                            Forms\Components\Select::make('user_id')
                                ->relationship('user', 'name')
                                ->required()
                                ->searchable()
                                ->preload()
                                ->label('Tenant'),
                            Forms\Components\TextInput::make('business_name')
                                ->maxLength(255)
                                ->default(null),
                            Forms\Components\TextInput::make('owner_name')
                                ->maxLength(255)
                                ->default(null),
                            Forms\Components\TextInput::make('email')
                                ->email()
                                ->maxLength(255)
                                ->default(null),
                            Forms\Components\TextInput::make('phone_number')
                                ->tel()
                                ->maxLength(255)
                                ->default(null),
                            Forms\Components\Select::make('business_type')
                                ->label('Business Type')
                                ->extraInputAttributes(['class' => 'capitalize'])
                                ->options([
                                    'food' => 'Food',
                                    'non-food' => 'Non Food',
                                    'other' => 'Other',
                                ])
                                ->native(false),
                            Forms\Components\TextInput::make('concourse_lease_term')
                                ->label('Concourse Lease Term')
                                ->disabled()
                                ->suffix('Months'),
                        ])->columns(3),
                        Forms\Components\TextInput::make('address')
                            ->maxLength(255)
                            ->default(null)
                            ->columnSpanFull(),
                        Forms\Components\TextInput::make('remarks')
                            ->maxLength(255)
                            ->default(null)
                            ->columnSpanFull(),
                    ])->columns(2),
                Forms\Components\Section::make('List of Required Documents')
                    ->description('Approved each documents for the application')
                    ->schema([
                        Forms\Components\Repeater::make('renewAppRequirements')
                            ->relationship('renewAppRequirements')
                            ->schema([
                                Forms\Components\Grid::make(2)->schema([
                                    Forms\Components\FileUpload::make('file')
                                        ->disk('public')
                                        ->directory('app-requirements')
                                        ->visibility('public')
                                        ->downloadable()
                                        ->disabled()
                                        ->openable()
                                        ->columnSpanFull(),
                                ])->columnSpan([
                                    'sm' => 3,
                                    'md' => 3,
                                    'lg' => 2
                                ]),
                                Forms\Components\Grid::make(1)->schema([
                                    Forms\Components\TextInput::make('name')
                                        ->required()
                                        ->disabled()
                                        ->readOnly(),
                                    Forms\Components\Select::make('status')
                                        ->required()
                                        ->options([
                                            'pending' => 'Pending',
                                            'approved' => 'Approved',
                                            'rejected' => 'Rejected',
                                        ]),
                                    Forms\Components\TextInput::make('remarks')
                                        ->label('Remarks')
                                        ->maxLength(255)
                                        ->default(null),
                                ])->columnSpan([
                                    'sm' => 3,
                                    'md' => 3,
                                    'lg' => 1
                                ]),
                            ])
                            ->columns(3)
                            ->columnSpanFull()
                            ->defaultItems(0)
                            ->disableItemCreation()
                            ->disableItemDeletion(),
                    ])->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('user.name')
                    ->sortable()
                    ->label('Tenant'),
                Tables\Columns\TextColumn::make('concourse.name')
                    ->sortable()
                    ->copyable(),
                Tables\Columns\TextColumn::make('space.name')
                    ->sortable()
                    ->copyable(),
                Tables\Columns\TextColumn::make('business_name')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('owner_name')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('address')
                    ->searchable()
                    ->limit(20)
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('phone_number')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('email')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('business_type')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('requirements_status')
                    ->searchable()
                    ->badge()
                    ->color(fn($state) => $state === 'approved' ? 'success' : 'warning')
                    ->extraAttributes(['class' => 'capitalize']),
                Tables\Columns\TextColumn::make('application_status')
                    ->searchable()
                    ->badge()
                    ->color(fn($state) => $state === 'approved' ? 'secondary' : 'danger')
                    ->extraAttributes(['class' => 'capitalize']),
                Tables\Columns\TextColumn::make('space_type')
                    ->searchable()
                    ->extraAttributes(['class' => 'capitalize'])
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('remarks')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\ToggleColumn::make('is_active')
                    ->label('Active')
                    ->onIcon('heroicon-m-bolt')
                    ->offIcon('heroicon-m-bolt-slash')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('deleted_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
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
                Tables\Filters\TrashedFilter::make(),
                Tables\Filters\SelectFilter::make('is_active')
                    ->options([
                        '1' => 'Active',
                        '0' => 'Inactive',
                    ])
                    ->label('Active'),
                Tables\Filters\SelectFilter::make('requirements_status')
                    ->options([
                        'pending' => 'Pending',
                        'approved' => 'Approved',
                        'rejected' => 'Rejected',
                    ])
                    ->label('Requirements Status'),
                Tables\Filters\SelectFilter::make('application_status')
                    ->options([
                        'pending' => 'Pending',
                        'approved' => 'Approved',
                        'rejected' => 'Rejected',
                    ])
                    ->label('Application Status'),
            ])
            ->actions([
                Tables\Actions\ActionGroup::make([
                    Tables\Actions\ViewAction::make()->color('info'),
                    Tables\Actions\EditAction::make()->color('primary'),
                    Tables\Actions\DeleteAction::make()->label('Archive'),
                    Tables\Actions\RestoreAction::make(),
                    Tables\Actions\ForceDeleteAction::make()->label('Permanent Delete'),
                ])
                    ->icon('heroicon-m-ellipsis-vertical')
                    ->tooltip('Actions')

            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->poll('3s');
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
            'index' => Pages\ListRenews::route('/'),
            'create' => Pages\CreateRenew::route('/create'),
            'edit' => Pages\EditRenew::route('/{record}/edit'),
        ];
    }
}
