<?php

namespace App\Filament\Admin\Resources\ApplicationResource\Pages;

use App\Filament\Admin\Resources\ApplicationResource;
use App\Mail\LeaseContractMail;
use App\Mail\ApplicationRejectedMail;
use App\Mail\RequirementsRejectMail;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Filament\Notifications\Notification;
use Filament\Notifications\Actions\Action;
use App\Models\User;
use App\Models\Space;
use App\Models\ApprovedApplication;
use App\Models\Tenant;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;

class EditApplication extends EditRecord
{
    // protected function getRedirectUrl(): string
    // {
    //     return $this->getResource()::getUrl('index');
    // }

    protected static string $resource = ApplicationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('approveRequirements')
                ->label('Approve Requirements')
                ->icon('heroicon-o-check-circle')
                ->visible(fn($record) => $record->requirements_status === 'pending')
                ->action(function () {
                    $application = $this->getRecord();

                    DB::transaction(function () use ($application) {
                        // Update application status
                        $application->update(['requirements_status' => 'approved']);

                        // Notify the authenticated user
                        $authUser = Auth::user();
                        Notification::make()
                            ->success()
                            ->title('Application Requirements Approved')
                            ->body("You successfully approved the application requirements.")
                            ->actions([
                                Action::make('view')
                                    ->button()
                                    ->url(route('filament.admin.resources.applications.index')),
                            ])
                            ->sendToDatabase($authUser);

                        // Notify the application's user
                        $applicationUser = User::find($application->user_id);
                        Notification::make()
                            ->success()
                            ->title('Application Requirements Approved')
                            ->body("Your application requirements have been approved.")
                            ->sendToDatabase($applicationUser);

                        // Show a success message in the UI
                        Notification::make()
                            ->success()
                            ->title('Application Requirements Approved')
                            ->body("The application requirements have been successfully approved and notifications sent.")
                            ->send();

                        // Send lease contract email
                        $space = Space::find($application->space_id);
                        $this->sendLeaseContractEmail($space, $application);

                        // Redirect to the list view after approval
                        return redirect()->route('filament.admin.resources.applications.index');
                    });
                })
                ->color('success')
                ->requiresConfirmation(),
            Actions\Action::make('approveApplication')
                ->label('Approve Application')
                ->icon('heroicon-o-check-circle')
                ->visible(fn($record) => $record->requirements_status == 'approved' || $record->requirements_status == 'rejected')
                ->action(function () {
                    $application = $this->getRecord();

                    DB::transaction(function () use ($application) {
                        // Update application status
                        $application->update(['application_status' => 'approved']);


                        // Update space status and details if space is found
                        $space = Space::find($application->space_id);
                        if ($space) {
                            $space->update([
                                'status' => 'occupied',
                                'application_id' => $application->id,
                                'user_id' => $application->user_id,
                                'concourse_id' => $application->concourse_id,
                                'lease_start' => Carbon::now(),
                                'email' => $application->email,
                                'owner_name' => $application->owner_name,
                                'business_name' => $application->business_name,
                                'business_type' => $application->business_type,
                                'address' => $application->address,
                                'phone_number' => $application->phone_number,
                                'lease_due' => Carbon::now()->addMonths(1),
                                'lease_end' => Carbon::parse($application->lease_start)->addMonths($application->concourse_lease_term),
                                'lease_term' => $application->concourse_lease_term,
                                'lease_status' => 'active',
                                'application_status' => 'approved',
                                'requirements_status' => 'approved',
                                'space_type' => $application->space_type,
                                'bills' => $application->bills ? json_encode($application->bills) : null,
                                'monthly_payment' => $application->monthly_payment ? $application->monthly_payment : 0,
                                'payment_status' => '',
                                'is_active' => true,
                                'remarks' => $application->remarks,
                            ]);
                        }

                        // Send approval email
                        $concourse = $space->concourse; // Assuming you have a relationship set up to fetch concourse details
                        $mailData = [
                            'spaceName' => $space->name,
                            'concourseName' => $concourse->name,
                            'concourseAddress' => $concourse->address,
                        ];
                        Mail::to($application->user->email)
                            ->send(new \App\Mail\ApplicationApproveMail($mailData));

                        // Notify the authenticated user
                        $authUser = Auth::user();
                        Notification::make()
                            ->success()
                            ->title('Application Approved')
                            ->body("You successfully approved the application and associated space.")
                            ->sendToDatabase($authUser);

                        // Notify the application's user
                        $applicationUser = User::find($application->user_id);
                        Notification::make()
                            ->success()
                            ->title('Application Approved')
                            ->body("Your application and associated space have been approved.")
                            ->sendToDatabase($applicationUser);

                        // Show a success message in the UI
                        Notification::make()
                            ->success()
                            ->title('Application and Space Approved')
                            ->body("The application and associated space have been successfully approved and notifications sent.")
                            ->send();

                        $application->delete();

                        // Redirect to the list view after approval
                        return redirect($this->getResource()::getUrl('index'));
                    });
                })
                ->color('success')
                ->requiresConfirmation(),
            Actions\Action::make('rejectRequirements')
                ->label('Reject Requirements')
                ->icon('heroicon-o-x-circle')
                ->visible(fn($record) => $record->requirements_status === 'pending' || $record->requirements_status === 'approved')
                ->action(function () {
                    $application = $this->getRecord();

                    DB::transaction(function () use ($application) {
                        // Update requirements status
                        $application->update(['requirements_status' => 'rejected']);

                        // Notify the authenticated user
                        $authUser = Auth::user();
                        Notification::make()
                            ->warning()
                            ->title('Application Requirements Rejected')
                            ->body("You have rejected the application requirements.")
                            ->sendToDatabase($authUser);

                        // Notify the application's user
                        $applicationUser = User::find($application->user_id);
                        Notification::make()
                            ->warning()
                            ->title('Application Requirements Rejected')
                            ->body("Your application requirements have been rejected. Please review and resubmit.")
                            ->sendToDatabase($applicationUser);

                        // Show a success message in the UI
                        Notification::make()
                            ->warning()
                            ->title('Application Requirements Rejected')
                            ->body("The application requirements have been rejected and notifications sent.")
                            ->send();

                        // Send rejection email
                        $this->sendRequirementsRejectionEmail($application);

                        // Redirect to the list view after rejection
                        return redirect()->route('filament.admin.resources.applications.index');
                    });
                })
                ->color('danger')
                ->requiresConfirmation(),
            Actions\Action::make('rejectApplication')
                ->label('Reject Application')
                ->icon('heroicon-o-x-circle')
                ->visible(fn($record) => $record->requirements_status == 'rejected' || $record->requirements_status == 'pending' || $record->requirements_status == 'approved')
                ->action(function () {
                    $application = $this->getRecord();

                    DB::transaction(function () use ($application) {
                        // Update space status to 'available' and set user_id to null
                        $space = Space::find($application->space_id);
                        if ($space) {
                            $space->update([
                                'status' => 'available',
                                'application_id' => null,
                                'user_id' => null,
                            ]);
                        }

                        // Notify the authenticated user
                        $authUser = Auth::user();
                        Notification::make()
                            ->warning()
                            ->title('Application Rejected')
                            ->body("You have rejected and deleted the application.")
                            ->sendToDatabase($authUser);

                        // Notify the application's user
                        $applicationUser = User::find($application->user_id);
                        Notification::make()
                            ->warning()
                            ->title('Application Rejected')
                            ->body("Your application has been rejected and deleted.")
                            ->sendToDatabase($applicationUser);

                        // Send rejection email
                        $space = Space::find($application->space_id);
                        $concourse = $space->concourse; // Ensure you have a relationship set up to fetch concourse details

                        $mailData = [
                            'spaceName' => $space->name,
                            'concourseName' => $concourse->name,
                            'concourseAddress' => $concourse->address,
                        ];

                        Mail::to($application->user->email)
                            ->send(new \App\Mail\ApplicationRejectMail($mailData));

                        // Permanently delete the application
                        $application->forceDelete();

                        // Additional function to perform after deletion
                        $this->additionalDeletionTasks($application);

                        // Show a success message in the UI
                        Notification::make()
                            ->warning()
                            ->title('Application Rejected')
                            ->body("The application has been rejected, deleted, and notifications sent.")
                            ->send();

                        // Redirect to the list view after rejection
                        return redirect()->route('filament.admin.resources.applications.index');
                    });
                })
                ->color('danger')
                ->requiresConfirmation(),
        ];
    }

    private function sendLeaseContractEmail(Space $space, $application)
    {
        $owner = Auth::user();
        $tenantUser = User::find($space->user_id);
        $space = Space::find($space->id);

        // Fetch additional information
        $ownerAddress = $application->address ?? 'Address not provided';
        $tenantAddress = $tenantUser->address ?? 'Address not provided';
        $businessName = $application->business_name ?? 'Business name not provided';
        $ownerName = $application->owner_name ?? 'Owner name not provided';
        $phoneNumber = $application->phone_number ?? 'Phone number not provided';
        $businessType = $application->business_type ?? 'Business type not provided';
        $email = $application->email ?? 'Email not provided';
        $applicationId = $application->id;
        $remarks = $application->remarks ?? 'Remarks not provided';

        Mail::to($tenantUser->email)->send(new LeaseContractMail(
            $owner,
            $tenantUser,
            $space,
            $application,
            $ownerAddress,
            $tenantAddress,
            $businessName,
            $ownerName,
            $phoneNumber,
            $businessType,
            $email,
            $applicationId,
            $remarks
        ));
    }

    protected function getSavedNotification(): ?Notification
    {
        $record = $this->getRecord();

        // Check if the application status is 'approved'
        if ($record->status === 'approved') {
            return null; // Don't send any notification
        }

        $authUser = auth()->user();

        // Notification for the authenticated user
        $authNotification = Notification::make()
            ->success()
            ->icon('heroicon-o-document-text')
            ->title('Application Updated')
            ->body("Application {$record->name} has been updated.")
            ->actions([
                Action::make('markAsRead')
                    ->label('Mark as read')
                    ->button()
                    ->markAsRead(),
                Action::make('delete')
                    ->label('Delete')
                    ->color('danger')
                    ->icon('heroicon-o-trash')
                    ->action(fn(Notification $notification) => $notification->delete()),
            ])
            ->sendToDatabase($authUser);

        // Notification for the application owner (if different from auth user)
        $selectedUser = User::find($record->user_id);
        if ($selectedUser && $selectedUser->id !== $authUser->id) {
            $url = route('filament.app.pages.edit-requirement', [
                'concourse_id' => $record->concourse_id,
                'space_id' => $record->space_id,
                'user_id' => $record->user_id,
            ]);

            Notification::make()
                ->success()
                ->icon('heroicon-o-user-circle')
                ->title('Application Updated')
                ->body("Application {$record->name} Updated. Please review it!")
                ->actions([
                    Action::make('view')
                        ->label('View Application')
                        ->button()
                        ->url($url),
                    Action::make('delete')
                        ->label('Delete')
                        ->color('danger')
                        ->icon('heroicon-o-trash')
                        ->action(fn(Notification $notification) => $notification->delete()),
                ])
                ->sendToDatabase($selectedUser);
        }

        return $authNotification;
    }

    private function sendRejectionEmail($application)
    {
        $tenantUser = User::find($application->user_id);

        Mail::to($tenantUser->email)->send(new ApplicationRejectedMail($application));
    }

    private function additionalDeletionTasks($application)
    {
        // Implement additional tasks here, for example, logging or further cleanup
        // Log::info("Application permanently deleted: {$application->id}");
    }

    private function sendRequirementsRejectionEmail($application)
    {
        $space = Space::find($application->space_id);
        $concourse = $space->concourse; // Assuming you have a relationship set up to fetch concourse details

        $mailData = [
            'spaceName' => $space->name,
            'concourseName' => $concourse->name,
            'concourseAddress' => $concourse->address,
        ];

        Mail::to($application->user->email)
            ->send(new \App\Mail\RequirementsRejectMail($mailData));
    }
}
