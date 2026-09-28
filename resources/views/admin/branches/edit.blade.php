@extends('components.layouts.admin')
@section('title', 'Edit ' . $branch->name)
@section('content')
<div class="bg-brand-100 border border-brand-200 p-6 max-w-3xl">
    <div class="flex items-start justify-between mb-4">
        <h1 class="text-xl font-semibold">Edit Branch</h1>
        <span class="text-xs text-brand-500">Branch ID: {{ $branch->code }}</span>
    </div>
    <form method="POST" action="{{ route('admin.branches.update', $branch) }}" class="space-y-6">@csrf @method('PUT')
        <div>
            <h2 class="font-semibold mb-3">Branch details</h2>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                <label class="block text-sm font-medium">Branch name *<input name="name" required value="{{ old('name', $branch->name) }}" class="w-full border border-brand-300 bg-brand-50 px-3 py-2 text-sm mt-1" /></label>
                <label class="block text-sm font-medium">Parent branch<select name="parent_id" class="w-full border border-brand-300 bg-brand-50 px-3 py-2 text-sm mt-1"><option value="">None (top level)</option>@foreach($parents as $p)<option value="{{ $p->id }}" @selected(old('parent_id', $branch->parent_id) == $p->id)>{{ $p->name }} ({{ $p->code }})</option>@endforeach</select></label>
                <label class="block text-sm font-medium">Phone<input name="phone" value="{{ old('phone', $branch->phone) }}" class="w-full border border-brand-300 bg-brand-50 px-3 py-2 text-sm mt-1" /></label>
                <label class="block text-sm font-medium">Website<input name="website" value="{{ old('website', $branch->website) }}" class="w-full border border-brand-300 bg-brand-50 px-3 py-2 text-sm mt-1" /></label>
            </div>
        </div>
        <div>
            <h2 class="font-semibold mb-3">Address</h2>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                <label class="block text-sm font-medium">Address line 1<input name="address_line1" value="{{ old('address_line1', $branch->address_line1) }}" class="w-full border border-brand-300 bg-brand-50 px-3 py-2 text-sm mt-1" /></label>
                <label class="block text-sm font-medium">Address line 2<input name="address_line2" value="{{ old('address_line2', $branch->address_line2) }}" class="w-full border border-brand-300 bg-brand-50 px-3 py-2 text-sm mt-1" /></label>
                <label class="block text-sm font-medium">City<input name="city" value="{{ old('city', $branch->city) }}" class="w-full border border-brand-300 bg-brand-50 px-3 py-2 text-sm mt-1" /></label>
                <label class="block text-sm font-medium">Pincode<input name="postal_code" value="{{ old('postal_code', $branch->postal_code) }}" class="w-full border border-brand-300 bg-brand-50 px-3 py-2 text-sm mt-1" /></label>
                <label class="block text-sm font-medium">Country<select name="country" class="w-full border border-brand-300 bg-brand-50 px-3 py-2 text-sm mt-1"><option>South Africa</option>@foreach(['Namibia','Botswana','Zimbabwe','Mozambique','Lesotho','Eswatini'] as $c)<option @selected(old('country', $branch->country) === $c)>{{ $c }}</option>@endforeach</select></label>
                <label class="block text-sm font-medium">Province<select name="province" class="w-full border border-brand-300 bg-brand-50 px-3 py-2 text-sm mt-1"><option value="">Select province</option>@foreach(['Eastern Cape','Free State','Gauteng','KwaZulu-Natal','Limpopo','Mpumalanga','Northern Cape','North West','Western Cape'] as $p)<option @selected(old('province', $branch->province) === $p)>{{ $p }}</option>@endforeach</select></label>
            </div>
        </div>
        <div>
            <h2 class="font-semibold mb-3">Transaction</h2>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                <label class="block text-sm font-medium">VAT rate %<input name="tax_rate" type="number" step="0.01" min="0" max="100" value="{{ old('tax_rate', $branch->tax_rate) }}" class="w-full border border-brand-300 bg-brand-50 px-3 py-2 text-sm mt-1" /></label>
                <label class="block text-sm font-medium">Transaction series *<input name="transaction_series" required value="{{ old('transaction_series', $branch->transaction_series) }}" class="w-full border border-brand-300 bg-brand-50 px-3 py-2 text-sm mt-1" /></label>
            </div>
            <label class="block text-sm font-medium mt-3">Associated warehouses <span class="font-normal text-brand-500">(one per line)</span><textarea name="warehouses" rows="3" class="w-full border border-brand-300 bg-brand-50 px-3 py-2 text-sm mt-1">{{ old('warehouses', $branch->warehouses ? implode("\n", $branch->warehouses) : '') }}</textarea></label>
        </div>
        <button class="px-4 py-2 bg-brand-900 text-white text-sm">Save branch</button>
    </form>
</div>
@endsection
