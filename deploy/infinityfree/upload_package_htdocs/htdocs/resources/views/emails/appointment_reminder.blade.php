<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Appointment Reminder</title>
</head>
<body style="font-family: Arial, sans-serif; color: #243447; line-height: 1.6;">
    <h2 style="margin-bottom: 8px;">ViLoCare Appointment Reminder</h2>
    <p style="margin-top: 0;">
        {{ $manual ? 'A manual reminder was sent from the dashboard support panel.' : 'A new appointment reminder has been generated in ViLoCare.' }}
    </p>

    <table cellpadding="8" cellspacing="0" border="0" style="border-collapse: collapse; width: 100%; max-width: 640px;">
        <tr>
            <td style="background: #f4f8fb; width: 180px;"><strong>Patient</strong></td>
            <td>{{ trim($patient->first_name . ' ' . $patient->last_name) ?: 'Unknown patient' }}</td>
        </tr>
        <tr>
            <td style="background: #f4f8fb;"><strong>ART Number</strong></td>
            <td>{{ $patient->art_number ?: 'N/A' }}</td>
        </tr>
        <tr>
            <td style="background: #f4f8fb;"><strong>Appointment Date</strong></td>
            <td>{{ \Illuminate\Support\Carbon::parse($appointment->appointment_date)->format('d M Y') }}</td>
        </tr>
        <tr>
            <td style="background: #f4f8fb;"><strong>Status</strong></td>
            <td>{{ $appointment->status }}</td>
        </tr>
        <tr>
            <td style="background: #f4f8fb;"><strong>Reason</strong></td>
            <td>{{ $appointment->reason ?: 'Not specified' }}</td>
        </tr>
        <tr>
            <td style="background: #f4f8fb;"><strong>Phone</strong></td>
            <td>{{ $patient->phone ?: 'Not available' }}</td>
        </tr>
    </table>
</body>
</html>
