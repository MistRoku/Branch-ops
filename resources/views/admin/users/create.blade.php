<x-layouts.admin title="New User">
<div class="max-w-xl bg-white border p-6"><h1 class="text-xl font-semibold mb-4">New User</h1>
<form method="POST" action="{{ route('admin.users.store') }}" class="space-y-3">@csrf
<input name="name" required placeholder="Name" class="w-full border px-3 py-2 text-sm"/>
<input name="email" type="email" required placeholder="Email" class="w-full border px-3 py-2 text-sm"/>
<input name="password" type="password" required placeholder="Password (min 8)" class="w-full border px-3 py-2 text-sm"/>
<select name="role" class="w-full border px-3 py-2 text-sm"><option value="staff">staff</option><option value="branch_manager">branch_manager</option><option value="super_admin">super_admin</option></select>
<select name="branch_id" class="w-full border px-3 py-2 text-sm"><option value="">No branch</option>@foreach($branches as $b)<option value="{{ $b->id }}">{{ $b->name }}</option>@endforeach</select>
<button class="px-4 py-2 bg-gray-900 text-white text-sm">Create</button></form></div>
</x-layouts.admin>
