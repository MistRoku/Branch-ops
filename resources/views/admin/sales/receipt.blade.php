<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<title>Receipt {{ $sale->invoice_number }}</title>
<style>
    body { font-family: 'IBM Plex Sans', system-ui, sans-serif; color: #0f172a; background: #f8fafc; margin: 0; }
    .receipt { width: 300px; margin: 16px auto; background: #f1f5f9; border: 1px solid #cbd5e1; padding: 12px 14px; font-size: 12px; line-height: 1.45; }
    .logo { display: block; margin: 0 auto 6px; max-width: 120px; max-height: 60px; }
    h1 { font-size: 14px; margin: 0; text-align: center; }
    .c { text-align: center; } .m { color: #475569; font-size: 11px; }
    hr { border: 0; border-top: 1px solid #cbd5e1; margin: 8px 0; }
    table { width: 100%; border-collapse: collapse; }
    td { padding: 1px 0; vertical-align: top; }
    .r { text-align: right; white-space: nowrap; }
    .t { font-weight: 700; font-size: 14px; }
    .toolbar { text-align: center; margin: 16px; }
    .toolbar button { padding: 8px 20px; background: #0f172a; color: #fff; border: 0; font-size: 14px; cursor: pointer; }
    @media print { .toolbar { display: none; } body { background: #fff; } .receipt { border: 0; margin: 0; width: auto; } }
</style>
</head>
<body>
<div class="toolbar"><button onclick="window.print()">Print receipt</button></div>
<div class="receipt">
    @if($sale->branch?->logo_path)<img src="{{ asset('storage/' . $sale->branch->logo_path) }}" alt="Branch logo" class="logo" />@endif
    <h1>{{ $sale->branch?->receipt_header ?? $sale->branch?->name ?? 'BranchOps' }}</h1>
    <p class="c m">{{ $sale->branch?->address }}<br>{{ $sale->branch?->phone }}</p>
    <hr>
    <table>
        <tr><td class="m">Invoice</td><td class="r">{{ $sale->invoice_number }}</td></tr>
        <tr><td class="m">Date</td><td class="r">{{ $sale->created_at->format('Y-m-d H:i') }}</td></tr>
        <tr><td class="m">Cashier</td><td class="r">{{ $sale->user?->name }}</td></tr>
        @if($sale->customer)<tr><td class="m">Customer</td><td class="r">{{ $sale->customer->name }}</td></tr>@endif
        <tr><td class="m">Payment</td><td class="r">{{ ucfirst($sale->payment_method) }}</td></tr>
        @if($sale->fulfillment === 'delivery')<tr><td class="m">Delivery</td><td class="r">{{ $sale->delivery_address }}</td></tr>@endif
    </table>
    <hr>
    <table>
        @foreach($sale->items as $it)
        <tr><td>{{ $it->quantity }} x {{ $it->product?->name }}</td><td class="r">R {{ number_format($it->total, 2) }}</td></tr>
        <tr><td class="m">{{ $it->product?->unit_of_measure ?? 'piece' }} @ R {{ number_format($it->unit_price, 2) }}</td><td></td></tr>
        @endforeach
    </table>
    <hr>
    <table>
        <tr><td class="m">Subtotal</td><td class="r">R {{ number_format($sale->subtotal, 2) }}</td></tr>
        @if($sale->discount_amount > 0)<tr><td class="m">Discount</td><td class="r">R {{ number_format($sale->discount_amount, 2) }}</td></tr>@endif
        <tr><td class="m">Tax</td><td class="r">R {{ number_format($sale->tax_amount, 2) }}</td></tr>
        @if($sale->tip_amount > 0)<tr><td class="m">Tip</td><td class="r">R {{ number_format($sale->tip_amount, 2) }}</td></tr>@endif
        @if($sale->delivery_fee > 0)<tr><td class="m">Delivery</td><td class="r">R {{ number_format($sale->delivery_fee, 2) }}</td></tr>@endif
        <tr><td class="t">TOTAL</td><td class="r t">R {{ number_format($sale->total_amount, 2) }}</td></tr>
        @if($sale->tendered_amount !== null)<tr><td class="m">Tendered</td><td class="r">R {{ number_format($sale->tendered_amount, 2) }}</td></tr>
        <tr><td class="m">Change</td><td class="r">R {{ number_format($sale->change_amount, 2) }}</td></tr>@endif
        @if($sale->payments)<tr><td class="m">Split</td><td class="r">@foreach($sale->payments as $p){{ $p['method'] }} R {{ number_format($p['amount'], 2) }}@if(!$loop->last)<br>@endif@endforeach</td></tr>@endif
    </table>
    <hr>
    <p class="c m">{{ $sale->branch?->receipt_footer ?? 'Thank you for shopping with us' }}</p>
</div>
</body>
</html>
