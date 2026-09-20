@extends('components.layouts.admin')
@section('title', 'Documents')
@section('content')
<div class="bg-white border p-6"><h1 class="text-xl font-semibold">Documents</h1>
<form method="POST" action="{{ route('admin.documents.store') }}" enctype="multipart/form-data" class="flex gap-2 mt-4 text-sm">@csrf
<input type="file" name="file" required class="border px-2 py-1"/>
<select name="type" class="border px-2 py-1"><option value="other">other</option><option value="invoice">invoice</option><option value="delivery_note">delivery_note</option><option value="spec_sheet">spec_sheet</option></select>
<button class="px-3 py-1 bg-gray-900 text-white">Upload</button></form>
<ul class="text-sm mt-4 divide-y">@forelse($documents as $d)<li class="py-2 flex justify-between"><span>{{ $d->original_name }} ({{ $d->type }})</span><a href="{{ route('admin.documents.download', $d) }}" class="underline">Download</a></li>@empty<li class="py-4 text-gray-500">No documents</li>@endforelse</ul></div>
@endsection
