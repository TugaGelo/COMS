<?php

namespace App\Services;

use App\Models\Requirement;
use Filament\Forms;
use Illuminate\Support\Facades\Auth;
use App\Models\RenewAppRequirements;

final class RenewForm
{
    public static function schema($record = null): array
    {
        $user = Auth::user();
        return [
            Forms\Components\Hidden::make('user_id')
                ->default(fn() => $user->id),
            Forms\Components\Hidden::make('space_id')
                ->default($record?->id),
            Forms\Components\Hidden::make('concourse_id')
                ->default($record?->concourse_id),
            Forms\Components\Hidden::make('status')
                ->default('pending'),
            Forms\Components\Section::make('Business Information')->description('Security Deposit: 3 months deposit of the total rent amount total')
                ->schema([
                    Forms\Components\TextInput::make('business_name')
                        ->label('Business Name')
                        ->default($record?->business_name)
                        ->required(),
                    Forms\Components\TextInput::make('owner_name')
                        ->label('Owner Name')
                        ->default($record?->owner_name)
                        ->required(),
                    Forms\Components\TextInput::make('email')
                        ->label('Email')
                        ->email()
                        ->default(fn() => $user->email)
                        ->readOnly(),
                    Forms\Components\TextInput::make('phone_number')
                        ->label('Phone Number')
                        ->default($record ? $record->phone_number : fn() => $user->phone_number)
                        ->required(),
                    Forms\Components\TextInput::make('address')
                        ->label('Permanent Address')
                        ->default($record ? $record->address : fn() => $user->address)
                        ->columnSpanFull()
                        ->required(),
                    Forms\Components\Select::make('business_type')
                        ->label('Business Type')
                        ->options([
                            'food' => 'Food',
                            'non-food' => 'Non Food',
                            'other' => 'Other',
                        ])
                        ->default($record ? $record->business_type : '')
                        ->required()
                        ->native(false),
                    Forms\Components\TextInput::make('concourse_lease_term')
                        ->label('Concourse Lease Term')
                        ->default($record?->concourse?->lease_term)
                        ->suffix('Months')
                        ->readOnly(),
                ])->columns(2),

            Forms\Components\Section::make('Requirements')
                ->schema(function () {
                    $requirements = Requirement::all();
                    return $requirements->map(function ($requirement) {
                        return Forms\Components\FileUpload::make("requirements.{$requirement->id}")
                            ->label($requirement->name)
                            ->disk('public')
                            ->directory('renew-requirements')
                            ->acceptedFileTypes(['application/pdf', 'image/*'])
                            ->maxSize(5120);
                    })->toArray();
                })
                ->columns(2),
        ];
    }
}
