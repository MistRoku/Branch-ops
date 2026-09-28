<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<title>Receipt {{ $sale->invoice_number }}</title>
<meta name="description" content="Sales receipt {{ $sale->invoice_number }}">
<style>
    body { font-family: 'IBM Plex Sans', system-ui, sans-serif; color: #0f172a; background: #f8fafc; margin: 0; }
    .receipt { width: 300px; margin: 16px auto; background: #f1f5f9; border: 1px solid #cbd5e1; padding: 12px 14px; font-size: 12px; line-height: 1.5; }
    .logo { display: block; margin: 0 auto 6px; max-width: 120px; max-height: 60px; }
    h1 { font-size: 14px; margin: 0; text-align: center; }
    .c { text-align: center; } .m { color: #475569; font-size: 11px; }
    hr { border: 0; border-top: 1px solid #cbd5e1; margin: 8px 0; }
    table { width: 100%; border-collapse: collapse; }
    td { padding: 1px 0; vertical-align: top; }
    .r { text-align: right; white-space: nowrap; }
    .t { font-weight: 700; font-size: 14px; }
    .items th { font-size: 10px; text-transform: uppercase; color: #475569; text-align: left; border-bottom: 1px solid #cbd5e1; padding-bottom: 3px; }
    .toolbar { text-align: center; margin: 16px; }
    .toolbar button { padding: 8px 20px; background: #0f172a; color: #fff; border: 0; font-size: 14px; cursor: pointer; }
    @media print { .toolbar { display: none; } body { background: #fff; } .receipt { border: 0; margin: 0; width: auto; } }
</style>
</head>
<body>
<div class="toolbar"><button onclick="window.print()">Print receipt</button></div>
<div class="receipt">
    @if(($business->use_logo_in_invoice ?? true) && ($business->logo_path ?? null))
    <img src="{{ asset('storage/' . $business->logo_path) }}" alt="Business logo" class="logo" />
    @endif
    @if(! (($business->use_logo_in_invoice ?? true) && ($business->logo_path ?? null)) && $sale->branch?->logo_path)
    <img src="{{ asset('storage/' . $sale->branch->logo_path) }}" alt="Branch logo" class="logo" />
    @endif
    <h1>{{ $business->business_name ?? $sale->branch?->receipt_header ?? $sale->branch?->name ?? 'BranchOps' }}</h1>
    <p class="c m">
        @if($business->register_number) Reg {{ $business->register_number }} @endif
        @if($business->vat_number) VAT {{ $business->vat_number }} @endif
        <br>{{ $sale->branch?->address_line1 ?? $sale->branch?->address }}<br>{{ $sale->branch?->phone }}
    </p>
    <hr>
    <table>
        <tr><td class="m">Invoice</td><td class="r">{{ $sale->invoice_number }}</td></tr>
        <tr><td class="m">Date</td><td class="r">{{ $sale->created_at->format('Y-m-d H:i') }}</td></tr>
        <tr><td class="m">Cashier</td><td class="r">{{ $sale->user?->name }}</td></tr>
        @if($sale->customer)
        <tr><td class="m">Customer</td><td class="r">{{ $sale->customer->name }}</td></tr>
        @endif
        <tr><td class="m">Payment</td><td class="r">{{ ucfirst($sale->payment_method) }}</td></tr>
        @if($sale->payment_reference)
        <tr><td class="m">Reference</td><td class="r">{{ $sale->payment_reference }}</td></tr>
        @endif
        @if($sale->fulfillment === 'delivery')
        <tr><td class="m">Deliver to</td><td class="r">{{ $sale->delivery_address }}</td></tr>
        @endif
    </table>
    <hr>
    <table class="items">
        <thead><tr><th>Qty</th><th>Item</th><th class="r">Total</th></tr></thead>
        <tbody>
        @foreach($sale->items as $it)
        <tr>
            <td>{{ $it->quantity }}</td>
            <td>{{ $it->product?->name }}<br><span class="m">{{ $it->product?->sku }} · {{ $it->product?->unit_of_measure ?? 'piece' }} @ R {{ number_format($it->unit_price, 2) }}</span></td>
            <td class="r">R {{ number_format($it->total, 2) }}</td>
        </tr>
        @endforeach
        </tbody>
    </table>
    <hr>
    <table>
        <tr><td class="m">Subtotal</td><td class="r">R {{ number_format($sale->subtotal, 2) }}</td></tr>
        @if($sale->discount_amount > 0)
        <tr><td class="m">Discount @if($sale->coupon_code) ({{ $sale->coupon_code }}) @endif</td><td class="r">R {{ number_format($sale->discount_amount, 2) }}</td></tr>
        @endif
        <tr><td class="m">Tax</td><td class="r">R {{ number_format($sale->tax_amount, 2) }}</td></tr>
        @if($sale->tip_amount > 0)
        <tr><td class="m">Tip</td><td class="r">R {{ number_format($sale->tip_amount, 2) }}</td></tr>
        @endif
        @if($sale->delivery_fee > 0)
        <tr><td class="m">Delivery fee</td><td class="r">R {{ number_format($sale->delivery_fee, 2) }}</td></tr>
        @endif
        <tr><td class="t">TOTAL</td><td class="r t">R {{ number_format($sale->total_amount, 2) }}</td></tr>
        @if($sale->tendered_amount !== null)
        <tr><td class="m">Tendered</td><td class="r">R {{ number_format($sale->tendered_amount, 2) }}</td></tr>
        <tr><td class="m">Change</td><td class="r">R {{ number_format($sale->change_amount, 2) }}</td></tr>
        @endif
        @if($sale->payments)
        <tr><td class="m">Split</td><td class="r">@foreach($sale->payments as $p)<span style="display:block">{{ $p['method'] }} R {{ number_format($p['amount'], 2) }}</span>@endforeach</td></tr>
        @endif
    </table>
    <hr>
    <p class="c m">{{ $sale->branch?->receipt_footer ?? 'Thank you for shopping with us' }}</p>
</div>
</body>
</html>
