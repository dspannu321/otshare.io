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
    const [previewUrl, setPreviewUrl] = useState(null);
    const [previewLoading, setPreviewLoading] = useState(false);
    const [busy, setBusy] = useState(false);
    const [error, setError] = useState('');

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
        const prefetch = mime.startsWith('image/') && size > 0 && size <= PREVIEW_MAX_BYTES;
        if (!prefetch) return;

        let cancelled = false;
        setPreviewLoading(true);
        downloadShareBlob(apiBase, meta.download_token)
            .then((b) => {
                if (!cancelled) setBlob(b);
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
        setPreviewUrl(null);
        setPreviewLoading(false);
        setError('');
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
            setPhase('done');
            setBlob(null);
            setMeta(null);
        } catch (err) {
            setError(err instanceof Error ? err.message : 'Download failed.');
        } finally {
            setBusy(false);
        }
    };

    if (phase === 'done') {
        return (
            <div className="v2-card p-6 sm:p-8 text-center">
                <div className="mx-auto mb-4 flex h-14 w-14 items-center justify-center rounded-2xl border border-emerald-500/30 bg-emerald-500/10">
                    <svg className="h-7 w-7 text-emerald-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" strokeWidth={2}>
                        <path strokeLinecap="round" strokeLinejoin="round" d="M5 13l4 4L19 7" />
                    </svg>
                </div>
                <h2 className="text-lg font-semibold text-white">Download started</h2>
                <p className="mt-2 text-sm text-slate-400">Check your downloads folder. This pickup used one download from the sender&apos;s limit.</p>
                <button type="button" onClick={reset} className="v2-btn-primary mt-6 w-full py-3 text-sm font-semibold">
                    Download another file
                </button>
            </div>
        );
    }

    if (phase === 'ready' && meta) {
        const displayName = meta.original_name || 'Shared file';
        const isImage = (meta.mime || '').startsWith('image/');

        return (
            <div className="v2-card overflow-hidden p-0">
                <div className="border-b border-white/[0.06] bg-black/20 px-5 py-4 sm:px-6">
                    <p className="text-[11px] font-semibold uppercase tracking-wider text-slate-500">Your file</p>
                    <p className="mt-1 break-all text-lg font-semibold text-white">{displayName}</p>
                    <p className="mt-1 text-sm text-slate-500">
                        {formatFileSize(meta.size_bytes)}
                        {meta.mime ? ` · ${meta.mime}` : ''}
                    </p>
                    {meta.expires_at && (
                        <p className="mt-2 text-xs text-slate-600">
                            Download link valid until{' '}
                            <time className="font-mono text-slate-500" dateTime={meta.expires_at}>
                                {formatInAppTimezone(meta.expires_at, { dateStyle: 'medium', timeStyle: 'medium' })}
                            </time>{' '}
                            <span className="text-slate-600">({getAppTimezone()})</span>
                        </p>
                    )}
                </div>

                <div className="space-y-5 p-5 sm:p-6">
                    {isImage && previewLoading && (
                        <p className="text-center text-sm text-slate-500">Loading preview…</p>
                    )}
                    {previewUrl && isImage && (
                        <div className="overflow-hidden rounded-xl border border-white/[0.08] bg-black/30">
                            <img src={previewUrl} alt="" className="mx-auto max-h-[min(50vh,320px)] w-full object-contain" />
                        </div>
                    )}

                    {error && (
                        <p className="rounded-xl border border-rose-500/25 bg-rose-500/10 px-4 py-3 text-center text-sm text-rose-200/90" role="alert">
                            {error}
                        </p>
                    )}

                    <button
                        type="button"
                        onClick={handleSave}
                        disabled={busy}
                        className="v2-btn-primary w-full py-3.5 text-base font-semibold disabled:cursor-not-allowed disabled:opacity-50"
                    >
                        {busy ? 'Saving…' : 'Save to my computer'}
                    </button>

                    <button type="button" onClick={reset} className="w-full text-center text-sm text-slate-500 underline decoration-slate-600 underline-offset-2 hover:text-slate-400">
                        Use a different code
                    </button>

                    <p className="text-center text-xs leading-relaxed text-slate-500">
                        The save dialog opens after you confirm. Each completed save counts as one download.
                    </p>
                </div>
            </div>
        );
    }

    return (
        <div className="v2-card p-6 sm:p-8">
            <form onSubmit={handleLookup} className="space-y-6">
                <div>
                    <label className="mb-3 block text-center text-xs font-semibold uppercase tracking-wider text-slate-500">Pickup code</label>
                    <PickupCodeInputV2 value={code} onChange={setCode} className="justify-center" />
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
                    {busy ? 'Checking…' : 'Look up file'}
                </button>
            </form>
        </div>
    );
}
