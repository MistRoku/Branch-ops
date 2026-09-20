import './bootstrap';
import Alpine from 'alpinejs';
import { getEcho } from './echo';
import { http } from './http';
import { formatPrice } from './format';
import { posTerminal } from './pos';
import { activityFeed, connectionStatus, revenueChart } from './dashboard';

window.Alpine = Alpine;

// Re-export shared helpers for any remaining inline scripts.
window.http = http;
window.formatPrice = formatPrice;

Alpine.data('posTerminal', (options) => posTerminal(options));
Alpine.data('activityFeed', (options) => activityFeed(options));
Alpine.data('connectionStatus', () => connectionStatus());
Alpine.data('revenueChart', () => revenueChart());
Alpine.data('layoutData', () => connectionStatus());
Alpine.data('posLayout', () => connectionStatus());

Alpine.start();

// Initialise realtime lazily; absence of Reverb config is normal locally.
getEcho();
