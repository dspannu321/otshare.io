import { useRef } from 'react';

const boxClass = 'otshare-charbox';

/**
 * Pickup code: 4 alphanumeric + dash + 6 digits (e.g. K7P4-839217).
 * All 10 boxes + dash on one horizontal line. Paste full code in any box to fill all.
 */
export function PickupCodeInput({ value, onChange, className = '' }) {
  const raw = (value || '').replace(/\s/g, '').replace(/-/g, '').toUpperCase();
  const part1 = (raw.slice(0, 4).match(/[A-Z0-9]/g) || []).join('').slice(0, 4);
  const part2 = raw.slice(4).replace(/\D/g, '').slice(0, 6);
  const combined = part1 + part2;
  const refs = useRef([]);

  const setCombined = (s) => {
    const a = s.slice(0, 4).replace(/[^A-Z0-9]/g, '');
    const b = s.slice(4).replace(/\D/g, '').slice(0, 6);
    onChange(a + b);
  };

  const handleChange = (i, char) => {
    const upper = (char || '').toUpperCase();
    if (i < 4 && upper && !/^[A-Z0-9]*$/.test(upper)) return;
    if (i >= 4 && char && !/^\d*$/.test(char)) return;
    if (char.length > 1) {
      const pasted = upper.replace(/[^A-Z0-9]/g, '').slice(0, 4) + char.replace(/\D/g, '').slice(0, 6);
      const a = pasted.slice(0, 4);
      const b = pasted.slice(4, 10);
      setCombined(a + b);
      const next = Math.min(a.length + b.length, 9);
      refs.current[next]?.focus();
      return;
    }
    const next = combined.slice(0, i) + char + combined.slice(i + 1);
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
    const text = (e.clipboardData?.getData('text') || '').replace(/\s/g, '').replace(/-/g, '').toUpperCase();
    const a = text.replace(/[^A-Z0-9]/g, '').slice(0, 4);
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
    <div
      className={`flex flex-nowrap items-center justify-center gap-0.5 sm:gap-1 ${className}`}
      onPaste={handlePaste}
    >
      {chars.map((c, i) => (
        <span key={i} className="flex items-center gap-0.5 sm:gap-1 shrink-0">
          {i === 4 && <span className="text-zinc-500 font-mono text-sm sm:text-base w-3 sm:w-4 text-center">-</span>}
          <input
            ref={el => { refs.current[i] = el; }}
            type="text"
            inputMode={i < 4 ? 'text' : 'numeric'}
            autoComplete="off"
            maxLength={i === 0 ? 10 : 1}
            value={c}
            onChange={e => handleChange(i, e.target.value)}
            onKeyDown={e => handleKeyDown(i, e)}
            className={`${boxClass} uppercase`}
            aria-label={i < 4 ? `Code ${i + 1}` : `Digit ${i - 3}`}
          />
        </span>
      ))}
    </div>
  );
}
