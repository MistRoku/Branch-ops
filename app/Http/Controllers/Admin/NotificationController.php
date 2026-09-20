<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Notification;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;

class NotificationController extends Controller
{
    public function index(): View
    {
        $notes = Notification::where('user_id', Auth::id())->orderByDesc('created_at')->paginate(20);
        return view('admin.notifications.index', compact('notes'));
    }

    public function markAsRead(int $id): RedirectResponse
    {
        Notification::where('user_id', Auth::id())->where('id', $id)->update(['read_at' => now()]);
        return back();
    }

    public function markAllAsRead(): RedirectResponse
    {
        Notification::where('user_id', Auth::id())->whereNull('read_at')->update(['read_at' => now()]);
        return back()->with('success', 'All marked read');
    }
}
