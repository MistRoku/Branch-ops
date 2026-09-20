<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Support\Facades\Auth;

/**
 * Admin Dashboard Controller - Web interface for admin dashboard
 * 
 * Serves the main admin dashboard view with KPIs and activity feed.
 */
class DashboardController extends Controller
{
    /**
     * Display the admin dashboard
     * 
     * @param Request $request The HTTP request
     * @return View The dashboard view
     */
    public function index(Request $request): View
    {
        $user = Auth::user();
        $branchId = null;
        
        // Determine branch scope
        if ($request->has('branch_id') && $user->isSuperAdmin()) {
            $branchId = $request->branch_id;
        } elseif (!$user->isSuperAdmin()) {
            $branchId = $user->branch_id;
        }
        
        return view('admin.dashboard', [
            'user' => $user,
            'branchId' => $branchId,
        ]);
    }
}
