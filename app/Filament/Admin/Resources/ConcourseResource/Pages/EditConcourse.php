<?php

namespace App\Filament\Admin\Resources\ConcourseResource\Pages;

use App\Filament\Admin\Resources\ConcourseResource;
use App\Filament\Admin\Resources\ConcourseResource\Widgets\SpaceOverview;
use App\Models\Concourse;
use App\Models\ConcourseRate;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Filament\Notifications\Notification;
use Filament\Notifications\Actions\Action;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class EditConcourse extends EditRecord
{
    // protected function getRedirectUrl(): string
    // {
    //     return $this->getResource()::getUrl('index');
    // }

    protected function getHeaderWidgets(): array
    {
        return [
            SpaceOverview::class,
        ];
    }

    protected static string $resource = ConcourseResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('viewSpaces')
                ->label('View Layout')
                ->url(fn() => $this->getResource()::getUrl('view-spaces', ['record' => $this->getRecord()]))
                ->color('success'),
        ];
    }

    // protected function hasSpacesWithBills(): bool
    // {
    //     $concourse = $this->getRecord();
    //     $spacesCount = $concourse->spaces()->count();
    //     $spacesWithValidBills = $concourse->spaces()
    //         ->where(function ($query) {
    //             $query->whereRaw("JSON_CONTAINS(bills, '{\"name\": \"Water\"}', '$')")
    //                 ->whereRaw("JSON_CONTAINS(bills, '{\"name\": \"Electricity\"}', '$')")
    //                 ->whereNotNull('bills')
    //                 ->where('bills', '!=', '[]');
    //         })
    //         ->count();

    //     return $spacesCount > 0 && $spacesCount === $spacesWithValidBills;
    // }

    protected function getSavedNotification(): ?Notification
    {
        $record = $this->getRecord();

        $notification = Notification::make()
            ->success()
            ->icon('heroicon-o-currency-dollar')
            ->title('Concourse Updated')
            ->body("Concourse {$record->name} address in {$record->address} Updated!");

        // Get all users with the 'panel_user' or 'accountant' role
        $notifiedUsers = User::role(['panel_user'])->get();

        // Send notification to all panel users and accountants
        foreach ($notifiedUsers as $user) {
            $notification->sendToDatabase($user);
        }

        // Send notification to the authenticated user
        $notification->sendToDatabase(auth()->user());

        return $notification;
    }

    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        $concourse = parent::handleRecordUpdate($record, $data);

        if ($concourse instanceof Concourse && $concourse->wasChanged('rate_id')) {
            $this->updateSpacePrices($concourse);
        }

        return $concourse;
    }

    protected function updateSpacePrices(Concourse $concourse): void
    {
        $rate = ConcourseRate::find($concourse->rate_id);

        if ($rate) {
            $concourse->spaces()->each(function ($space) use ($rate) {
                $spacePrice = $rate->price * $space->sqm;
                $space->update(['price' => $spacePrice]);
            });
        }
    }

   
}
