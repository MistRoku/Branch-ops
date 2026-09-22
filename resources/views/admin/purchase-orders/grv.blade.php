<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<title>GRV {{ $po->grv_number ?? $po->po_number }}</title>
<style>
    body { font-family: 'IBM Plex Sans', system-ui, sans-serif; color: #0f172a; background: #f8fafc; margin: 0; }
    .doc { max-width: 720px; margin: 24px auto; background: #f1f5f9; border: 1px solid #cbd5e1; padding: 24px; }
    h1 { font-size: 20px; margin: 0; } .small { font-size: 12px; color: #475569; }
    table { width: 100%; font-size: 14px; border-collapse: collapse; margin-top: 16px; }
    th { text-align: left; font-size: 12px; text-transform: uppercase; color: #475569; border-bottom: 2px solid #cbd5e1; padding: 6px 8px 6px 0; }
    td { padding: 6px 8px 6px 0; border-bottom: 1px solid #e2e8f0; }
    .toolbar { text-align: center; margin: 16px; }
    .toolbar button { padding: 8px 20px; background: #0f172a; color: #fff; border: 0; font-size: 14px; cursor: pointer; }
    .sign { display: flex; gap: 24px; margin-top: 32px; font-size: 13px; }
    .sign div { flex: 1; border-top: 1px solid #0f172a; padding-top: 4px; }
    @media print { .toolbar { display: none; } body { background: #fff; } .doc { border: 0; margin: 0; max-width: none; } }
</style>
</head>
<body>
<div class="toolbar"><button onclick="window.print()">Print GRV</button></div>
<div class="doc">
    <h1>Goods Received Voucher</h1>
    <p class="small">{{ $po->grv_number ?? 'GRV pending' }} · Branch: {{ $po->branch?->name }} · Supplier: {{ $po->supplier?->name }}</p>
    <p class="small">PO: {{ $po->po_number }} · Received by: {{ $po->receivedBy?->name ?? '—' }} · Date: {{ $po->received_at?->format('Y-m-d') ?? $po->updated_at->format('Y-m-d') }}</p>
    @if($po->delivery_notes)<p class="small">Delivery notes: {{ $po->delivery_notes }}</p>@endif
    <table>
        <thead><tr><th>Product</th><th>UOM</th><th>Ordered</th><th>Received</th><th>Unit cost</th></tr></thead>
        <tbody>
        @foreach($po->items as $it)
        <tr><td>{{ $it->product?->name }}<br><span class="small">{{ $it->product?->sku }}</span></td>
        <td>{{ $it->product?->unit_of_measure ?? 'piece' }}</td><td>{{ $it->quantity_ordered }}</td><td>{{ $it->quantity_received }}</td><td>R {{ number_format($it->unit_cost, 2) }}</td></tr>
        @endforeach
        </tbody>
    </table>
    <div class="sign"><div>Received by</div><div>Checked by</div></div>
</div>
</body>
</html>
