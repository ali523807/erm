<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>{{ $creditNote->credit_note_number }}</title>
    <style>
        body { color: #111827; font-family: DejaVu Sans, sans-serif; font-size: 12px; line-height: 1.45; margin: 0; }
        .header { border-bottom: 2px solid #111827; display: table; margin-bottom: 18px; padding-bottom: 14px; width: 100%; }
        .col { display: table-cell; vertical-align: top; width: 50%; }
        .right { text-align: right; }
        h1, h2 { margin: 0; }
        h1 { font-size: 24px; }
        h2 { font-size: 14px; }
        .muted, .text-muted { color: #6b7280; }
        .box { border: 1px solid #e5e7eb; margin-bottom: 14px; padding: 10px; }
        .document-col { display: inline-block; vertical-align: top; width: 48%; }
        .detail-grid div { display: table; padding: 7px 0; width: 100%; }
        .detail-grid dt { color: #6b7280; display: table-cell; font-weight: 700; width: 45%; }
        .detail-grid dd { display: table-cell; margin: 0; text-align: right; width: 55%; }
    </style>
</head>
<body>
    <div class="header">
        <div class="col">
            <h1>Credit Note</h1>
            <div class="muted">{{ auth()->user()->currentCompany?->name ?? 'RentalHook' }}</div>
            <div class="muted">{{ auth()->user()->currentCompany?->email }}</div>
        </div>
        <div class="col right">
            <strong>{{ $creditNote->credit_note_number }}</strong><br>
            {{ $creditNote->customer?->company_name }}<br>
            <span class="muted">{{ $creditNote->credit_date?->format('Y-m-d') }}</span>
        </div>
    </div>
    @include('credit-notes._document', ['printMode' => true])
</body>
</html>
