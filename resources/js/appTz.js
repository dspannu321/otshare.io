/**
 * Format API ISO timestamps for display using APP_TIMEZONE (injected via <meta name="app-timezone">).
 */

export function getAppTimezone() {
    if (typeof document === 'undefined') {
        return 'UTC';
    }
    const c = document.querySelector('meta[name="app-timezone"]')?.getAttribute('content')?.trim();
    return c || 'UTC';
}

/**
 * @param {string|Date|null|undefined} isoOrDate
 * @param {{ dateStyle?: Intl.DateTimeFormatOptions['dateStyle'], timeStyle?: Intl.DateTimeFormatOptions['timeStyle'] }} [opts]
 */
export function formatInAppTimezone(isoOrDate, opts = {}) {
    if (isoOrDate == null || isoOrDate === '') {
        return '—';
    }
    const d = isoOrDate instanceof Date ? isoOrDate : new Date(isoOrDate);
    if (Number.isNaN(d.getTime())) {
        return '—';
    }
    const tz = getAppTimezone();
    try {
        return new Intl.DateTimeFormat(undefined, {
            timeZone: tz,
            dateStyle: opts.dateStyle ?? 'medium',
            timeStyle: opts.timeStyle ?? 'short',
        }).format(d);
    } catch {
        return `${d.toISOString().replace('T', ' ').slice(0, 19)} UTC`;
    }
}
