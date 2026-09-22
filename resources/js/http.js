import axios from 'axios';

const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');

export const http = axios.create({
    withCredentials: true,
    withXSRFToken: true,
    headers: {
        'X-Requested-With': 'XMLHttpRequest',
        Accept: 'application/json',
        ...(csrfToken ? { 'X-CSRF-TOKEN': csrfToken } : {}),
    },
});

// Expired sessions surface a clear message and redirect to login at most
// once per page load. Redirecting on every 401 caused a login/POS
// ping-pong that hammered the API until the rate limiter returned 429.
let redirectedToLogin = false;

http.interceptors.response.use(
    (response) => response,
    async (error) => {
        const status = error.response?.status;
        const original = error.config;

        if (status === 429) {
            return Promise.reject(error);
        }

        if (status === 401 && !original?._handled401) {
            if (original) {
                original._handled401 = true;
            }
            const onLoginPage = window.location.pathname === '/login';
            if (!onLoginPage && !redirectedToLogin) {
                redirectedToLogin = true;
                window.location.href = '/login';
            }
            return Promise.reject(error);
        }

        if (status === 419 && !original?._retried) {
            original._retried = true;
            const fresh = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
            if (fresh) {
                original.headers['X-CSRF-TOKEN'] = fresh;
                return http(original);
            }
            window.location.reload();
        }

        return Promise.reject(error);
    },
);

/**
 * Extract a human-readable message from any API error shape.
 */
export function errorMessage(error, fallback = 'Something went wrong. Please try again.') {
    const status = error.response?.status;
    if (status === 429) {
        return 'Too many requests. Please wait a moment and try again.';
    }
    if (status === 401) {
        return 'Your session has expired. Please log in again.';
    }
    if (status === 419) {
        return 'Your session has expired. Please refresh and log in again.';
    }
    if (status === 403) {
        return 'You do not have permission to do that.';
    }
    const data = error.response?.data;
    if (typeof data?.message === 'string' && data.message) {
        return data.message;
    }
    const firstFieldError = data?.errors ? Object.values(data.errors).flat()[0] : null;
    if (typeof firstFieldError === 'string') {
        return firstFieldError;
    }
    if (error instanceof Error && error.message && !error.message.startsWith('Network')) {
        return fallback;
    }

    return fallback;
}
