<?php

namespace App\Services;

use App\Models\Requirement;
use App\Models\User;
use Filament\Forms;
use Illuminate\Support\Facades\Auth;

final class ReportForm
{
    public static function schema(): array
    {
        return [
            Forms\Components\Hidden::make('concourse_id')
                ->default(fn($record) => $record->concourse_id)
                ->required(),
            Forms\Components\Hidden::make('space_id')
                ->default(fn($record) => $record->id)
                ->required(),
            Forms\Components\Hidden::make('created_by')
                ->default(auth()->user()->id)
                ->required(),
            Forms\Components\TextInput::make('incident_ticket_number')
                ->default(fn() => 'INC' . str_pad(rand(0, 99999999), 8, '0', STR_PAD_LEFT))
                ->required()
                ->hiddenOn(['edit'])
                ->readOnly(),
            Forms\Components\TextInput::make('title')
                ->required(),
            Forms\Components\Textarea::make('description')
                ->required()
                ->rows(3),
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
            Forms\Components\FileUpload::make('images')
                ->multiple(),
        ];
    }
}
