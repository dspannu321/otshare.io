import { useState } from 'react';
import { UploadFlow } from './UploadFlow';
import { DownloadFlow } from './DownloadFlow';
import { SecurityTrust } from './SecurityTrust';
import { ApiDocs } from './ApiDocs';

const API_BASE = '/api/v1';

function TabIcon({ type, className }) {
  switch (type) {
    case 'upload':
      return (
        <svg className={className} fill="none" viewBox="0 0 24 24" stroke="currentColor" strokeWidth={2} strokeLinecap="round" strokeLinejoin="round">
          <path d="M21 15v4a2 2 0 01-2 2H5a2 2 0 01-2-2v-4M17 8l-5-5-5 5M12 3v12" />
        </svg>
      );
    case 'download':
      return (
        <svg className={className} fill="none" viewBox="0 0 24 24" stroke="currentColor" strokeWidth={2} strokeLinecap="round" strokeLinejoin="round">
          <path d="M21 15v4a2 2 0 01-2 2H5a2 2 0 01-2-2v-4M7 10l5 5 5-5M12 15V3" />
        </svg>
      );
    case 'security':
      return (
        <svg className={className} fill="none" viewBox="0 0 24 24" stroke="currentColor" strokeWidth={2} strokeLinecap="round" strokeLinejoin="round">
          <path d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
        </svg>
      );
    case 'api':
      return (
        <svg className={className} fill="none" viewBox="0 0 24 24" stroke="currentColor" strokeWidth={2} strokeLinecap="round" strokeLinejoin="round">
          <path d="M10 20l4-16m4 4l4 4-4 4M6 16l-4-4 4-4" />
        </svg>
      );
    default:
      return null;
  }
}

export function App() {
  const [tab, setTab] = useState('upload');

  return (
    <div className="min-h-screen otshare-bg flex flex-col items-center justify-center p-6 font-sans">
      <div className="w-full max-w-lg">
        {/* Hero */}
        <div className="text-center mb-10">
          <div className="inline-flex items-center justify-center w-14 h-14 rounded-2xl bg-violet-500/15 border border-violet-500/25 mb-4">
            <svg className="w-7 h-7 text-violet-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" strokeWidth={2}>
              <path strokeLinecap="round" strokeLinejoin="round" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
            </svg>
          </div>
          <h1 className="text-3xl font-bold tracking-tight text-white mb-2">
            otshare.io
          </h1>
          <p className="text-zinc-500 text-base max-w-sm mx-auto leading-relaxed">
            Share files securely. One link, one download. Encrypted in your browser — we never see your data.
          </p>
          <div className="flex flex-wrap gap-2 justify-center mt-4">
            <span className="px-2.5 py-1 rounded-md bg-white/[0.06] border border-white/[0.08] text-zinc-400 text-xs">AES-256-GCM</span>
            <span className="px-2.5 py-1 rounded-md bg-white/[0.06] border border-white/[0.08] text-zinc-400 text-xs">Argon2id</span>
            <span className="px-2.5 py-1 rounded-md bg-white/[0.06] border border-white/[0.08] text-zinc-400 text-xs">E2E encrypted</span>
          </div>
        </div>

        {/* Tabs */}
        <div className="flex flex-wrap gap-1.5 mb-8">
          <button
            type="button"
            onClick={() => setTab('upload')}
            className={`flex items-center justify-center gap-2 py-3 px-4 rounded-xl text-sm font-semibold transition-all duration-200 ${
              tab === 'upload' ? 'otshare-tab-active' : 'otshare-tab-inactive'
            }`}
          >
            <TabIcon type="upload" className="w-4 h-4 shrink-0" />
            Share file
          </button>
          <button
            type="button"
            onClick={() => setTab('download')}
            className={`flex items-center justify-center gap-2 py-3 px-4 rounded-xl text-sm font-semibold transition-all duration-200 ${
              tab === 'download' ? 'otshare-tab-active' : 'otshare-tab-inactive'
            }`}
          >
            <TabIcon type="download" className="w-4 h-4 shrink-0" />
            Get file
          </button>
          <button
            type="button"
            onClick={() => setTab('security')}
            className={`flex items-center justify-center gap-2 py-3 px-4 rounded-xl text-sm font-semibold transition-all duration-200 ${
              tab === 'security' ? 'otshare-tab-active' : 'otshare-tab-inactive'
            }`}
          >
            <TabIcon type="security" className="w-4 h-4 shrink-0" />
            Security
          </button>
          <button
            type="button"
            onClick={() => setTab('api')}
            className={`flex items-center justify-center gap-2 py-3 px-4 rounded-xl text-sm font-semibold transition-all duration-200 ${
              tab === 'api' ? 'otshare-tab-active' : 'otshare-tab-inactive'
            }`}
          >
            <TabIcon type="api" className="w-4 h-4 shrink-0" />
            API
          </button>
        </div>

        {tab === 'upload' && <UploadFlow apiBase={API_BASE} />}
        {tab === 'download' && <DownloadFlow apiBase={API_BASE} />}
        {tab === 'security' && <SecurityTrust />}
        {tab === 'api' && <ApiDocs />}
      </div>

      <p className="mt-14 text-zinc-600 text-xs text-center">
        Max 100MB · 30 min · One download
      </p>
    </div>
  );
}
