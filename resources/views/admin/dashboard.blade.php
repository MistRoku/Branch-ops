@extends('components.layouts.admin')

@section('title', 'Dashboard - BranchOps Platform')

@section('content')
<div class="space-y-6">
    <!-- Page Header -->
    <div class="flex items-center justify-between">
        <h1 class="text-xl font-semibold">Dashboard</h1>
        <div class="flex items-center gap-3">
            @if(isset($branches) && $branches->count())
                <form method="GET" action="{{ route('admin.dashboard') }}">
                    <select name="branch_id" onchange="this.form.submit()"
                        class="text-sm border border-brand-200 bg-white px-2 py-1">
                        <option value="">All branches</option>
                        @foreach($branches as $branch)
                            <option value="{{ $branch->id }}" @selected(($branchId ?? null) == $branch->id)>{{ $branch->name }}</option>
                        @endforeach
                    </select>
                </form>
            @endif
            <div class="text-sm text-brand-600">
                {{ now()->format('l, F d, Y') }}
            </div>
        </div>
    </div>

    <!-- KPI Cards - Horizontal scrollable region -->
    <div class="scroll-x-auto pb-2">
        <div class="flex space-x-4 min-w-max">
            <!-- Today's Sales -->
            <x-ui.card class="w-64 flex-shrink-0">
                <div class="text-xs text-brand-600 uppercase tracking-wide mb-2">Today's Sales</div>
                <div class="text-2xl font-bold mb-1" x-data="{ value: 0 }" x-init="$nextTick(() => value = {{ $kpi['today_sales'] ?? 0 }})">
                    $<span x-text="value.toLocaleString()"></span>
                </div>
                <div class="text-xs text-brand-500">
                    <span class="text-success">{{ $kpi['sales_change'] ?? '+0' }}%</span> from yesterday
                </div>
            </x-ui.card>

            <!-- Orders Count -->
            <x-ui.card class="w-64 flex-shrink-0">
                <div class="text-xs text-brand-600 uppercase tracking-wide mb-2">Orders Today</div>
                <div class="text-2xl font-bold mb-1">{{ $kpi['orders_count'] ?? 0 }}</div>
                <div class="text-xs text-brand-500">
                    <span class="text-success">{{ $kpi['orders_change'] ?? '+0' }}%</span> from yesterday
                </div>
            </x-ui.card>

            <!-- Low Stock Items -->
            <x-ui.card class="w-64 flex-shrink-0">
                <div class="text-xs text-brand-600 uppercase tracking-wide mb-2">Low Stock Items</div>
                <div class="text-2xl font-bold mb-1 text-warning">{{ $kpi['low_stock'] ?? 0 }}</div>
                <div class="text-xs text-brand-500">
                    Requires attention
                </div>
            </x-ui.card>

            <!-- Active Transfers -->
            <x-ui.card class="w-64 flex-shrink-0">
                <div class="text-xs text-brand-600 uppercase tracking-wide mb-2">Active Transfers</div>
                <div class="text-2xl font-bold mb-1">{{ $kpi['active_transfers'] ?? 0 }}</div>
                <div class="text-xs text-brand-500">
                    In transit between branches
                </div>
            </x-ui.card>

            <!-- Monthly Revenue -->
            <x-ui.card class="w-64 flex-shrink-0">
                <div class="text-xs text-brand-600 uppercase tracking-wide mb-2">Monthly Revenue</div>
                <div class="text-2xl font-bold mb-1">
                    $<span>{{ number_format($kpi['monthly_revenue'] ?? 0, 2) }}</span>
                </div>
                <div class="text-xs text-brand-500">
                    Current month
                </div>
            </x-ui.card>

            <!-- Total Products -->
            <x-ui.card class="w-64 flex-shrink-0">
                <div class="text-xs text-brand-600 uppercase tracking-wide mb-2">Total Products</div>
                <div class="text-2xl font-bold mb-1">{{ $kpi['total_products'] ?? 0 }}</div>
                <div class="text-xs text-brand-500">
                    Across all branches
                </div>
            </x-ui.card>
        </div>
    </div>

    <!-- Main Content Grid -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Revenue Chart -->
        <x-ui.card class="lg:col-span-2">
            <div class="flex items-center justify-between mb-4">
                <h2 class="text-lg font-semibold">Revenue Overview</h2>
                <span class="text-xs text-brand-500">Last 14 days</span>
            </div>
            <div class="h-64">
                <canvas id="revenueChart"></canvas>
            </div>
        </x-ui.card>

        <!-- Recent Activity Feed -->
        <x-ui.card>
            <h2 class="text-lg font-semibold mb-4">Live Activity Feed</h2>
            <div class="space-y-3" x-data="activityFeed()">
                <template x-for="activity in activities" :key="activity.id">
                    <div class="flex items-start space-x-3 py-2 border-b border-brand-100 last:border-0">
                        <div class="w-2 h-2 mt-1.5 rounded-none" 
                             :class="{
                                 'bg-success': activity.type === 'sale',
                                 'bg-warning': activity.type === 'stock',
                                 'bg-info': activity.type === 'transfer',
                                 'bg-danger': activity.type === 'alert'
                             }">
                        </div>
                        <div class="flex-1 min-w-0">
                            <p class="text-sm truncate" x-text="activity.message"></p>
                            <p class="text-xs text-brand-500" x-text="activity.time"></p>
                        </div>
                    </div>
                </template>
                
                @if(empty($activities))
                    <div class="text-center py-8 text-brand-500 text-sm">
                        No recent activity
                    </div>
                @endif
            </div>
        </x-ui.card>
    </div>

    <!-- Low Stock Alert Table -->
    <x-ui.card>
        <div class="flex items-center justify-between mb-4">
            <h2 class="text-lg font-semibold">Low Stock Alerts</h2>
            <a href="/admin/inventory" class="text-sm text-accent-600 hover:underline">View All Inventory</a>
        </div>
        
        <x-ui.table :columns="['Product', 'SKU', 'Branch', 'Current Stock', 'Min Level', 'Status']">
            @forelse($lowStockItems ?? [] as $item)
                <tr class="border-b border-brand-100 last:border-0">
                    <td class="py-2 pr-4 text-sm">{{ $item['product_name'] }}</td>
                    <td class="py-2 pr-4 text-sm">{{ $item['sku'] }}</td>
                    <td class="py-2 pr-4 text-sm">{{ $item['branch'] }}</td>
                    <td class="py-2 pr-4 text-sm font-semibold text-warning">{{ $item['current_stock'] }}</td>
                    <td class="py-2 pr-4 text-sm">{{ $item['min_level'] }}</td>
                    <td class="py-2 text-xs">
                        <span class="px-2 py-0.5 bg-danger/10 text-danger">
                            {{ $item['current_stock'] <= 0 ? 'Out of stock' : 'Low' }}
                        </span>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="6" class="text-center py-8 text-brand-500">
                        No low stock items
                    </td>
                </tr>
            @endforelse
        </x-ui.table>
    </x-ui.card>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
