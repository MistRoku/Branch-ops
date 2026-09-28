import Echo from 'laravel-echo';

let instance = null;
let pending = null;

/**
 * Lazily create the Echo client. pusher-js loads on demand so pages that
 * never use realtime skip the extra bundle weight. Returns null when
 * realtime is not configured (local dev without Reverb) instead of
 * throwing at boot.
 */
export function getEcho() {
    if (instance !== undefined && instance !== null) {
        return Promise.resolve(instance);
    }
    if (pending) {
        return pending;
    }

    const key = import.meta.env.VITE_REVERB_APP_KEY;
    const host = import.meta.env.VITE_REVERB_HOST;

    if (!key || !host) {
        instance = null;
        return Promise.resolve(instance);
    }

    pending = import('pusher-js')
        .then((mod) => {
            window.Pusher = window.Pusher ?? mod.default ?? mod;
            instance = new Echo({
                broadcaster: 'reverb',
                key,
                wsHost: host,
                wsPort: Number(import.meta.env.VITE_REVERB_PORT) || 80,
                wssPort: Number(import.meta.env.VITE_REVERB_PORT) || 443,
                forceTLS: (import.meta.env.VITE_REVERB_SCHEME ?? 'https') === 'https',
                enabledTransports: ['ws', 'wss'],
            });

            return instance;
        })
        .catch(() => {
            instance = null;

            return instance;
        })
        .finally(() => {
            pending = null;
        });

    return pending;
}

/**
 * Subscribe to the dashboard channels for a branch scope. The backend
 * broadcasts SaleRecorded on `dashboard` (all branches) or
 * `dashboard.{branchId}`, and StockLowAlert on `dashboard.{branchId}`.
 */
export async function dashboardChannels(branchId) {
    const echo = await getEcho();
    if (!echo) {
        return [];
    }

    const channels = [echo.channel('dashboard')];
    if (branchId) {
        channels.push(echo.channel(`dashboard.${branchId}`));
    }

    return channels;
}

/**
 * Bind connection-status callbacks. No-op when Echo is unavailable.
 */
export async function onConnectionChange({ connected, disconnected, connecting }) {
    const echo = await getEcho();
    const connection = echo?.connector?.pusher?.connection;
    if (!connection) {
        disconnected?.();
        return () => {};
    }

    if (connected) {
        connection.bind('connected', connected);
    }
    if (disconnected) {
        connection.bind('disconnected', disconnected);
    }
    if (connecting) {
        connection.bind('connecting', connecting);
    }

    return () => {
        if (connected) {
            connection.unbind('connected', connected);
        }
        if (disconnected) {
            connection.unbind('disconnected', disconnected);
        }
        if (connecting) {
            connection.unbind('connecting', connecting);
        }
    };
}
