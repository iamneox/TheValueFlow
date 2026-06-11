<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 12px; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { border: 1px solid #ccc; padding: 8px; text-align: left; }
        th { background: #f3f4f6; }
        .header { margin-bottom: 30px; }
        .total { font-size: 16px; font-weight: bold; margin-top: 20px; }
    </style>
</head>
<body>
    <div class="header">
        <h1>Cierre {{ $invoice->number }}</h1>
        <p>Partner: {{ $invoice->partner->name }}</p>
        <p>Periodo: {{ $invoice->period_start->format('d/m/Y') }} - {{ $invoice->period_end->format('d/m/Y') }}</p>
    </div>
    <table>
        <thead>
            <tr>
                <th>Campaña</th>
                <th>Cantidad</th>
                <th>Tipo</th>
                <th>Payout</th>
                <th>Total</th>
            </tr>
        </thead>
        <tbody>
            @foreach($invoice->lines as $line)
            <tr>
                <td>{{ $line->campaign_name }}</td>
                <td>{{ $line->quantity }}</td>
                <td>{{ $line->quantity_type }}</td>
                <td>{{ number_format($line->payout, 2) }} €</td>
                <td>{{ number_format($line->total_payout, 2) }} €</td>
            </tr>
            @endforeach
        </tbody>
    </table>
    <p class="total">Total: {{ number_format($invoice->total_amount, 2) }} €</p>
</body>
</html>
