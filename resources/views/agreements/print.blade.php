<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $agreement->agreement_number }}</title>
    <style>
        body { background: #f3f4f6; color: #111827; font-family: Arial, sans-serif; margin: 0; padding: 24px; }
        .toolbar { display: flex; gap: 8px; justify-content: flex-end; margin: 0 auto 16px; max-width: 920px; }
        .btn { background: #111827; border: 1px solid #111827; border-radius: 6px; color: #fff; cursor: pointer; padding: 9px 14px; text-decoration: none; }
        .btn.secondary { background: #fff; color: #111827; }
        .document { background: #fff; box-shadow: 0 12px 35px rgba(15, 23, 42, .12); margin: 0 auto; max-width: 920px; padding: 42px; }
        .header { align-items: flex-start; border-bottom: 2px solid #111827; display: flex; justify-content: space-between; margin-bottom: 28px; padding-bottom: 18px; }
        .brand { font-size: 24px; font-weight: 700; }
        .muted { color: #6b7280; }
        h1 { font-size: 30px; margin: 0 0 6px; text-align: right; }
        h2 { font-size: 13px; letter-spacing: .05em; margin: 26px 0 8px; text-transform: uppercase; }
        .grid { display: grid; gap: 28px; grid-template-columns: 1fr 1fr; }
        .right { text-align: right; }
        table { border-collapse: collapse; margin-top: 12px; width: 100%; }
        th { background: #f3f4f6; color: #374151; font-size: 12px; padding: 10px; text-align: left; text-transform: uppercase; }
        td { border-bottom: 1px solid #e5e7eb; padding: 10px; }
        .box { background: #f9fafb; border: 1px solid #e5e7eb; padding: 14px; }
        .signature { border-top: 1px solid #111827; margin-top: 54px; padding-top: 8px; }
        @media print {
            body { background: #fff; padding: 0; }
            .toolbar { display: none; }
            .document { box-shadow: none; max-width: none; padding: 0; }
        }
    </style>
</head>
<body>
    <div class="toolbar">
        <a class="btn secondary" href="{{ route('agreements.download', $agreement) }}">Download PDF</a>
        <button class="btn" type="button" onclick="window.print()">Print Agreement</button>
    </div>

    <main class="document">
        @include('agreements._print-body', ['agreement' => $agreement])
    </main>
</body>
</html>
