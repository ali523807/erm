<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Statement - {{ $customer->company_name }}</title>
    <style>
        body {
            color: #111827;
            font-family: Arial, sans-serif;
            font-size: 12px;
            line-height: 1.45;
            margin: 24px;
        }

        .header {
            align-items: flex-start;
            border-bottom: 2px solid #111827;
            display: flex;
            justify-content: space-between;
            margin-bottom: 18px;
            padding-bottom: 14px;
        }

        h1, h2 {
            margin: 0;
        }

        h1 {
            font-size: 24px;
        }

        h2 {
            font-size: 14px;
        }

        .muted, .text-muted {
            color: #6b7280;
        }

        .eyebrow {
            color: #64748b;
            display: block;
            font-size: 10px;
            font-weight: 700;
            letter-spacing: .04em;
            text-transform: uppercase;
        }

        .statement-card {
            display: inline-block;
            margin: 0 1% 12px 0;
            vertical-align: top;
            width: 18%;
        }

        .box {
            border: 1px solid #e5e7eb;
            border-radius: 6px;
            margin-bottom: 14px;
            padding: 12px;
        }

        .panel-header {
            margin-bottom: 8px;
        }

        table {
            border-collapse: collapse;
            width: 100%;
        }

        th {
            background: #f3f4f6;
            color: #374151;
            font-size: 10px;
            padding: 8px;
            text-align: left;
            text-transform: uppercase;
        }

        td {
            border-bottom: 1px solid #e5e7eb;
            padding: 8px;
        }

        .text-end {
            text-align: right;
        }

        .badge {
            color: #374151;
        }

        @media print {
            .no-print {
                display: none;
            }
        }
    </style>
</head>
<body>
    <button class="no-print" onclick="window.print()">Print</button>

    <div class="header">
        <div>
            <h1>Customer Statement</h1>
            <div class="muted">{{ auth()->user()->currentCompany?->name ?? 'ERM Cloud' }}</div>
        </div>
        <div style="text-align: right;">
            <strong>{{ $customer->company_name }}</strong><br>
            <span class="muted">As of {{ $asOfDate->format('Y-m-d') }}</span><br>
            <span class="muted">{{ $fromDate ? 'From '.$fromDate->format('Y-m-d') : 'Full history' }}</span>
        </div>
    </div>

    @include('customers.statement-body', ['printMode' => true])
</body>
</html>
