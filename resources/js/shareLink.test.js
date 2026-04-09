import { describe, it, expect, beforeEach, afterEach } from 'vitest';
import { normalizePickupQueryToCombined, buildUnlockUrl } from './shareLink.js';

describe('shareLink', () => {
    describe('normalizePickupQueryToCombined', () => {
        it('accepts dashed code', () => {
            expect(normalizePickupQueryToCombined('ab12-345678')).toBe('AB12345678');
        });
        it('accepts compact code', () => {
            expect(normalizePickupQueryToCombined('AB12345678')).toBe('AB12345678');
        });
        it('rejects incomplete code', () => {
            expect(normalizePickupQueryToCombined('AB12-345')).toBe(null);
        });
        it('rejects empty', () => {
            expect(normalizePickupQueryToCombined('')).toBe(null);
            expect(normalizePickupQueryToCombined(null)).toBe(null);
        });
    });

    describe('buildUnlockUrl', () => {
        const origin = 'https://example.com';

        beforeEach(() => {
            global.window = { location: { origin } };
        });

        afterEach(() => {
            delete global.window;
        });

        it('builds /download?code= with dashed param', () => {
            const u = buildUnlockUrl('K7P4-839217');
            expect(u).toBe('https://example.com/download?code=K7P4-839217');
        });

        it('accepts 10-char combined', () => {
            const u = buildUnlockUrl('K7P4839217');
            expect(u).toBe('https://example.com/download?code=K7P4-839217');
        });
    });
});
