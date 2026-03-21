import { useRef } from 'react';

/**
 * Pickup code: 4 alphanumeric + dash + 6 digits (e.g. K7P4-839217).
 * Pickup code input with v2 styling.
 */
export function PickupCodeInputV2({ value, onChange, className = '' }) {
    const raw = (value || '').replace(/\s/g, '').replace(/-/g, '').toUpperCase();
    const part1 = (raw.slice(0, 4).match(/[A-Z0-9]/g) || []).join('').slice(0, 4);
    const part2 = raw.slice(4).replace(/\D/g, '').slice(0, 6);
    const combined = part1 + part2;
    const refs = useRef([]);

    const setCombined = (s) => {
        const a = s.slice(0, 4).replace(/[^A-Za-z0-9]/g, '').toUpperCase().slice(0, 4);
        const b = s.slice(4).replace(/\D/g, '').slice(0, 6);
        onChange(a + b);
    };

    const handleChange = (i, char) => {
        const upper = (char || '').toUpperCase();
        if (i < 4 && upper && !/^[A-Z0-9]*$/.test(upper)) return;
        if (i >= 4 && char && !/^\d*$/.test(char)) return;
        if (char.length > 1) {
            const pasted = (char || '').replace(/\s/g, '').replace(/-/g, '');
            const a = pasted.replace(/[^A-Za-z0-9]/g, '').toUpperCase().slice(0, 4);
            const b = pasted.slice(4).replace(/\D/g, '').slice(0, 6);
            setCombined(a + b);
            const next = Math.min(a.length + b.length, 9);
            refs.current[next]?.focus();
            return;
        }
        const charToAdd = i < 4 ? upper : char;
        const next = combined.slice(0, i) + charToAdd + combined.slice(i + 1);
        setCombined(next);
        if (char && i < 9) refs.current[i + 1]?.focus();
    };

    const handleKeyDown = (i, e) => {
        if (e.key === 'Backspace' && !combined[i] && i > 0) {
            setCombined(combined.slice(0, i - 1) + combined.slice(i));
            refs.current[i - 1]?.focus();
        }
    };

    const handlePaste = (e) => {
        e.preventDefault();
        const text = (e.clipboardData?.getData('text') || '').replace(/\s/g, '').replace(/-/g, '');
        const a = text.replace(/[^A-Za-z0-9]/g, '').toUpperCase().slice(0, 4);
        const b = text.slice(4).replace(/\D/g, '').slice(0, 6);
        const filled = a + b;
        if (filled.length > 0) {
            onChange(filled);
            const next = Math.min(filled.length, 9);
            refs.current[next]?.focus();
        }
    };

    const chars = (part1 + part2).split('').concat(Array(10).fill('')).slice(0, 10);

    return (
        <div className={`flex flex-nowrap items-center justify-center gap-0.5 sm:gap-1 ${className}`} onPaste={handlePaste}>
            {chars.map((c, i) => (
                <span key={i} className="flex shrink-0 items-center gap-0.5 sm:gap-1">
                    {i === 4 && (
                        <span className="w-3 text-center font-mono text-sm text-slate-500 sm:w-4 sm:text-base">-</span>
                    )}
                    <input
                        ref={(el) => {
                            refs.current[i] = el;
                        }}
                        type="text"
                        inputMode={i < 4 ? 'text' : 'numeric'}
                        autoComplete="off"
                        maxLength={i === 0 ? 10 : 1}
                        value={c}
                        onChange={(e) => handleChange(i, e.target.value)}
                        onKeyDown={(e) => handleKeyDown(i, e)}
                        className="v2-charbox uppercase"
                        aria-label={i < 4 ? `Code ${i + 1}` : `Digit ${i - 3}`}
                    />
                </span>
            ))}
        </div>
    );
}
