<?php

namespace App\Filament\App\Pages;

use App\Models\Application;
use App\Models\AppRequirement;
use App\Models\Requirement;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Auth;
use Filament\Notifications\Notification;

class EditRequirement extends Page implements Forms\Contracts\HasForms
{
    use Forms\Concerns\InteractsWithForms;

    protected static ?string $navigationLabel = 'Edit Application';
    protected static ?string $navigationIcon = 'heroicon-o-document-text';
    protected static string $view = 'filament.app.pages.edit-requirement';
    protected static bool $shouldRegisterNavigation = false;

    public ?array $data = [];
    public ?Application $application;
    public $appRequirements;
    public $allRequirements;

    public function mount(): void
    {
        $concourseId = request()->query('concourse_id');
        $spaceId = request()->query('space_id');
        $userId = Auth::id();

        $this->application = Application::where('concourse_id', $concourseId)
            ->where('space_id', $spaceId)
            ->where('user_id', $userId)
            ->firstOrFail();

        $this->appRequirements = AppRequirement::where('application_id', $this->application->id)->get();

        // Fetch all requirements for the concourse
        // Adjust this query based on your actual database structure
        $this->allRequirements = Requirement::all();

        $formData = $this->application->toArray();
        foreach ($this->allRequirements as $requirement) {
            $appRequirement = $this->appRequirements->firstWhere('requirement_id', $requirement->id);
            $formData['requirements'][$requirement->id] = $appRequirement ? $appRequirement->file : null;
            $formData['requirement_status'][$requirement->id] = $appRequirement ? $appRequirement->status : 'pending';
            $formData['remarks'][$requirement->id] = $appRequirement ? $appRequirement->remarks : null;
        }
        $this->form->fill($formData);
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Business Information')
                    ->schema([
                        Forms\Components\TextInput::make('business_name')
                            ->label('Business Name'),
                        Forms\Components\TextInput::make('owner_name')
                            ->label('Owner Name'),
                        Forms\Components\TextInput::make('email')
                            ->label('Email')
                            ->email()
                            ->default(fn() => $this->application->email)
                            ->disabled(),
                        Forms\Components\TextInput::make('phone_number')
                            ->label('Phone Number')
                            ->default(fn() => $this->application->phone_number)
                            ->disabled(),
                        Forms\Components\TextInput::make('address')
                            ->label('Permanent Address')
                            ->default(fn() => $this->application->address)
                            ->columnSpanFull(),
                        Forms\Components\Select::make('business_type')
                            ->label('Business Type')
                            ->options([
                                'food' => 'Food',
                                'non-food' => 'Non Food',
                                'other' => 'Other',
                            ])
                            ->native(false),
                        Forms\Components\TextInput::make('concourse_lease_term')
                            ->label('Lease Agreement Date')
                            ->disabled()
                            ->suffix('Months'),
                        Forms\Components\TextInput::make('requirements_status')
                            ->label('Requirements Status')
                            ->disabled()
                            ->extraInputAttributes(['class' => 'capitalize']),
                        Forms\Components\TextInput::make('application_status')
                            ->label('Application Status')
                            ->disabled()
                            ->extraInputAttributes(['class' => 'capitalize']),
                        Forms\Components\Section::make('Requirements')
                            ->schema(function () {
                                return $this->allRequirements->map(function ($requirement) {
                                    $appRequirement = $this->appRequirements->firstWhere('requirement_id', $requirement->id);
                                    return Forms\Components\Grid::make(2)
                                        ->schema([
                                            Forms\Components\FileUpload::make("requirements.{$requirement->id}")
                                                ->label('')
                                                ->disk('public')
                                                ->directory('requirements')
                                                ->acceptedFileTypes(['application/pdf', 'image/*'])
                                                ->maxSize(5120)
                                                ->openable()
                                                ->imagePreviewHeight('250')
                                                ->loadingIndicatorPosition('left')
                                                ->panelAspectRatio('2:1')
                                                ->panelLayout('integrated')
                                                ->removeUploadedFileButtonPosition('right')
                                                ->uploadButtonPosition('left')
                                                ->uploadProgressIndicatorPosition('left')
                                                ->columnSpan(1),
                                            Forms\Components\Grid::make(1)->schema([
                                                Forms\Components\TextInput::make("requirement_status.{$requirement->id}")
                                                    ->label($requirement->name)
                                                    ->extraInputAttributes(['class' => 'capitalize'])
                                                    ->disabled(),
                                                Forms\Components\TextInput::make("remarks.{$requirement->id}")
                                                    ->label('Remarks')
                                                    ->disabled(),
                                            ])->columnSpan(1),
                                        ]);
                                })->toArray();
                            }),
                    ])->columns(3),
            ])
            ->statePath('data');
    }

    public function save(): void
    {
        $data = $this->form->getState();

        // Update Application
        $this->application->update($data);

        // Update or Create AppRequirements
        if (isset($data['requirements'])) {
            foreach ($data['requirements'] as $requirementId => $file) {
                $appRequirement = $this->appRequirements->firstWhere('requirement_id', $requirementId);
                if ($appRequirement) {
                    if ($file) {
                        $appRequirement->update([
                            'file' => $file,
                            'status' => 'pending',
                        ]);
                    }
                } else {
                    // Create new AppRequirement if it doesn't exist
                    AppRequirement::create([
                        'requirement_id' => $requirementId,
                        'user_id' => Auth::id(),
                        'space_id' => $this->application->space_id,
                        'concourse_id' => $this->application->concourse_id,
                        'application_id' => $this->application->id,
                        'name' => $this->allRequirements->firstWhere('id', $requirementId)->name,
                        'status' => 'pending',
                        'file' => $file,
                    ]);
                }
            }
        }

        Notification::make()
            ->success()
            ->title('Application updated successfully')
            ->send();

        Notification::make()
            ->success()
            ->title('Application updated successfully')
            ->sendToDatabase(Auth::user(),);
    }
}
