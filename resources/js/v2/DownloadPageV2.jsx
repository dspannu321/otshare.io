import { useState, useEffect } from 'react';
import { downloadBlob } from '../crypto';
import { redeemPickupCode, downloadShareBlob, confirmDownload, formatPickupCodeForApi } from '../api';
import { formatInAppTimezone, getAppTimezone } from '../appTz';
import { PickupCodeInputV2 } from './PickupCodeInputV2.jsx';

const PREVIEW_MAX_BYTES = 8 * 1024 * 1024;

function formatFileSize(bytes) {
    if (bytes == null || Number.isNaN(bytes)) return '—';
    const n = Number(bytes);
    if (n < 1024) return `${n} B`;
    if (n < 1024 * 1024) return `${(n / 1024).toFixed(1)} KB`;
    return `${(n / (1024 * 1024)).toFixed(1)} MB`;
}

export function DownloadPageV2({ apiBase }) {
    const [phase, setPhase] = useState('code');
    const [code, setCode] = useState('');
    const [meta, setMeta] = useState(null);
    const [blob, setBlob] = useState(null);
    const [textContent, setTextContent] = useState(null);
    const [previewUrl, setPreviewUrl] = useState(null);
    const [previewLoading, setPreviewLoading] = useState(false);
    const [busy, setBusy] = useState(false);
    const [error, setError] = useState('');
    const [textCopied, setTextCopied] = useState(false);
    const [doneWasText, setDoneWasText] = useState(false);

    const formatted = formatPickupCodeForApi(code);
    const canLookup = formatted.length === 11 && !busy && phase === 'code';

    useEffect(() => {
        if (!blob || !(meta?.mime || '').startsWith('image/')) {
            setPreviewUrl(null);
            return;
        }
        const url = URL.createObjectURL(blob);
        setPreviewUrl(url);
        return () => URL.revokeObjectURL(url);
    }, [blob, meta?.mime]);

    useEffect(() => {
        if (phase !== 'ready' || !meta) return;
        const mime = meta.mime || '';
        const size = meta.size_bytes ?? 0;
        const prefetchImage = mime.startsWith('image/') && size > 0 && size <= PREVIEW_MAX_BYTES;
        const prefetchText = mime.startsWith('text/plain') && size > 0 && size <= PREVIEW_MAX_BYTES;
        if (!prefetchImage && !prefetchText) return;

        let cancelled = false;
        setPreviewLoading(true);
        downloadShareBlob(apiBase, meta.download_token)
            .then(async (b) => {
                if (cancelled) return;
                setBlob(b);
                if (prefetchText) {
                    try {
                        const t = await b.text();
                        if (!cancelled) setTextContent(t);
                    } catch {
                        /* Copy / done will retry fetch */
                    }
                }
            })
            .catch(() => {
                /* Save will retry fetch */
            })
            .finally(() => {
                if (!cancelled) setPreviewLoading(false);
            });

        return () => {
            cancelled = true;
        };
    }, [phase, meta, apiBase]);

    const reset = () => {
        setPhase('code');
        setMeta(null);
        setBlob(null);
        setTextContent(null);
        setPreviewUrl(null);
        setPreviewLoading(false);
        setError('');
        setTextCopied(false);
        setDoneWasText(false);
    };

    const handleLookup = async (e) => {
        e.preventDefault();
        setError('');
        if (!canLookup) {
            setError('Enter the full pickup code (4 characters + 6 digits).');
            return;
        }
        setBusy(true);
        try {
            const m = await redeemPickupCode(apiBase, formatted);
            setMeta(m);
            setBlob(null);
            setTextContent(null);
            setTextCopied(false);
            setPhase('ready');
        } catch (err) {
            setError(err instanceof Error ? err.message : 'Something went wrong.');
        } finally {
            setBusy(false);
        }
    };

    const handleSave = async () => {
        if (!meta?.download_token) return;
        setError('');
        setBusy(true);
        try {
            let b = blob;
            if (!b) {
                b = await downloadShareBlob(apiBase, meta.download_token);
            }
            const name = meta.original_name || 'download';
            downloadBlob(b, name);
            await confirmDownload(apiBase, meta.download_token, true);
            setDoneWasText(false);
            setPhase('done');
            setBlob(null);
            setMeta(null);
            setTextContent(null);
        } catch (err) {
            setError(err instanceof Error ? err.message : 'Download failed.');
        } finally {
            setBusy(false);
        }
    };

    const copySharedText = async () => {
        if (!meta?.download_token) return;
        setError('');
        try {
            let t = textContent;
            let b = blob;
            if (t == null) {
                b = b ?? (await downloadShareBlob(apiBase, meta.download_token));
                t = await b.text();
                setBlob(b);
                setTextContent(t);
            }
            await navigator.clipboard.writeText(t);
            setTextCopied(true);
            setTimeout(() => setTextCopied(false), 2000);
        } catch {
            setError('Could not copy. Select the text manually or save as a file.');
        }
    };

    const handleTextDone = async () => {
        if (!meta?.download_token) return;
        setError('');
        setBusy(true);
        try {
            let b = blob;
            if (!b) {
                b = await downloadShareBlob(apiBase, meta.download_token);
                setBlob(b);
            }
            await confirmDownload(apiBase, meta.download_token, true);
            setDoneWasText(true);
            setPhase('done');
            setBlob(null);
            setMeta(null);
            setTextContent(null);
        } catch (err) {
            setError(err instanceof Error ? err.message : 'Something went wrong.');
        } finally {
            setBusy(false);
        }
    };

    if (phase === 'done') {
        return (
            <div className="v2-card p-5 sm:p-8 text-center">
                <div className="mx-auto mb-4 flex h-14 w-14 items-center justify-center rounded-2xl border border-emerald-500/30 bg-emerald-500/10">
                    <svg className="h-7 w-7 text-emerald-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" strokeWidth={2}>
                        <path strokeLinecap="round" strokeLinejoin="round" d="M5 13l4 4L19 7" />
                    </svg>
                </div>
                <h2 className="text-lg font-semibold text-white">{doneWasText ? 'All set' : 'Download started'}</h2>
                <p className="mt-2 text-sm text-slate-400">
                    {doneWasText
                        ? 'This pickup used one unlock from the sender’s limit.'
                        : 'Check your downloads folder. This pickup used one download from the sender’s limit.'}
                </p>
                <button type="button" onClick={reset} className="v2-btn-primary mt-6 w-full py-3 text-sm font-semibold">
                    {doneWasText ? 'Unlock another share' : 'Download another file'}
                </button>
            </div>
        );
    }

    if (phase === 'ready' && meta) {
        const displayName = meta.original_name || 'Shared file';
        const isImage = (meta.mime || '').startsWith('image/');
        const isText = (meta.mime || '').startsWith('text/plain');
        const textTooLarge = isText && (meta.size_bytes ?? 0) > PREVIEW_MAX_BYTES;

        return (
            <div className="v2-card overflow-hidden p-0">
                <div className="border-b border-white/[0.06] bg-black/20 px-4 py-4 sm:px-6">
                    <p className="text-[11px] font-semibold uppercase tracking-wider text-slate-500">{isText ? 'Shared text' : 'Your file'}</p>
                    <p className="mt-1 break-all text-base font-semibold text-white sm:text-lg">{displayName}</p>
                    <p className="mt-1 text-sm text-slate-500">
                        {formatFileSize(meta.size_bytes)}
                        {meta.mime ? ` · ${meta.mime}` : ''}
                    </p>
                    {meta.expires_at && (
                        <p className="mt-2 text-xs leading-relaxed text-slate-600">
                            <span className="block sm:inline">{isText ? 'Unlock link valid until ' : 'Download link valid until '} </span>
                            <time className="font-mono text-slate-500" dateTime={meta.expires_at}>
                                {formatInAppTimezone(meta.expires_at, { dateStyle: 'medium', timeStyle: 'medium' })}
                            </time>
                            <span className="block text-slate-600 sm:inline sm:ml-1">({getAppTimezone()})</span>
                        </p>
                    )}
                </div>

                <div className="space-y-5 p-4 sm:p-6">
                    {isImage && previewLoading && (
                        <p className="text-center text-sm text-slate-500">Loading preview…</p>
                    )}
                    {isText && previewLoading && !textTooLarge && (
                        <p className="text-center text-sm text-slate-500">Loading text…</p>
                    )}
                    {previewUrl && isImage && (
                        <div className="overflow-hidden rounded-xl border border-white/[0.08] bg-black/30">
                            <img src={previewUrl} alt="" className="mx-auto max-h-[min(50vh,320px)] w-full object-contain" />
                        </div>
                    )}

                    {isText && !textTooLarge && textContent != null && (
                        <div className="overflow-hidden rounded-xl border border-white/[0.08] bg-black/30">
                            <pre className="max-h-[min(50vh,360px)] overflow-auto whitespace-pre-wrap break-words p-4 font-mono text-sm leading-relaxed text-slate-200">
                                {textContent}
                            </pre>
                        </div>
                    )}

                    {isText && textTooLarge && (
                        <p className="text-center text-sm text-slate-400">
                            This text is large. Use &quot;Save to my computer&quot; to download it as a file, then open it locally.
                        </p>
                    )}

                    {error && (
                        <p className="rounded-xl border border-rose-500/25 bg-rose-500/10 px-4 py-3 text-center text-sm text-rose-200/90" role="alert">
                            {error}
                        </p>
                    )}

                    {isText && !textTooLarge ? (
                        <>
                            <div className="flex flex-col gap-3 sm:flex-row">
                                <button
                                    type="button"
                                    onClick={copySharedText}
                                    disabled={busy || previewLoading}
                                    className="inline-flex flex-1 items-center justify-center gap-2 rounded-xl border border-white/10 bg-white/[0.06] px-4 py-3.5 text-base font-semibold text-slate-200 transition hover:border-sky-400/30 hover:bg-sky-400/10 hover:text-white disabled:cursor-not-allowed disabled:opacity-50"
                                >
                                    {textCopied ? (
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
                                            Copy text
                                        </>
                                    )}
                                </button>
                                <button
                                    type="button"
                                    onClick={handleTextDone}
                                    disabled={busy || previewLoading}
                                    className="v2-btn-primary flex-1 py-3.5 text-base font-semibold disabled:cursor-not-allowed disabled:opacity-50"
                                >
                                    {busy ? 'Finishing…' : 'Done — I’ve copied it'}
                                </button>
                            </div>
                            <button
                                type="button"
                                onClick={handleSave}
                                disabled={busy}
                                className="w-full text-center text-sm text-slate-500 underline decoration-slate-600 underline-offset-2 hover:text-slate-400"
                            >
                                Save as file instead
                            </button>
                        </>
                    ) : (
                        <button
                            type="button"
                            onClick={handleSave}
                            disabled={busy}
                            className="v2-btn-primary w-full py-3.5 text-base font-semibold disabled:cursor-not-allowed disabled:opacity-50"
                        >
                            {busy ? 'Saving…' : 'Save to my computer'}
                        </button>
                    )}

                    <button type="button" onClick={reset} className="w-full text-center text-sm text-slate-500 underline decoration-slate-600 underline-offset-2 hover:text-slate-400">
                        Use a different code
                    </button>

                    <p className="text-center text-xs leading-relaxed text-slate-500">
                        {isText && !textTooLarge
                            ? 'When you tap Done, this unlock counts toward the sender’s limit. Save as file uses the same limit.'
                            : 'The save dialog opens after you confirm. Each completed save counts as one download.'}
                    </p>
                </div>
            </div>
        );
    }

    return (
        <div className="v2-card p-5 sm:p-8">
            <form onSubmit={handleLookup} className="space-y-6">
                <div>
                    <label className="mb-3 block text-center text-xs font-semibold uppercase tracking-wider text-slate-500">Pickup code</label>
                    <PickupCodeInputV2 value={code} onChange={setCode} className="justify-center" />
                    <p className="mt-2 text-center text-xs text-slate-600">Format: 4 letters/numbers + 6 digits</p>
                </div>
                {error && (
                    <p className="rounded-xl border border-rose-500/25 bg-rose-500/10 px-4 py-3 text-center text-sm text-rose-200/90" role="alert">
                        {error}
                    </p>
                )}
                <button
                    type="submit"
                    disabled={!canLookup}
                    className="v2-btn-primary w-full py-3.5 text-base font-semibold disabled:cursor-not-allowed disabled:opacity-40"
                >
                    {busy ? 'Checking…' : 'Look up share'}
                </button>
            </form>
        </div>
    );
}
