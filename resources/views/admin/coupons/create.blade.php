@extends('components.layouts.admin')
@section('title', 'New Coupon')
@section('content')
<div class="bg-brand-100 border border-brand-200 p-6 max-w-xl">
    <h1 class="text-xl font-semibold mb-4">New Coupon or Voucher</h1>
    <form method="POST" action="{{ route('admin.coupons.store') }}" class="space-y-3">@csrf
        <div><label class="block text-sm font-medium mb-1">Code</label><input name="code" required maxlength="50" placeholder="SAVE10" class="w-full border border-brand-300 bg-brand-50 px-3 py-2 text-sm" /></div>
        <div><label class="block text-sm font-medium mb-1">Description</label><input name="description" maxlength="255" class="w-full border border-brand-300 bg-brand-50 px-3 py-2 text-sm" /></div>
        <div class="flex gap-2">
            <div class="flex-1"><label class="block text-sm font-medium mb-1">Type</label>
            <select name="type" class="w-full border border-brand-300 bg-brand-50 px-3 py-2 text-sm"><option value="percent">Percent off</option><option value="fixed">Fixed rand amount</option></select></div>
            <div class="flex-1"><label class="block text-sm font-medium mb-1">Value</label><input name="value" type="number" step="0.01" min="0" required class="w-full border border-brand-300 bg-brand-50 px-3 py-2 text-sm" /></div>
        </div>
        <div class="flex gap-2">
            <div class="flex-1"><label class="block text-sm font-medium mb-1">Minimum basket (R)</label><input name="min_total" type="number" step="0.01" min="0" value="0" class="w-full border border-brand-300 bg-brand-50 px-3 py-2 text-sm" /></div>
            <div class="flex-1"><label class="block text-sm font-medium mb-1">Usage limit</label><input name="usage_limit" type="number" min="1" placeholder="Unlimited" class="w-full border border-brand-300 bg-brand-50 px-3 py-2 text-sm" /></div>
        </div>
        <div class="flex gap-2">
            <div class="flex-1"><label class="block text-sm font-medium mb-1">Starts</label><input name="starts_at" type="date" class="w-full border border-brand-300 bg-brand-50 px-3 py-2 text-sm" /></div>
            <div class="flex-1"><label class="block text-sm font-medium mb-1">Ends</label><input name="ends_at" type="date" class="w-full border border-brand-300 bg-brand-50 px-3 py-2 text-sm" /></div>
        </div>
        <button class="px-4 py-2 bg-brand-900 text-white text-sm">Create coupon</button>
    </form>
</div>
@endsection
