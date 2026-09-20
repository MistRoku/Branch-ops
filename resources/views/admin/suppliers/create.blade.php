<x-layouts.admin title="New Supplier">
<div class="max-w-xl bg-white border p-6">
<h1 class="text-xl font-semibold mb-4">New Supplier</h1>
<form method="POST" action="{{ route('admin.suppliers.store') }}" class="space-y-3">@csrf
<input name="name" required placeholder="Name" value="{{ old('name') }}" class="w-full border px-3 py-2 text-sm"/>
<input name="contact_name" placeholder="Contact name" value="{{ old('contact_name') }}" class="w-full border px-3 py-2 text-sm"/>
<input name="email" type="email" placeholder="Email" value="{{ old('email') }}" class="w-full border px-3 py-2 text-sm"/>
<input name="phone" placeholder="Phone" value="{{ old('phone') }}" class="w-full border px-3 py-2 text-sm"/>
<input name="tax_id" placeholder="Tax ID" value="{{ old('tax_id') }}" class="w-full border px-3 py-2 text-sm"/>
<textarea name="address" placeholder="Address" class="w-full border px-3 py-2 text-sm">{{ old('address') }}</textarea>
<textarea name="notes" placeholder="Notes" class="w-full border px-3 py-2 text-sm">{{ old('notes') }}</textarea>
<button class="px-4 py-2 bg-gray-900 text-white text-sm">Create</button></form>
</div>
</x-layouts.admin>
