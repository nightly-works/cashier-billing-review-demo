<!doctype html>
<html lang="en"><head><meta charset="utf-8"><title>Ledgerly pricing</title></head>
<body>
<h1>Ledgerly</h1>
<form method="POST" action="/billing/checkout">
    @csrf
    <input type="hidden" name="plan" value="basic">
    <button>Basic · 9 € / month</button>
</form>
<form method="POST" action="/billing/checkout">
    @csrf
    <input type="hidden" name="plan" value="pro">
    <button>Pro · 29 € / month · CSV export and forecast</button>
</form>
</body></html>
