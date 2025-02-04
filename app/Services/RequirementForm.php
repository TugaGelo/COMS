<?php

namespace App\Services;

use App\Models\Requirement;
use Filament\Forms;
use Illuminate\Support\Facades\Auth;

final class RequirementForm
{
   

    public static function schema($concourseId = null, $spaceId = null, $concourseLeaseTerm = null): array
    {
        $user = Auth::user();
        return [
            Forms\Components\Hidden::make('user_id')
                ->default(fn() => $user->id),
            Forms\Components\Hidden::make('space_id')
                ->default($spaceId),
            Forms\Components\Hidden::make('concourse_id')
                ->default($concourseId),
            Forms\Components\Hidden::make('status')
                ->default('pending'),
            Forms\Components\Section::make('Business Information')->description('Security Deposit: 3 months deposit of the total rent amount total')
                ->schema([
                    Forms\Components\TextInput::make('business_name')
                        ->label('Business Name')
                        ->required(),
                    Forms\Components\TextInput::make('owner_name')
                        ->label('Owner Name')
                        ->default(fn() => $user->name)
                        ->readOnly()
                        ->required(),
                    Forms\Components\TextInput::make('email')
                        ->label('Email')
                        ->email()
                        ->default(fn() => $user->email)
                        ->readOnly(),
                    Forms\Components\TextInput::make('phone_number')
                        ->label('Phone Number')
                        ->default(fn() => $user->phone_number)
                        ->required(),
                    Forms\Components\Textarea::make('address')
                        ->label('Permanent Address')
                        ->default(fn() => $user->address)
                        ->columnSpanFull()
                        ->required(),
                    Forms\Components\Select::make('business_type')
                        ->label('Business Type')
                        ->options([
                            'food' => 'Food',
                            'non-food' => 'Non Food',
                            'other' => 'Other',
                        ])
                        ->required()
                        ->native(false),
                    Forms\Components\TextInput::make('concourse_lease_term')
                        ->label('Concourse Lease Term')
                        ->default($concourseLeaseTerm)
                        ->suffix('Months')
                        ->readOnly(),
                ])
                ->columns(2),

            Forms\Components\Section::make('Requirements')
                ->schema(function () {
                    $requirements = Requirement::all();
                    return $requirements->map(function ($requirement) {
                        return Forms\Components\FileUpload::make("requirements.{$requirement->id}")
                            ->label($requirement->name)
                            ->disk('public')
                            ->directory('requirements')
                            ->acceptedFileTypes(['application/pdf', 'image/*'])
                            ->maxSize(5120) // 5MB max file size
                        ;
                    })->toArray();
                })
                ->columns(2),
        ];
    }
}
