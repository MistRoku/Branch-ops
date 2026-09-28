@extends('components.layouts.admin')
@section('title', 'Business Profile')
@section('content')
<div class="bg-brand-100 border border-brand-200 p-6 max-w-3xl">
    <div class="flex items-start justify-between mb-1">
        <h1 class="text-xl font-semibold">Business Profile</h1>
        <span class="text-xs text-brand-500">ID: {{ str_pad($profile->id, 10, '0', STR_PAD_LEFT) }}</span>
    </div>
    <p class="text-sm text-brand-600 mb-6">One organization across all applications. Changes here update everywhere.</p>
    @if($errors->any())
    <div class="border border-danger text-danger text-sm p-3 mb-4">
        <ul class="list-disc pl-5">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
    </div>
    @endif
    <form method="POST" action="{{ route('admin.business-profile.update') }}" enctype="multipart/form-data" class="space-y-6">@csrf @method('PUT')
        <div>
            <h2 class="font-semibold mb-3">Company</h2>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                <label class="block text-sm font-medium">Company<input name="company" value="{{ old('company', $profile->company) }}" class="w-full border border-brand-300 bg-brand-50 px-3 py-2 text-sm mt-1" /></label>
                <label class="block text-sm font-medium">Business name *<input name="business_name" required value="{{ old('business_name', $profile->business_name) }}" class="w-full border border-brand-300 bg-brand-50 px-3 py-2 text-sm mt-1" /></label>
                <label class="block text-sm font-medium">Business type<input name="business_type" value="{{ old('business_type', $profile->business_type) }}" placeholder="Retail, wholesale..." class="w-full border border-brand-300 bg-brand-50 px-3 py-2 text-sm mt-1" /></label>
                <label class="block text-sm font-medium">Business location<input name="location" value="{{ old('location', $profile->location) }}" class="w-full border border-brand-300 bg-brand-50 px-3 py-2 text-sm mt-1" /></label>
                <label class="block text-sm font-medium">Country<select name="country" class="w-full border border-brand-300 bg-brand-50 px-3 py-2 text-sm mt-1"><option>South Africa</option>@foreach(['Namibia','Botswana','Zimbabwe','Mozambique','Lesotho','Eswatini'] as $c)<option @selected(old('country', $profile->country) === $c)>{{ $c }}</option>@endforeach</select></label>
                <label class="block text-sm font-medium">Phone *<input name="phone" value="{{ old('phone', $profile->phone) }}" class="w-full border border-brand-300 bg-brand-50 px-3 py-2 text-sm mt-1" /></label>
                <label class="block text-sm font-medium">Email<input name="email" type="email" value="{{ old('email', $profile->email) }}" class="w-full border border-brand-300 bg-brand-50 px-3 py-2 text-sm mt-1" /></label>
                <label class="block text-sm font-medium">Website<input name="website" value="{{ old('website', $profile->website) }}" placeholder="https://..." class="w-full border border-brand-300 bg-brand-50 px-3 py-2 text-sm mt-1" /></label>
                <label class="block text-sm font-medium">Register number<input name="register_number" value="{{ old('register_number', $profile->register_number) }}" class="w-full border border-brand-300 bg-brand-50 px-3 py-2 text-sm mt-1" /></label>
                <label class="block text-sm font-medium">VAT number<input name="vat_number" value="{{ old('vat_number', $profile->vat_number) }}" class="w-full border border-brand-300 bg-brand-50 px-3 py-2 text-sm mt-1" /></label>
            </div>
        </div>
        <div>
            <h2 class="font-semibold mb-3">Logo</h2>
            @if($profile->logo_path)<img src="{{ asset('storage/' . $profile->logo_path) }}" alt="Business logo" class="h-16 mb-2 border border-brand-200" />@endif
            <input name="logo" type="file" accept="image/*" class="w-full border border-brand-300 bg-brand-50 px-3 py-2 text-sm" />
            <label class="text-sm flex gap-2 items-center mt-2"><input type="checkbox" name="use_logo_in_invoice" value="1" @checked(old('use_logo_in_invoice', $profile->use_logo_in_invoice)) /> Use this logo in invoices and receipts</label>
        </div>
        <div>
            <h2 class="font-semibold mb-3">Currency and fiscal</h2>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                <label class="block text-sm font-medium">Currency<input name="currency" value="{{ old('currency', $profile->currency) }}" maxlength="10" class="w-full border border-brand-300 bg-brand-50 px-3 py-2 text-sm mt-1" /></label>
                <label class="block text-sm font-medium">Base currency
                    <input name="base_currency" value="{{ old('base_currency', $profile->base_currency) }}" maxlength="10" @disabled($transactionsExist) class="w-full border border-brand-300 bg-brand-50 px-3 py-2 text-sm mt-1 disabled:opacity-50" />
                    @if($transactionsExist)<span class="text-xs text-brand-500">Locked: transactions are recorded in this organization.</span>@endif
                </label>
                <label class="block text-sm font-medium">Fiscal year<input name="fiscal_year" value="{{ old('fiscal_year', $profile->fiscal_year) }}" placeholder="2026/2027" class="w-full border border-brand-300 bg-brand-50 px-3 py-2 text-sm mt-1" /></label>
                <label class="block text-sm font-medium">Time zone
                    <select name="timezone" class="w-full border border-brand-300 bg-brand-50 px-3 py-2 text-sm mt-1">
                        @foreach(['Africa/Johannesburg','Africa/Windhoek','Africa/Gaborone','Africa/Harare','Africa/Maputo','UTC'] as $tz)<option @selected(old('timezone', $profile->timezone) === $tz)>{{ $tz }}</option>@endforeach
                    </select>
                </label>
                <label class="block text-sm font-medium">Language
                    <select name="language" class="w-full border border-brand-300 bg-brand-50 px-3 py-2 text-sm mt-1">
                        @foreach(['en' => 'English', 'af' => 'Afrikaans', 'zu' => 'Zulu'] as $code => $name)<option value="{{ $code }}" @selected(old('language', $profile->language) === $code)>{{ $name }}</option>@endforeach
                    </select>
                </label>
                <label class="block text-sm font-medium">Date format
                    <select name="date_format" class="w-full border border-brand-300 bg-brand-50 px-3 py-2 text-sm mt-1">
                        @foreach(['d M Y' => '01 Mar 2026', 'Y-m-d' => '2026-03-01', 'd/m/Y' => '01/03/2026', 'm/d/Y' => '03/01/2026'] as $fmt => $example)<option value="{{ $fmt }}" @selected(old('date_format', $profile->date_format) === $fmt)>{{ $example }}</option>@endforeach
                    </select>
                </label>
            </div>
        </div>
        <button class="px-4 py-2 bg-brand-900 text-white text-sm">Save business profile</button>
    </form>
</div>
@endsection
