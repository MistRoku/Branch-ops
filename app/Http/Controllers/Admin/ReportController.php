<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Payout;
use App\Models\Sale;
use App\Models\SaleRefund;
use App\Models\StockLevel;
use App\Models\StockMovement;
use App\Models\WasteLog;
use Dompdf\Dompdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReportController extends Controller
{
    public function index(Request $request): View
    {
        $branches = Auth::user()->isSuperAdmin()
            ? \App\Models\Branch::orderBy('name')->get(['id', 'name'])
            : collect();

        return view('admin.reports.index', compact('branches'));
    }

    public function export(Request $request, string $type): Response
    {
        $allowed = ['sales', 'refunds', 'voids', 'inventory', 'movements', 'waste', 'payouts'];
        abort_unless(in_array($type, $allowed, true), 404, 'Unknown report type');

        $format = $request->get('format', 'csv');
        abort_unless(in_array($format, ['csv', 'pdf'], true), 404, 'Unknown format');

        [$headers, $rows] = $this->buildReport($type, $request);
        $filename = "{$type}-report-".now()->format('Ymd-His');

        if ($format === 'pdf') {
            return $this->pdf($filename, $type, $headers, $rows);
        }

        return response()->streamDownload(function () use ($headers, $rows) {
            $out = fopen('php://output', 'w');
            fputcsv($out, $headers);
            foreach ($rows as $r) {
                fputcsv($out, $r);
            }
            fclose($out);
        }, "{$filename}.csv", ['Content-Type' => 'text/csv']);
    }

    /**
     * @return array{0: string[], 1: array<int, array>}
     */
    protected function buildReport(string $type, Request $request): array
    {
        $user = Auth::user();
        $branchId = $request->get('branch_id') ?: ($user->isSuperAdmin() ? null : $user->branch_id);
        $from = $request->get('from');
        $to = $request->get('to');

        $dateScope = function ($query) use ($from, $to) {
            if ($from) {
                $query->whereDate('created_at', '>=', $from);
            }
            if ($to) {
                $query->whereDate('created_at', '<=', $to);
            }

            return $query;
        };

        return match ($type) {
            'sales' => [
                ['Invoice', 'Branch', 'Cashier', 'Payment', 'Subtotal', 'Discount', 'Tax', 'Tip', 'Total', 'Status', 'Date'],
                $dateScope(Sale::with(['branch', 'user'])->orderByDesc('created_at'))
                    ->when($branchId, fn ($q) => $q->where('branch_id', $branchId))
                    ->limit(5000)->get()
                    ->map(fn ($s) => [$s->invoice_number, $s->branch?->name, $s->user?->name, $s->payment_method, $s->subtotal, $s->discount_amount, $s->tax_amount, $s->tip_amount, $s->total_amount, $s->status, $s->created_at?->format('Y-m-d H:i')])->all(),
            ],
            'refunds' => [
                ['Sale', 'Branch', 'Type', 'Amount', 'Processed by', 'Reason', 'Date'],
                $dateScope(SaleRefund::with(['sale', 'branch', 'user'])->orderByDesc('created_at'))
                    ->when($branchId, fn ($q) => $q->where('branch_id', $branchId))
                    ->limit(5000)->get()
                    ->map(fn ($r) => [$r->sale?->invoice_number, $r->branch?->name, $r->refund_type, $r->refund_amount, $r->user?->name, $r->refund_reason, $r->created_at?->format('Y-m-d H:i')])->all(),
            ],
            'voids' => [
                ['Invoice', 'Branch', 'Cashier', 'Total', 'Voided by', 'Reason', 'Voided at'],
                $dateScope(Sale::with(['branch', 'user', 'voidedBy'])->where('status', 'void')->orderByDesc('created_at'))
                    ->when($branchId, fn ($q) => $q->where('branch_id', $branchId))
                    ->limit(5000)->get()
                    ->map(fn ($s) => [$s->invoice_number, $s->branch?->name, $s->user?->name, $s->total_amount, $s->voidedBy?->name, $s->void_reason, $s->voided_at?->format('Y-m-d H:i')])->all(),
            ],
            'inventory' => [
                ['SKU', 'Product', 'UOM', 'Branch', 'Quantity', 'Reorder level', 'Low stock'],
                StockLevel::with(['product', 'branch'])
                    ->when($branchId, fn ($q) => $q->where('branch_id', $branchId))
                    ->orderBy('quantity')->limit(5000)->get()
                    ->map(fn ($s) => [$s->product?->sku, $s->product?->name, $s->product?->unit_of_measure ?? 'piece', $s->branch?->name, $s->quantity, $s->product?->reorder_level, $s->quantity <= ($s->product?->reorder_level ?? 0) ? 'Yes' : 'No'])->all(),
            ],
            'movements' => [
                ['Date', 'Product', 'Branch', 'Change', 'Type', 'Reference', 'Notes'],
                $dateScope(StockMovement::with(['product', 'branch'])->orderByDesc('created_at'))
                    ->when($branchId, fn ($q) => $q->where('branch_id', $branchId))
                    ->limit(5000)->get()
                    ->map(fn ($m) => [$m->created_at?->format('Y-m-d H:i'), $m->product?->name, $m->branch?->name, $m->quantity_change, $m->type, $m->reference_type.' #'.$m->reference_id, $m->notes])->all(),
            ],
            'waste' => [
                ['Date', 'Product', 'Branch', 'Quantity', 'Reason', 'Logged by'],
                WasteLog::with(['product', 'branch', 'user'])->orderByDesc('logged_at')
                    ->when($branchId, fn ($q) => $q->where('branch_id', $branchId))
                    ->when($from, fn ($q) => $q->whereDate('logged_at', '>=', $from))
                    ->when($to, fn ($q) => $q->whereDate('logged_at', '<=', $to))
                    ->limit(5000)->get()
                    ->map(fn ($w) => [$w->logged_at?->format('Y-m-d H:i'), $w->product?->name, $w->branch?->name, $w->quantity.' '.$w->uom, $w->reason, $w->user?->name])->all(),
            ],
            'payouts' => [
                ['Date', 'Branch', 'Payee', 'Amount', 'Reason', 'Logged by', 'Status'],
                Payout::with(['branch', 'user'])->orderByDesc('created_at')
                    ->when($branchId, fn ($q) => $q->where('branch_id', $branchId))
                    ->when($from, fn ($q) => $q->whereDate('created_at', '>=', $from))
                    ->when($to, fn ($q) => $q->whereDate('created_at', '<=', $to))
                    ->limit(5000)->get()
                    ->map(fn ($p) => [$p->created_at?->format('Y-m-d H:i'), $p->branch?->name, $p->payee, $p->amount, $p->reason, $p->user?->name, $p->status])->all(),
            ],
        };
    }

    protected function pdf(string $filename, string $type, array $headers, array $rows): Response
    {
        $title = ucfirst($type).' report — '.now()->format('Y-m-d H:i');
        $html = '<html><head><style>body{font-family:sans-serif;font-size:11px;color:#0f172a}h1{font-size:16px}table{width:100%;border-collapse:collapse}th,td{border:1px solid #cbd5e1;padding:4px;text-align:left}th{background:#f1f5f9}</style></head><body>';
        $html .= '<h1>'.e($title).'</h1><table><thead><tr>';
        foreach ($headers as $h) {
            $html .= '<th>'.e($h).'</th>';
        }
        $html .= '</tr></thead><tbody>';
        foreach (array_slice($rows, 0, 1000) as $r) {
            $html .= '<tr>';
            foreach ($r as $c) {
                $html .= '<td>'.e((string) $c).'</td>';
            }
            $html .= '</tr>';
        }
        $html .= '</tbody></table></body></html>';

        $pdf = new Dompdf;
        $pdf->loadHtml($html);
        $pdf->setPaper('A4', 'landscape');
        $pdf->render();

        return new Response($pdf->output(), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => "attachment; filename=\"{$filename}.pdf\"",
        ]);
    }
}
