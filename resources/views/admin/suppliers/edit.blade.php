@extends('components.layouts.admin')
@section('title', 'Edit Supplier')
@section('content')
<div class="max-w-xl">
    <a href="{{ route('admin.suppliers.show', $supplier) }}" class="text-sm text-gray-500 hover:underline">&larr; Back to {{ $supplier->name }}</a>
    <h1 class="text-2xl font-semibold mt-2 mb-4">Edit Supplier</h1>
    @if($errors->any())<div class="bg-red-50 border border-red-200 text-red-700 text-sm p-4 mb-4"><ul class="list-disc ml-5">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul></div>@endif
    <form method="POST" action="{{ route('admin.suppliers.update', $supplier) }}" class="bg-white border p-6 space-y-4">@csrf @method('PUT')
        <div><label class="block text-xs font-medium uppercase mb-1">Name *</label><input name="name" required value="{{ old('name', $supplier->name) }}" class="w-full border px-3 py-2 text-sm"/></div>
        <div class="grid grid-cols-2 gap-3">
            <div><label class="block text-xs font-medium uppercase mb-1">Contact name</label><input name="contact_name" value="{{ old('contact_name', $supplier->contact_name) }}" class="w-full border px-3 py-2 text-sm"/></div>
            <div><label class="block text-xs font-medium uppercase mb-1">Phone</label><input name="phone" value="{{ old('phone', $supplier->phone) }}" class="w-full border px-3 py-2 text-sm"/></div>
        </div>
        <div><label class="block text-xs font-medium uppercase mb-1">Email</label><input name="email" type="email" value="{{ old('email', $supplier->email) }}" class="w-full border px-3 py-2 text-sm"/></div>
        <div><label class="block text-xs font-medium uppercase mb-1">Tax ID</label><input name="tax_id" value="{{ old('tax_id', $supplier->tax_id) }}" class="w-full border px-3 py-2 text-sm"/></div>
        <div><label class="block text-xs font-medium uppercase mb-1">Address</label><textarea name="address" rows="2" class="w-full border px-3 py-2 text-sm">{{ old('address', $supplier->address) }}</textarea></div>
        <div><label class="block text-xs font-medium uppercase mb-1">Notes</label><textarea name="notes" rows="2" class="w-full border px-3 py-2 text-sm">{{ old('notes', $supplier->notes) }}</textarea></div>
        <label class="flex items-center gap-2 text-sm"><input type="checkbox" name="is_active" value="1" @checked(old('is_active', $supplier->is_active))/> Active</label>
        <div class="flex gap-2"><button class="px-4 py-2 bg-gray-900 text-white text-sm">Save changes</button><a href="{{ route('admin.suppliers.show', $supplier) }}" class="px-4 py-2 border text-sm">Cancel</a></div>
    </form>
</div>
@endsection
