<?php

namespace App\Filament\Admin\Resources\ConcourseRateResource\Pages;

use App\Filament\Admin\Resources\ConcourseRateResource;
use App\Models\User;
use Filament\Actions;
use Filament\Notifications\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;

class CreateConcourseRate extends CreateRecord
{
    protected static string $resource = ConcourseRateResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function getCreatedNotification(): ?Notification
    {
        $record = $this->getRecord(); 

        $notification = Notification::make()
            ->success()
            ->icon('heroicon-o-currency-dollar')
            ->title('Rate Created')
            ->body("New Rate {$record->name} {$record->price} Created!")
            ->actions([
                Action::make('view')
                    ->label('Mark as read')
                    ->link()
                    ->markAsRead(),
            ]);

        // Get all users with the 'panel_user' role
        $panelUsers = User::role('panel_user')->get();

        // Send notification to all panel users
        foreach ($panelUsers as $user) {
            $notification->sendToDatabase($user);
        }

        // Send notification to the authenticated user
        $notification->sendToDatabase(auth()->user());

        return $notification;
    }
}
