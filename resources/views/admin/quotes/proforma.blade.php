<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<title>Proforma {{ $quote->quote_number }}</title>
<style>
    body { font-family: 'IBM Plex Sans', system-ui, sans-serif; color: #0f172a; background: #f8fafc; margin: 0; }
    .doc { max-width: 680px; margin: 24px auto; background: #f1f5f9; border: 1px solid #cbd5e1; padding: 24px; }
    h1 { font-size: 20px; margin: 0; }
    .small { font-size: 12px; color: #475569; }
    table { width: 100%; font-size: 14px; border-collapse: collapse; margin-top: 16px; }
    th { text-align: left; font-size: 12px; text-transform: uppercase; color: #475569; border-bottom: 2px solid #cbd5e1; padding: 6px 8px 6px 0; }
    td { padding: 6px 8px 6px 0; border-bottom: 1px solid #e2e8f0; }
    .toolbar { text-align: center; margin: 16px; }
    .toolbar button { padding: 8px 20px; background: #0f172a; color: #fff; border: 0; font-size: 14px; cursor: pointer; }
    @media print { .toolbar { display: none; } body { background: #fff; } .doc { border: 0; margin: 0; max-width: none; } }
</style>
</head>
<body>
<div class="toolbar"><button onclick="window.print()">Print proforma</button></div>
<div class="doc">
    <h1>Proforma Invoice</h1>
    <p class="small">{{ $quote->quote_number }} · {{ $quote->branch?->name }} · Valid until {{ $quote->valid_until?->format('Y-m-d') ?? '—' }}</p>
    <p class="small">Bill to: {{ $quote->customer?->name ?? 'Walk-in customer' }}@if($quote->customer?->address) · {{ $quote->customer->address }}@endif</p>
    <table>
        <thead><tr><th>Product</th><th>UOM</th><th>Qty</th><th>Price</th><th style="text-align:right">Line total</th></tr></thead>
        <tbody>
        @foreach($quote->items as $it)
        <tr><td>{{ $it->product?->name }}<br><span class="small">{{ $it->product?->sku }}</span></td>
        <td>{{ $it->product?->unit_of_measure ?? 'piece' }}</td><td>{{ $it->quantity }}</td><td>R {{ number_format($it->unit_price, 2) }}</td><td style="text-align:right">R {{ number_format($it->total, 2) }}</td></tr>
        @endforeach
        </tbody>
    </table>
    <p style="text-align:right">Subtotal: R {{ number_format($quote->subtotal, 2) }}<br>Discount: R {{ number_format($quote->discount_amount, 2) }}<br>Tax: R {{ number_format($quote->tax_amount, 2) }}<br><strong>Total: R {{ number_format($quote->total_amount, 2) }}</strong></p>
    @if($quote->notes)<p class="small">{{ $quote->notes }}</p>@endif
    <p class="small">This is a proforma invoice, not a tax invoice. Prices valid until {{ $quote->valid_until?->format('Y-m-d') ?? 'expiry' }}.</p>
</div>
</body>
</html>
