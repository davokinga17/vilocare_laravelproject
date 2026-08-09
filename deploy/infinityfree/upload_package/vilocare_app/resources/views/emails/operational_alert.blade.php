<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>{{ $title }}</title>
</head>
<body style="font-family: Arial, sans-serif; color: #243447; line-height: 1.6;">
    <h2 style="margin-bottom: 8px;">{{ $title }}</h2>
    <p style="margin-top: 0;">{{ $summary }}</p>

    @if(!empty($details))
        <table cellpadding="8" cellspacing="0" border="0" style="border-collapse: collapse; width: 100%; max-width: 680px;">
            @foreach($details as $label => $value)
                <tr>
                    <td style="background: #f4f8fb; width: 220px;"><strong>{{ $label }}</strong></td>
                    <td>{{ $value ?: 'N/A' }}</td>
                </tr>
            @endforeach
        </table>
    @endif
</body>
</html>
