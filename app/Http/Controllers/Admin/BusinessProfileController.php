<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\BusinessProfile;
use App\Models\Sale;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class BusinessProfileController extends Controller
{
    protected function authorizeSuperAdmin(): void
    {
        if (! Auth::user()->isSuperAdmin()) {
            abort(403, 'Only super admins can manage the business profile');
        }
    }

    public function edit(): View
    {
        $this->authorizeSuperAdmin();
        $profile = BusinessProfile::current();
        $transactionsExist = Sale::exists();

        return view('admin.business-profile.edit', compact('profile', 'transactionsExist'));
    }

    public function update(Request $request): RedirectResponse
    {
        $this->authorizeSuperAdmin();
        $profile = BusinessProfile::current();

        $validated = $request->validate([
            'company' => 'nullable|string|max:255',
            'business_name' => 'required|string|max:255',
            'business_type' => 'nullable|string|max:100',
            'location' => 'nullable|string|max:255',
            'country' => 'nullable|string|max:100',
            'phone' => 'nullable|string|max:30',
            'email' => 'nullable|email|max:255',
            'website' => 'nullable|string|max:255',
            'register_number' => 'nullable|string|max:100',
            'vat_number' => 'nullable|string|max:100',
            'logo' => 'nullable|image|max:2048',
            'use_logo_in_invoice' => 'boolean',
            'currency' => 'nullable|string|max:10',
            'base_currency' => 'nullable|string|max:10',
            'fiscal_year' => 'nullable|string|max:20',
            'timezone' => 'nullable|string|max:100',
            'language' => 'nullable|string|max:20',
            'date_format' => 'nullable|string|max:30',
        ]);

        // Base currency is locked once transactions exist.
        if (Sale::exists() && ! empty($validated['base_currency']) && $validated['base_currency'] !== $profile->base_currency) {
            return back()->withErrors(['base_currency' => 'Base currency cannot change while transactions exist in the organization.'])->withInput();
        }

        $data = $validated;
        unset($data['logo']);
        $data['use_logo_in_invoice'] = $request->boolean('use_logo_in_invoice');

        if ($request->hasFile('logo')) {
            if ($profile->logo_path) {
                Storage::disk('public')->delete($profile->logo_path);
            }
            $data['logo_path'] = $request->file('logo')->store('business-logos', 'public');
        }

        $profile->update($data);
        \Illuminate\Support\Facades\Cache::forget('business-profile-locale');

        return back()->with('success', 'Business profile updated across the organization');
    }
}
