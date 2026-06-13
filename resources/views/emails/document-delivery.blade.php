<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>{{ $delivery->subject }}</title>
</head>
<body style="font-family: Arial, sans-serif; color: #111827; line-height: 1.5;">
    <p>Hello {{ $delivery->recipient_name ?: 'there' }},</p>

    <p>{{ $delivery->message ?: 'Please find the attached document for your records.' }}</p>

    <p>
        Regards,<br>
        {{ $delivery->company?->name ?? config('app.name') }}
    </p>
</body>
</html>
