@extends('components.layouts.admin')
@section('title', 'New Branch')
@section('content')
<div class="bg-brand-100 border border-brand-200 p-6 max-w-xl">
    <h1 class="text-xl font-semibold mb-4">New Branch</h1>
    <form method="POST" action="{{ route('admin.branches.store') }}" class="space-y-3">@csrf
        <div class="flex gap-2">
            <label class="block text-sm font-medium flex-1">Name<input name="name" required class="w-full border border-brand-300 bg-brand-50 px-3 py-2 text-sm mt-1" /></label>
            <label class="block text-sm font-medium w-32">Code<input name="code" required maxlength="20" placeholder="JHB-02" class="w-full border border-brand-300 bg-brand-50 px-3 py-2 text-sm mt-1" /></label>
        </div>
        <label class="block text-sm font-medium">Address<input name="address" class="w-full border border-brand-300 bg-brand-50 px-3 py-2 text-sm mt-1" /></label>
        <div class="flex gap-2">
            <label class="block text-sm font-medium flex-1">Phone<input name="phone" class="w-full border border-brand-300 bg-brand-50 px-3 py-2 text-sm mt-1" /></label>
            <label class="block text-sm font-medium flex-1">Email<input name="email" type="email" class="w-full border border-brand-300 bg-brand-50 px-3 py-2 text-sm mt-1" /></label>
        </div>
        <label class="block text-sm font-medium w-40">Tax rate %<input name="tax_rate" type="number" step="0.01" min="0" max="100" value="15" class="w-full border border-brand-300 bg-brand-50 px-3 py-2 text-sm mt-1" /></label>
        <button class="px-4 py-2 bg-brand-900 text-white text-sm">Create branch</button>
    </form>
</div>
@endsection
