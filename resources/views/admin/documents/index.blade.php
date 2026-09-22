@extends('components.layouts.admin')
@section('title', 'Documents')
@section('content')
<div class="bg-brand-100 border border-brand-200 p-6"><h1 class="text-xl font-semibold">Documents</h1>
<p class="text-sm text-brand-600 mt-1">Central file store for paperwork that supports daily operations. Upload supplier invoices to match against purchase orders, delivery notes to file with goods received vouchers, product spec sheets for the shop floor, stock take reports for audits, and anything else under general files. Files can later be linked to sales, purchase orders and products.</p>
<div class="text-sm mt-3 p-3 border border-brand-200 bg-brand-50">
<p class="font-medium mb-1">When to use each type</p>
<ul class="list-disc pl-5 space-y-1 text-brand-700">
<li><span class="font-medium">Supplier invoice:</span> the bill that arrives with or after supplier stock. File it here, then match its total against the purchase order before receiving.</li>
<li><span class="font-medium">Delivery note:</span> proof of what the driver dropped off. Attach it on the day so shortages can be claimed.</li>
<li><span class="font-medium">Product spec sheet:</span> ingredients, allergens, sizes and handling instructions cashiers may need at the till.</li>
<li><span class="font-medium">Stock take report:</span> signed count sheets from a stock take session.</li>
<li><span class="font-medium">General file:</span> photos of damage for waste claims, till payout slips, anything without its own category.</li>
</ul>
</div>
<form method="POST" action="{{ route('admin.documents.store') }}" enctype="multipart/form-data" class="flex flex-wrap gap-2 mt-4 text-sm">@csrf
<input type="file" name="file" required class="border border-brand-300 bg-brand-50 px-2 py-1"/>
<select name="type" class="border border-brand-300 bg-brand-50 px-2 py-1"><option value="other">General file</option><option value="invoice">Supplier invoice</option><option value="delivery_note">Delivery note</option><option value="spec_sheet">Product spec sheet</option><option value="stock_take_report">Stock take report</option></select>
<input name="description" placeholder="What is this file for?" class="border border-brand-300 bg-brand-50 px-2 py-1 flex-1"/>
<button class="px-3 py-1 bg-brand-900 text-white">Upload</button></form>
<ul class="text-sm mt-4 divide-y divide-brand-100">@forelse($documents as $d)<li class="py-2 flex justify-between gap-3"><span>{{ $d->original_name }} ({{ str_replace('_', ' ', $d->type) }}) @if($d->description)<span class="text-brand-500">— {{ $d->description }}</span>@endif</span><a href="{{ route('admin.documents.download', $d) }}" class="underline shrink-0">Download</a></li>@empty<li class="py-4 text-brand-500">No documents</li>@endforelse</ul></div>
@endsection
