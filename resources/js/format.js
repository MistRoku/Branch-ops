/**
 * Shared currency formatter (South African Rand).
 */
export function formatPrice(value) {
    const amount = Number(value) || 0;

    return new Intl.NumberFormat('en-ZA', {
        style: 'currency',
        currency: 'ZAR',
        minimumFractionDigits: 2,
    }).format(amount);
}