<script>
const revenueCtx = document.getElementById('revenueChart');
if (revenueCtx && window.Chart) {
    new Chart(revenueCtx, {
        type: 'line',
        data: {
            labels: @json($revenueLabels ?? []),
            datasets: [{
                label: 'Revenue (R)',
                data: @json($revenueValues ?? []),
                fill: true,
                tension: 0.3,
            }],
        },
        options: { responsive: true, maintainAspectRatio: false, scales: { y: { beginAtZero: true } } },
    });
}
function activityFeed() {
    return {
        activities: @json($activities ?? []),
        init() {
            // Listen for real-time events
            window.Echo?.channel('dashboard')
                ?.listen('SaleRecorded', (e) => {
                    this.activities.unshift({
                        id: Date.now(),
                        type: 'sale',
                        message: `New sale: $${e.sale.total} at ${e.branch}`,
                        time: 'Just now'
                    });
                    this.activities = this.activities.slice(0, 10);
                })
                ?.listen('StockLowAlert', (e) => {
                    this.activities.unshift({
                        id: Date.now(),
                        type: 'alert',
                        message: `Low stock: ${e.product.name} (${e.currentStock} remaining)`,
                        time: 'Just now'
                    });
                    this.activities = this.activities.slice(0, 10);
                });
        }
    }
}
</script>
@endsection
