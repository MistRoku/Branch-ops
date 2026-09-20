<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;

class SettingsController extends Controller
{
    public function index(): View
    {
        return view('admin.settings.index');
    }

    public function updateProfile(Request $request): RedirectResponse
    {
        $validated = $request->validate(['name' => 'required|string|max:255', 'email' => 'required|email|unique:users,email,'.$request->user()->id]);
        $request->user()->update($validated);

        return back()->with('success', 'Profile updated');
    }

    public function changePassword(Request $request): RedirectResponse
    {
        $validated = $request->validate(['current_password' => 'required|current_password', 'password' => 'required|string|min:8|confirmed']);
        $request->user()->update(['password' => Hash::make($validated['password'])]);

        return back()->with('success', 'Password changed');
    }
}
