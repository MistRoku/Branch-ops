import axios from 'axios';

const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');

export const http = axios.create({
    headers: {
        'X-Requested-With': 'XMLHttpRequest',
        ...(csrfToken ? { 'X-CSRF-TOKEN': csrfToken } : {}),
    },
});

// Expired sessions go back to login; expired CSRF tokens retry once
// after a refresh instead of surfacing a cryptic 419 to the user.
http.interceptors.response.use(
    (response) => response,
    async (error) => {
        const status = error.response?.status;
        const original = error.config;

        if (status === 401 && !original?._retried) {
            window.location.href = '/login';
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
