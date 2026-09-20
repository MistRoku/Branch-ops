<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SearchController extends Controller
{
    public function index(Request $request): View
    {
        $grouped = null;
        if ($request->filled('q')) {
            $api = app(\App\Http\Controllers\Api\V1\SearchController::class);
            $response = $api->index(Request::create('/api/search', 'GET', $request->only('q')));
            $grouped = $response->getData(true)['data']['results'] ?? [];
        }
        return view('search.index', compact('grouped'));
    }
}
