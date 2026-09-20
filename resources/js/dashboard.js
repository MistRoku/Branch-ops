import { Chart, registerables } from 'chart.js';
import { dashboardChannels, onConnectionChange } from './echo';

Chart.register(...registerables);

/**
 * Shared connection-status widget used by both layouts.
 */
export function connectionStatus() {
    return {
        connectionStatus: 'Offline',
        destroy: null,
        init() {
            this.destroy = onConnectionChange({
                connected: () => {
                    this.connectionStatus = 'Connected';
                },
                disconnected: () => {
                    this.connectionStatus = 'Offline';
                },
                connecting: () => {
                    this.connectionStatus = 'Connecting...';
                },
            });
        },
        destroyHook() {
            this.destroy?.();
        },
    };
}

/**
 * Dashboard live activity feed. Subscribes to both the global and the
 * branch-scoped dashboard channels so events arrive regardless of scope.
 */
export function activityFeed({ activities = [], branchId = null, limit = 10 } = {}) {
    return {
        activities: [...activities],
        destroy: null,

        init() {
            const channels = dashboardChannels(branchId);
            const push = (entry) => {
                this.activities.unshift({ id: Date.now() + Math.random(), time: 'Just now', ...entry });
                this.activities = this.activities.slice(0, limit);
            };

            const cleanups = channels.map((channel) => {
                const onSale = (e) => {
                    push({
                        type: 'sale',
                        message: `New sale: ${e?.sale?.total ?? '?'} at ${e?.branch ?? 'branch'}`,
                    });
                };
                const onAlert = (e) => {
                    push({
                        type: 'alert',
                        message: `Low stock: ${e?.product?.name ?? 'Product'} (${e?.currentStock ?? '?'} remaining)`,
                    });
                };
                channel.listen('SaleRecorded', onSale);
                channel.listen('StockLowAlert', onAlert);

                return () => {
                    channel.stopListening('SaleRecorded', onSale);
                    channel.stopListening('StockLowAlert', onAlert);
                };
            });

            this.destroy = () => cleanups.forEach((fn) => fn());
        },

        destroyHook() {
            this.destroy?.();
        },
    };
}

/**
 * Revenue line chart. Reads labels/values from data attributes so the
 * Blade view stays free of inline JavaScript.
 */
export function revenueChart() {
    return {
        chart: null,
        init() {
            const canvas = this.$el;
            if (!(canvas instanceof HTMLCanvasElement) || typeof Chart === 'undefined') {
                return;
            }

            let labels = [];
            let values = [];
            try {
                labels = JSON.parse(canvas.dataset.labels || '[]');
                values = JSON.parse(canvas.dataset.values || '[]');
            } catch {
                return;
            }

            this.chart = new Chart(canvas, {
                type: 'line',
                data: {
                    labels,
                    datasets: [
                        {
                            label: 'Revenue (R)',
                            data: values,
                            fill: true,
                            tension: 0.3,
                        },
                    ],
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    scales: { y: { beginAtZero: true } },
                },
            });
        },
        destroyHook() {
            this.chart?.destroy();
        },
    };
}
