import { formatPickupCodeForApi } from './api.js';

/**
 * Parse ?code= value into 10-char combined form (4 alnum + 6 digits), or null if invalid.
 * @param {string|null|undefined} raw
 * @returns {string|null}
 */
export function normalizePickupQueryToCombined(raw) {
    if (raw == null || String(raw).trim() === '') {
        return null;
    }
    const c = String(raw).replace(/\s/g, '').replace(/-/g, '');
    const a = c
        .slice(0, 4)
        .replace(/[^A-Za-z0-9]/g, '')
        .toUpperCase()
        .slice(0, 4);
    const b = c.slice(4).replace(/\D/g, '').slice(0, 6);
    if (a.length !== 4 || b.length !== 6) {
        return null;
    }
    return a + b;
}

/**
 * Full URL to open the unlock page with the pickup code prefilled (?code=XXXX-XXXXXX).
 * @param {string} pickupCode - formatted XXXX-XXXXXX or 10-char combined
 * @returns {string} empty string if invalid or not in browser
 */
export function buildUnlockUrl(pickupCode) {
    if (!pickupCode || typeof window === 'undefined' || !window.location?.origin) {
        return '';
    }
    const trimmed = String(pickupCode).replace(/\s/g, '');
    const dashForm = trimmed.includes('-') ? trimmed : formatPickupCodeForApi(trimmed);
    if (!dashForm || dashForm.length !== 11) {
        return '';
    }
    const params = new URLSearchParams({ code: dashForm });
    return `${window.location.origin}/download?${params.toString()}`;
}
