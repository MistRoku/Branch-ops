<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Document;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class DocumentController extends Controller
{
    public function index(): View
    {
        return view('admin.documents.index', ['documents' => Document::orderByDesc('created_at')->paginate(20)]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate(['file' => 'required|file|max:10240|mimes:jpeg,jpg,png,gif,pdf,csv,xls,xlsx', 'type' => 'nullable|string']);
        $file = $request->file('file');
        $path = $file->store('documents', 'local');
        Document::create(['user_id' => Auth::id(), 'entity_type' => 'general', 'entity_id' => 0, 'file_name' => basename($path), 'original_name' => $file->getClientOriginalName(), 'mime_type' => $file->getMimeType(), 'file_size' => $file->getSize(), 'disk' => 'local', 'path' => $path, 'type' => $validated['type'] ?? 'other']);

        return back()->with('success', 'Uploaded');
    }

    public function download(int $id)
    {
        $doc = Document::findOrFail($id);

        return Storage::disk($doc->disk)->download($doc->path, $doc->original_name);
    }
}
