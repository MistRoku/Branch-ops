import { dashboardChannels, onConnectionChange } from './echo';

/**
 * Shared connection-status widget used by both layouts.
 */
export function connectionStatus() {
    return {
        connectionStatus: 'Offline',
        destroy: null,
        init() {
            this.destroy = null;
            onConnectionChange({
                connected: () => {
                    this.connectionStatus = 'Connected';
                },
                disconnected: () => {
                    this.connectionStatus = 'Offline';
                },
                connecting: () => {
                    this.connectionStatus = 'Connecting...';
                },
            }).then((stop) => {
                this.destroy = stop;
            }).catch(() => {
                this.connectionStatus = 'Offline';
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
 * Falls back to polling the activity endpoint every 30 seconds when
 * realtime is unavailable, so the feed still updates everywhere.
 */
export function activityFeed({ activities = [], branchId = null, limit = 10, feedUrl = null } = {}) {
    return {
        activities: [...activities],
        destroy: null,
        timer: null,

        async init() {
            const seen = new Set(this.activities.map((a) => a.id));
            const push = (entry) => {
                if (entry.id && seen.has(entry.id)) {
                    return;
                }
                if (entry.id) {
                    seen.add(entry.id);
                }
                this.activities.unshift({ id: Date.now() + Math.random(), time: 'Just now', ...entry });
                this.activities = this.activities.slice(0, limit);
            };

            const normalize = (item) => ({
                id: item.id ?? `feed_${item.created_at ?? ''}_${item.title ?? item.message ?? ''}`,
                type: item.type ?? 'sale',
                message: item.message ?? item.description ?? item.title ?? 'Update',
                time: item.time ?? item.created_at ?? 'Just now',
            });

            const channels = await dashboardChannels(branchId);

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

            if (feedUrl) {
                const poll = async () => {
                    try {
                        const params = new URLSearchParams({ limit: String(limit) });
                        if (branchId) {
                            params.append('branch_id', String(branchId));
                        }
                        const response = await fetch(`${feedUrl}?${params.toString()}`, {
                            headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                            credentials: 'same-origin',
                        });
                        if (!response.ok) {
                            return;
                        }
                        const payload = await response.json();
                        const list = payload?.data ?? payload ?? [];
                        [...list].reverse().forEach((item) => push(normalize(item)));
                    } catch {
                        // Polling is best-effort; realtime or the next tick covers it.
                    }
                };
                poll();
                this.timer = setInterval(poll, 30000);
                const stopChannels = this.destroy;
                this.destroy = () => {
                    stopChannels();
                    if (this.timer) {
                        clearInterval(this.timer);
                    }
                };
            }
        },

        destroyHook() {
            this.destroy?.();
            if (this.timer) {
                clearInterval(this.timer);
            }
        },
    };
}

/**
 * Revenue line chart. Chart.js loads on demand so the main bundle stays
 * lean on pages without charts. Reads labels/values from data attributes
 * so the Blade view stays free of inline JavaScript.
 */
export function revenueChart() {
    return {
        chart: null,
        async init() {
            const canvas = this.$el;
            if (!(canvas instanceof HTMLCanvasElement)) {
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

            let ChartLib;
            try {
                ChartLib = await import('chart.js');
            } catch {
                return;
            }
            const { Chart, registerables } = ChartLib;
            Chart.register(...registerables);

            if (!document.contains(canvas)) {
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
