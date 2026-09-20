<x-layouts.admin title="Search">
<div class="bg-white border p-6 max-w-2xl"><h1 class="text-xl font-semibold">Global Search</h1>
<form method="GET" action="{{ route('admin.search') }}" class="flex gap-2 mt-4">@csrf
<input name="q" value="{{ request('q') }}" minlength="2" required placeholder="Products, suppliers, POs..." class="flex-1 border px-3 py-2 text-sm"/>
<button class="px-4 py-2 bg-gray-900 text-white text-sm">Search</button></form>
@if(isset($grouped))
<div class="mt-6 space-y-4 text-sm">@foreach($grouped as $type => $items)<div><h2 class="font-semibold uppercase text-xs">{{ $type }} ({{ count($items) }})</h2><ul class="list-disc ml-5">@foreach($items as $it)<li><a class="underline" href="{{ $it['url'] ?? '#' }}">{{ $it['title'] ?? $it['name'] ?? $it['id'] }}</a> — {{ $it['subtitle'] ?? '' }}</li>@endforeach</ul></div>@endforeach</div>
@endif</div>
</x-layouts.admin>
