@extends('components.layouts.admin')
@section('title', 'Reports')
@section('content')
<div class="bg-brand-100 border border-brand-200 p-6 max-w-3xl"><h1 class="text-xl font-semibold">Reports</h1>
<p class="text-sm text-brand-600 mt-1">Filter by branch and date, preview the scope below, then download as CSV for spreadsheets or PDF for filing and printing. Amounts are in rand.</p>
<form method="GET" action="{{ route('admin.reports.export', 'sales') }}" class="flex flex-wrap gap-2 mt-4 text-sm" id="report-form">
<label class="block">Report
<select name="report-type" id="report-type" class="border border-brand-300 bg-brand-50 px-2 py-1 ml-1">
<option value="sales">Sales</option><option value="refunds">Refunds</option><option value="voids">Voids</option>
<option value="inventory">Inventory</option><option value="movements">Stock movements</option>
<option value="waste">Waste</option><option value="payouts">Payouts</option>
</select></label>
@if($branches->count())
<label class="block">Branch
<select name="branch_id" class="border border-brand-300 bg-brand-50 px-2 py-1 ml-1"><option value="">All branches</option>@foreach($branches as $b)<option value="{{ $b->id }}">{{ $b->name }}</option>@endforeach</select></label>
@endif
<label class="block">From<input name="from" type="date" class="border border-brand-300 bg-brand-50 px-2 py-1 ml-1"/></label>
<label class="block">To<input name="to" type="date" class="border border-brand-300 bg-brand-50 px-2 py-1 ml-1"/></label>
</form>
<div class="flex gap-2 mt-4 text-sm">
<button onclick="downloadReport('csv')" class="px-4 py-2 bg-brand-900 text-white">Download CSV</button>
<button onclick="downloadReport('pdf')" class="px-4 py-2 border border-brand-300">Download PDF</button>
</div>
<p class="text-xs text-brand-500 mt-3">Sales shows tenders, tips, discounts and coupons per invoice. Voids lists every voided sale with actor and reason. Waste and payouts track shrinkage and cash out of the drawer.</p></div>
<script>
function downloadReport(format) {
    const form = document.getElementById('report-form');
    const data = new FormData(form);
    const type = document.getElementById('report-type').value;
    const params = new URLSearchParams({format: format});
    if (data.get('branch_id')) params.append('branch_id', data.get('branch_id'));
    if (data.get('from')) params.append('from', data.get('from'));
    if (data.get('to')) params.append('to', data.get('to'));
    window.location.href = '/admin/reports/export/' + type + '?' + params.toString();
}
</script>
@endsection
