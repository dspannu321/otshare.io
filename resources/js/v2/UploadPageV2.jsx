import { useState, useRef, useEffect } from 'react';
import flatpickr from 'flatpickr';
import { createShareWithFile, createShareWithText } from '../api';
import { formatInLocalTime, getLocalTimezoneLabel } from '../appTz';

const MAX_FILE_SIZE = 100 * 1024 * 1024;
const MAX_EXPIRY_MINUTES = 7 * 24 * 60;

function formatFileSize(bytes) {
    if (bytes < 1024) return `${bytes} B`;
    if (bytes < 1024 * 1024) return `${(bytes / 1024).toFixed(1)} KB`;
    return `${(bytes / (1024 * 1024)).toFixed(1)} MB`;
}

export function UploadPageV2({ apiBase }) {
    const [uploadMode, setUploadMode] = useState('file');
    const [textBody, setTextBody] = useState('');
    const [file, setFile] = useState(null);
    const [expiryAt, setExpiryAt] = useState(null);
    const [maxDownloads, setMaxDownloads] = useState(1);
    const [step, setStep] = useState('select');
    const [doneResult, setDoneResult] = useState(null);
    /** @type {'file' | 'text' | null} */
    const [doneKind, setDoneKind] = useState(null);
    const [error, setError] = useState('');
    const [copied, setCopied] = useState(false);
    const [isDragging, setIsDragging] = useState(false);
    const fileInputRef = useRef(null);
    const expiryInputRef = useRef(null);
    const flatpickrRef = useRef(null);

    useEffect(() => {
        if (step !== 'select') {
            if (flatpickrRef.current) {
                flatpickrRef.current.destroy();
                flatpickrRef.current = null;
            }
            return;
        }

        const el = expiryInputRef.current;
        if (!el || flatpickrRef.current) return;

        const fp = flatpickr(el, {
            enableTime: true,
            dateFormat: 'Y-m-d H:i',
            time_24hr: true,
            minuteIncrement: 1,
            allowInput: false,
            clickOpens: true,
            disableMobile: true,
            defaultDate: expiryAt ?? null,
            onChange: (dates) => setExpiryAt(dates[0] ?? null),
            onOpen: (_dates, _str, instance) => {
                instance.set('minDate', new Date(Date.now() + 60 * 1000));
                instance.set('maxDate', new Date(Date.now() + MAX_EXPIRY_MINUTES * 60 * 1000));
            },
            onReady: (_dates, _str, instance) => {
                instance.calendarContainer.classList.add('v2-flatpickr');
            },
        });
        flatpickrRef.current = fp;

        return () => {
            if (flatpickrRef.current === fp) {
                fp.destroy();
                flatpickrRef.current = null;
            }
        };
    }, [step]);

    const handleFileChange = (e) => {
        const f = e.target.files?.[0];
        if (!f) return;
        if (f.size > MAX_FILE_SIZE) {
            setError('File is too large (max 100MB).');
            return;
        }
        setError('');
        setFile(f);
    };

    const handleDrop = (e) => {
        e.preventDefault();
        setIsDragging(false);
        const f = e.dataTransfer?.files?.[0];
        if (!f) return;
        if (f.size > MAX_FILE_SIZE) {
            setError('File is too large (max 100MB).');
            return;
        }
        setError('');
        setFile(f);
    };

    const handleDragOver = (e) => {
        e.preventDefault();
        setIsDragging(true);
    };

    const handleDragLeave = () => setIsDragging(false);

    const copyCode = async () => {
        if (!doneResult?.pickup_code) return;
        try {
            await navigator.clipboard.writeText(doneResult.pickup_code);
            setCopied(true);
            setTimeout(() => setCopied(false), 2000);
        } catch (_) {}
    };

    const submitHandler = async (e) => {
        e.preventDefault();
        if (uploadMode === 'file' && !file) {
            setError('Choose a file to upload.');
            return;
        }
        if (uploadMode === 'text') {
            const t = textBody.replace(/\r\n/g, '\n');
            if (!t.trim()) {
                setError('Enter some text to share.');
                return;
            }
            const bytes = new TextEncoder().encode(t).length;
            if (bytes > MAX_FILE_SIZE) {
                setError('Text is too large (max 100MB).');
                return;
            }
        }
        if (!expiryAt) {
            setError('Choose when this share should expire.');
            return;
        }
        setError('');
        setStep('uploading');
        try {
            const expiresInMinutes = Math.ceil((expiryAt.getTime() - Date.now()) / 60000);
            if (expiresInMinutes < 1 || expiresInMinutes > MAX_EXPIRY_MINUTES) {
                throw new Error('Expiry must be between 1 minute and 7 days from now.');
            }

            const expiresAtIso = expiryAt.toISOString();
            const data =
                uploadMode === 'text'
                    ? await createShareWithText(apiBase, {
                          text: textBody.replace(/\r\n/g, '\n'),
                          expiresAtIso,
                          maxDownloads,
                      })
                    : await createShareWithFile(apiBase, {
                          file,
                          expiresAtIso,
                          maxDownloads,
                      });
            setDoneResult({
                pickup_code: data.pickup_code,
                expires_at: data.expires_at,
            });
            setDoneKind(uploadMode === 'text' ? 'text' : 'file');
            setStep('done');
        } catch (err) {
            const msg = err.message || '';
            if (msg.includes('Failed to fetch') || msg.includes('NetworkError') || msg.includes('Load failed')) {
                setError('Network error. Check your connection and try again.');
            } else if (msg.includes('413')) {
                setError('Upload rejected by server size limit. Try a smaller file.');
            } else if (msg.includes('crypto_meta') || msg.includes('ciphertext') || msg.includes('validation')) {
                setError(msg);
            } else if (msg) {
                setError(msg);
            } else {
                setError('Something went wrong. Please try again.');
            }
            setStep('select');
        }
    };

    const reset = () => {
        setStep('select');
        setFile(null);
        setTextBody('');
        setMaxDownloads(1);
        setDoneResult(null);
        setDoneKind(null);
        setError('');
        setExpiryAt(null);
        flatpickrRef.current?.clear();
    };

    const switchMode = (mode) => {
        setUploadMode(mode);
        setError('');
        if (mode === 'file') {
            setTextBody('');
        } else {
            setFile(null);
        }
    };

    if (step === 'uploading') {
        const isText = uploadMode === 'text';
        return (
            <div className="v2-card p-6 sm:p-10">
                <div className="flex flex-col items-center justify-center py-10">
                    <div className="relative mb-6 h-14 w-14">
                        <div className="absolute inset-0 rounded-2xl bg-sky-400/20 blur-xl" />
                        <div className="relative flex h-14 w-14 items-center justify-center rounded-2xl border border-sky-400/30 bg-sky-400/10">
                            <svg className="h-7 w-7 animate-spin text-sky-300" fill="none" viewBox="0 0 24 24">
                                <circle className="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" strokeWidth="4" />
                                <path
                                    className="opacity-90"
                                    fill="currentColor"
                                    d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"
                                />
                            </svg>
                        </div>
                    </div>
                    <p className="text-center text-base font-semibold text-white">{isText ? 'Creating share' : 'Uploading'}</p>
                    <p className="mt-2 text-center text-sm text-slate-500">
                        {isText ? 'Securing your text — hang tight.' : 'Sending your file — hang tight.'}
                    </p>
                </div>
            </div>
        );
    }

    if (step === 'done') {
        return (
            <div className="v2-card p-6 sm:p-10">
                <div className="mb-8 flex gap-4">
                    <div className="flex h-14 w-14 shrink-0 items-center justify-center rounded-2xl border border-emerald-400/35 bg-emerald-400/10">
                        <svg className="h-7 w-7 text-emerald-300" fill="none" viewBox="0 0 24 24" stroke="currentColor" strokeWidth={2}>
                            <path strokeLinecap="round" strokeLinejoin="round" d="M5 13l4 4L19 7" />
                        </svg>
                    </div>
                    <div>
                        <h2 className="text-xl font-bold tracking-tight text-white">You&apos;re all set</h2>
                        <p className="mt-1 text-sm leading-relaxed text-slate-400">
                            Send the pickup code to the recipient. They can unlock up to {maxDownloads} time
                            {maxDownloads === 1 ? '' : 's'} before the link expires.
                        </p>
                    </div>
                </div>

                <div className="rounded-2xl border border-white/[0.08] bg-black/25 p-5 sm:p-6">
                    <p className="text-[11px] font-semibold uppercase tracking-[0.2em] text-slate-500">Pickup code</p>
                    <div className="mt-3 flex flex-col gap-3 sm:flex-row sm:items-center">
                        <code className="font-mono text-xl font-medium tracking-[0.2em] text-sky-300 sm:flex-1 sm:text-2xl">{doneResult.pickup_code}</code>
                        <button
                            type="button"
                            onClick={copyCode}
                            className="inline-flex shrink-0 items-center justify-center gap-2 rounded-xl border border-white/10 bg-white/[0.06] px-4 py-3 text-sm font-medium text-slate-200 transition hover:border-sky-400/30 hover:bg-sky-400/10 hover:text-white"
                        >
                            {copied ? (
                                <>
                                    <svg className="h-4 w-4 text-emerald-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M5 13l4 4L19 7" />
                                    </svg>
                                    Copied
                                </>
                            ) : (
                                <>
                                    <svg className="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path
                                            strokeLinecap="round"
                                            strokeLinejoin="round"
                                            strokeWidth={2}
                                            d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"
                                        />
                                    </svg>
                                    Copy
                                </>
                            )}
                        </button>
                    </div>
                </div>

                {doneResult.expires_at && (
                    <p className="mt-4 text-center text-sm text-slate-500">
                        Share expires{' '}
                        <time className="font-mono text-slate-400" dateTime={doneResult.expires_at}>
                            {formatInLocalTime(doneResult.expires_at, { dateStyle: 'medium', timeStyle: 'short' })}
                        </time>
                        <span className="block text-xs text-slate-600">({getLocalTimezoneLabel()})</span>
                    </p>
                )}

                <button type="button" onClick={reset} className="v2-btn-ghost mt-6 w-full border border-white/10 py-3 text-slate-300 hover:text-white">
                    {doneKind === 'text' ? 'Share something else' : 'Share another file'}
                </button>
            </div>
        );
    }

    return (
            <div className="v2-card p-6 sm:p-10">
            <h2 className="mb-6 text-lg font-bold text-white">Create a share</h2>

            <div className="mb-8 flex rounded-2xl border border-white/[0.08] bg-black/20 p-1">
                <button
                    type="button"
                    onClick={() => switchMode('file')}
                    className={`flex-1 rounded-xl px-3 py-2.5 text-sm font-semibold transition ${
                        uploadMode === 'file'
                            ? 'bg-white/[0.1] text-white shadow-sm ring-1 ring-white/10'
                            : 'text-slate-500 hover:text-slate-300'
                    }`}
                >
                    File
                </button>
                <button
                    type="button"
                    onClick={() => switchMode('text')}
                    className={`flex-1 rounded-xl px-3 py-2.5 text-sm font-semibold transition ${
                        uploadMode === 'text'
                            ? 'bg-white/[0.1] text-white shadow-sm ring-1 ring-white/10'
                            : 'text-slate-500 hover:text-slate-300'
                    }`}
                >
                    Text
                </button>
            </div>

            <form onSubmit={submitHandler} className="space-y-6">
                <div>
                    <label className="mb-2 block text-xs font-semibold uppercase tracking-wider text-slate-500">
                        {uploadMode === 'text' ? 'Text' : 'File'}
                    </label>
                    {uploadMode === 'text' ? (
                        <textarea
                            value={textBody}
                            onChange={(e) => {
                                setTextBody(e.target.value);
                                setError('');
                            }}
                            placeholder="Paste or type what you want to share…"
                            rows={10}
                            className="v2-input min-h-[200px] resize-y font-mono text-sm leading-relaxed"
                            spellCheck="true"
                        />
                    ) : !file ? (
                        <div
                            role="button"
                            tabIndex={0}
                            onClick={() => fileInputRef.current?.click()}
                            onKeyDown={(e) => {
                                if (e.key === 'Enter' || e.key === ' ') {
                                    e.preventDefault();
                                    fileInputRef.current?.click();
                                }
                            }}
                            onDrop={handleDrop}
                            onDragOver={handleDragOver}
                            onDragLeave={handleDragLeave}
                            className={`v2-dropzone flex cursor-pointer flex-col items-center justify-center px-5 py-10 sm:px-6 sm:py-12 outline-none focus-visible:ring-2 focus-visible:ring-sky-400/40 ${
                                isDragging ? 'v2-dropzone-active' : ''
                            }`}
                        >
                            <input ref={fileInputRef} type="file" onChange={handleFileChange} className="hidden" />
                            <div className="mb-4 flex h-14 w-14 items-center justify-center rounded-2xl bg-sky-400/10 ring-1 ring-sky-400/20">
                                <svg className="h-7 w-7 text-sky-300/90" fill="none" viewBox="0 0 24 24" stroke="currentColor" strokeWidth={1.5}>
                                    <path
                                        strokeLinecap="round"
                                        strokeLinejoin="round"
                                        d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"
                                    />
                                </svg>
                            </div>
                            <p className="text-center font-semibold text-slate-200">Drag &amp; drop or click to browse</p>
                            <p className="mt-1 text-center text-sm text-slate-500">Any type · up to 100MB</p>
                        </div>
                    ) : (
                        <div className="flex flex-col gap-3 rounded-2xl border border-sky-400/25 bg-sky-400/[0.07] p-4 ring-1 ring-sky-400/10 sm:flex-row sm:items-center sm:gap-4">
                            <div className="flex h-12 w-12 shrink-0 items-center justify-center rounded-xl bg-sky-400/15">
                                <svg className="h-6 w-6 text-sky-300" fill="none" viewBox="0 0 24 24" stroke="currentColor" strokeWidth={1.5}>
                                    <path
                                        strokeLinecap="round"
                                        strokeLinejoin="round"
                                        d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"
                                    />
                                </svg>
                            </div>
                            <div className="min-w-0 flex-1">
                                <p className="text-[11px] font-semibold uppercase tracking-wider text-sky-300/80">Selected</p>
                                <p className="truncate font-medium text-white">{file.name}</p>
                                <p className="text-sm text-slate-500">{formatFileSize(file.size)}</p>
                            </div>
                            <button
                                type="button"
                                onClick={(e) => {
                                    e.preventDefault();
                                    fileInputRef.current?.click();
                                }}
                                className="w-full rounded-lg border border-sky-400/30 px-3 py-2 text-sm font-semibold text-sky-300 hover:border-sky-300/50 hover:text-sky-200 sm:w-auto sm:border-0 sm:p-0"
                            >
                                Change
                            </button>
                            <input ref={fileInputRef} type="file" onChange={handleFileChange} className="hidden" />
                        </div>
                    )}
                    {uploadMode === 'text' && (
                        <p className="mt-2 text-xs text-slate-600">Plain text · up to 100MB (UTF-8)</p>
                    )}
                </div>

                <div className="grid gap-4 sm:grid-cols-2">
                    <div className="sm:col-span-2">
                        <label htmlFor="v2-expiry" className="mb-2 block text-xs font-semibold uppercase tracking-wider text-slate-500">
                            Expires at
                        </label>
                        <input
                            ref={expiryInputRef}
                            id="v2-expiry"
                            type="text"
                            readOnly
                            placeholder="Select date & time"
                            className="v2-input v2-expiry-input cursor-pointer"
                            autoComplete="off"
                        />
                        <p className="mt-2 text-xs text-slate-600">Between 1 minute and 7 days from now.</p>
                    </div>
                    <div className="sm:col-span-2">
                        <p className="mb-2 text-xs font-semibold uppercase tracking-wider text-slate-500">Downloads allowed</p>
                        <div className="flex gap-2">
                            {[1, 2, 3, 4, 5].map((n) => (
                                <button
                                    key={n}
                                    type="button"
                                    onClick={() => setMaxDownloads(n)}
                                    className={`v2-dl-btn ${maxDownloads === n ? 'v2-dl-btn-active' : ''}`}
                                    aria-pressed={maxDownloads === n}
                                >
                                    {n}
                                </button>
                            ))}
                        </div>
                    </div>
                </div>

                {error && (
                    <div className="flex gap-3 rounded-xl border border-red-500/25 bg-red-500/10 px-4 py-3 text-sm text-red-200">
                        <svg className="mt-0.5 h-5 w-5 shrink-0 text-red-300" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        {error}
                    </div>
                )}

                <button
                    type="submit"
                    disabled={!expiryAt || (uploadMode === 'file' ? !file : !textBody.trim())}
                    className="v2-btn-primary"
                >
                    Create share
                </button>
            </form>
        </div>
    );
}
