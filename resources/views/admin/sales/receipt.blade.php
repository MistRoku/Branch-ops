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
    h1 { font-size: 18px; margin: 0; text-align: center; letter-spacing: 1px; }
    .c { text-align: center; } .m { color: #334155; font-size: 11px; }
    .sep { border: 0; border-top: 2px dashed #94a3b8; margin: 8px 0; }
    .sep-thin { border: 0; border-top: 1px dashed #94a3b8; margin: 8px 0; }
    table { width: 100%; border-collapse: collapse; }
    td { padding: 1px 0; vertical-align: top; }
    .r { text-align: right; white-space: nowrap; }
    .doc-title { text-align: center; font-size: 15px; font-weight: 700; margin: 0; }
    .grand { font-weight: 700; font-size: 14px; }
    .pair td { width: 50%; }
    .barcode { display: flex; align-items: stretch; justify-content: center; height: 48px; margin: 8px 0 2px; }
    .barcode i { display: block; background: #0f172a; }
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
        {{ $sale->branch?->address_line1 ?? $sale->branch?->address }}<br>
        {{ $sale->branch?->city }}{{ $sale->branch?->city ? ',' : '' }} {{ $sale->branch?->country }}<br>
        @if($business->register_number) Reg No: {{ $business->register_number }}<br> @endif
        @if($business->email) Email: {{ $business->email }}<br> @endif
        Tel: {{ $sale->branch?->phone }}
    </p>
    <hr class="sep">
    <p class="doc-title">Tax Invoice</p>
    <p class="c" style="margin:2px 0 0">{{ $sale->invoice_number }}<br><span class="m">{{ $sale->created_at->format('d-m-Y h:i:s A') }}</span></p>
    <hr class="sep">
    <table>
        <tr><td>Customer:</td><td class="r">{{ $sale->customer?->name ?? 'Walk-in' }}</td></tr>
        <tr><td>Cashier:</td><td class="r">{{ $sale->user?->name }}</td></tr>
        <tr><td>Payment:</td><td class="r">{{ ucfirst($sale->payment_method) }}</td></tr>
        @if($sale->fulfillment === 'delivery')
        <tr><td>Deliver to:</td><td class="r">{{ $sale->delivery_address }}</td></tr>
        @endif
    </table>
    <hr class="sep-thin">
    <table>
        <tr><td class="m">Qty</td><td class="m">Product</td><td class="m r">Price</td></tr>
        @foreach($sale->items as $it)
        <tr>
            <td>{{ $it->quantity }}</td>
            <td>{{ $it->product?->name }} <span class="m">{{ $it->product?->unit_of_measure ?? 'piece' }} @ R {{ number_format($it->unit_price, 2) }}</span></td>
            <td class="r">R {{ number_format($it->total, 2) }}</td>
        </tr>
        @endforeach
    </table>
    <hr class="sep-thin">
    <table>
        <tr><td></td><td class="r">Sub Total</td><td class="r">R {{ number_format($sale->subtotal, 2) }}</td></tr>
        <tr><td></td><td class="r">VAT</td><td class="r">R {{ number_format($sale->tax_amount, 2) }}</td></tr>
        @if($sale->discount_amount > 0)
        <tr><td></td><td class="r">Discount @if($sale->coupon_code) ({{ $sale->coupon_code }}) @endif</td><td class="r">R {{ number_format($sale->discount_amount, 2) }}</td></tr>
        @endif
        @if($sale->delivery_fee > 0)
        <tr><td></td><td class="r">Delivery</td><td class="r">R {{ number_format($sale->delivery_fee, 2) }}</td></tr>
        @endif
        <tr><td></td><td class="r grand">Grand Total</td><td class="r grand">R {{ number_format($sale->total_amount, 2) }}</td></tr>
    </table>
    <hr class="sep-thin">
    <table class="pair">
        <tr><td class="c">Customer Paid</td><td class="c">Change</td></tr>
        <tr><td class="c">R {{ number_format($sale->tendered_amount ?? $sale->total_amount, 2) }}</td><td class="c">R {{ number_format($sale->change_amount, 2) }}</td></tr>
    </table>
    <hr class="sep-thin">
    <table class="pair">
        <tr><td class="c">Cash Sale</td><td class="c">Card Sale</td></tr>
        <tr>
            <td class="c">R {{ number_format($sale->payment_method === 'cash' ? $sale->total_amount : collect($sale->payments ?? [])->where('method', 'cash')->sum('amount'), 2) }}</td>
            <td class="c">R {{ number_format($sale->payment_method === 'card' ? $sale->total_amount : collect($sale->payments ?? [])->where('method', 'card')->sum('amount'), 2) }}</td>
        </tr>
    </table>
    <hr class="sep-thin">
    <table>
        <tr><td>Tip:</td><td class="r">R {{ number_format($sale->tip_amount, 2) }}</td></tr>
        <tr><td class="grand">Total:</td><td class="r grand">R {{ number_format($sale->total_amount, 2) }}</td></tr>
    </table>
    <hr class="sep">
    @php
        $code39 = ['0' => 'nnnwwnwnn','1' => 'wnnwnnnnw','2' => 'nnwwnnnnw','3' => 'wnwwnnnnn','4' => 'nnnwwnnnw','5' => 'wnnwwnnnn','6' => 'nnwwwnnnn','7' => 'nnnwnnwnw','8' => 'wnnwnnwnn','9' => 'nnwwnnwnn','A' => 'wnnnnwnnw','B' => 'nnwnnwnnw','C' => 'wnwnnwnnn','D' => 'nnnnwwnnw','E' => 'wnnnwwnnn','F' => 'nnwnwwnnn','G' => 'nnnnnwwnw','H' => 'wnnnnwwnn','I' => 'nnwnnwwnn','J' => 'nnnnwwwnn','K' => 'wnnnnnnww','L' => 'nnwnnnnww','M' => 'wnwnnnnwn','N' => 'nnnnwnnww','O' => 'wnnnwnnwn','P' => 'nnwnwnnwn','Q' => 'nnnnnnwww','R' => 'wnnnnnwwn','S' => 'nnwnnnwwn','T' => 'nnnnnwwwn','U' => 'wwnnnnnnw','V' => 'nwwnnnnnw','W' => 'wwwnnnnnn','X' => 'nwnnwnnnw','Y' => 'wwnnwnnnn','Z' => 'nwwnwnnnn','-' => 'nwnnnnwnw','.' => 'wwnnnnwnn',' ' => 'nwwnnnwnn','*' => 'nwnnwnwnn','$' => 'nwnwnwnnn','/' => 'nwnwnnnwn','+' => 'nwnnnwnwn','%' => 'nnnwnwnwn'];
        $bars = '';
        foreach (str_split('*' . strtoupper($sale->invoice_number) . '*') as $ch) {
            $pattern = $code39[$ch] ?? $code39['-'];
            for ($i = 0; $i < 9; $i++) {
                $wide = $pattern[$i] === 'w';
                if ($i % 2 === 0) {
                    $bars .= '<i style="width:' . ($wide ? '4px' : '2px') . '"></i>';
                } else {
                    $bars .= '<i style="width:' . ($wide ? '4px' : '2px') . ';background:transparent"></i>';
                }
            }
            $bars .= '<i style="width:2px;background:transparent"></i>';
        }
    @endphp
    <div class="barcode" role="img" aria-label="Invoice barcode">{!! $bars !!}</div>
    <p class="c m">{{ $sale->invoice_number }}</p>
    <p class="c" style="margin-bottom:0">Thank You<br><span class="m">{{ $sale->branch?->receipt_footer ?? 'Glad to see you again!' }}</span></p>
</div>
</body>
</html>
