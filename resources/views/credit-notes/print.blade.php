<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>{{ $creditNote->credit_note_number }}</title>
    <style>
        body { color: #111827; font-family: Arial, sans-serif; font-size: 12px; line-height: 1.45; margin: 24px; }
        .header { align-items: flex-start; border-bottom: 2px solid #111827; display: flex; justify-content: space-between; margin-bottom: 18px; padding-bottom: 14px; }
        h1, h2 { margin: 0; }
        h1 { font-size: 24px; }
        h2 { font-size: 14px; }
        .muted, .text-muted { color: #6b7280; }
        .box { border: 1px solid #e5e7eb; border-radius: 6px; margin-bottom: 14px; padding: 12px; }
        .document-grid { display: flex; gap: 12px; }
        .document-col { flex: 1; }
        .right { text-align: right; }
        .detail-grid div { display: flex; justify-content: space-between; padding: 7px 0; }
        .detail-grid dt { color: #6b7280; font-weight: 700; }
        .detail-grid dd { margin: 0; }
        @media print { .no-print { display: none; } }
    </style>
</head>
<body>
    <button class="no-print" onclick="window.print()">Print</button>
    <div class="header">
        <div>
            <h1>Credit Note</h1>
            <div class="muted">{{ auth()->user()->currentCompany?->name ?? 'RentalHook' }}</div>
        </div>
        <div class="right">
            <strong>{{ $creditNote->credit_note_number }}</strong><br>
            <span class="muted">{{ $creditNote->credit_date?->format('Y-m-d') }}</span>
        </div>
    </div>
    @include('credit-notes._document', ['printMode' => true])
</body>
</html>
