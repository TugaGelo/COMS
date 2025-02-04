<?php

namespace App\Filament\Admin\Resources\ConcourseRateResource\Pages;

use App\Filament\Admin\Resources\ConcourseRateResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Filament\Notifications\Actions\Action;
use Filament\Notifications\Notification;
use App\Models\User;

class EditConcourseRate extends EditRecord
{
    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
    
    protected static string $resource = ConcourseRateResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }

    protected function getSavedNotification(): ?Notification
    {
        $record = $this->getRecord();

        $notification = Notification::make()
            ->success()
            ->icon('heroicon-o-currency-dollar')
            ->title('Concourse Rate Updated')
            ->body("Concourse Rate {$record->name} Updated!")
            ->actions([
                Action::make('view')
                    ->label('Mark as read')
                    ->link()
                    ->markAsRead(),
            ]);

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
}
