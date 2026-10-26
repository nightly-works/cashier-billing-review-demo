<!doctype html>
<html lang="en"><head><meta charset="utf-8"><title>Reports</title></head>
<body>
<h1>Reports</h1>
@if ($user->hasPro())
    <a href="/reports/export">Export CSV</a>
    <a href="/reports/forecast">Forecast</a>
@else
    <p>CSV export and forecast are part of <a href="/pricing">Pro</a>.</p>
@endif
</body></html>
