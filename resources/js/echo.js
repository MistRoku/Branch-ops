import Echo from 'laravel-echo';
import Pusher from 'pusher-js';

window.Pusher = window.Pusher ?? Pusher;

let instance = null;

/**
 * Lazily create the Echo client. Returns null when realtime is not
 * configured (local dev without Reverb) instead of throwing at boot.
 */
export function getEcho() {
    if (instance !== undefined && instance !== null) {
        return instance;
    }

    const key = import.meta.env.VITE_REVERB_APP_KEY;
    const host = import.meta.env.VITE_REVERB_HOST;

    if (!key || !host) {
        instance = null;
        return instance;
    }

    try {
        instance = new Echo({
            broadcaster: 'reverb',
            key,
            wsHost: host,
            wsPort: Number(import.meta.env.VITE_REVERB_PORT) || 80,
            wssPort: Number(import.meta.env.VITE_REVERB_PORT) || 443,
            forceTLS: (import.meta.env.VITE_REVERB_SCHEME ?? 'https') === 'https',
            enabledTransports: ['ws', 'wss'],
        });
    } catch {
        instance = null;
    }

    return instance;
}

/**
 * Subscribe to the dashboard channels for a branch scope. The backend
 * broadcasts SaleRecorded on `dashboard` (all branches) or
 * `dashboard.{branchId}`, and StockLowAlert on `dashboard.{branchId}`.
 */
export function dashboardChannels(branchId) {
    const echo = getEcho();
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
export function onConnectionChange({ connected, disconnected, connecting }) {
    const echo = getEcho();
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
