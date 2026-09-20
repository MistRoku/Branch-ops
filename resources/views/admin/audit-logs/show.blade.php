<x-layouts.admin title="Audit Log">
<div class="bg-white border p-6 max-w-2xl"><h1 class="text-xl font-semibold">{{ $log->action }} — {{ $log->entity_type }} #{{ $log->entity_id }}</h1>
<p class="text-sm text-gray-500">{{ $log->created_at }} · {{ $log->user?->name }} · {{ $log->ip_address }}</p>
<pre class="text-xs bg-gray-50 border p-4 mt-4 overflow-auto">{{ json_encode(['old' => $log->old_values, 'new' => $log->new_values], JSON_PRETTY_PRINT) }}</pre></div>
</x-layouts.admin>
