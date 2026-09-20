<x-layouts.admin title="User">
<div class="bg-white border p-6 max-w-xl"><h1 class="text-xl font-semibold">{{ $user->name }}</h1><p class="text-sm text-gray-500">{{ $user->email }} · {{ $user->role }} · {{ $user->branch?->name ?? 'No branch' }} · {{ $user->is_active ? 'Active' : 'Inactive' }}</p>
@if($user->isLocked())<p class="text-sm text-red-600 mt-2">Locked until {{ $user->locked_until }}</p>@endif
<a href="{{ route('admin.users.edit', $user) }}" class="text-sm underline mt-4 inline-block">Edit</a></div>
</x-layouts.admin>
