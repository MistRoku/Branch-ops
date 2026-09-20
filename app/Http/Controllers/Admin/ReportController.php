<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Sale;
use App\Models\StockLevel;
use App\Models\StockMovement;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReportController extends Controller
{
    public function index(): View { return view('admin.reports.index'); }

    public function export(string $type): StreamedResponse
    {
        $rows = match ($type) {
            'sales' => Sale::with('branch')->orderByDesc('created_at')->limit(1000)->get()->map(fn($s) => [$s->id, $s->branch?->name, $s->total, $s->status, $s->created_at]),
            'inventory' => StockLevel::with(['product', 'branch'])->get()->map(fn($s) => [$s->product?->sku, $s->product?->name, $s->branch?->name, $s->quantity]),
            default => StockMovement::with(['product', 'branch'])->orderByDesc('created_at')->limit(1000)->get()->map(fn($m) => [$m->created_at, $m->product?->name, $m->branch?->name, $m->quantity_change]),
        };
        return response()->streamDownload(function () use ($rows) {
            $out = fopen('php://output', 'w');
            foreach ($rows as $r) {
                fputcsv($out, $r->toArray());
            }
            fclose($out);
        }, "{$type}-report.csv");
    }
}
