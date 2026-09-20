@extends('components.layouts.admin')

@section('title', 'Dashboard - BranchOps Platform')

@section('content')
<div class="space-y-6">
    <!-- Page Header -->
    <div class="flex items-center justify-between">
        <h1 class="text-xl font-semibold">Dashboard</h1>
        <div class="text-sm text-brand-600">
            {{ now()->format('l, F d, Y') }}
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
        <!-- Revenue Chart (placeholder) -->
        <x-ui.card class="lg:col-span-2">
            <h2 class="text-lg font-semibold mb-4">Revenue Overview</h2>
            <div class="h-64 bg-brand-50 border border-brand-200 flex items-center justify-center">
                <div class="text-center text-brand-500">
                    <svg class="w-12 h-12 mx-auto mb-2 opacity-50" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="square" stroke-linejoin="miter" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                    </svg>
                    <p class="text-sm">Chart placeholder - integrate with Chart.js or similar</p>
                </div>
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
                [
                    "{{ $item['product_name'] }}",
                    "{{ $item['sku'] }}",
                    "{{ $item['branch'] }}",
                    "{{ $item['current_stock'] }}",
                    "{{ $item['min_level'] }}",
                    null // Will render badge below
                ]
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

<script>
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
