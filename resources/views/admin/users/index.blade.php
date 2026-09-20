<x-layouts.admin title="Users">
<div class="space-y-6">
<div class="flex items-center justify-between border-b pb-4"><h1 class="text-2xl font-semibold">Users</h1><a href="{{ route('admin.users.create') }}" class="px-4 py-2 bg-gray-900 text-white text-sm">Add User</a></div>
<div class="bg-white border"><table class="min-w-full divide-y divide-gray-200">
<thead class="bg-gray-50"><tr><th class="px-6 py-3 text-left text-xs uppercase">Name</th><th class="px-6 py-3 text-left text-xs uppercase">Role</th><th class="px-6 py-3 text-left text-xs uppercase">Branch</th><th class="px-6 py-3 text-left text-xs uppercase">Status</th><th class="px-6 py-3 text-right text-xs uppercase">Actions</th></tr></thead>
<tbody class="divide-y">@forelse($users as $u)<tr><td class="px-6 py-4 text-sm">{{ $u->name }}<div class="text-xs text-gray-500">{{ $u->email }}</div></td><td class="px-6 py-4 text-sm">{{ $u->role }}</td><td class="px-6 py-4 text-sm">{{ $u->branch?->name ?? '-' }}</td><td class="px-6 py-4 text-sm">{{ $u->is_active ? 'Active' : 'Inactive' }}</td><td class="px-6 py-4 text-right text-sm"><a href="{{ route('admin.users.show', $u) }}" class="hover:underline">View</a> <a href="{{ route('admin.users.edit', $u) }}" class="hover:underline ml-2">Edit</a></td></tr>@empty<tr><td colspan="5" class="px-6 py-8 text-center text-sm text-gray-500">No users</td></tr>@endforelse</tbody>
</table>@if($users->hasPages())<div class="px-6 py-4 border-t">{{ $users->links() }}</div>@endif</div>
</div>
</x-layouts.admin>
