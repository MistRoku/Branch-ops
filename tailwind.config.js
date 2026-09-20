/** @type {import('tailwindcss').Config} */
export default {
    content: [
        "./resources/**/*.blade.php",
        "./resources/**/*.js",
        "./resources/**/*.vue",
    ],
    theme: {
        extend: {
            fontFamily: {
                sans: ['IBM Plex Sans', 'system-ui', 'sans-serif'],
            },
            colors: {
                // Enterprise flat color palette - no pastels, no neon
                brand: {
                    50: '#f8fafc',
                    100: '#f1f5f9',
                    200: '#e2e8f0',
                    300: '#cbd5e1',
                    400: '#94a3b8',
                    500: '#64748b',
                    600: '#475569',
                    700: '#334155',
                    800: '#1e293b',
                    900: '#0f172a',
                },
                accent: {
                    500: '#2563eb',
                    600: '#1d4ed8',
                    700: '#1e40af',
                },
                success: '#16a34a',
                warning: '#ca8a04',
                danger: '#dc2626',
                info: '#0891b2',
            },
            borderRadius: {
                // No soft corners - sharp edges only
                none: '0',
                sm: '0',
                DEFAULT: '0',
                md: '0',
                lg: '0',
                xl: '0',
                '2xl': '0',
                full: '0',
            },
            boxShadow: {
                // No drop shadows
                none: 'none',
            },
        },
    },
    plugins: [],
};
