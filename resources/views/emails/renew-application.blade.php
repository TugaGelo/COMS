<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lease Renewal Application</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            color: #333;
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
        }
        .header {
            background-color: #f8f9fa;
            padding: 20px;
            border-radius: 5px;
            margin-bottom: 20px;
        }
        .content {
            background-color: #ffffff;
            padding: 20px;
            border-radius: 5px;
            border: 1px solid #dee2e6;
        }
        .footer {
            margin-top: 20px;
            font-size: 0.9em;
            color: #6c757d;
        }
    </style>
</head>
<body>
    <div class="header">
        <h2>New Lease Renewal Application</h2>
    </div>

    <div class="content">
        <p>Dear {{ $tenant->name }},</p>

        <p>Thank you for submitting your renewal application for {{ $space->name }} in {{ $concourse->name }} at {{ $concourse->address }}. We have received your application and will review it promptly.</p>

        <p>You will receive a follow-up notification once your application has been processed.</p>

        <p>Regards,<br>COMS</p>
    </div>

    <div class="footer">
        <p>This is an automated message. Please do not reply directly to this email.</p>
        <p>© {{ date('Y') }} Your Company Name. All rights reserved.</p>
    </div>
</body>
</html>