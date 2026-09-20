<x-layouts.admin title="Edit Supplier">
<div class="max-w-xl bg-white border p-6">
<h1 class="text-xl font-semibold mb-4">Edit {{ $supplier->name }}</h1>
<form method="POST" action="{{ route('admin.suppliers.update', $supplier) }}" class="space-y-3">@csrf @method('PUT')
<input name="name" required value="{{ old('name', $supplier->name) }}" class="w-full border px-3 py-2 text-sm"/>
<input name="contact_name" value="{{ old('contact_name', $supplier->contact_name) }}" class="w-full border px-3 py-2 text-sm"/>
<input name="email" type="email" value="{{ old('email', $supplier->email) }}" class="w-full border px-3 py-2 text-sm"/>
<input name="phone" value="{{ old('phone', $supplier->phone) }}" class="w-full border px-3 py-2 text-sm"/>
<input name="tax_id" value="{{ old('tax_id', $supplier->tax_id) }}" class="w-full border px-3 py-2 text-sm"/>
<textarea name="address" class="w-full border px-3 py-2 text-sm">{{ old('address', $supplier->address) }}</textarea>
<button class="px-4 py-2 bg-gray-900 text-white text-sm">Save</button></form>
</div>
</x-layouts.admin>
