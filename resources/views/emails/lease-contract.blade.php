<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lease Contract</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            color: #333;
        }
        h1 {
            color: #2c3e50;
        }
        .signature-line {
            border-top: 1px solid #333;
            margin-top: 20px;
            padding-top: 10px;
        }
    </style>
</head>
<body>
    <h1>Lease Agreement</h1>

    <p>Your application requirements for {{ $space->name }} in {{ $space->concourse->name }} at {{ $space->concourse->address }} have been approved. Please go to {{ $space->concourse->address }} to submit your security deposit, which is equivalent to two months' rent. Additionally, please read, print, and sign the attached contract, and submit it to the owner. Once these steps are completed, your application will be fully approved.</p>
   
    <p>This Lease Agreement ("Agreement") is made and entered into on this {{ $space->lease_start instanceof \DateTime ? $space->lease_start->format('F j, Y') : \Carbon\Carbon::parse($space->lease_start)->format('F j, Y') }} by and between:</p>

    <p>
        <strong>Owner Name:</strong> {{ $owner->name }}, hereinafter referred to as "Owner"<br>
        <strong>Owner Address:</strong> {{ $ownerAddress }}
    </p>

    <p>and</p>

    <p>
        <strong>Tenant Name:</strong> {{ $tenantUser->name }}, hereinafter referred to as "Tenant"<br>
        <strong>Business Name:</strong> {{ $businessName }}<br>
        <strong>Tenant Address:</strong> {{ $tenantAddress }}
    </p>

    <h2>Recitals:</h2>
    <p>Whereas, Owner is the rightful owner of the premises located at {{ $space->address }} ("Premises"), and Tenant desires to lease the Premises for the operation of {{ $application->business_name }}, subject to the terms and conditions set forth in this Agreement.</p>

    <h2>NOW, THEREFORE, the parties agree as follows:</h2>

    <h3>1. Lease Term</h3>
    <p>The lease term shall commence on {{ $space->lease_start instanceof \DateTime ? $space->lease_start->format('F j, Y') : \Carbon\Carbon::parse($space->lease_start)->format('F j, Y') }} and continue until {{ $space->lease_end instanceof \DateTime ? $space->lease_end->format('F j, Y') : \Carbon\Carbon::parse($space->lease_end)->format('F j, Y') }} unless terminated earlier as provided herein ("Lease Term").</p>

    <h3>2. Rent</h3>
    <p>The Tenant agrees to pay monthly rent of {{ $space->monthly_payment }} on or before the 1st of each month to the Owner at {{ $owner->payment_address }}. Any late payment shall be subject to a penalty of 5% of the monthly rent.</p>

    <h3>3. Security Deposit</h3>
    <p>Tenant shall provide a security deposit of {{ $application->security_deposit }} to be held by the Owner as security for the performance of Tenant's obligations under this Lease. The deposit will be refunded at the end of the Lease Term, provided no damage beyond normal wear and tear has occurred.</p>

    <h3>4. Use of Premises</h3>
    <p>The Premises shall be used exclusively for the operation of {{ $application->business_name }} and for no other purpose without prior written consent from the Owner.</p>

    <h3>5. Maintenance and Repairs</h3>
    <p>Tenant shall maintain the Premises in good and spaceable condition. Tenant shall be responsible for any repairs needed due to negligence or intentional acts. The Owner shall be responsible for structural repairs and other maintenance necessary for the general upkeep of the property.</p>

    <h3>6. Alterations</h3>
    <p>Tenant shall not make any substantial alterations, additions, or improvements to the Premises without the prior written consent of the Owner.</p>

    <h3>7. Utilities</h3>
    <p>Tenant shall be responsible for the payment of all utilities, including electricity, water, gas, and other services used on the Premises during the Lease Term.</p>

    <h3>8. Insurance</h3>
    <p>Tenant agrees to maintain adequate business liability insurance during the Lease Term and provide proof of such insurance to the Owner upon request.</p>

    <h3>9. Indemnification</h3>
    <p>Tenant shall indemnify, defend, and hold harmless the Owner from any and all claims, liabilities, and expenses arising out of the use or occupancy of the Premises, except for the Owner's negligence or willful misconduct.</p>

    <h3>10. Termination</h3>
    <p>Either party may terminate this Agreement by providing 30 days written notice to the other party prior to the expiration of the Lease Term. In the event of default by the Tenant, the Owner may terminate this Lease upon 7 days written notice.</p>

    <h3>11. Governing Law</h3>
    <p>This Agreement shall be governed by and construed in accordance with the laws of the state of {{ $space->state }}.</p>

    <h3>12. Entire Agreement</h3>
    <p>This Agreement contains the entire understanding between the parties and may only be modified in writing and signed by both parties.</p>

    <p>IN WITNESS WHEREOF, the parties hereto have executed this Lease Agreement on the day and year first above written.</p>

    <div class="signature-line">
        <p>
            <strong>Owner Signature:</strong> {{ $owner->name }}<br>
            <strong>Owner Name:</strong> {{ $owner->name }}<br>
            <strong>Date:</strong> {{ $space->lease_start instanceof \DateTime ? $space->lease_start->format('F j, Y') : \Carbon\Carbon::parse($space->lease_start)->format('F j, Y') }}
        </p>
    </div>

    <div class="signature-line">
        <p>
            <strong>Tenant Signature:</strong> {{ $tenantUser->name }}<br>
            <strong>Tenant Name:</strong> {{ $tenantUser->name }}<br>
            <strong>Space Name:</strong> {{ $space->name }}<br>
            <strong>Date:</strong> {{ $space->lease_start instanceof \DateTime ? $space->lease_start->format('F j, Y') : \Carbon\Carbon::parse($space->lease_start)->format('F j, Y') }}
        </p>
    </div>

    <p>Regards,</p>
    <p>COMS</p>
</body>
</html>
