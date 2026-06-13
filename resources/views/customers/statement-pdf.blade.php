<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Statement - {{ $customer->company_name }}</title>
    <style>
        body {
            color: #111827;
            font-family: DejaVu Sans, sans-serif;
            font-size: 11px;
            line-height: 1.45;
            margin: 0;
        }

        .header {
            border-bottom: 2px solid #111827;
            display: table;
            margin-bottom: 18px;
            padding-bottom: 14px;
            width: 100%;
        }

        .col {
            display: table-cell;
            vertical-align: top;
            width: 50%;
        }

        .right, .text-end {
            text-align: right;
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
            font-size: 9px;
            font-weight: 700;
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
            margin-bottom: 14px;
            padding: 10px;
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
            font-size: 9px;
            padding: 7px;
            text-align: left;
            text-transform: uppercase;
        }

        td {
            border-bottom: 1px solid #e5e7eb;
            padding: 7px;
        }

        .badge {
            color: #374151;
        }
    </style>
</head>
<body>
    <div class="header">
        <div class="col">
            <h1>Customer Statement</h1>
            <div class="muted">{{ auth()->user()->currentCompany?->name ?? 'ERM Cloud' }}</div>
            <div class="muted">{{ auth()->user()->currentCompany?->email }}</div>
        </div>
        <div class="col right">
            <strong>{{ $customer->company_name }}</strong><br>
            {{ $customer->contact_person }}<br>
            {{ $customer->email }}<br>
            <span class="muted">As of {{ $asOfDate->format('Y-m-d') }}</span><br>
            <span class="muted">{{ $fromDate ? 'From '.$fromDate->format('Y-m-d') : 'Full history' }}</span>
        </div>
    </div>

    @include('customers.statement-body', ['printMode' => true])
</body>
</html>
