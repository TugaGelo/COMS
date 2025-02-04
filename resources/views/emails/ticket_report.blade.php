<!DOCTYPE html>
<html>
<head>
    <title>Ticket Report</title>
</head>
<body>
    <p>Dear {{ $adminName }},</p>
    <p>{{ $tenantName }} from {{ $spaceName }} at {{ $concourseName }} has submitted a ticket report regarding their concern.</p>
    <p>Details of the Ticket Report:</p>
    <ul>
        <li>Concern Type: {{ $concernType }}</li>
        <li>Description: {{ $description }}</li>
        <li>Ticket Number: {{ $ticketNumber }}</li>
        <li>Submitted: {{ $submittedDate }}</li>
    </ul>
    <p>Please review the report and take the necessary action.</p>
    <p>Regards,</p>
    <p>COMS</p>
</body>
</html>
