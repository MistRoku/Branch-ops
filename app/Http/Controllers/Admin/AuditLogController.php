<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class AuditLogController extends Controller
{
    public function index(Request $request): View
    {
        if (! Auth::user()->isSuperAdmin()) {
            abort(403);
        }
        $query = AuditLog::with('user')->orderByDesc('created_at');
        if ($request->filled('action')) {
            $query->where('action', $request->action);
        }
        if ($request->filled('entity_type')) {
            $query->where('entity_type', $request->entity_type);
        }

        return view('admin.audit-logs.index', ['logs' => $query->paginate(50)->withQueryString()]);
    }

    public function show(int $id): View
    {
        if (! Auth::user()->isSuperAdmin()) {
            abort(403);
        }

        return view('admin.audit-logs.show', ['log' => AuditLog::with('user')->findOrFail($id)]);
    }
}
