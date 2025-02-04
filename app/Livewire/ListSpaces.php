<?php

namespace App\Livewire;

use App\Models\Concourse;
use App\Models\Space;
use App\Models\User;
use App\Services\RequirementForm;
use Livewire\Component;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Actions\Action;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Mail;
use App\Mail\NewApplicationSubmitted;

class ListSpaces extends Component implements HasTable, HasForms
{
    use InteractsWithForms, InteractsWithTable;

    public $concourseId;
    public $concourse_lease_term;
    public $concourse;
    public $spaces;
    public $selectedSpace = null;
    public $showModal = false;

    public function mount()
    {
        $this->concourseId = request()->query('concourse_id');
        $this->concourse = Concourse::find($this->concourseId);
        $this->concourse_lease_term = $this->concourse->lease_term;
        $this->spaces = Space::where('concourse_id', $this->concourseId)->get();
    }

    public function render()
    {
        return view('livewire.list-spaces');
    }

    public function showSpaceDetails($spaceId)
    {
        $this->selectedSpace = Space::find($spaceId);
        $this->showModal = true;
    }

    public function closeModal()
    {
        $this->showModal = false;
        $this->selectedSpace = null;
    }

    private function createApplication(array $data, $record)
    {
        // Create the application
        $application = \App\Models\Application::create($data);

        // Store the uploaded requirements
        if (isset($data['requirements'])) {
            foreach ($data['requirements'] as $requirementId => $file) {
                if ($file) {
                    \App\Models\AppRequirement::create([
                        'requirement_id' => $requirementId,
                        'user_id' => Auth::id(),
                        'space_id' => $record->id,
                        'concourse_id' => $this->concourseId,
                        'application_id' => $application->id,
                        'name' => \App\Models\Requirement::find($requirementId)->name,
                        'status' => 'pending',
                        'file' => $file,
                    ]);
                }
            }
        }

        // Update space status
        if ($record) {
            $record->update([
                'user_id' => Auth::id(),
                'status' => 'pending'
            ]);
        }

        // Send notifications
        $this->sendApplicationNotifications($application);

        return $application;
    }

    private function sendApplicationNotifications($application)
    {
        Notification::make()
            ->title('Application Submitted')
            ->body('Your application has been submitted.')
            ->icon('heroicon-o-document-text')
            ->sendToDatabase(Auth::user());

        Notification::make()
            ->title('New Application')
            ->body('A new application has been submitted.')
            ->icon('heroicon-o-document-text')
            ->sendToDatabase(User::find(1));

        // Send email to admin
        $admin = User::find(1);
        if ($admin) {
            Mail::to($admin->email)->send(new NewApplicationSubmitted($application));
        }
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(Space::query()
                ->where('is_active', true)
                ->when($this->concourseId, function ($query) {
                    $query->where('concourse_id', $this->concourseId);
                }))
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Space Name')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('user.name')
                    ->label('Tenant')
                    ->searchable()
                    ->state(function ($record) {
                        return $record->user ? $record->user->name : 'No Tenant';
                    }),
                Tables\Columns\TextColumn::make('price')
                    ->searchable()
                    ->sortable()
                    ->money('PHP'),
                Tables\Columns\TextColumn::make('sqm')
                    ->label('Size (sqm)')
                    ->searchable()
                    ->sortable()
                    ->suffix(' sqm'),
                Tables\Columns\TextColumn::make('concourse.lease_term')
                    ->label('Lease Term')
                    ->searchable()
                    ->sortable()
                    ->formatStateUsing(function ($state) {
                        if ($state == 1) {
                            return $state . ' Month';
                        } else {
                            return $state . ' Months';
                        }
                    }),
                Tables\Columns\TextColumn::make('status')
                    ->searchable()
                    ->sortable()
                    ->badge()
                    ->extraAttributes(['class' => 'capitalize']),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime('F j, Y')
                    ->color('gray')
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('updated_at')
                    ->since()
                    ->color('gray')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->options([
                        'available' => 'Available',
                        'occupied' => 'Occupied',
                        'pending' => 'Pending',
                    ]),
            ])
            ->actions([
                Tables\Actions\CreateAction::make()
                    ->disableCreateAnother()
                    ->modalSubmitActionLabel('Submit')
                    ->label('Apply Now')
                    ->modalHeading('Application Form')
                    ->slideOver()
                    ->icon('heroicon-o-plus')
                    ->form(function ($record) {
                        $spaceId = $record ? $record->id : null;
                        return RequirementForm::schema($this->concourseId, $spaceId, $this->concourse_lease_term);
                    })
                    ->using(function (array $data, $record) {
                        return $this->createApplication($data, $record);
                    })
                    ->hidden(function ($record) {
                        if (!$record) return true; // Hide if no record (shouldn't happen, but just in case)

                        // Hide if space is not available
                        if ($record->status !== 'available') return true;

                        // Hide if user already has an application for this space
                        return \App\Models\Application::where('user_id', Auth::id())
                            ->where('concourse_id', $this->concourseId)
                            ->where('space_id', $record->id)
                            ->exists();
                    }),
                Tables\Actions\Action::make('Check Application')
                    ->link()
                    ->icon('heroicon-o-pencil')
                    ->url(fn($record) => route('filament.app.pages.edit-requirement', ['concourse_id' => $this->concourseId, 'space_id' => $record->id, 'user_id' => Auth::id()]))
                    ->openUrlInNewTab()
                    ->visible(function ($record) {
                        // Hide if status is approved
                        if ($record->status === 'approved') {
                            return false;
                        }

                        return \App\Models\Application::where('user_id', Auth::id())
                            ->where('concourse_id', $this->concourseId)
                            ->where('space_id', $record->id)
                            ->exists();
                    }),
            ])
            ->poll('3s');
    }
}
