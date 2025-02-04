<?php

namespace App\Livewire;

use App\Models\Concourse;
use Livewire\Component;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Enums\FiltersLayout;

class ListConcourses extends Component implements HasTable, HasForms
{
    use InteractsWithForms, InteractsWithTable;

    public $concourses;

    public function mount()
    {
        $this->concourses = Concourse::where('is_active', true)->get();
    }

    public function render()
    {
        return view('livewire.list-concourses');
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(Concourse::query()->where('is_active', true))
            ->contentGrid([
                'md' => 2,
                'lg' => 3,
            ])
            ->columns([
                Tables\Columns\Layout\Stack::make([
                    Tables\Columns\ImageColumn::make('image')
                        ->width('100%')
                        ->height('100%')
                        ->defaultImageUrl(
                            fn($record) =>
                            $record->image === null
                                ? 'https://placehold.co/600x250?text=No+Image'
                                : null
                        ),
                    Tables\Columns\TextColumn::make('name')
                        ->weight('bold')
                        ->sortable()
                        ->searchable(),
                    Tables\Columns\TextColumn::make('address')
                        ->color('gray'),
                    Tables\Columns\TextColumn::make('spaces_count')
                        ->counts('spaces')
                        ->sortable()
                        ->prefix('Spaces: ')
                        ->label('Spaces'),
                    Tables\Columns\TextColumn::make('available_spaces')
                        ->label('Available Spaces')
                        ->default(fn($record) => 'Available: ' . $record->spaces()->where('status', 'available')->count()),
                ]),
            ])
            ->filters([])
            ->actions([
                Tables\Actions\Action::make('view_spaces')
                    ->label('View Spaces')
                    ->icon('heroicon-o-rectangle-stack')
                    ->url(fn($record) => route('filament.app.pages.list-space-table', ['concourse_id' => $record->id]))
                    ->openUrlInNewTab(),
                // Tables\Actions\ViewAction::make(),
                // Tables\Actions\EditAction::make(),
                // Tables\Actions\DeleteAction::make(),
            ])
            ->headerActions([
                // Tables\Actions\CreateAction::make()
                //     ->model(Concourse::class)
                //     ->form([
                //         \Filament\Forms\Components\TextInput::make('name')
                //             ->required(),
                //     ]),
            ]);
    }
}
