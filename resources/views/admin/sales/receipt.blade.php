<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<title>Receipt {{ $sale->invoice_number }}</title>
<style>
    body { font-family: 'IBM Plex Sans', system-ui, sans-serif; color: #0f172a; background: #f8fafc; margin: 0; }
    .receipt { width: 320px; margin: 24px auto; background: #f1f5f9; border: 1px solid #cbd5e1; padding: 16px; }
    h1 { font-size: 16px; margin: 0 0 2px; text-align: center; }
    .center { text-align: center; } .small { font-size: 12px; color: #475569; }
    table { width: 100%; font-size: 13px; border-collapse: collapse; margin-top: 12px; }
    td { padding: 3px 0; vertical-align: top; }
    .totals td { border-top: 1px solid #cbd5e1; padding-top: 6px; font-weight: 600; }
    .toolbar { text-align: center; margin: 16px; }
    .toolbar button { padding: 8px 20px; background: #0f172a; color: #fff; border: 0; font-size: 14px; cursor: pointer; }
    @media print { .toolbar { display: none; } body { background: #fff; } .receipt { border: 0; margin: 0; width: auto; } }
</style>
</head>
<body>
<div class="toolbar"><button onclick="window.print()">Print receipt</button></div>
<div class="receipt">
    <h1>{{ $sale->branch?->receipt_header ?? $sale->branch?->name ?? 'BranchOps' }}</h1>
    <p class="center small">{{ $sale->branch?->address }}<br>{{ $sale->branch?->phone }}</p>
    <p class="small">Invoice: {{ $sale->invoice_number }}<br>Date: {{ $sale->created_at->format('Y-m-d H:i') }}<br>Cashier: {{ $sale->user?->name }}<br>Payment: {{ ucfirst($sale->payment_method) }}</p>
    <table>
        @foreach($sale->items as $it)
        <tr><td>{{ $it->quantity }} x {{ $it->product?->name }}<br><span class="small">{{ $it->product?->unit_of_measure ?? 'piece' }} @ R {{ number_format($it->unit_price, 2) }}</span></td><td style="text-align:right">R {{ number_format($it->total, 2) }}</td></tr>
        @endforeach
        <tr><td class="small">Subtotal</td><td style="text-align:right" class="small">R {{ number_format($sale->subtotal, 2) }}</td></tr>
        <tr><td class="small">Discount</td><td style="text-align:right" class="small">R {{ number_format($sale->discount_amount, 2) }}</td></tr>
        <tr><td class="small">Tax</td><td style="text-align:right" class="small">R {{ number_format($sale->tax_amount, 2) }}</td></tr>
        <tr><td class="small">Tip</td><td style="text-align:right" class="small">R {{ number_format($sale->tip_amount, 2) }}</td></tr>
        <tr class="totals"><td>Total</td><td style="text-align:right">R {{ number_format($sale->total_amount, 2) }}</td></tr>
        @if($sale->tendered_amount !== null)<tr><td class="small">Tendered</td><td style="text-align:right" class="small">R {{ number_format($sale->tendered_amount, 2) }}</td></tr>
        <tr><td class="small">Change</td><td style="text-align:right" class="small">R {{ number_format($sale->change_amount, 2) }}</td></tr>@endif
    </table>
    <p class="center small">{{ $sale->branch?->receipt_footer ?? 'Thank you for shopping with us' }}</p>
</div>
</body>
</html>
