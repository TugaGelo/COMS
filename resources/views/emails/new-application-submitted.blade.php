<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>New Application Submitted</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            color: #333;
        }

        h1 {
            color: #2c3e50;
        }
    </style>
</head>

<body>
    @php
    $user = \App\Models\User::find(1);
    @endphp
    <h1>Dear {{ $user->name }}</h1>

    <p>A new application has been submitted by {{ $application->user->name }} for the space {{ $application->space->name }} in the concourse {{ $application->concourse->name }} at {{ $application->concourse->address }}.</p>

    <p>
        <strong>Application ID:</strong> {{ $application->id }}<br>
        <strong>User:</strong> {{ $application->user->name ?? 'N/A' }}<br>
        <strong>Business Name:</strong> {{ $application->business_name ?? 'N/A' }}<br>
        <strong>Space:</strong> {{ $application->space->name ?? 'N/A' }}<br>
        <strong>Concourse:</strong> {{ $application->concourse->name ?? 'N/A' }}<br>
        <strong>Submitted at:</strong> {{ $application->created_at ? $application->created_at->format('F j, Y H:i:s') : 'N/A' }}
    </p>

    <h2>Application Details:</h2>
    <p>
        <strong>Lease Term:</strong> {{ $application->concourse_lease_term ?? 'N/A' }} months<br>
        <strong>Requested Move-in Date:</strong> {{ $application->requested_move_in_date ? $application->requested_move_in_date->format('F j, Y') : 'N/A' }}<br>
    </p>

    <h2>Applicant Information:</h2>
    <p>
        <strong>Name:</strong> {{ $application->user->name ?? 'N/A' }}<br>
        <strong>Email:</strong> {{ $application->user->email ?? 'N/A' }}<br>
        <strong>Phone:</strong> {{ $application->user->phone ?? 'N/A' }}
    </p>

    <p>Please review the application in the admin panel for more details and to take appropriate action.</p>

    <p>Regards,</p>
    <p>COMS</p>
    
    <p>
        <a href="{{ route('filament.admin.resources.applications.edit', ['record' => $application->id]) }}">
            View Application in Admin Panel
        </a>
    </p>
</body>

</html>