<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Application Rejected</title>
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
    <h1>Application Rejected</h1>

    <p>Dear {{ $application->owner_name }},</p>

    <p>We regret to inform you that your renewal application for {{ $application->space->name }} in {{ $application->concourse->name }} at {{ $application->concourse->address }} has been rejected. Unfortunately, your application did not meet the necessary criteria for renewal at this time.</p>

    <p>If you have any questions regarding this decision or wish to discuss future opportunities, please feel free to contact us.</p>

    <p>Regards,<br>
    COMS</p>
</body>

</html>
