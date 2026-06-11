<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 12px; }
        table { width: 100%; border-collapse: collapse; }
        th, td { border: 1px solid #ccc; padding: 6px; }
        th { background: #f3f4f6; }
    </style>
</head>
<body>
    <h1>Reporte TheValueFlow</h1>
    <table>
        <thead>
            <tr>
                <th>Offer ID</th>
                <th>Impressions</th>
                <th>Clicks</th>
                <th>Conversions</th>
                <th>Revenue</th>
                <th>Payout</th>
            </tr>
        </thead>
        <tbody>
            @foreach($data as $row)
            <tr>
                <td>{{ $row->offer_id }}</td>
                <td>{{ $row->impressions }}</td>
                <td>{{ $row->gross_clicks }}</td>
                <td>{{ $row->conversions }}</td>
                <td>{{ $row->revenue }}</td>
                <td>{{ $row->payout }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>
</body>
</html>
