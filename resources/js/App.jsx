import { UploadPageV2 } from './v2/UploadPageV2.jsx';
import { DownloadPageV2 } from './v2/DownloadPageV2.jsx';

const API_BASE = '/api/v1';

function getPageFromPath() {
    if (typeof window === 'undefined') {
        return 'upload';
    }
    const p = window.location.pathname.replace(/\/$/, '') || '/';
    const last = p.split('/').filter(Boolean).pop() ?? '';
    return last === 'download' ? 'download' : 'upload';
}

function navLinkClass(active) {
    return [
        'rounded-xl px-3 py-2 text-sm font-semibold transition',
        active ? 'bg-white/[0.08] text-sky-200 ring-1 ring-sky-400/20' : 'text-slate-400 hover:bg-white/[0.04] hover:text-slate-200',
    ].join(' ');
}

export function App() {
    const page = getPageFromPath();

    return (
        <div className="v2-shell v2-noise relative overflow-x-hidden">
            <div className="pointer-events-none absolute inset-0 bg-[radial-gradient(ellipse_60%_40%_at_50%_0%,rgba(56,189,248,0.06),transparent)]" />

            <header className="relative z-10 mx-auto flex w-full max-w-6xl flex-wrap items-center justify-between gap-3 px-4 py-4 sm:gap-4 sm:px-8 sm:py-5">
                <a href="/" className="group flex items-center gap-2.5 sm:gap-3">
                    <span className="flex h-10 w-10 items-center justify-center rounded-xl bg-sky-400/15 ring-1 ring-sky-400/25 transition group-hover:bg-sky-400/20">
                        <svg className="h-5 w-5 text-sky-300" fill="none" viewBox="0 0 24 24" stroke="currentColor" strokeWidth={2}>
                            <path strokeLinecap="round" strokeLinejoin="round" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                        </svg>
                    </span>
                    <span className="text-base font-bold tracking-tight text-white sm:text-lg">otshare</span>
                </a>
                <nav className="flex w-full flex-wrap items-center justify-end gap-1.5 sm:w-auto sm:gap-2">
                    <a href="/app" className={navLinkClass(page === 'upload')}>
                        Create
                    </a>
                    <a href="/download" className={navLinkClass(page === 'download')}>
                        Unlock
                    </a>
                </nav>
            </header>

            <main className="relative z-10 mx-auto flex min-h-[calc(100svh-150px)] w-full max-w-6xl flex-1 items-center justify-center px-4 py-6 sm:min-h-[calc(100vh-160px)] sm:px-8 sm:py-8">
                <section className="v2-animate-in w-full max-w-xl">
                    <div className="mb-5 text-center">
                        {page === 'upload' ? (
                            <>
                                <p className="mb-3 inline-flex items-center gap-2 rounded-full border border-sky-400/20 bg-sky-400/5 px-3 py-1 text-xs font-medium text-sky-200/90">
                                    <span className="h-1.5 w-1.5 rounded-full bg-emerald-400 shadow-[0_0_8px_rgba(52,211,153,0.8)]" />
                                    Simple timed sharing
                                </p>
                                <h1 className="text-2xl font-bold leading-tight tracking-tight text-white sm:text-4xl">
                                    Upload &amp; share
                                </h1>
                                <p className="mt-2 text-sm text-slate-400">
                                    Share a file or text, choose expiry and unlocks — one pickup code or link for your recipient.
                                </p>
                            </>
                        ) : (
                            <>
                                <p className="mb-3 inline-flex items-center gap-2 rounded-full border border-sky-400/20 bg-sky-400/5 px-3 py-1 text-xs font-medium text-sky-200/90">
                                    <span className="h-1.5 w-1.5 rounded-full bg-sky-400 shadow-[0_0_8px_rgba(56,189,248,0.5)]" />
                                    Have a pickup code?
                                </p>
                                <h1 className="text-2xl font-bold leading-tight tracking-tight text-white sm:text-4xl">
                                    Unlock a share
                                </h1>
                            </>
                        )}
                    </div>
                    <div className="v2-animate-in" style={{ animationDelay: '80ms' }}>
                        {page === 'upload' && <UploadPageV2 apiBase={API_BASE} />}
                        {page === 'download' && <DownloadPageV2 apiBase={API_BASE} />}
                    </div>
                </section>
            </main>

            <footer className="relative z-10 mx-auto flex max-w-6xl flex-col items-center gap-2 px-5 py-5 text-center text-xs text-slate-600 sm:px-8">
                <p>
                    <a href="/" className="text-slate-500 underline decoration-slate-700 underline-offset-2 hover:text-slate-400">
                        Home
                    </a>
                    <span className="mx-2 text-slate-700">·</span>
                    <a href="/privacy" className="text-slate-500 underline decoration-slate-700 underline-offset-2 hover:text-slate-400">
                        Privacy
                    </a>
                    <span className="mx-2 text-slate-700">·</span>
                    <a href="/terms" className="text-slate-500 underline decoration-slate-700 underline-offset-2 hover:text-slate-400">
                        Terms
                    </a>
                </p>
                <span>Max 100MB · File or text · Up to 5 unlocks per share</span>
            </footer>
        </div>
    );
}
